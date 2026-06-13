"""CLI runner: load a scenario, run assess|stackelberg, write report + run_state.

    python3 run.py scenarios/siege_symmetric.yaml --out runs/symmetric
"""

from __future__ import annotations

import argparse
import json
import os
import random
import time

from evaluator import BattleSim
from pool import Pool
from workers import MatchPool
import genome as G
import tournament as T
import search as S
import analysis as A
import report as R
import scenario as SC


def _sample(pool, side, con, rng):
    lo = G.sample_loadout(pool, side, con.race, con.tech_cap, con.n_fleets,
                          con.pp_budget, con.max_ships, rng)
    return G.repair(lo, pool, side, con.tech_cap, con.pp_budget, con.max_ships,
                    rng, con.n_fleets)


def _winrates(M):
    return [[c.win_rate for c in row] for row in M]


def _crashes(M):
    return sum(c.raw["crashes"] for row in M for c in row)


def _pp(v: float) -> str:
    """Compact PP formatting: 1.2M / 340K / 850."""
    v = float(v)
    if v >= 1e6:
        return f"{v / 1e6:.1f}M"
    if v >= 1e3:
        return f"{v / 1e3:.0f}K"
    return f"{v:.0f}"


def _verdict(value: float, kind: str) -> str:
    if value > 0.55:
        who = f"**siege favors the ATTACKER** (equilibrium attacker win-rate {value:.2f})"
    elif value < 0.45:
        who = f"**siege favors the DEFENDER** (equilibrium attacker win-rate {value:.2f})"
    else:
        who = f"**siege is BALANCED** (equilibrium attacker win-rate {value:.2f})"
    return f"Under optimized play the meta-game is **{kind}**; {who}."


_START = None


def _write_state(outdir, state):
    os.makedirs(outdir, exist_ok=True)
    state = dict(state)
    state["ts"] = time.time()
    if _START is not None:
        state["elapsed"] = round(time.time() - _START, 1)
    with open(os.path.join(outdir, "run_state.json"), "w") as f:
        json.dump(state, f, indent=2)


def _scenario_dict(sc: SC.Scenario) -> dict:
    def side(s):
        return {"race": s.race, "tech_cap": s.tech_cap, "pp_budget": s.pp_budget,
                "n_fleets": s.n_fleets, "max_ships": s.max_ships}
    return {"name": sc.name, "attacker": side(sc.attacker), "defender": side(sc.defender),
            "base_seed": sc.base_seed, "turn_cap": sc.turn_cap, "replicates": sc.replicates}


def run_assess(sim, pool, sc, rng, outdir, mpool=None):
    atk, dfn = sc.constraints("attacker"), sc.constraints("defender")
    _write_state(outdir, {"phase": "sampling", "mode": "assess"})
    A_pop = [_sample(pool, "attacker", atk, rng) for _ in range(sc.population)]
    D_pop = [_sample(pool, "defender", dfn, rng) for _ in range(sc.population)]

    dec_atk = [R.decode_loadout(a, pool, atk.tech_cap) for a in A_pop]
    dec_dfn = [R.decode_loadout(d, pool, dfn.tech_cap) for d in D_pop]
    _write_state(outdir, {"phase": "evaluating matrix", "mode": "assess",
                          "n": sc.population,
                          "configs": {"attackers": dec_atk, "defenders": dec_dfn}})
    M = T.payoff_matrix(sim, pool, A_pop, D_pop, atk.tech_cap,
                        base_seed=sc.base_seed, replicates=sc.replicates,
                        turn_cap=sc.turn_cap, mpool=mpool)
    W = _winrates(M)
    an = A.classify(W)

    return {
        "scenario": _scenario_dict(sc), "mode": "assess",
        "verdict": _verdict(an["value"], an["kind"]),
        "analysis": an, "matrix": W,
        "loadouts": {"attackers": dec_atk, "defenders": dec_dfn,
                     "attackers_encoded": [G.encode_side(a, pool, atk.tech_cap, 100) for a in A_pop],
                     "defenders_encoded": [G.encode_side(d, pool, dfn.tech_cap, 200) for d in D_pop]},
        "anomalies": {"crashes": _crashes(M)},
    }


