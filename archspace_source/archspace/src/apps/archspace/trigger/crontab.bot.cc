// crontab.bot.cc -- bot (NPC) players: population top-up + a band-aware,
// self-sustaining defensive-economy AI.
//
// Two time-based crontabs (registered in main.cc, same mechanism as the
// CCronTabEmpire* jobs):
//
//   CCronTabBotPopulation  keeps a fixed, band-locked spread of bots alive.
//                          Target is BotPerBand (default 25) in each of the
//                          NUM_BOT_BANDS (6) power bands -> 150 bots total. Spawns
//                          at most BotSpawnBatch new bots per run (default 100, so
//                          a cold start fills over a couple of runs; lower it to
//                          spread a cold start over more runs if desired).
//
//   CCronTabBotAI          drives each living bot. It ALWAYS keeps a band-sized
//                          defense reserve (5/10/15/20 fleets by band, capped at
//                          20 so bands 3-5 all hold 20) on stand-
//                          by and fully manned: it first REBUILDS any defenders
//                          lost since spawn back up to that reserve (the per-band
//                          minimum is otherwise only set at spawn and decays as a
//                          bot takes losses), then keeps them manned -- so a bot
//                          always has its full reserve defending. It is spawned
//                          at its band FLOOR and is meant to STAY in band, so it
//                          does not chase the ceiling with planets: it only auto-
//                          expeditions when it has fallen BELOW its floor, and
//                          recalls those expeditions once back at/above it. It also
//                          always PRIORITISES its strongest commanders on defence:
//                          stronger commanders from the bench are swapped onto the
//                          weakest defenders and those fleets grow to the new
//                          commander's capacity -- but only while still under the
//                          band CEILING, after which fleet size is frozen, so
//                          promotion fills out the band without overshooting it.
//                          Displaced commanders return to the pool to be retrained.
//                          It also keeps a generic DEFENCE PLAN in sync with its
//                          defenders (every fleet on the FORMATION command) so a
//                          sieged/blockaded bot fights by plan -- except ENSIGN
//                          (lowest-rank) bots, which keep no plan and fall back to
//                          the engine's auto-deployment.
//                          SEPARATELY (and regardless of the ceiling) it runs a
//                          COMMANDER-TRAINING program: every
//                          below-cap commander in the pool is put into its own
//                          auto-repeat training fleet and trained up; on reaching
//                          the level cap (BotTrainLevelCap, default 20 = MAX_LEVEL)
//                          the fleet is disbanded and the maxed commander returns
//                          to the pool as a ready bench the rebuild step can draft.
//                          Bounded to BotAIPerRun bots per run (round-robin across
//                          the player table) so a full population can't stall the
//                          engine loop.
//
// Bot identity and band are encoded in the portal id (see player.h: is_bot(),
// bot_band(), bot_band_ceiling()); nothing extra is persisted.

#include <libintl.h>
#include <cstring>
#include "../triggers.h"
#include "../archspace.h"

static int
bot_cfg(const char *aKey, int aDefault)
{
	return ARCHSPACE->configuration().get_integer("Game", aKey, aDefault);
}

