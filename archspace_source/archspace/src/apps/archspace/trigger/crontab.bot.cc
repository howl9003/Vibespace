// crontab.bot.cc -- bot (NPC) players: a tiered, self-running rise-and-fall.
//
// Each tier (= bot band 0..5, see bot_tier_spec in player.h) flies a distinct
// ship class at a fixed tech level -- Frigate/Cruiser/BattleShip/Dreadnaught/
// Doomstar/Doomstar at component level 3/3/4/4/5/5 -- and has its own fleet caps
// and target population (a pyramid: more weak bots, fewer apex). Two time-based
// crontabs (registered in main.cc, same mechanism as the CCronTabEmpire* jobs):
//
//   CCronTabBotPopulation  keeps each tier's target population alive (pyramid
//                          counts from bot_tier_spec; ~150 total). Spawns at most
//                          BotSpawnBatch new bots per run (default 100). Logs a
//                          per-tier bot/fleet-count histogram each run.
//
//   CCronTabBotRegen       the autonomous rise-and-fall. Roughly hourly (every 60
//                          turns) each bot gains one fresh full fleet (a level-20
//                          commander of its race) of its tier's ship class, plus
//                          it always keeps exactly one fleet on a permanent auto-
//                          repeat expedition. On reaching its tier's max_fleets the
//                          bot is culled back to cull_to (oldest fleets first, the
//                          expedition fleet kept). Battle losses are otherwise NOT
//                          rebuilt, so a bot's strength genuinely drifts: it climbs
//                          +1 fleet/hour to the cap, sheds back down, and regrows
//                          -- a slow sawtooth. Only the fleet COUNT breathes; the
//                          tier's ship class/tech level are fixed. Bot-only, so
//                          human players are never touched.
//
// Bot identity and tier are encoded in the portal id (see player.h: is_bot(),
// bot_band(), bot_tier_spec()); nothing extra is persisted.

#include <libintl.h>
#include <cstring>
#include "../triggers.h"
#include "../archspace.h"
#include "../council.h"

static int
bot_cfg(const char *aKey, int aDefault)
{
	return ARCHSPACE->configuration().get_integer("Game", aKey, aDefault);
}

