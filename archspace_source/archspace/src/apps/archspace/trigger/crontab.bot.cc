// crontab.bot.cc -- bot (NPC) players: population top-up + a band-aware,
// self-sustaining defensive-economy AI.
//
// Two time-based crontabs (registered in main.cc, same mechanism as the
// CCronTabEmpire* jobs):
//
//   CCronTabBotPopulation  keeps a fixed, band-locked spread of bots alive.
//                          Target is BotPerBand (default 25) in each of the
//                          NUM_BOT_BANDS power bands -> 100 bots total. Spawns
//                          at most BotSpawnBatch new bots per run (default 100,
//                          i.e. fill the whole population in one run; lower it to
//                          spread a cold start over several runs if desired).
//
//   CCronTabBotAI          drives each living bot. It ALWAYS keeps a band-sized
//                          defense reserve (5/10/15/20 fleets by band) on stand-
//                          by and fully manned: it first REBUILDS any defenders
//                          lost since spawn back up to that reserve (the per-band
//                          minimum is otherwise only set at spawn and decays as a
//                          bot takes losses), then keeps them manned -- so a bot
//                          always has its full reserve defending. Below its band
//                          ceiling a bot GROWS with the SURPLUS beyond that
//                          reserve: surplus fleets auto-expedition for planets.
//                          At/above the ceiling it THROTTLES: it only defends,
//                          which holds it inside its band so the 25/25/25/25
//                          spread stays stable. SEPARATELY (and regardless of the
//                          ceiling) it runs a COMMANDER-TRAINING program: every
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
//   band 0 (0-10k)    -> 5     band 2 (50-100k)  -> 15
//   band 1 (10-50k)   -> 10    band 3 (100-200k) -> 20
// (band+1)*5. These are never sent on a mission, so a bot that has fleets always
// has fleets standing by to defend.
// ---------------------------------------------------------------------------
static int
bot_defense_reserve(int aBand)
{
	if (aBand < 0) aBand = 0;
	if (aBand >= NUM_BOT_BANDS) aBand = NUM_BOT_BANDS - 1;
	return (aBand + 1) * 5;
}

// Fleets that count toward the defensive floor: everything except the temporary
// training fleets (those are a commander-development "factory", not defenders --
// see bot_train_pool). Without this, trainees would masquerade as reserve fleets
// and the rebuild below would leave the bot short of real defenders.
static int
bot_defense_fleet_count(CFleetList *aList)
{
	int Count = 0;
	for (int i=0 ; i<aList->length() ; i++)
	{
		CFleet *F = (CFleet *)aList->get(i);
		if (F == NULL) continue;
		if (F->get_mission().get_mission() == CMission::MISSION_TRAIN) continue;
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
	while (bot_defense_fleet_count(FleetList) < aReserve && Built < aMaxBuild)
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
// Per-bot AI step.
// ---------------------------------------------------------------------------
static void
bot_ai_act(CPlayer *aPlayer, int aRebuildPerRun, int aTrainPerRun, int aLevelCap)
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

	// (0) Maintain the band minimum: rebuild lost defenders back up to the reserve
	// (training fleets don't count -- see bot_defense_fleet_count). Done before the
	// defense/growth steps so the new fleets are counted there (manned, stand-by).
	if (bot_rebuild_reserve(aPlayer, Reserve, aRebuildPerRun))
		aPlayer->refresh_power();

	bool BelowCeiling = aPlayer->get_power() < Ceiling;

	int N = FleetList->length();

	// (1) Defense -- ALWAYS, even when throttled: keep idle, in-system fleets
	// fully manned (re-crew battle losses up to each fleet's capacity).
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

	// (2) Growth -- only below the band ceiling. Hold back the band's defense
	// reserve of idle stand-by fleets (step (1) keeps them manned) and send the
	// surplus on auto-repeat expeditions to claim planets. Commander training is
	// handled separately by the pool program below, so surplus fleets here go
	// straight to expeditions. (At/above the ceiling the bot throttles: it skips
	// this growth and only defends + trains.)
	if (BelowCeiling)
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

	// (3) Train the commander pool toward the level cap. Runs even when throttled:
	// it develops commanders rather than chasing power, and is self-limiting (a
	// trainee leaves the pool while training and returns maxed in step (A)).
	bot_train_pool(aPlayer, aLevelCap, aTrainPerRun);

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

		bot_ai_act(Player, RebuildPerRun, TrainPerRun, LevelCap);
		Processed++;
	}

	SLOG("SYSTEM : bot AI crontab end (acted on %d bots)", Processed);
}
