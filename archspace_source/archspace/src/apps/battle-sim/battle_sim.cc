// battle-sim : standalone, DB-free Archspace battle evaluator.
//
// Spike A: boot the real engine loading ONLY the script tables (no MariaDB),
// build one siege (attacker vs defender) entirely in memory, run the real
// CBattle, and print attacker_win() + basic result metrics.
//
// This links the real engine objects (apps/archspace/*.o, minus main.o) so the
// battle mechanics are the genuine in-game ones, not a re-implementation.

#include "archspace.h"   // application/game framework
#include "frame.h"       // extern CApplication* gApplication
#include "game.h"
#include "player.h"
#include "council.h"
#include "race.h"
#include "component.h"
#include "ship.h"
#include "fleet.h"
#include "admiral.h"
#include "defense.plan.h"
#include "planet.h"
#include "battle.h"

#include <pth.h>
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>

// ---- inject a DB-free booted CGame into a real CArchspace ------------------
// The global-table macros (COMPONENT_TABLE / SHIP_SIZE_TABLE / RACE_TABLE ...)
// resolve through ((CArchspace*)gApplication)->game(), so we need a real
// CArchspace-layout object whose protected mGame points at our booted game.
class CBattleSimApp : public CArchspace
{
	public:
		void set_game(CGame *aGame) { mGame = aGame; }
};

// ---- minimal in-memory side builders ---------------------------------------

static CPlayer *make_player(int aGameID, int aRace)
{
	CPlayer *Player = new CPlayer(aGameID);
	Player->set_race(aRace);          // resolves CRace* via RACE_TABLE
	Player->set_honor(50);            // neutral

	// get_council() resolves through COUNCIL_TABLE by id, so register the stub
	// council there (id = game id) before wiring it to the player.
	CCouncil *Council = new CCouncil();
	Council->set_id(aGameID);
	Council->set_honor(50);           // neutral; battle averages player+council
	COUNCIL_TABLE->add_council(Council);
	Player->sim_set_council_id(aGameID);   // resolves via COUNCIL_TABLE, no add_member

	// Battle resolves a fleet's owning player via PLAYER_TABLE->get_by_game_id(),
	// e.g. CAdmiral::get_overall_attack() checks the owner's racial abilities.
	PLAYER_TABLE->add_player(Player);

	return Player;
}

// A simple, valid Frigate (size 4003: 2 weapon slots, 1 device slot).
// Component IDs are real entries from script/component.en; battle reads their
// stats from COMPONENT_TABLE by id (tech-gating is a design-time concern).
static void fill_design(CShipDesign *aDesign)
{
	aDesign->set_body(4003);          // Frigate
	aDesign->set_armor(5101);         // Titanium
	aDesign->set_computer(5201);
	aDesign->set_shield(5301);
	aDesign->set_engine(5401);
	aDesign->set_weapon(0, 6101);     // basic beam
	aDesign->set_weapon_number(0, 2);
	aDesign->set_weapon(1, 6101);
	aDesign->set_weapon_number(1, 2);
}

static CFleet *make_fleet(CPlayer *aOwner, int aFleetID, const char *aName,
						  CShipDesign *aDesign, int aAdmiralID, int aShipCount)
{
	CFleet *Fleet = new CFleet();
	Fleet->set_ship_class(aDesign);   // copies the CShipDesign sub-object FIRST
	Fleet->set_owner(aOwner->get_game_id());
	Fleet->set_id(aFleetID);
	Fleet->set_name(aName);
	Fleet->set_admiral(aAdmiralID);
	Fleet->set_max_ship(aShipCount);
	Fleet->set_current_ship(aShipCount);
	Fleet->set_exp(100);              // engine max; pins one morale confounder
	Fleet->set_status(CFleet::FLEET_STAND_BY);
	aOwner->get_fleet_list()->add_fleet(Fleet);
	return Fleet;
}

static CDefenseFleet *add_deploy(CDefensePlan *aPlan, CPlayer *aOwner,
								 int aFleetID, int aCommand, int aX, int aY)
{
	CDefenseFleet *DF = new CDefenseFleet();
	DF->set_owner(aOwner->get_game_id());
	DF->set_fleet_id(aFleetID);
	DF->set_command(aCommand);
	DF->set_x(aX);
	DF->set_y(aY);
	aPlan->add_defense_fleet(DF);
	return DF;
}

