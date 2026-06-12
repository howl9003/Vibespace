// battle-sim : standalone, DB-free Archspace battle evaluator.
//
// Boots the real engine without MariaDB and serves a line-oriented JSON
// protocol on stdin/stdout (one request object per line, one response per
// line). Requests:
//   {"cmd":"ping"}                         -> {"ok":true,...}
//   {"cmd":"pool","race":R,"tech_cap":T}   -> legal components + hulls for R
//   {"cmd":"quit"}                         -> exits
// Run with `--demo [seed]` to run the hand-built Spike siege (regression).
//
// Links the real engine objects so the mechanics are the genuine in-game ones.

#include "archspace.h"
#include "frame.h"       // extern CApplication* gApplication
#include "game.h"
#include "player.h"
#include "council.h"
#include "race.h"
#include "tech.h"
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
#include <string.h>
#include <unistd.h>
#include <string>
#include <sstream>
#include <iostream>

#include "json_mini.h"

// ---- inject a DB-free booted CGame into a real CArchspace ------------------
class CBattleSimApp : public CArchspace
{
	public:
		void set_game(CGame *aGame) { mGame = aGame; }
};

static void emit(const std::string &aLine)
{
	fputs(aLine.c_str(), stdout);
	fputc('\n', stdout);
	fflush(stdout);
}

static void emit_error(const char *aWhat)
{
	std::ostringstream o;
	o << "{\"ok\":false,\"error\":\"" << aWhat << "\"}";
	emit(o.str());
}

// =====================  pool query  =========================================

static const char *category_name(int aCategory)
{
	switch (aCategory)
	{
		case CComponent::CC_ARMOR:    return "ARMOR";
		case CComponent::CC_COMPUTER: return "COMP";
		case CComponent::CC_SHIELD:   return "SHLD";
		case CComponent::CC_ENGINE:   return "ENGN";
		case CComponent::CC_DEVICE:   return "DEV";
		case CComponent::CC_WEAPON:   return "WPN";
		default:                      return "UNKNOWN";
	}
}

// Synthetic player of race R that knows every tech with level <= tech_cap, so
// CComponent::evaluate() returns the genuinely race/tech-legal component set.
static CPlayer *make_pool_player(int aRace, int aTechCap)
{
	CPlayer *Player = new CPlayer(900 + aRace);
	Player->set_race(aRace);

	for (int i = 0; i < TECH_TABLE->length(); i++)
	{
		CTech *Tech = (CTech *)TECH_TABLE->get(i);
		if (Tech->get_level() <= aTechCap)
			Player->add_tech(new CKnownTech(Player->get_game_id(), Tech->get_id(), 0));
	}
	return Player;
}

static void do_pool(const JValue &aReq)
{
	int Race    = aReq["race"].as_int(1);
	int TechCap = aReq["tech_cap"].as_int(999999);

	if (Race < 1 || Race > MAX_RACE) { emit_error("bad race"); return; }

	CPlayer *Player = make_pool_player(Race, TechCap);

	// Components grouped by category.
	std::ostringstream comp[CComponent::CC_MAX];
	bool first[CComponent::CC_MAX];
	for (int c = 0; c < CComponent::CC_MAX; c++) first[c] = true;

	for (int i = 0; i < COMPONENT_TABLE->length(); i++)
	{
		CComponent *Comp = (CComponent *)COMPONENT_TABLE->get(i);
		if (!Comp->evaluate(Player)) continue;
		int cat = Comp->get_category();
		if (cat < 0 || cat >= CComponent::CC_MAX) continue;
		if (!first[cat]) comp[cat] << ",";
		first[cat] = false;
		comp[cat] << "{\"id\":" << Comp->get_id()
				  << ",\"level\":" << Comp->get_level() << "}";
	}

	// Hulls (ship sizes).
	std::ostringstream hulls;
	bool hfirst = true;
	for (int i = 0; i < SHIP_SIZE_TABLE->length(); i++)
	{
		CShipSize *S = (CShipSize *)SHIP_SIZE_TABLE->get(i);
		if (!hfirst) hulls << ",";
		hfirst = false;
		hulls << "{\"id\":" << S->get_id()
			  << ",\"class\":" << S->get_class()
			  << ",\"cost\":" << S->get_cost()
			  << ",\"space\":" << S->get_space()
			  << ",\"weapon_slots\":" << S->get_weapon()
			  << ",\"device_slots\":" << S->get_device() << "}";
	}

	std::ostringstream o;
	o << "{\"ok\":true,\"cmd\":\"pool\",\"race\":" << Race
	  << ",\"tech_cap\":" << TechCap << ",\"hulls\":[" << hulls.str() << "]"
	  << ",\"components\":{";
	for (int c = 0; c < CComponent::CC_MAX; c++)
	{
		if (c) o << ",";
		o << "\"" << category_name(c) << "\":[" << comp[c].str() << "]";
	}
	o << "}}";
	emit(o.str());
}

// =====================  demo siege (regression)  ============================