// ---------------------------------------------------------------------------
// Population: keep each tier's target count (bot_tier_spec) of bots alive.
// ---------------------------------------------------------------------------
void
CCronTabBotPopulation::handler()
{
	if (!CGame::mUpdateTurn) return;
	SLOG("SYSTEM : bot population crontab start");

	int SpawnBatch = bot_cfg("BotSpawnBatch", 100);  // fill all 100 in one run

	// Name maintenance / backfill. make_bot_name only runs when a bot is *created*,
	// so a standing population keeps whatever names it was last given (including
	// names from earlier naming schemes -- e.g. a race-agnostic pool that could land
	// an "Evintos Foundry" on a Targoid). This pass renames any bot whose current
	// name isn't already appropriate for its race (bot_name_fits_race: a commander
	// name, or one of that race's own faction names) to a fresh, UNIQUE, race-fitting
	// one. Idempotent -- a fitting name is left alone -- so it settles to a no-op.
	// rename_player keeps the name index consistent (so get_by_name and the
	// human-registration duplicate check keep working); player.name isn't in the
	// normal UPDATE set, so persist it directly too (names may contain apostrophes,
	// hence add_slashes). Bounded per run so the first sweep doesn't stall the cron.
	int Renamed = 0, MaxRename = bot_cfg("BotRenamePerRun", 60);
	for (int i=0 ; i<PLAYER_TABLE->length() && Renamed<MaxRename ; i++)
	{
		CPlayer *P = (CPlayer *)PLAYER_TABLE->get(i);
		if (P == NULL || !P->is_bot() || P->is_dead()) continue;
		if (CGame::bot_name_fits_race(P->get_race(), P->get_real_name())) continue;

		char NewName[41];
		GAME->make_bot_name(P->get_race(), P->bot_band(), NewName, sizeof(NewName));
		if (!PLAYER_TABLE->rename_player(P, NewName)) continue;  // clash -> retry next run

		STORE_CENTER->query("player",
				(char *)format("UPDATE player SET name = '%s' WHERE game_id = %d",
						(char *)add_slashes(NewName), P->get_game_id()));
		Renamed++;
	}
	if (Renamed) SLOG("SYSTEM : bot population crontab renamed %d bot(s)", Renamed);

	// One-time migration: convert bots still flying their pre-tier ships onto the
	// tier roster -- scrap their old ships and rebuild the tier's named designs +
	// fleets (build_bot_roster). A migrated bot has a tier design (named
	// "<Hull> Mk. <level>"), so detect the absence of one; this is then a no-op.
	// Bounded per run (BotRerollPerRun) so the whole population converts over a few
	// runs rather than thousands of deletes/inserts in a single tick.
	int Reseeded = 0, MaxReseed = bot_cfg("BotRerollPerRun", 20);
	for (int i=0 ; i<PLAYER_TABLE->length() && Reseeded<MaxReseed ; i++)
	{
		CPlayer *P = (CPlayer *)PLAYER_TABLE->get(i);
		if (P == NULL || !P->is_bot() || P->is_dead()) continue;

		CShipDesignList *DL = P->get_ship_design_list();
		bool HasTierDesign = false;
		for (int d=0 ; d<DL->length() ; d++)
		{
			CShipDesign *D = (CShipDesign *)DL->get(d);
			if (D != NULL && D->get_name() && strstr(D->get_name(), " Mk. "))
			{ HasTierDesign = true; break; }
		}
		if (HasTierDesign) continue;

		GAME->build_bot_roster(P, P->bot_band());
		P->refresh_power();
		P->type(QUERY_UPDATE);
		STORE_CENTER->store(*P);
		Reseeded++;
	}
	if (Reseeded) SLOG("SYSTEM : bot population crontab reseeded %d bot(s) to tier ships", Reseeded);

	int Spawned = 0;
	for (int band=0 ; band<NUM_BOT_BANDS && Spawned<SpawnBatch ; band++)
	{
		int Target = bot_tier_spec(band).mPopulation;   // pyramid: more low, fewer high

		int Alive = 0;
		for (int i=0 ; i<PLAYER_TABLE->length() ; i++)
		{
			CPlayer *P = (CPlayer *)PLAYER_TABLE->get(i);
			if (P == NULL) continue;
			if (P->is_bot() && P->bot_band() == band && !P->is_dead()) Alive++;
		}

		for (int n=Alive ; n<Target && Spawned<SpawnBatch ; n++)
		{
			if (GAME->create_bot_player(band) != NULL) Spawned++;
		}
	}

	// per-tier fleet-count histogram, so live logs show the rise-and-fall spread.
	int TierBots[NUM_BOT_BANDS] = {0}, TierFleets[NUM_BOT_BANDS] = {0};
	for (int i=0 ; i<PLAYER_TABLE->length() ; i++)
	{
		CPlayer *P = (CPlayer *)PLAYER_TABLE->get(i);
		if (P == NULL || !P->is_bot() || P->is_dead()) continue;
		int b = P->bot_band();
		if (b < 0 || b >= NUM_BOT_BANDS) continue;
		TierBots[b]++;
		TierFleets[b] += P->get_fleet_list()->length();
	}
	SLOG("SYSTEM : bot tiers bots[%d/%d/%d/%d/%d/%d] fleets[%d/%d/%d/%d/%d/%d]",
			TierBots[0], TierBots[1], TierBots[2], TierBots[3], TierBots[4], TierBots[5],
			TierFleets[0], TierFleets[1], TierFleets[2], TierFleets[3], TierFleets[4], TierFleets[5]);

	// Reflect freshly spawned / reseeded bots in the live rankings NOW. create_bot_player
	// adds its rank entry (via create_new_player) before granting the bot's tech/planets and
	// building its tier roster -- and the reseed above rebuilds rosters too -- so those rank
	// entries hold a near-zero, pre-roster power until the next hourly CTriggerRank. That hour
	// of staleness is exactly why a just-respawned bot population showed up in the rankings at
	// ~0 power. Rebuilding here (only when something actually changed, so this settles to a
	// no-op once the population is full) keeps the player AND council rankings in step with
	// real bot power within a population-cron cycle instead of up to an hour later.
	if (Spawned || Reseeded)
	{
		PLAYER_TABLE->refresh_rank_table();
		COUNCIL_TABLE->refresh_rank_table();
	}

	SLOG("SYSTEM : bot population crontab end (spawned %d this run)", Spawned);
}

// ---------------------------------------------------------------------------
// Count a bot's fleets currently out on an expedition (either prospecting or
// returning with a claimed planet). Every bot permanently keeps exactly one such
// fleet (auto-repeat), so this is normally 1; the regen cron re-launches one if
// it ever hits 0, and the cull never removes it.
// ---------------------------------------------------------------------------
static int
bot_expedition_fleet_count(CFleetList *aList)
{
	int Count = 0;
	for (int i=0 ; i<aList->length() ; i++)
	{
		CFleet *F = (CFleet *)aList->get(i);
		if (F == NULL) continue;
		int M = F->get_mission().get_mission();
		if (M == CMission::MISSION_EXPEDITION ||
			M == CMission::MISSION_RETURNING_WITH_PLANET)
			Count++;
	}
	return Count;
}