int main(int argc, char **argv)
{
	pth_init();

	const char *ConfigPath = (argc > 1) ? argv[1] : "spikeA.config";

	CIniFile Config;
	if (!Config.load(ConfigPath))
	{
		fprintf(stderr, "battle-sim: cannot load config '%s'\n", ConfigPath);
		return 1;
	}

	// Stand up the engine DB-free.
	CBattleSimApp App;
	gApplication = &App;
	CGame *Game = new CGame();
	App.set_game(Game);

	if (!Game->boot_scripts_only(&Config))
	{
		fprintf(stderr, "battle-sim: boot_scripts_only failed\n");
		return 1;
	}
	fprintf(stderr, "battle-sim: engine booted (DB-free). Building siege...\n");

	// --- build the two sides (race 1 = Human on both, for Spike A) ----------
	CPlayer *Attacker = make_player(1, 1);
	CPlayer *Defender = make_player(2, 1);

	CAdmiral *AtkAdmiral = new CAdmiral(20, 5, 0, 1);
	CAdmiral *DefAdmiral = new CAdmiral(20, 5, 0, 1);
	AtkAdmiral->set_owner(Attacker->get_game_id());
	DefAdmiral->set_owner(Defender->get_game_id());
	Attacker->get_admiral_list()->add_admiral(AtkAdmiral);
	Defender->get_admiral_list()->add_admiral(DefAdmiral);

	CShipDesign Design;
	fill_design(&Design);

	// Asymmetric force so the outcome is decisive (validates the mechanics
	// actually engage): a large attacker fleet vs a small defender fleet.
	make_fleet(Attacker, 101, "Strike Force", &Design, AtkAdmiral->get_id(), 30);
	make_fleet(Defender, 201, "Home Guard",   &Design, DefAdmiral->get_id(),  5);

	// Offense plan: charge the capital fleet in.
	CDefensePlan OffensePlan;
	OffensePlan.set_owner(Attacker->get_game_id());
	OffensePlan.set_capital(101);
	add_deploy(&OffensePlan, Attacker, 101, CDefenseFleet::COMMAND_FREE, 4500, 5000);

	// Defense plan: hold the line.
	CDefensePlan DefensePlan;
	DefensePlan.set_owner(Defender->get_game_id());
	DefensePlan.set_capital(201);
	add_deploy(&DefensePlan, Defender, 201, CDefenseFleet::COMMAND_FREE, 5500, 5000);

	// Minimal battlefield planet (siege reads id/name; Spike A stops before the
	// ground siege_war() phase, so no defense-system data is required).
	CPlanet Planet;
	Planet.set_id(1);
	Planet.set_name("Spike-A Test Planet");

	CBattle Battle(CBattle::WAR_SIEGE, Attacker, Defender, (void *)&Planet);

	if (!Battle.init_battle_fleet(&OffensePlan,
								  Attacker->get_fleet_list(), Attacker->get_admiral_list(),
								  &DefensePlan,
								  Defender->get_fleet_list(), Defender->get_admiral_list()))
	{
		fprintf(stderr, "battle-sim: init_battle_fleet returned false (no deployment)\n");
		return 1;
	}

	fprintf(stderr, "battle-sim: deployed offense=%d defense=%d fleets; running...\n",
			Battle.get_offense_battle_fleet_list().length(),
			Battle.get_defense_battle_fleet_list().length());

	while (Battle.run_step())
		;

	bool AttackerWon = Battle.attacker_win();
	printf("RESULT attacker_win=%d turns=%d offense_fleets=%d defense_fleets=%d\n",
		   AttackerWon ? 1 : 0,
		   Battle.get_record()->get_turn(),
		   Battle.get_offense_battle_fleet_list().length(),
		   Battle.get_defense_battle_fleet_list().length());

	// Skip the engine's global/CGame teardown: its table destructors
	// double-free (the real long-lived server never destructs CGame either).
	// A per-battle worker process is short-lived, so _exit() is the clean exit.
	fflush(stdout);
	fflush(stderr);
	_exit(AttackerWon ? 0 : 0);
}