static CPlayer *make_player(int aGameID, int aRace)
{
	CPlayer *Player = new CPlayer(aGameID);
	Player->set_race(aRace);
	Player->set_honor(50);

	CCouncil *Council = new CCouncil();
	Council->set_id(aGameID);
	Council->set_honor(50);
	COUNCIL_TABLE->add_council(Council);
	Player->sim_set_council_id(aGameID);

	PLAYER_TABLE->add_player(Player);
	return Player;
}

static void fill_design(CShipDesign *aDesign)
{
	aDesign->set_body(4003);
	aDesign->set_armor(5101);
	aDesign->set_computer(5201);
	aDesign->set_shield(5301);
	aDesign->set_engine(5401);
	aDesign->set_weapon(0, 6101);
	aDesign->set_weapon_number(0, 2);
	aDesign->set_weapon(1, 6101);
	aDesign->set_weapon_number(1, 2);
}

static CFleet *make_fleet(CPlayer *aOwner, int aFleetID, const char *aName,
						  CShipDesign *aDesign, int aAdmiralID, int aShipCount)
{
	CFleet *Fleet = new CFleet();
	Fleet->set_ship_class(aDesign);
	Fleet->set_owner(aOwner->get_game_id());
	Fleet->set_id(aFleetID);
	Fleet->set_name(aName);
	Fleet->set_admiral(aAdmiralID);
	Fleet->set_max_ship(aShipCount);
	Fleet->set_current_ship(aShipCount);
	Fleet->set_exp(100);
	Fleet->set_status(CFleet::FLEET_STAND_BY);
	aOwner->get_fleet_list()->add_fleet(Fleet);
	return Fleet;
}

static void add_deploy(CDefensePlan *aPlan, CPlayer *aOwner,
					   int aFleetID, int aCommand, int aX, int aY)
{
	CDefenseFleet *DF = new CDefenseFleet();
	DF->set_owner(aOwner->get_game_id());
	DF->set_fleet_id(aFleetID);
	DF->set_command(aCommand);
	DF->set_x(aX);
	DF->set_y(aY);
	aPlan->add_defense_fleet(DF);
}

static void run_demo(unsigned long aSeed)
{
	seed_rng(aSeed);

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

	make_fleet(Attacker, 101, "Strike Force", &Design, AtkAdmiral->get_id(), 16);
	make_fleet(Defender, 201, "Home Guard",   &Design, DefAdmiral->get_id(), 12);

	CDefensePlan OffensePlan;
	OffensePlan.set_owner(Attacker->get_game_id());
	OffensePlan.set_capital(101);
	add_deploy(&OffensePlan, Attacker, 101, CDefenseFleet::COMMAND_FREE, 4500, 5000);

	CDefensePlan DefensePlan;
	DefensePlan.set_owner(Defender->get_game_id());
	DefensePlan.set_capital(201);
	add_deploy(&DefensePlan, Defender, 201, CDefenseFleet::COMMAND_FREE, 5500, 5000);

	CPlanet Planet;
	Planet.set_id(1);
	Planet.set_name("Demo Planet");

	CBattle Battle(CBattle::WAR_SIEGE, Attacker, Defender, (void *)&Planet);
	if (!Battle.init_battle_fleet(&OffensePlan,
								  Attacker->get_fleet_list(), Attacker->get_admiral_list(),
								  &DefensePlan,
								  Defender->get_fleet_list(), Defender->get_admiral_list()))
	{
		emit_error("demo init_battle_fleet failed");
		return;
	}
	while (Battle.run_step()) ;

	std::ostringstream o;
	o << "{\"ok\":true,\"cmd\":\"demo\",\"seed\":" << aSeed
	  << ",\"attacker_win\":" << (Battle.attacker_win() ? 1 : 0)
	  << ",\"turns\":" << Battle.get_record()->get_turn() << "}";
	emit(o.str());
}

// =====================  server loop  ========================================

static void serve()
{
	std::string line;
	while (std::getline(std::cin, line))
	{
		if (line.empty()) continue;
		JValue req;
		if (!JParser::parse(line, req) || !req.is_obj()) { emit_error("parse"); continue; }

		std::string cmd = req["cmd"].as_str();
		if (cmd == "pool")      do_pool(req);
		else if (cmd == "ping") emit("{\"ok\":true,\"cmd\":\"ping\"}");
		else if (cmd == "quit") break;
		else                    emit_error("unknown cmd");
	}
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

	CBattleSimApp App;
	gApplication = &App;
	CGame *Game = new CGame();
	App.set_game(Game);

	if (!Game->boot_scripts_only(&Config))
	{
		fprintf(stderr, "battle-sim: boot_scripts_only failed\n");
		return 1;
	}
	fprintf(stderr, "battle-sim: engine booted (DB-free).\n");

	if (argc > 2 && strcmp(argv[2], "--demo") == 0)
	{
		unsigned long Seed = (argc > 3) ? strtoul(argv[3], NULL, 10) : 12345UL;
		run_demo(Seed);
	}
	else
	{
		serve();
	}

	// Skip the engine's buggy global/CGame teardown (table dtors double-free;
	// the long-lived server never destructs CGame either).
	fflush(stdout);
	fflush(stderr);
	_exit(0);
}