// ---------------------------------------------------------------------------
// Population: keep BotPerBand bots alive in each power band.
// ---------------------------------------------------------------------------
void
CCronTabBotPopulation::handler()
{
	if (!CGame::mUpdateTurn) return;
	SLOG("SYSTEM : bot population crontab start");

	int PerBand    = bot_cfg("BotPerBand", 25);
	int SpawnBatch = bot_cfg("BotSpawnBatch", 100);  // fill all 100 in one run

	// One-time on deploy: convert legacy "BOT(n)" bots to the rank+commander
	// name scheme, using each bot's own race and band. The new name never starts
	// with "BOT(", so this is idempotent and a cheap no-op once all are renamed.
	// player.name isn't part of the player UPDATE set, so persist it directly.
	int Renamed = 0;
	for (int i=0 ; i<PLAYER_TABLE->length() ; i++)
	{
		CPlayer *P = (CPlayer *)PLAYER_TABLE->get(i);
		if (P == NULL || !P->is_bot() || P->is_dead()) continue;
		const char *Nm = P->get_name();
		if (!Nm || strncmp(Nm, "BOT(", 4) != 0) continue;

		char NewName[41];
		GAME->make_bot_name(P->get_race(), P->bot_band(), NewName, sizeof(NewName));
		P->set_name(NewName);
		STORE_CENTER->query("player",
				(char *)format("UPDATE player SET name = '%s' WHERE game_id = %d",
						(char *)add_slashes(NewName), P->get_game_id()));
		Renamed++;
	}
	if (Renamed) SLOG("SYSTEM : bot population crontab renamed %d legacy bot(s)", Renamed);

	int Spawned = 0;
	for (int band=0 ; band<NUM_BOT_BANDS && Spawned<SpawnBatch ; band++)
	{
		int Alive = 0;
		for (int i=0 ; i<PLAYER_TABLE->length() ; i++)
		{
			CPlayer *P = (CPlayer *)PLAYER_TABLE->get(i);
			if (P == NULL) continue;
			if (P->is_bot() && P->bot_band() == band && !P->is_dead()) Alive++;
		}

		for (int n=Alive ; n<PerBand && Spawned<SpawnBatch ; n++)
		{
			if (GAME->create_bot_player(band) != NULL) Spawned++;
		}
	}

	SLOG("SYSTEM : bot population crontab end (spawned %d this run)", Spawned);
}

// ---------------------------------------------------------------------------
// Minimum fleets a bot always keeps on stand-by to defend, by power band:
//   band 0 (0-10k)    -> 5     band 3 (100-200k)        -> 20
//   band 1 (10-50k)   -> 10    band 4 (200-500k, Grand) -> 20
//   band 2 (50-100k)  -> 15    band 5 (500k-1M, Supreme)-> 20
// (band+1)*5, capped at 20 (which is also the defence-plan fleet limit). These
// are never sent on a mission, so a bot that has fleets always has fleets
// standing by to defend.
// ---------------------------------------------------------------------------
static int
bot_defense_reserve(int aBand)
{
	if (aBand < 0) aBand = 0;
	if (aBand >= NUM_BOT_BANDS) aBand = NUM_BOT_BANDS - 1;
	int Reserve = (aBand + 1) * 5;
	if (Reserve > 20) Reserve = 20;
	return Reserve;
}

// Fleets actually standing by to defend RIGHT NOW: idle, in-system, on no
// mission. This deliberately excludes fleets that are away -- on expedition
// (claiming planets) or training -- because those don't defend the home system
// and will not be present in a battle. The rebuild below tops this count back up
// to the reserve; counting away fleets here was the bug that let a bot with a
// few auto-repeating expeditions sit at 1-2 real defenders while "looking" full.
static int
bot_standby_defender_count(CFleetList *aList)
{
	int Count = 0;
	for (int i=0 ; i<aList->length() ; i++)
	{
		CFleet *F = (CFleet *)aList->get(i);
		if (F == NULL) continue;
		if (F->get_status() != CFleet::FLEET_STAND_BY) continue;
		if (F->under_mission()) continue;
		if (F->get_mission().get_mission() != CMission::MISSION_NONE) continue;
		Count++;
	}
	return Count;
}