// ---------------------------------------------------------------------------
// Delete a bot fleet and retire its commander outright. The regen grant always
// creates a fresh level-20 commander, so a culled fleet's admiral is deleted
// rather than recycled. Mirrors the disband store pattern (QUERY_DELETE fleet +
// admiral). Frees aFleet via remove_fleet(), so don't use it after this returns.
// ---------------------------------------------------------------------------
static void
bot_delete_fleet(CPlayer *aPlayer, CFleet *aFleet)
{
	int FleetID = aFleet->get_id();
	CAdmiral *Admiral =
		aPlayer->get_admiral_list()->get_by_id(aFleet->get_admiral_id());

	aFleet->type(QUERY_DELETE);
	STORE_CENTER->store(*aFleet);                 // query built from owner+id now
	aPlayer->get_fleet_list()->remove_fleet(FleetID);   // frees aFleet

	if (Admiral != NULL)
	{
		Admiral->set_fleet_number(0);
		Admiral->type(QUERY_DELETE);
		STORE_CENTER->store(*Admiral);
		aPlayer->get_admiral_list()->remove_admiral(Admiral->get_id());  // frees it
	}
}

// ---------------------------------------------------------------------------
// Cull a bot back down to aTarget fleets, oldest-first (lowest fleet id = front
// of the sorted list), but NEVER the permanent auto-expedition fleet. Oldest-first
// also self-heals migration: a legacy bot's pre-tier fleets are its oldest, so
// they retire first and the bot converges to an all-tier roster.
// ---------------------------------------------------------------------------
static void
bot_cull_to(CPlayer *aPlayer, int aTarget)
{
	CFleetList *FleetList = aPlayer->get_fleet_list();
	for (int i=0 ; i<FleetList->length() && FleetList->length()>aTarget ; )
	{
		CFleet *F = (CFleet *)FleetList->get(i);
		if (F == NULL) { i++; continue; }
		int M = F->get_mission().get_mission();
		if (M == CMission::MISSION_EXPEDITION ||
			M == CMission::MISSION_RETURNING_WITH_PLANET)
		{ i++; continue; }                 // keep the one expedition fleet
		bot_delete_fleet(aPlayer, F);      // removes element i; tail shifts into i
	}
}

// ---------------------------------------------------------------------------
// Regen: the autonomous rise-and-fall. Roughly hourly (every 60 turns) each bot
//   (1) makes sure it has its one permanent auto-repeat expedition fleet,
//   (2) gains one fresh full fleet (level-20 commander of its race) of its tier's
//       ship class, and
//   (3) once it reaches its tier's max_fleets, is culled back to cull_to (oldest
//       fleets first, the expedition fleet always kept).
// Battle losses are NOT otherwise rebuilt, so a bot's strength genuinely drifts:
// it climbs +1 fleet/hour to the cap, sheds back down, and regrows -- a slow
// sawtooth. Tier ship class + tech level are fixed by bot_tier_spec; only the
// fleet COUNT breathes. Bot-only (is_bot guard), so human players are untouched.
// ---------------------------------------------------------------------------
void
CCronTabBotRegen::handler()
{
	if (!CGame::mUpdateTurn) return;
	SLOG("SYSTEM : bot regen crontab start");

	int FleetExp = bot_cfg("BotFleetExp", 100);
	int CmdLevel = bot_cfg("BotCommanderLevel", 20);

	int Acted = 0;
	for (int i=0 ; i<PLAYER_TABLE->length() ; i++)
	{
		CPlayer *Player = (CPlayer *)PLAYER_TABLE->get(i);
		if (Player == NULL) continue;
		if (!Player->is_bot() || Player->is_dead()) continue;

		const CBotTierSpec &Spec = bot_tier_spec(Player->bot_band());
		CFleetList      *FleetList  = Player->get_fleet_list();
		CShipDesignList *DesignList = Player->get_ship_design_list();

		// fly a random one of the bot's own tier designs (built at spawn)
		CShipDesign *Design = DesignList->length()
			? (CShipDesign *)DesignList->get(number(DesignList->length()) - 1)
			: NULL;
		if (Design == NULL) continue;

		// (1) ensure the one permanent auto-repeat expedition fleet exists
		if (bot_expedition_fleet_count(FleetList) == 0)
			bot_add_fleet(Player, Design, CmdLevel, FleetExp, true);

		// (2) grant one new defender fleet
		bot_add_fleet(Player, Design, CmdLevel, FleetExp, false);

		// (3) at the cap, cull back to cull_to (oldest first, expedition kept)
		if (FleetList->length() >= Spec.mMaxFleets)
			bot_cull_to(Player, Spec.mCullTo);

		Player->refresh_power();
		Player->type(QUERY_UPDATE);
		STORE_CENTER->store(*Player);
		Acted++;
	}

	// The grow/cull above changed every acted-on bot's fleet power, but rank entries are only
	// rebuilt by the hourly CTriggerRank -- so without this the rankings would lag a full hour
	// behind each bot's rise-and-fall. Rebuild the player and council rank tables now so they
	// track the drift. Bounded to when we actually touched bots.
	if (Acted)
	{
		PLAYER_TABLE->refresh_rank_table();
		COUNCIL_TABLE->refresh_rank_table();
	}

	SLOG("SYSTEM : bot regen crontab end (acted on %d bots)", Acted);
}
