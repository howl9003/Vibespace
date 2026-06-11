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
//                          by and fully manned: it first REBUILDS any fleets lost
//                          since spawn back up to that reserve (the per-band
//                          minimum is otherwise only set at spawn and decays as a
//                          bot takes losses), then keeps them manned -- so a bot
//                          always has its full reserve defending. Below its band ceiling a bot
//                          GROWS with the SURPLUS beyond that reserve: surplus
//                          fleets auto-expedition for planets or train their
//                          commanders. At/above the ceiling it
//                          THROTTLES: it only defends (keeps fleets manned),
//                          which holds it inside its band so the 25/25/25/25
//                          spread stays stable. Bounded to BotAIPerRun bots per
//                          run (round-robin across the player table) so a full
//                          population can't stall the engine loop.
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
	while (FleetList->length() < aReserve && Built < aMaxBuild)
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
// Per-bot AI step.
// ---------------------------------------------------------------------------
static void
bot_ai_act(CPlayer *aPlayer, int aTrainExpTarget, int aRebuildPerRun)
{
	aPlayer->refresh_power();

	int Ceiling      = bot_band_ceiling(aPlayer->bot_band());
	int  Reserve      = bot_defense_reserve(aPlayer->bot_band());

	CFleetList *FleetList = aPlayer->get_fleet_list();

	// (0) Maintain the band minimum: rebuild fleets lost since spawn back up to
	// the defense reserve. Without this the floor is only ever set at spawn and
	// decays as the bot takes losses. Done first so the new fleets are counted by
	// the defense/growth steps below (they start fully manned, on stand-by).
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

	if (!BelowCeiling)
	{
		// throttled: hold the band, defend only.
		aPlayer->refresh_power();
		return;
	}

	// (2) Growth -- below the band ceiling. Count idle stand-by fleets and ALWAYS
	// hold back the band's defense reserve (5/10/15/20 by band): those are left
	// on stand-by (step (1) keeps them manned) so they auto-deploy to defend.
	// Only the *surplus* beyond that reserve is put to work -- under-experienced
	// surplus fleets train their commanders, the rest auto-repeat expeditions to
	// claim planets. The reserve is never missioned, so a bot that has fleets
	// always has fleets standing by to defend.
	//
	// (The reserve used to be the slice that got trained, which flipped it to
	// FLEET_UNDER_MISSION and left the bot with no defenders -- fixed here by
	// missioning only the surplus.)
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

	int Seen = 0, Sent = 0, Trained = 0;
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

		if (Fleet->get_exp() < aTrainExpTarget)
		{
			// surplus fleet, under-trained -> train its commander.
			Fleet->init_mission(CMission::MISSION_TRAIN, 0);
			Fleet->type(QUERY_UPDATE);
			STORE_CENTER->store(*Fleet);
			Trained++;
		}
		else
		{
			// surplus fleet -> claim planets; target=1 -> auto-repeat on return.
			Fleet->init_mission(CMission::MISSION_EXPEDITION, 1);
			Fleet->type(QUERY_UPDATE);
			STORE_CENTER->store(*Fleet);
			Sent++;
		}
	}

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
	int TrainExpTarget = bot_cfg("BotTrainExpTarget", 300);
	int RebuildPerRun  = bot_cfg("BotRebuildPerRun", 5);  // fleets rebuilt per bot per run

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

		bot_ai_act(Player, TrainExpTarget, RebuildPerRun);
		Processed++;
	}

	SLOG("SYSTEM : bot AI crontab end (acted on %d bots)", Processed);
}