// ---------------------------------------------------------------------------
// Rebuild lost fleets back up to the band's defense reserve. The per-band
// minimum is only ever established at spawn (CGame::create_bot_player); combat
// losses then erode it permanently, because the rest of the AI only re-crews and
// missions *existing* fleets -- nothing builds replacements. Topping the fleet
// list back up here maintains the floor for the life of the bot, and retrofits
// bots that were spawned before the reserve sizing existed. Mirrors the spawn
// loop (commander on demand, latest design, manned to commander capacity), but
// bounded per call so a cold retrofit of a large population spreads over several
// runs instead of bursting a few thousand inserts in one go. Returns the count
// of fleets built.
// ---------------------------------------------------------------------------
static int
bot_rebuild_reserve(CPlayer *aPlayer, int aReserve, int aMaxBuild)
{
	CFleetList      *FleetList      = aPlayer->get_fleet_list();
	CAdmiralList    *AdmiralList    = aPlayer->get_admiral_list();
	CAdmiralList    *AdmiralPool    = aPlayer->get_admiral_pool();
	CShipDesignList *ShipDesignList = aPlayer->get_ship_design_list();

	int Built = 0;
	while (bot_standby_defender_count(FleetList) < aReserve && Built < aMaxBuild)
	{
		if (AdmiralPool->length() == 0)
		{
			CAdmiral *NewAdmiral = new CAdmiral(aPlayer);
			AdmiralPool->add_admiral(NewAdmiral);
			NewAdmiral->type(QUERY_INSERT);
			STORE_CENTER->store(*NewAdmiral);
		}
		CAdmiral *Admiral = (CAdmiral *)AdmiralPool->get(0);
		// fly the bot's latest design (the best-components design built at spawn
		// is the newest entry); bail if it somehow has no design at all.
		CShipDesign *ShipDesign = ShipDesignList->length()
			? (CShipDesign *)ShipDesignList->get(ShipDesignList->length() - 1)
			: NULL;
		if (Admiral == NULL || ShipDesign == NULL) break;

		int Capacity = Admiral->get_fleet_commanding();

		CFleet *Fleet = new CFleet();
		Fleet->set_id(FleetList->get_new_fleet_id());
		Fleet->set_owner(aPlayer->get_game_id());
		Fleet->set_name((char *)format("BOT Fleet(%d)", Fleet->get_id()));
		Fleet->set_admiral(Admiral->get_id());
		Fleet->set_ship_class(ShipDesign);
		Fleet->set_max_ship(Capacity);
		Fleet->set_current_ship(Capacity);
		Fleet->set_exp(25 + aPlayer->get_control_model()->get_military()*3);
		FleetList->add_fleet(Fleet);

		Admiral->set_fleet_number(Fleet->get_id());
		AdmiralPool->remove_without_free_admiral(Admiral->get_id());
		AdmiralList->add_admiral(Admiral);

		Fleet->type(QUERY_INSERT);
		STORE_CENTER->store(*Fleet);
		Admiral->type(QUERY_UPDATE);
		STORE_CENTER->store(*Admiral);

		Built++;
	}
	return Built;
}

// ---------------------------------------------------------------------------
// Disband a finished training fleet: the commander has hit the level cap, so the
// fleet stops training, is deleted, and the (now fully trained) commander goes
// back to the pool. Mirrors the player disband path (page/fleet/disband_result),
// minus the dock return -- a bot's trainee ships are synthesized for free at
// creation, so they simply vanish on disband rather than piling up in the dock.
// NOTE: frees the fleet object via remove_fleet(), so aFleet must not be used
// after this returns.
// ---------------------------------------------------------------------------
static void
bot_disband_training_fleet(CPlayer *aPlayer, CFleet *aFleet, CAdmiral *aAdmiral)
{
	int FleetID = aFleet->get_id();

	aFleet->type(QUERY_DELETE);
	STORE_CENTER->store(*aFleet);                 // built from owner+id now
	aPlayer->get_fleet_list()->remove_fleet(FleetID);   // frees aFleet

	aAdmiral->set_fleet_number(0);
	aPlayer->get_admiral_list()->remove_without_free_admiral(aAdmiral->get_id());
	aPlayer->get_admiral_pool()->add_admiral(aAdmiral);
	aAdmiral->type(QUERY_UPDATE);
	STORE_CENTER->store(*aAdmiral);
}

