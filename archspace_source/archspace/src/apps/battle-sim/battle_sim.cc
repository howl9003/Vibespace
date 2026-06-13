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
		if (cat == CComponent::CC_WEAPON)   // weapon space -> how many fit per slot
			comp[cat] << ",\"space\":" << ((CWeapon *)Comp)->get_space();
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
static CPlayer *build_side(const JValue &aSide, int aGameID, CDefensePlan *aPlan)
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

		bool isCap = F["capital"].as_bool(false);

		// Build the commander, then overwrite the constructor's randomized base
		// skills with the exact resolved stats from the spec (the orchestrator's
		// commander point-model produces these values; defaults = "good commander"
		// baseline). Racial-ability effects still layer on via the getters.
		const JValue &A = F["admiral"];
		CAdmiral *Adm = new CAdmiral(20, 5, 0, Race);
		Adm->set_owner(Player->get_game_id());
		int siege = A["siege"].as_int(13);
		Adm->sim_set_skill(CAdmiral::SIEGE_PLANET,    siege);
		Adm->sim_set_skill(CAdmiral::SIEGE_REPELLING, siege);
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

struct RepResult { int win; int turns; long pp_atk_lost; long pp_atk_dest; };

// Run ONE battle (called inside a forked child). Returns false if it couldn't deploy.
static bool run_one_match(const JValue &aReq, unsigned long aSeed, RepResult &aOut)
{
	seed_rng(aSeed);
	int turn_cap = aReq["turn_cap"].as_int(1800);

	CDefensePlan Offense, Defense;
	CPlayer *Atk = build_side(aReq["attacker"], 1, &Offense);
	CPlayer *Def = build_side(aReq["defender"], 2, &Defense);

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

// Fork one battle into a COW child; the child writes "OK win turns ppl ppd" (or
// "ERR") to the pipe and exits. Returns false if fork/pipe failed (no slot).
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
			dprintf(fds[1], "OK %d %d %ld %ld\n", r.win, r.turns, r.pp_atk_lost, r.pp_atk_dest);
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

	// Run replicates concurrently across cores, each in its own COW child. The
	// aggregate is order-independent (sums; each replicate's seed = mix_seed(base,k)),
	// so parallelism yields the exact same MatchResult — give Docker/WSL more cores
	// to go faster. Concurrency is capped at the CPU count.
	long ncpu = sysconf(_SC_NPROCESSORS_ONLN);
	int conc = (ncpu > 0) ? (int)ncpu : 1;
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

		char buf[160];
		ssize_t n = read(active[idx].fd, buf, sizeof(buf) - 1);
		close(active[idx].fd);
		active.erase(active.begin() + idx);

		int win, turns; long ppl, ppd;
		if (n > 0 && buf[0] == 'O' && (buf[n] = 0, sscanf(buf, "OK %d %d %ld %ld", &win, &turns, &ppl, &ppd) == 4))
		{
			wins += win; completed++; sum_turns += turns;
			pp_lost_sum += ppl; pp_dest_sum += ppd;
			if (turns >= turn_cap) cap_hits++;
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
	  << ",\"econ\":" << (appd - appl) << "}";
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
