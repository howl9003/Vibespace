// crontab.bot.cc -- bot (NPC) players: population top-up + a band-aware,
// self-sustaining defensive-economy AI.
//
// Two time-based crontabs (registered in main.cc, same mechanism as the
// CCronTabEmpire* jobs):
//
//   CCronTabBotPopulation  keeps a fixed, band-locked spread of bots alive.
//                          Target is BotPerBand (default 25) in each of the
//                          NUM_BOT_BANDS power bands -> 100 bots total. Spawns
//                          at most BotSpawnBatch new bots per run so a cold
//                          start fills in gracefully rather than in one spike.
//
//   CCronTabBotAI          drives each living bot. Below its band ceiling a bot
//                          GROWS: idle fleets auto-expedition for planets,
//                          reserve fleets train their commanders, and existing
//                          fleets are kept manned. At/above the ceiling it
//                          THROTTLES: it only defends (keeps fleets manned),
//                          which holds it inside its band so the 25/25/25/25
//                          spread stays stable. Bounded to BotAIPerRun bots per
//                          run (round-robin across the player table) so a full
//                          population can't stall the engine loop.
//
// Bot identity and band are encoded in the portal id (see player.h: is_bot(),
// bot_band(), bot_band_ceiling()); nothing extra is persisted.

#include <libintl.h>
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
	int SpawnBatch = bot_cfg("BotSpawnBatch", 5);

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
// Per-bot AI step.
// ---------------------------------------------------------------------------
static void
bot_ai_act(CPlayer *aPlayer, int aReserveFleets, int aTrainExpTarget)
{
	aPlayer->refresh_power();

	int Ceiling      = bot_band_ceiling(aPlayer->bot_band());
	bool BelowCeiling = aPlayer->get_power() < Ceiling;

	CFleetList *FleetList = aPlayer->get_fleet_list();
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

	// (2) Growth -- below the band ceiling. Count idle stand-by fleets, keep a
	// home reserve, send the rest on auto-repeat expeditions to claim planets,
	// and train under-experienced reserve fleets' commanders.
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

	int ToExpedition = IdleCount - aReserveFleets;
	if (ToExpedition < 0) ToExpedition = 0;

	int Seen = 0, Sent = 0, Trained = 0;
	for (int i=0 ; i<N ; i++)
	{
		CFleet *Fleet = (CFleet *)FleetList->get(i);
		if (Fleet == NULL) continue;
		if (Fleet->get_status() != CFleet::FLEET_STAND_BY) continue;
		if (Fleet->under_mission()) continue;
		if (Fleet->get_mission().get_mission() != CMission::MISSION_NONE) continue;

		if (Seen < ToExpedition)
		{
			// claim planets; target=1 -> auto-repeat after each return.
			Fleet->init_mission(CMission::MISSION_EXPEDITION, 1);
			Fleet->type(QUERY_UPDATE);
			STORE_CENTER->store(*Fleet);
			Sent++;
		}
		else if (Fleet->get_exp() < aTrainExpTarget)
		{
			// reserve fleet, under-trained -> train its commander.
			Fleet->init_mission(CMission::MISSION_TRAIN, 0);
			Fleet->type(QUERY_UPDATE);
			STORE_CENTER->store(*Fleet);
			Trained++;
		}
		Seen++;
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
	int ReserveFleets = bot_cfg("BotDefenseReserveFleets", 2);
	int TrainExpTarget = bot_cfg("BotTrainExpTarget", 300);

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

		bot_ai_act(Player, ReserveFleets, TrainExpTarget);
		Processed++;
	}

	SLOG("SYSTEM : bot AI crontab end (acted on %d bots)", Processed);
}