// ---------------------------------------------------------------------------
// Train the commander pool: pull every below-cap pool commander into its own
// training fleet (auto-repeat MISSION_TRAIN) so it gains exp turn over turn. A
// trainee leaves the pool while training and returns at the cap via
// bot_disband_training_fleet, so this is self-limiting -- once every pool
// commander is maxed there is nothing left to start. Already-maxed pool
// commanders are left in the pool (a ready bench of strong commanders that the
// rebuild step can draft into real fleets). Bounded per run so a deep pool
// spreads over several ticks. Trainee ships are synthesized like the rebuild
// path (manned to capacity, no dock draw). Returns the count started.
// ---------------------------------------------------------------------------
static int
bot_train_pool(CPlayer *aPlayer, int aLevelCap, int aMaxStart)
{
	CFleetList      *FleetList      = aPlayer->get_fleet_list();
	CAdmiralList    *AdmiralList    = aPlayer->get_admiral_list();
	CAdmiralList    *AdmiralPool    = aPlayer->get_admiral_pool();
	CShipDesignList *ShipDesignList = aPlayer->get_ship_design_list();

	int Started = 0;
	// Walk the pool with a cursor; assigning a commander removes it from the pool
	// (the tail shifts down), so only advance the cursor when we leave one in place.
	for (int i=0 ; i<AdmiralPool->length() && Started<aMaxStart ; )
	{
		CAdmiral *Admiral = (CAdmiral *)AdmiralPool->get(i);
		if (Admiral == NULL) { i++; continue; }
		if (Admiral->get_level() >= aLevelCap) { i++; continue; }  // maxed: keep on the bench

		CShipDesign *ShipDesign = ShipDesignList->length()
			? (CShipDesign *)ShipDesignList->get(ShipDesignList->length() - 1)
			: NULL;
		if (ShipDesign == NULL) break;

		int Capacity = Admiral->get_fleet_commanding();

		CFleet *Fleet = new CFleet();
		Fleet->set_id(FleetList->get_new_fleet_id());
		Fleet->set_owner(aPlayer->get_game_id());
		Fleet->set_name((char *)format("BOT Trainee(%d)", Fleet->get_id()));
		Fleet->set_admiral(Admiral->get_id());
		Fleet->set_ship_class(ShipDesign);
		Fleet->set_max_ship(Capacity);
		Fleet->set_current_ship(Capacity);
		Fleet->set_exp(25 + aPlayer->get_control_model()->get_military()*3);
		FleetList->add_fleet(Fleet);

		Admiral->set_fleet_number(Fleet->get_id());
		AdmiralPool->remove_without_free_admiral(Admiral->get_id());
		AdmiralList->add_admiral(Admiral);

		// target=1 -> auto-repeat: keeps re-entering training every cycle (no per-
		// cycle news for bots) until the commander maxes and graduates.
		Fleet->init_mission(CMission::MISSION_TRAIN, 1);
		Fleet->type(QUERY_INSERT);
		STORE_CENTER->store(*Fleet);
		Admiral->type(QUERY_UPDATE);
		STORE_CENTER->store(*Admiral);

		Started++;
		// Admiral removed from the pool -> the front shifted; re-read index i.
	}
	return Started;
}