def run_stackelberg(sim, pool, sc, rng, outdir, mpool=None):
    atk, dfn = sc.constraints("attacker"), sc.constraints("defender")
    fixed_def = _sample(pool, "defender", dfn, rng)

    history_acc = []
    leaders_acc = {}

    def log(*a):
        msg = " ".join(str(x) for x in a)
        print(msg)
        _write_state(outdir, {"phase": "search", "mode": "stackelberg",
                              "log": msg, "history": history_acc, "leaders": leaders_acc})

    def on_progress(history, lib_size, robust_def=None, a_best=None, attacker_lib=None):
        leaders = {}
        if robust_def is not None:
            leaders["robust_defender"] = R.decode_loadout(robust_def, pool, dfn.tech_cap)
        if a_best is not None:
            leaders["best_exploit"] = R.decode_loadout(a_best, pool, atk.tech_cap)
        if attacker_lib is not None:
            leaders["attacker_library"] = [R.decode_loadout(a, pool, atk.tech_cap)
                                           for a in attacker_lib]
        history_acc[:] = list(history)
        leaders_acc.clear()
        leaders_acc.update(leaders)
        _write_state(outdir, {"phase": "search", "mode": "stackelberg",
                              "scenario": sc.name, "history": history_acc,
                              "library_size": lib_size, "leaders": leaders_acc})

    def on_gen(rnd, side, gen, total, best_fit, net_pp=0.0):
        # per-generation heartbeat: where the oracle is in the cycle, the best
        # win-rate, and the best loadout's net PP (econ = PP killed - PP lost), the
        # secondary objective the search maximises.
        net = f"net PP {'+' if net_pp >= 0 else '-'}{_pp(abs(net_pp))}"
        where = ("stage 1 · seed attacker" if side == "seed"
                 else f"round {rnd} · {side} oracle")
        stage = f"{where} · gen {gen}/{total} · best {best_fit:.3f} · {net}"
        _write_state(outdir, {"phase": "search", "mode": "stackelberg",
                              "scenario": sc.name, "history": history_acc,
                              "leaders": leaders_acc, "stage": stage,
                              "gen": gen, "gen_total": total,
                              "best_fit": round(best_fit, 4), "round": rnd, "oracle": side,
                              "net_pp": round(net_pp)})

    out = S.stackelberg(sim, pool, atk, dfn, fixed_def,
                        rounds=sc.rounds, epsilon=sc.epsilon, mu=sc.mu, lam=sc.lam,
                        gens=sc.generations, replicates=sc.replicates,
                        base_seed=sc.base_seed, rng=rng, log=log,
                        on_progress=on_progress, on_gen=on_gen, mpool=mpool)
    history_acc[:] = out["history"]

    # Final meta-game: attacker library vs {fixed, robust} defenders.
    robust = out["robust_defender"]
    defenders = [fixed_def, robust]
    M = T.payoff_matrix(sim, pool, out["attacker_library"], defenders, atk.tech_cap,
                        base_seed=sc.base_seed, replicates=sc.replicates,
                        turn_cap=sc.turn_cap, mpool=mpool)
    W = _winrates(M)
    an = A.classify(W)
    best_exploit = out["history"][-1]["best_exploit_atkwin"] if out["history"] else an["value"]

    verdict = (f"The least-exploitable defender still loses **{best_exploit:.2f}** to the "
               f"best attacker exploit. " + _verdict(an["value"], an["kind"]))

    return {
        "scenario": _scenario_dict(sc), "mode": "stackelberg",
        "verdict": verdict, "analysis": an, "matrix": W, "history": out["history"],
        "loadouts": {
            "robust_defender": R.decode_loadout(robust, pool, dfn.tech_cap),
            "robust_defender_encoded": G.encode_side(robust, pool, dfn.tech_cap, 200),
            "attackers": [R.decode_loadout(a, pool, atk.tech_cap) for a in out["attacker_library"]],
            "attackers_encoded": [G.encode_side(a, pool, atk.tech_cap, 100) for a in out["attacker_library"]],
            "defenders": [R.decode_loadout(d, pool, dfn.tech_cap) for d in defenders],
            "defenders_encoded": [G.encode_side(d, pool, dfn.tech_cap, 200) for d in defenders],
        },
        "anomalies": {"crashes": _crashes(M)},
    }


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("scenario")
    ap.add_argument("--out", default="runs/latest")
    ap.add_argument("--mode", choices=["assess", "stackelberg"], default=None)
    args = ap.parse_args()

    sc = SC.load(args.scenario)
    mode = args.mode or sc.mode
    rng = random.Random(sc.base_seed)

    global _START
    _START = t0 = time.time()
    # One worker owns the Pool cache + genetic ops (single-threaded); the MatchPool
    # of extra workers evaluates many cells/candidates concurrently (cell-level
    # parallelism). Determinism is preserved — only evaluation is parallelized.
    with BattleSim() as sim, MatchPool() as mpool:
        pool = Pool(sim)
        if mode == "assess":
            result = run_assess(sim, pool, sc, rng, args.out, mpool)
        else:
            result = run_stackelberg(sim, pool, sc, rng, args.out, mpool)

    result["wall_seconds"] = round(time.time() - t0, 1)
    R.write_report(result, args.out)
    _write_state(args.out, {"phase": "done", "mode": mode,
                            "wall_seconds": result["wall_seconds"]})
    print(f"\n{result['verdict']}")
    print(f"report -> {os.path.join(args.out, 'report.md')}  ({result['wall_seconds']}s)")


if __name__ == "__main__":
    main()
