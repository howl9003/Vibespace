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
#include <math.h>
#include <sys/wait.h>
#include <string>
#include <sstream>
#include <iostream>
#include <vector>
#include <map>

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

// Append a string as JSON-escaped content (no surrounding quotes).
static void json_append_escaped(std::ostringstream &o, const char *s)
{
	if (!s) return;
	for (const char *p = s; *p; p++)
	{
		unsigned char c = (unsigned char)*p;
		switch (c)
		{
			case '"':  o << "\\\""; break;
			case '\\': o << "\\\\"; break;
			case '\n': o << "\\n";  break;
			case '\r': o << "\\r";  break;
			case '\t': o << "\\t";  break;
			default:
				if (c < 0x20) { char b[8]; snprintf(b, sizeof b, "\\u%04x", c); o << b; }
				else o << (char)c;
		}
	}
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

// Append ,"effects":[{type,amount,apply,target,range,period},...] for a component
// (the component's declared <Effect> blocks). type is the CFleetEffect enum id; the
// UI maps it to a name. Lets the pool tell e.g. a PSI weapon (has WE_PSI) from a plain one.
static void emit_effects(std::ostringstream &o, CComponent *aComp)
{
	o << ",\"effects\":[";
	CFleetEffectListStatic &L = aComp->get_effect_list();
	for (int i = 0; i < L.length(); i++)
	{
		CFleetEffect *E = (CFleetEffect *)L.get(i);
		if (i) o << ",";
		o << "{\"type\":" << E->get_type()
		  << ",\"amount\":" << E->get_amount()
		  << ",\"apply\":" << E->get_apply_type()
		  << ",\"target\":" << E->get_target()
		  << ",\"range\":" << E->get_range()
		  << ",\"period\":" << E->get_period() << "}";
	}
	o << "]";
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
				  << ",\"level\":" << Comp->get_level();
		comp[cat] << ",\"name\":\"";
		json_append_escaped(comp[cat], Comp->get_name());
		comp[cat] << "\"";
		switch (cat)   // full per-category stats for the UI + eligibility rules
		{
			case CComponent::CC_WEAPON:
			{
				CWeapon *W = (CWeapon *)Comp;
				comp[cat] << ",\"wtype\":" << W->get_weapon_type()     // 0 BM,1 MSL,2 PRJ,3 FGT
						  << ",\"ar\":" << W->get_attacking_rate()
						  << ",\"roll\":" << W->get_damage_roll()
						  << ",\"dice\":" << W->get_damage_dice()
						  << ",\"space\":" << W->get_space()           // how many fit per slot
						  << ",\"cool\":" << W->get_cooling_time()
						  << ",\"range\":" << W->get_range()
						  << ",\"aof\":" << W->get_angle_of_fire()
						  << ",\"wspeed\":" << W->get_speed();
				break;
			}
			case CComponent::CC_ARMOR:
			{
				CArmor *A = (CArmor *)Comp;
				comp[cat] << ",\"atype\":" << A->get_armor_type()      // 0 Norm,1 Bio,2 React
						  << ",\"dr\":" << A->get_defense_rate()
						  << ",\"hp_mult\":" << A->get_hp_multiplier();
				break;
			}
			case CComponent::CC_DEVICE:
			{
				CDevice *D = (CDevice *)Comp;
				comp[cat] << ",\"min_class\":" << D->get_min_class()
						  << ",\"max_class\":" << D->get_max_class()
						  << ",\"psi_only\":"    << (Comp->has_attribute(CComponent::CA_PSI_RACE_ONLY)     ? 1 : 0)
						  << ",\"bio_only\":"    << (Comp->has_attribute(CComponent::CA_BIO_ARMOR_ONLY)     ? 1 : 0)
						  << ",\"nonbio_only\":" << (Comp->has_attribute(CComponent::CA_NON_BIO_ARMOR_ONLY) ? 1 : 0);
				break;
			}
			case CComponent::CC_COMPUTER:
			{
				CComputer *C = (CComputer *)Comp;
				comp[cat] << ",\"ar\":" << C->get_attacking_rate()
						  << ",\"dr\":" << C->get_defense_rate();
				break;
			}
			case CComponent::CC_SHIELD:
				comp[cat] << ",\"solidity\":" << ((CShield *)Comp)->get_deflection_solidity();
				break;
			case CComponent::CC_ENGINE:
				comp[cat] << ",\"cruise\":" << ((CEngine *)Comp)->get_cruise_speed();
				break;
		}
		emit_effects(comp[cat], Comp);
		comp[cat] << "}";
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
			  << ",\"slot\":" << S->get_slot()          // space per weapon slot
			  << ",\"weapon_slots\":" << S->get_weapon()
			  << ",\"device_slots\":" << S->get_device();
		hulls << ",\"name\":\"";
		json_append_escaped(hulls, S->get_name());
		hulls << "\"}";
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

// =====================  match (MatchSpec -> MatchResult)  ===================

static void build_design(const JValue &aD, CShipDesign *aOut)
{
	aOut->set_body(aD["body"].as_int(4003));
	aOut->set_armor(aD["armor"].as_int(5101));
	aOut->set_computer(aD["computer"].as_int(5201));
	aOut->set_shield(aD["shield"].as_int(5301));
	aOut->set_engine(aD["engine"].as_int(5401));

	const JValue &W = aD["weapons"];
	for (size_t i = 0; i < W.size() && i < WEAPON_MAX_NUMBER; i++)
	{
		aOut->set_weapon((int)i, W[i]["id"].as_int(0));
		aOut->set_weapon_number((int)i, W[i]["n"].as_int(1));
	}
	const JValue &D = aD["devices"];
	for (size_t i = 0; i < D.size() && i < DEVICE_MAX_NUMBER; i++)
		aOut->set_device((int)i, D[i].as_int(0));
}

// Build one side (race + fleets) into a CPlayer and populate its deployment plan.
// If aFleetE is given, record fleet_id -> expected per-gun-shot damage E = roll*(dice+1)/2
// of the fleet's (homogeneous) weapon, so the record parser can value raw incoming damage.
static CPlayer *build_side(const JValue &aSide, int aGameID, CDefensePlan *aPlan,
						   std::map<int, double> *aFleetE = NULL)
{
	int Race = aSide["race"].as_int(1);
	CPlayer *Player = make_player(aGameID, Race);
	aPlan->set_owner(Player->get_game_id());

	const JValue &Fleets = aSide["fleets"];
	bool capitalSet = false;
	for (size_t f = 0; f < Fleets.size(); f++)
	{
		const JValue &F = Fleets[f];
		int fid = F["id"].as_int(aGameID * 100 + (int)f + 1);

		CShipDesign *Design = new CShipDesign();
		build_design(F["design"], Design);

		if (aFleetE)   // E = roll*(dice+1)/2 of the fleet's homogeneous weapon
		{
			double E = 0.0;
			const JValue &W = F["design"]["weapons"];
			if (W.size() > 0)
			{
				CWeapon *Wp = (CWeapon *)COMPONENT_TABLE->get_by_id(W[(size_t)0]["id"].as_int(0));
				if (Wp) E = (double)Wp->get_damage_roll() * (Wp->get_damage_dice() + 1) / 2.0;
			}
			(*aFleetE)[fid] = E;
		}

		bool isCap = F["capital"].as_bool(false);

		// Build the commander, then overwrite the constructor's randomized base
		// skills with the exact resolved stats from the spec (the orchestrator's
		// commander point-model produces these values; defaults = "good commander"
		// baseline). Racial-ability effects still layer on via the getters.
		const JValue &A = F["admiral"];
		CAdmiral *Adm = new CAdmiral(20, 5, 0, Race);
		Adm->set_owner(Player->get_game_id());
		int siege = A["siege"].as_int(13);
		Adm->sim_set_skill(CAdmiral::OFFENSE, siege);
		Adm->sim_set_skill(CAdmiral::DEFENSE, siege);
		Adm->sim_set_skill(CAdmiral::DETECTION, A["detection"].as_int(11));
		Adm->sim_set_skill(CAdmiral::MANEUVER,  A["maneuver"].as_int(11));
		Adm->sim_set_efficiency(A["efficiency"].as_int(100));
		Adm->sim_set_fleet_commanding(A["fleet_commanding"].as_int(37));
		if (A.has("racial"))  Adm->sim_set_racial_ability(A["racial"].as_int(0));
		if (A.has("special")) Adm->sim_set_special_ability(A["special"].as_int(0));
		// Capital commander's armada class is broadcast to every fleet; default
		// AC_A (best) for the capital, none for the rest.
		if (A.has("armada"))  Adm->sim_set_armada_commanding(A["armada"].as_int(CAdmiral::AC_A));
		else if (isCap)       Adm->sim_set_armada_commanding(CAdmiral::AC_A);
		Player->get_admiral_list()->add_admiral(Adm);

		make_fleet(Player, fid, "F", Design, Adm->get_id(), F["ships"].as_int(1));

		int cmd = F["command"].as_int(CDefenseFleet::COMMAND_FREE);
		int x   = F["x"].as_int(aGameID == 1 ? 4500 : 5500);
		int y   = F["y"].as_int(5000);
		if (isCap && !capitalSet) { aPlan->set_capital(fid); capitalSet = true; }
		add_deploy(aPlan, Player, fid, cmd, x, y);
	}
	if (!capitalSet && Fleets.size() > 0)
		aPlan->set_capital(Fleets[(size_t)0]["id"].as_int(aGameID * 100 + 1));
	return Player;
}

// PP lost by a side = sum over its battle fleets of destroyed_ships * hull_cost.
static long side_pp_lost(CBattleFleetList &aList)
{
	long pp = 0;
	for (int i = 0; i < aList.length(); i++)
	{
		CBattleFleet *BF = (CBattleFleet *)aList.get(i);
		int killed = BF->get_max_ship() - BF->count_active_ship();
		if (killed < 0) killed = 0;
		CShipSize *S = (CShipSize *)SHIP_SIZE_TABLE->get_by_id(BF->get_body());
		long cost = S ? S->get_cost() : 0;
		pp += (long)killed * cost;
	}
	return pp;
}

struct FleetDmg { int id; int owner; long dealt; long taken; long avoided; };
struct RepResult {
	int win; int turns; long pp_atk_lost; long pp_atk_dest;
	std::vector<FleetDmg> fleets;   // per-fleet damage (both sides) for this battle
};

// Sum per-fleet damage from the engine's OWN battle record (the F+H lines it already
// accumulates). Each fire writes F (attacker/defender ids + shot count) immediately
// followed by H (actual damage applied), so we pair them by adjacency:
//   dealt[attacker]   += applied
//   taken[defender]   += applied
//   avoided[defender] += max(0, count*E[attacker] - applied)   // evaded + reduced
// raw incoming (count*E) minus what landed = the damage the defender prevented.
static void parse_fleet_damage(const char *aBuf, const std::map<int, double> &aFleetE,
							   RepResult &aOut)
{
	if (!aBuf) return;
	std::map<int, long> dealt, taken, avoided;
	std::map<int, int>  owner;     // fleet id -> side game-id (1 attacker / 2 defender)
	int pend_atk = -1, pend_def = -1; long pend_count = 0; bool have_F = false;

	for (const char *p = aBuf; *p; )
	{
		const char *eol = strchr(p, '\n');
		size_t len = eol ? (size_t)(eol - p) : strlen(p);
		if (len >= 2 && p[0] == 'F' && p[1] == '/')
		{
			int fid, turn, ao, ai, dood, di;
			if (sscanf(p, "F/%d/%d/%d/%d/%d/%d/", &fid, &turn, &ao, &ai, &dood, &di) == 6)
			{
				const char *e = p + len, *last = NULL, *prev = NULL;
				for (const char *q = p; q < e; q++)        // count = 2nd-to-last field
					if (*q == '/') { prev = last; last = q; }
				pend_count = (prev && last) ? atol(prev + 1) : 0;
				pend_atk = ai; pend_def = di; have_F = true;
				owner[ai] = ao; owner[di] = dood;
			}
			else have_F = false;
		}
		else if (len >= 2 && p[0] == 'H' && p[1] == '/' && have_F)
		{
			int fid, turn, hit, miss, totdmg, sunk;
			if (sscanf(p, "H/%d/%d/%d/%d/%d/%d/", &fid, &turn, &hit, &miss, &totdmg, &sunk) == 6)
			{
				dealt[pend_atk] += totdmg;
				taken[pend_def] += totdmg;
				std::map<int, double>::const_iterator it = aFleetE.find(pend_atk);
				long raw = (long)(pend_count * (it != aFleetE.end() ? it->second : 0.0) + 0.5);
				if (raw > totdmg) avoided[pend_def] += raw - totdmg;
			}
			have_F = false;
		}
		if (!eol) break;
		p = eol + 1;
	}

	for (std::map<int, int>::iterator oi = owner.begin(); oi != owner.end(); ++oi)
	{
		FleetDmg fd;
		fd.id = oi->first; fd.owner = oi->second;
		fd.dealt   = dealt.count(fd.id)   ? dealt[fd.id]   : 0;
		fd.taken   = taken.count(fd.id)   ? taken[fd.id]   : 0;
		fd.avoided = avoided.count(fd.id) ? avoided[fd.id] : 0;
		aOut.fleets.push_back(fd);
	}
}

// Run ONE battle (called inside a forked child). Returns false if it couldn't deploy.
static bool run_one_match(const JValue &aReq, unsigned long aSeed, RepResult &aOut)
{
	seed_rng(aSeed);
	int turn_cap = aReq["turn_cap"].as_int(1800);

	std::map<int, double> fleetE;
	CDefensePlan Offense, Defense;
	CPlayer *Atk = build_side(aReq["attacker"], 1, &Offense, &fleetE);
	CPlayer *Def = build_side(aReq["defender"], 2, &Defense, &fleetE);

	CPlanet Planet; Planet.set_id(1); Planet.set_name("P");

	CBattle Battle(CBattle::WAR_SIEGE, Atk, Def, (void *)&Planet);
	if (!Battle.init_battle_fleet(&Offense, Atk->get_fleet_list(), Atk->get_admiral_list(),
								  &Defense, Def->get_fleet_list(), Def->get_admiral_list()))
		return false;

	int steps = 0;
	while (Battle.run_step()) { if (++steps >= turn_cap) break; }

	aOut.win   = Battle.attacker_win() ? 1 : 0;
	aOut.turns = Battle.get_record()->get_turn();
	aOut.pp_atk_lost = side_pp_lost(Battle.get_offense_battle_fleet_list());
	aOut.pp_atk_dest = side_pp_lost(Battle.get_defense_battle_fleet_list());
	parse_fleet_damage(Battle.get_record()->get_buffer(), fleetE, aOut);
	return true;
}

// SplitMix64-style derivation so replicate seeds aren't linearly correlated.
static unsigned long mix_seed(unsigned long aBase, int aK)
{
	unsigned long x = aBase + 0x9E3779B97F4A7C15UL * (unsigned long)(aK + 1);
	x ^= x >> 30; x *= 0xBF58476D1CE4E5B9UL;
	x ^= x >> 27; x *= 0x94D049BB133111EBUL;
	x ^= x >> 31;
	return x;
}

static double wilson_bound(int k, int n, int sign)
{
	if (n == 0) return 0.0;
	double z = 1.96, phat = (double)k / n;
	double denom  = 1.0 + z * z / n;
	double center = phat + z * z / (2.0 * n);
	double margin = z * sqrt(phat * (1.0 - phat) / n + z * z / (4.0 * (double)n * n));
	double r = (center + sign * margin) / denom;
	if (r < 0.0) r = 0.0;
	if (r > 1.0) r = 1.0;
	return r;
}

struct MatchChild { pid_t pid; int fd; };

// Fork one battle into a COW child; the child writes one line and exits:
//   OK <win> <turns> <ppl> <ppd> A <natk> <id>:<dealt>:<taken>:<avoided> ... D <ndef> ...
// (or "ERR"). The per-fleet tail is small (<~2 KB for 40 fleets), well under the pipe
// buffer, so the child never blocks before exiting. Returns false if fork/pipe failed.
static bool spawn_match(const JValue &aReq, unsigned long aSeed, MatchChild &aOut)
{
	int fds[2];
	if (pipe(fds) != 0) return false;
	pid_t pid = fork();
	if (pid == 0)
	{
		close(fds[0]);
		RepResult r;
		if (run_one_match(aReq, aSeed, r))
		{
			std::ostringstream as, ds; int na = 0, nd = 0;
			for (size_t i = 0; i < r.fleets.size(); i++)
			{
				const FleetDmg &fd = r.fleets[i];
				std::ostringstream &t = (fd.owner == 1) ? as : ds;
				((fd.owner == 1) ? na : nd)++;
				t << " " << fd.id << ":" << fd.dealt << ":" << fd.taken << ":" << fd.avoided;
			}
			std::ostringstream os;
			os << "OK " << r.win << " " << r.turns << " " << r.pp_atk_lost << " " << r.pp_atk_dest
			   << " A " << na << as.str() << " D " << nd << ds.str() << "\n";
			std::string out = os.str();
			const char *q = out.c_str(); size_t n = out.size();
			while (n) { ssize_t w = write(fds[1], q, n); if (w <= 0) break; q += (size_t)w; n -= (size_t)w; }
		}
		else
			dprintf(fds[1], "ERR\n");
		close(fds[1]);
		_exit(0);
	}
	if (pid < 0) { close(fds[0]); close(fds[1]); return false; }
	close(fds[1]);
	aOut.pid = pid;
	aOut.fd = fds[0];
	return true;
}

// Accumulate one child's per-fleet tail ("... A <n> id:d:t:a ... D <n> ...") into the
// parent's running maps (summed across replicates; averaged later over `completed`).
static void parse_match_fleets(const std::string &aLine,
							   std::map<int, long> &aDealt, std::map<int, long> &aTaken,
							   std::map<int, long> &aAvoided, std::map<int, int> &aOwner)
{
	const char *s = aLine.c_str();
	for (int pass = 0; pass < 2; pass++)
	{
		char mk = pass == 0 ? 'A' : 'D'; int side = pass == 0 ? 1 : 2;
		const char *m = NULL;
		for (const char *q = s; q[0] && q[1] && q[2]; q++)
			if (q[0] == ' ' && q[1] == mk && q[2] == ' ') { m = q + 3; break; }
		if (!m) continue;
		int n = atoi(m);
		while (*m && *m != ' ') m++;        // skip the count
		for (int k = 0; k < n; k++)
		{
			while (*m == ' ') m++;
			if (!*m) break;
			int id; long d, t, a;
			if (sscanf(m, "%d:%ld:%ld:%ld", &id, &d, &t, &a) == 4)
			{
				aDealt[id] += d; aTaken[id] += t; aAvoided[id] += a; aOwner[id] = side;
			}
			while (*m && *m != ' ') m++;     // advance to next tuple
		}
	}
}

// Run N replicates, each in a forked COW child (crash isolation + no global-state
// accumulation in the long-lived parent), concurrently across CPU cores, and
// aggregate into a MatchResult.
static void do_match(const JValue &aReq)
{
	int N = aReq["replicates"].as_int(20);
	if (N < 1) N = 1;
	unsigned long base = (unsigned long)aReq["seed"].as_int(12345);
	int turn_cap = aReq["turn_cap"].as_int(1800);

	int wins = 0, completed = 0, crashes = 0, cap_hits = 0;
	long sum_turns = 0;
	long long pp_lost_sum = 0, pp_dest_sum = 0;
	std::map<int, long> g_dealt, g_taken, g_avoided;   // per-fleet damage, summed
	std::map<int, int>  g_owner;                        // fleet id -> side (1/2)

	// Run replicates concurrently across cores, each in its own COW child. The
	// aggregate is order-independent (sums; each replicate's seed = mix_seed(base,k)),
	// so parallelism yields the exact same MatchResult — give Docker/WSL more cores
	// to go faster. Concurrency is capped at the CPU count.
	long ncpu = sysconf(_SC_NPROCESSORS_ONLN);
	int conc = (ncpu > 0) ? (int)ncpu : 1;
	// Orchestrator hint: when many cells run in parallel across worker processes,
	// it sends conc=1 so this worker runs its replicates one-at-a-time (avoids
	// workers x replicates oversubscription). Default 0 -> use all cores.
	int conc_req = aReq["conc"].as_int(0);
	if (conc_req > 0 && conc_req < conc) conc = conc_req;
	if (conc > N) conc = N;

	std::vector<MatchChild> active;
	int next = 0;
	while (next < N && (int)active.size() < conc)
	{
		MatchChild c;
		if (spawn_match(aReq, mix_seed(base, next), c)) active.push_back(c);
		else crashes++;
		next++;
	}

	while (!active.empty())
	{
		pid_t done = waitpid(-1, NULL, 0);
		if (done <= 0)   // unexpected; drain so we don't spin
		{
			for (size_t i = 0; i < active.size(); i++) { close(active[i].fd); crashes++; }
			active.clear();
			break;
		}

		int idx = -1;
		for (size_t i = 0; i < active.size(); i++)
			if (active[i].pid == done) { idx = (int)i; break; }
		if (idx < 0) continue;   // not one of our children

		std::string acc; char rb[8192]; ssize_t rn;
		while ((rn = read(active[idx].fd, rb, sizeof rb)) > 0) acc.append(rb, (size_t)rn);
		close(active[idx].fd);
		active.erase(active.begin() + idx);

		int win, turns; long ppl, ppd;
		if (acc.size() >= 2 && acc[0] == 'O' &&
			sscanf(acc.c_str(), "OK %d %d %ld %ld", &win, &turns, &ppl, &ppd) == 4)
		{
			wins += win; completed++; sum_turns += turns;
			pp_lost_sum += ppl; pp_dest_sum += ppd;
			if (turns >= turn_cap) cap_hits++;
			parse_match_fleets(acc, g_dealt, g_taken, g_avoided, g_owner);
		}
		else crashes++;   // child died (signal) or failed to deploy

		if (next < N)   // refill the freed slot
		{
			MatchChild c;
			if (spawn_match(aReq, mix_seed(base, next), c)) active.push_back(c);
			else crashes++;
			next++;
		}
	}

	double wr   = completed ? (double)wins / completed : 0.0;
	double lo   = wilson_bound(wins, completed, -1);
	double hi   = wilson_bound(wins, completed, +1);
	double appl = completed ? (double)pp_lost_sum / completed : 0.0;
	double appd = completed ? (double)pp_dest_sum / completed : 0.0;

	// per-fleet damage averaged over completed replicates, grouped by side
	std::ostringstream af, df; bool aff = true, dff = true;
	for (std::map<int, int>::iterator gi = g_owner.begin(); gi != g_owner.end(); ++gi)
	{
		int id = gi->first;
		std::ostringstream &t = (gi->second == 1) ? af : df;
		bool &ff = (gi->second == 1) ? aff : dff;
		if (!ff) t << ","; ff = false;
		t << "{\"id\":" << id
		  << ",\"dealt\":"   << (completed ? (double)g_dealt[id]   / completed : 0.0)
		  << ",\"taken\":"   << (completed ? (double)g_taken[id]   / completed : 0.0)
		  << ",\"avoided\":" << (completed ? (double)g_avoided[id] / completed : 0.0) << "}";
	}

	std::ostringstream o;
	o << "{\"ok\":true,\"cmd\":\"match\",\"seed\":" << base
	  << ",\"replicates\":" << N << ",\"completed\":" << completed
	  << ",\"attacker_wins\":" << wins
	  << ",\"win_rate\":" << wr
	  << ",\"wilson_lo\":" << lo << ",\"wilson_hi\":" << hi
	  << ",\"avg_turns\":" << (completed ? (double)sum_turns / completed : 0.0)
	  << ",\"cap_hits\":" << cap_hits << ",\"crashes\":" << crashes
	  << ",\"attacker_pp_lost\":" << appl
	  << ",\"attacker_pp_destroyed\":" << appd
	  << ",\"econ\":" << (appd - appl)
	  << ",\"fleets\":{\"attacker\":[" << af.str() << "],\"defender\":[" << df.str() << "]}}";
	emit(o.str());
}

// =====================  replay (single battle -> turn log)  =================
//
// Reproduces ONE deterministic battle (replicate k of a matchup) and returns the
// engine's OWN turn-by-turn log: the exact FL/M/F/H/D/ENDTURN text — positions
// AND weapon fire/hit lines — that the in-game viewer (battle-replay.js) renders.
// The engine already accumulates this during run_step(); we read it back through
// a read-only accessor (CBattleRecord::get_buffer) and never call save()/the DB,
// so no game-engine behaviour changes.

static std::string build_replay_json(const JValue &aReq)
{
	int turn_cap     = aReq["turn_cap"].as_int(1800);
	unsigned long base = (unsigned long)aReq["seed"].as_int(12345);
	int k            = aReq["replicate"].as_int(0);
	seed_rng(mix_seed(base, k));   // same seed the payoff matrix used for cell k

	CDefensePlan Offense, Defense;
	CPlayer *Atk = build_side(aReq["attacker"], 1, &Offense);
	CPlayer *Def = build_side(aReq["defender"], 2, &Defense);

	CPlanet Planet; Planet.set_id(1); Planet.set_name("P");
	CBattle Battle(CBattle::WAR_SIEGE, Atk, Def, (void *)&Planet);
	if (!Battle.init_battle_fleet(&Offense, Atk->get_fleet_list(), Atk->get_admiral_list(),
								  &Defense, Def->get_fleet_list(), Def->get_admiral_list()))
		return std::string("{\"ok\":false,\"error\":\"replay deploy failed\"}");

	int steps = 0;
	while (Battle.run_step()) { if (++steps >= turn_cap) break; }

	int win   = Battle.attacker_win() ? 1 : 0;
	int turns = Battle.get_record()->get_turn();
	const char *log = Battle.get_record()->get_buffer();   // FL/M/F/H/D/ENDTURN

	std::ostringstream o;
	o << "{\"ok\":true,\"cmd\":\"replay\",\"seed\":" << base
	  << ",\"replicate\":" << k << ",\"win\":" << win
	  << ",\"turns\":" << turns << ",\"log\":\"";
	json_append_escaped(o, log ? log : "");
	o << "\"}";
	return o.str();
}

// Run the replay in a forked child (crash isolation, like do_match) and stream
// the (possibly large) JSON line back through a pipe.
static void do_replay(const JValue &aReq)
{
	int fds[2];
	if (pipe(fds) != 0) { emit_error("replay pipe"); return; }

	pid_t pid = fork();
	if (pid == 0)
	{
		close(fds[0]);
		std::string out = build_replay_json(aReq);
		const char *p = out.c_str(); size_t n = out.size();
		while (n) { ssize_t w = write(fds[1], p, n); if (w <= 0) break; p += (size_t)w; n -= (size_t)w; }
		close(fds[1]); _exit(0);
	}
	close(fds[1]);

	std::string acc; char buf[8192]; ssize_t r;
	while ((r = read(fds[0], buf, sizeof buf)) > 0) acc.append(buf, (size_t)r);
	close(fds[0]);
	if (pid > 0) waitpid(pid, NULL, 0);

	if (acc.empty()) emit_error("replay crashed");
	else             emit(acc);
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
		else if (cmd == "match") do_match(req);
		else if (cmd == "replay") do_replay(req);
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