// ---------------------------------------------------------------------------
// Always put the bot's strongest available commanders on its defenders. A
// higher-level commander fights better and commands more ships, so each pass
// swaps the WEAKEST defender's commander for the STRONGEST commander on the bench
// (the pool) and grows the fleet to that commander's capacity -- BUT only while
// the bot is still under its band ceiling. Once the band is full the fleet size
// is frozen, so promotion can fill out the band without pushing the bot past it.
// The displaced commander returns to the pool, where the training program levels it
// back up -- so over time every defender ends up under a maxed commander and the
// bench feeds a steady supply of graduates. "Defenders" are the idle, in-system
// stand-by fleets left after growth has sent the expedition surplus out (the
// held-back reserve). Bench commanders that are already mid-training sit in
// trainee fleets, not the pool, so they are never yanked off training here.
// Only strict improvements are made (bench level > defender level), so this is
// stable; bounded per run. Returns the number of swaps made.
// ---------------------------------------------------------------------------
static int
bot_promote_defenders(CPlayer *aPlayer, int aMaxSwaps, int aCeiling)
{
	CFleetList   *FleetList   = aPlayer->get_fleet_list();
	CAdmiralList *AdmiralList = aPlayer->get_admiral_list();
	CAdmiralList *AdmiralPool = aPlayer->get_admiral_pool();

	int Swaps = 0;
	while (Swaps < aMaxSwaps)
	{
		// weakest in-system defender and its commander
		CFleet   *WeakFleet     = NULL;
		CAdmiral *WeakCommander = NULL;
		for (int i=0 ; i<FleetList->length() ; i++)
		{
			CFleet *F = (CFleet *)FleetList->get(i);
			if (F == NULL) continue;
			if (F->get_status() != CFleet::FLEET_STAND_BY) continue;
			if (F->under_mission()) continue;
			if (F->get_mission().get_mission() != CMission::MISSION_NONE) continue;
			CAdmiral *C = AdmiralList->get_by_id(F->get_admiral_id());
			if (C == NULL) continue;
			if (WeakCommander == NULL || C->get_level() < WeakCommander->get_level())
			{
				WeakFleet     = F;
				WeakCommander = C;
			}
		}
		if (WeakFleet == NULL) break;

		// strongest commander on the bench
		CAdmiral *Best = NULL;
		for (int i=0 ; i<AdmiralPool->length() ; i++)
		{
			CAdmiral *P = (CAdmiral *)AdmiralPool->get(i);
			if (P == NULL) continue;
			if (Best == NULL || P->get_level() > Best->get_level()) Best = P;
		}
		if (Best == NULL || Best->get_level() <= WeakCommander->get_level()) break;

		// bench the weak commander, promote the strong one onto the defender.
		WeakCommander->set_fleet_number(0);
		AdmiralList->remove_without_free_admiral(WeakCommander->get_id());
		AdmiralPool->add_admiral(WeakCommander);

		AdmiralPool->remove_without_free_admiral(Best->get_id());
		AdmiralList->add_admiral(Best);
		Best->set_fleet_number(WeakFleet->get_id());

		WeakFleet->set_admiral(Best->get_id());
		// Grow the fleet to the new commander's full capacity ONLY while the bot is
		// still under its band ceiling: this lets defenders fill out the band, but
		// once the band is full the fleet size is frozen so the bot doesn't climb
		// past its ceiling. At/above the ceiling, keep the current size (shrink only
		// if the new commander can't command the existing crew).
		int Cap = Best->get_fleet_commanding();
		if (aPlayer->get_power() < aCeiling)
		{
			WeakFleet->set_max_ship(Cap);
			WeakFleet->set_current_ship(Cap);
		}
		else if (WeakFleet->get_max_ship() > Cap)
		{
			WeakFleet->set_max_ship(Cap);
			WeakFleet->set_current_ship(Cap);
		}

		WeakFleet->type(QUERY_UPDATE);
		STORE_CENTER->store(*WeakFleet);
		Best->type(QUERY_UPDATE);
		STORE_CENTER->store(*Best);
		WeakCommander->type(QUERY_UPDATE);
		STORE_CENTER->store(*WeakCommander);

		// Reflect the resize so the next swap's ceiling check sees current power.
		aPlayer->refresh_power();

		Swaps++;
	}
	return Swaps;
}

// Ensign bots (the lowest rank) carry no defence plan. The rank lives only in the
// player name (make_bot_name: "<Rank> <Commander>"), so detect it by prefix.
static bool
bot_is_ensign(CPlayer *aPlayer)
{
	const char *Name = aPlayer->get_name();
	return Name && strncmp(Name, "Ensign", 6) == 0;
}

// Delete a bot's generic defence plan and its deployment rows (DB + memory).
// Query strings are built from the live owner/id before each object is freed.
static void
bot_delete_defense_plan(CPlayer *aPlayer, CDefensePlan *aPlan)
{
	CDefenseFleetList *DFList = aPlan->get_fleet_list();
	for (int i=DFList->length()-1 ; i>=0 ; i--)
	{
		CDefenseFleet *DF = (CDefenseFleet *)DFList->get(i);
		DF->type(QUERY_DELETE);
		STORE_CENTER->store(*DF);
		DFList->remove_defense_fleet(DF->get_fleet_id());
	}
	int PlanID = aPlan->get_id();
	aPlan->type(QUERY_DELETE);
	STORE_CENTER->store(*aPlan);
	aPlayer->get_defense_plan_list()->remove_defense_plan(PlanID);   // frees aPlan
}

// ---------------------------------------------------------------------------
// Keep a bot's generic defence plan in sync with its current defenders, every
// fleet set to the FORMATION command. The generic plan is what get_optimal_plan()
// falls back to when the bot is sieged/blockaded (siege_planet_result.cc etc.),
// so it is what actually drives the defenders' battle behaviour; without a plan
// the engine uses auto_deployment instead. ENSIGN bots are exempt -- they keep no
// plan and fall back to auto_deployment, per the rule that the lowest-rank bots
// don't run a defence plan.
//
// Defenders = idle, in-system, stand-by fleets (the held-back reserve, after the
// expedition surplus has gone out); trainees/expeditions are UNDER_MISSION and so
// excluded. Capped at the plan's 20-fleet limit. The plan is rebuilt only when the
// defender SET changes -- commander promotions keep the same fleet ids, so a
// steady-state bot does an in-memory check here and writes nothing. Mirrors the
// player path in page/war/defense_plan_generic_result.cc.
// ---------------------------------------------------------------------------
static void
bot_sync_defense_plan(CPlayer *aPlayer)
{
	if (bot_is_ensign(aPlayer))
	{
		// Ensign bots run no plan. Drop a stale one a pre-rename "BOT(n)" bot may
		// have acquired before it was renamed into the Ensign rank.
		CDefensePlan *Stale = aPlayer->get_defense_plan_list()->get_generic_plan();
		if (Stale != NULL) bot_delete_defense_plan(aPlayer, Stale);
		return;
	}

	CFleetList *FleetList = aPlayer->get_fleet_list();

	int DefenderID[20];
	int DefenderCount = 0;
	for (int i=0 ; i<FleetList->length() && DefenderCount<20 ; i++)
	{
		CFleet *F = (CFleet *)FleetList->get(i);
		if (F == NULL) continue;
		if (F->get_status() != CFleet::FLEET_STAND_BY) continue;
		if (F->under_mission()) continue;
		if (F->get_mission().get_mission() != CMission::MISSION_NONE) continue;
		DefenderID[DefenderCount++] = F->get_id();
	}

	if (DefenderCount == 0) return;   // no defenders to plan around this tick

	CDefensePlanList *PlanList = aPlayer->get_defense_plan_list();
	CDefensePlan     *Plan     = PlanList->get_generic_plan();

	// Already covers exactly this defender set (same capital, same ids)? Promotions
	// only swap commanders, not fleet ids, so a settled bot returns here untouched.
	if (Plan != NULL && Plan->get_capital() == DefenderID[0])
	{
		CDefenseFleetList *DFList = Plan->get_fleet_list();
		if (DFList->length() == DefenderCount)
		{
			bool Same = true;
			for (int i=0 ; i<DefenderCount ; i++)
				if (DFList->get_by_id(DefenderID[i]) == NULL) { Same = false; break; }
			if (Same) return;
		}
	}

	// Rebuild: drop any existing deployment rows, then (create or update the plan
	// and) lay the defenders out in a non-overlapping grid, all on FORMATION.
	if (Plan != NULL)
	{
		CDefenseFleetList *DFList = Plan->get_fleet_list();
		for (int i=DFList->length()-1 ; i>=0 ; i--)
		{
			CDefenseFleet *DF = (CDefenseFleet *)DFList->get(i);
			DF->type(QUERY_DELETE);
			STORE_CENTER->store(*DF);
			DFList->remove_defense_fleet(DF->get_fleet_id());
		}
	}

	if (Plan == NULL)
	{
		Plan = new CDefensePlan();
		Plan->set_owner(aPlayer->get_game_id());
		Plan->set_id(PlanList->get_new_id());
		Plan->set_type(CDefensePlan::GENERIC_PLAN);
		Plan->set_capital(DefenderID[0]);
		PlanList->add_defense_plan(Plan);
		Plan->type(QUERY_INSERT);
		STORE_CENTER->store(*Plan);
	}
	else
	{
		Plan->set_capital(DefenderID[0]);
		Plan->type(QUERY_UPDATE);
		STORE_CENTER->store(*Plan);
	}

	int PlanID = Plan->get_id();
	for (int i=0 ; i<DefenderCount ; i++)
	{
		CDefenseFleet *DF = new CDefenseFleet();
		DF->set_owner(aPlayer->get_game_id());
		DF->set_plan_id(PlanID);
		DF->set_fleet_id(DefenderID[i]);
		DF->set_command(CDefenseFleet::COMMAND_FORMATION);
		DF->set_x(7000 + (i % 5) * 500);   // distinct cells -> no stacked fleets
		DF->set_y(3000 + (i / 5) * 500);
		Plan->add_defense_fleet(DF);
		DF->type(QUERY_INSERT);
		STORE_CENTER->store(*DF);
	}
}

// ---------------------------------------------------------------------------
// Per-bot AI step.
// ---------------------------------------------------------------------------
static void
bot_ai_act(CPlayer *aPlayer, int aRebuildPerRun, int aTrainPerRun, int aLevelCap,
		int aPromotePerRun)
{
	aPlayer->refresh_power();

	int Ceiling   = bot_band_ceiling(aPlayer->bot_band());
	int Reserve   = bot_defense_reserve(aPlayer->bot_band());

	CFleetList   *FleetList   = aPlayer->get_fleet_list();
	CAdmiralList *AdmiralList = aPlayer->get_admiral_list();

	// (A) Graduate: any training fleet whose commander has reached the level cap
	// stops training -- disband the fleet and return the now fully-trained
	// commander to the pool. Backward scan: disbanding removes the fleet from the
	// list. Runs even when throttled so commanders always cycle out at the cap.
	for (int i=FleetList->length()-1 ; i>=0 ; i--)
	{
		CFleet *Fleet = (CFleet *)FleetList->get(i);
		if (Fleet == NULL) continue;
		if (Fleet->get_mission().get_mission() != CMission::MISSION_TRAIN) continue;
		CAdmiral *Admiral = AdmiralList->get_by_id(Fleet->get_admiral_id());
		if (Admiral == NULL) continue;
		if (Admiral->get_level() < aLevelCap) continue;
		bot_disband_training_fleet(aPlayer, Fleet, Admiral);
	}

	// (0) Maintain the band minimum: rebuild lost defenders back up to the reserve.
	// Counts only fleets actually standing by (bot_standby_defender_count) -- fleets
	// away on expedition/training don't defend the home system. Done before the
	// defense/growth steps so the new fleets are counted there (manned, stand-by).
	if (bot_rebuild_reserve(aPlayer, Reserve, aRebuildPerRun))
		aPlayer->refresh_power();

	int Floor = bot_band_floor(aPlayer->bot_band());

	int N = FleetList->length();

	// (1) Defense -- ALWAYS: keep idle, in-system fleets fully manned (re-crew
	// battle losses up to each fleet's capacity).
	for (int i=0 ; i<N ; i++)
	{
		CFleet *Fleet = (CFleet *)FleetList->get(i);
		if (Fleet == NULL) continue;
		if (Fleet->get_status() != CFleet::FLEET_STAND_BY) continue;
		if (Fleet->under_mission()) continue;
		if (Fleet->get_current_ship() < Fleet->get_max_ship())
		{
			Fleet->set_current_ship(Fleet->get_max_ship());
			Fleet->type(QUERY_UPDATE);
			STORE_CENTER->store(*Fleet);
		}
	}

	// (2) Growth / hold. A bot is spawned at its band FLOOR, so it should NOT chase
	// the ceiling -- auto-repeat expeditions claiming planet after planet were a big
	// part of what pushed bots past their band. So expeditions run ONLY to climb
	// back when the bot has fallen BELOW its floor (heavy losses); the held-back
	// reserve is left alone and the surplus goes prospecting. At/above the floor the
	// bot instead RECALLS any auto-repeat expeditions, so it stops claiming new
	// planets and stops creeping up its band.
	if (aPlayer->get_power() < Floor)
	{
		int IdleCount = 0;
		for (int i=0 ; i<N ; i++)
		{
			CFleet *Fleet = (CFleet *)FleetList->get(i);
			if (Fleet == NULL) continue;
			if (Fleet->get_status() != CFleet::FLEET_STAND_BY) continue;
			if (Fleet->under_mission()) continue;
			if (Fleet->get_mission().get_mission() != CMission::MISSION_NONE) continue;
			IdleCount++;
		}

		int Surplus = IdleCount - Reserve;
		if (Surplus < 0) Surplus = 0;

		int Seen = 0;
		for (int i=0 ; i<N ; i++)
		{
			CFleet *Fleet = (CFleet *)FleetList->get(i);
			if (Fleet == NULL) continue;
			if (Fleet->get_status() != CFleet::FLEET_STAND_BY) continue;
			if (Fleet->under_mission()) continue;
			if (Fleet->get_mission().get_mission() != CMission::MISSION_NONE) continue;

			// The last Reserve idle fleets fall outside the surplus window and stay
			// on stand-by -- this is the home defense reserve, left untouched.
			if (Seen++ >= Surplus) continue;

			// surplus fleet -> claim planets; target=1 -> auto-repeat on return.
			Fleet->init_mission(CMission::MISSION_EXPEDITION, 1);
			Fleet->type(QUERY_UPDATE);
			STORE_CENTER->store(*Fleet);
		}
	}
	else
	{
		for (int i=0 ; i<N ; i++)
		{
			CFleet *Fleet = (CFleet *)FleetList->get(i);
			if (Fleet == NULL) continue;
			int Mission = Fleet->get_mission().get_mission();
			if (Mission == CMission::MISSION_EXPEDITION ||
				Mission == CMission::MISSION_RETURNING_WITH_PLANET)
			{
				Fleet->end_mission();   // recall -> stand-by; stops claiming planets
				Fleet->type(QUERY_UPDATE);
				STORE_CENTER->store(*Fleet);
			}
		}
	}

	// (P) Prioritise the strongest commanders on defence: swap higher-level
	// commanders from the bench onto the weakest held-back defenders, growing those
	// fleets to the new commander's capacity but only while still under the band
	// ceiling (bot_promote_defenders freezes size once the band is full). Displaced
	// commanders return to the pool to be retrained.
	if (bot_promote_defenders(aPlayer, aPromotePerRun, Ceiling))
		aPlayer->refresh_power();

	// (3) Train the commander pool toward the level cap. Runs even when throttled:
	// it develops commanders rather than chasing power, and is self-limiting (a
	// trainee leaves the pool while training and returns maxed in step (A)).
	bot_train_pool(aPlayer, aLevelCap, aTrainPerRun);

	// (4) Keep the defence plan (every defender on FORMATION) in sync with the
	// current defenders so a sieged/blockaded bot fights by plan. Ensign bots are
	// exempt and fall back to the engine's auto-deployment.
	bot_sync_defense_plan(aPlayer);

	aPlayer->refresh_power();
	aPlayer->type(QUERY_UPDATE);
	STORE_CENTER->store(*aPlayer);
}

void
CCronTabBotAI::handler()
{
	if (!CGame::mUpdateTurn) return;
	SLOG("SYSTEM : bot AI crontab start");

	int PerRun        = bot_cfg("BotAIPerRun", 25);
	int RebuildPerRun  = bot_cfg("BotRebuildPerRun", 5);  // defenders rebuilt per bot per run
	int TrainPerRun    = bot_cfg("BotTrainPerRun", 5);    // trainees started per bot per run
	int LevelCap       = bot_cfg("BotTrainLevelCap", 20); // graduate a commander at this level
	int PromotePerRun  = bot_cfg("BotPromotePerRun", 5);  // defender commander swaps per bot per run

	int Len = PLAYER_TABLE->length();
	if (Len <= 0) { SLOG("SYSTEM : bot AI crontab end (no players)"); return; }

	// round-robin cursor across the player table so big populations are covered
	// over successive runs without re-scanning the same head every time.
	static int sCursor = 0;

	int Processed = 0, Scanned = 0;
	while (Processed < PerRun && Scanned < Len)
	{
		if (sCursor >= Len) sCursor = 0;
		CPlayer *Player = (CPlayer *)PLAYER_TABLE->get(sCursor);
		sCursor++;
		Scanned++;

		if (Player == NULL) continue;
		if (!Player->is_bot() || Player->is_dead()) continue;

		bot_ai_act(Player, RebuildPerRun, TrainPerRun, LevelCap, PromotePerRun);
		Processed++;
	}

	SLOG("SYSTEM : bot AI crontab end (acted on %d bots)", Processed);
}
