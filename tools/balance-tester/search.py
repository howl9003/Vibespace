"""Stackelberg search: the (mu+lambda) best-response oracle + staged double-oracle.

INNER  best_response(): evolve a loadout for one side that maximizes its
       lexicographic payoff against a fixed opponent (or a gauntlet). Warm-started
       from a seed population; keeps the best-EXPECTED loadout (mean over CRN
       replicates), not a lucky roll.
OUTER  stackelberg(): (1) attacker best-response vs the fixed human defender seeds
       the attacker library; then alternate a maximin-robust defender oracle (best
       worst-case over the attacker gauntlet) with a fresh attacker best-response,
       until the best new attacker can't beat the robust defender by > epsilon.
"""

from __future__ import annotations

import copy
import random
from dataclasses import dataclass
from typing import Callable, List, Optional, Tuple

import genome as G
import pool as P
import tournament as T


@dataclass
class Constraints:
    race: int
    tech_cap: int
    pp_budget: Optional[int]
    n_fleets: int
    max_ships: int


Fitness = Tuple[float, float]   # lexicographic (primary, secondary)


def fitness(sim, pool: P.Pool, cand: G.Loadout, opponents: List[G.Loadout],
            side: str, tech_cap: int, base_seed: int, replicates: int,
            conc: Optional[int] = None) -> Fitness:
    """Lexicographic fitness of `cand` (on `side`) vs the opponent set.

    Attacker: MEAN (win-rate, econ) over the opponent field (exploit the field).
    Defender: MAXIMIN - the WORST (min) (def-win, -econ) over the attacker gauntlet.
    """
    if not opponents:
        return (0.0, 0.0)
    vals: List[Fitness] = []
    for opp in opponents:
        if side == "attacker":
            c = T.evaluate_cell(sim, pool, cand, opp, tech_cap, base_seed, replicates, conc=conc)
            vals.append((c.win_rate, c.econ))
        else:
            c = T.evaluate_cell(sim, pool, opp, cand, tech_cap, base_seed, replicates, conc=conc)
            vals.append((1.0 - c.win_rate, -c.econ))
    if side == "attacker":
        n = len(vals)
        return (sum(v[0] for v in vals) / n, sum(v[1] for v in vals) / n)
    return min(vals)   # maximin worst-case for the defender


def _seed_population(pool, side, con: Constraints, mu: int,
                     seed_pop: List[G.Loadout], rng) -> List[G.Loadout]:
    pop = [copy.deepcopy(lo) for lo in (seed_pop or [])[:mu]]
    while len(pop) < mu:
        lo = G.sample_loadout(pool, side, con.race, con.tech_cap, con.n_fleets,
                              con.pp_budget, con.max_ships, rng)
        pop.append(G.repair(lo, pool, side, con.tech_cap, con.pp_budget,
                            con.max_ships, rng, con.n_fleets))
    return pop


def best_response(sim, pool: P.Pool, opponents: List[G.Loadout], side: str,
                  con: Constraints, mu: int = 8, lam: int = 16,
                  generations: int = 12, replicates: int = 20, base_seed: int = 999,
                  seed_pop: Optional[List[G.Loadout]] = None, patience: int = 4,
                  rng: Optional[random.Random] = None,
                  log: Optional[Callable] = None, mpool=None,
                  cd_cycles: Optional[int] = None,
                  fleet_local: bool = True) -> Tuple[G.Loadout, Fitness]:
    """Coordinate-descent best response. Each cycle runs a LOADOUT phase then a POSITION
    phase, repeating until neither improves (or cd_cycles is hit). The LOADOUT phase does
    PER-FLEET local search: for each fleet, sample K single-fleet candidates and let that
    fleet pick DONE (maximize its damage dealt) or AVOIDED (maximize damage it prevented),
    keeping whichever IMPROVES the aggregate fitness — so the dense per-fleet signal guides
    component choice while aggregate (win, net-PP) stays the only acceptance test."""
    rng = rng or random.Random(0)
    conc_hint = 1 if mpool is not None else None
    base_id = 100 if side == "attacker" else 200

    def score_one(wsim, cand):
        """(aggregate fitness, {fleet_id: (dealt, taken, avoided)} mean over opponents)."""
        ids = [base_id + i for i in range(len(cand.fleets))]
        vals: List[Fitness] = []
        acc = {i: [0.0, 0.0, 0.0] for i in ids}
        for opp in opponents:
            if side == "attacker":
                c = T.evaluate_cell(wsim, pool, cand, opp, con.tech_cap, base_seed,
                                    replicates, conc=conc_hint)
                vals.append((c.win_rate, c.econ))
                fd = T.fleet_damage(c, "attacker", ids)
            else:
                c = T.evaluate_cell(wsim, pool, opp, cand, con.tech_cap, base_seed,
                                    replicates, conc=conc_hint)
                vals.append((1.0 - c.win_rate, -c.econ))
                fd = T.fleet_damage(c, "defender", ids)
            for fid in ids:
                d = fd.get(fid, (0.0, 0.0, 0.0))
                acc[fid][0] += d[0]; acc[fid][1] += d[1]; acc[fid][2] += d[2]
        if not vals:
            return ((0.0, 0.0), {})
        if side == "attacker":
            n = len(vals)
            fit = (sum(v[0] for v in vals) / n, sum(v[1] for v in vals) / n)
        else:
            fit = min(vals)                 # maximin worst-case for the defender
        nopp = max(1, len(opponents))
        dmg = {fid: (acc[fid][0] / nopp, acc[fid][1] / nopp, acc[fid][2] / nopp) for fid in ids}
        return (fit, dmg)

    _WORST = ((float("-inf"), float("-inf")), {})

    def ev_full(cands):
        """[(fitness, fleet_dmg), ...] — parallel across the pool when mpool is given."""
        cands = list(cands)
        if not cands:
            return []
        if mpool is None:
            return [score_one(sim, lo) for lo in cands]
        for lo in cands + list(opponents):     # warm the shared Pool cache single-threaded
            pool.get(lo.race, con.tech_cap)
        return [r if r is not None else _WORST for r in mpool.map(cands, score_one)]

    # ---- seed the incumbent (best-expected of the seeded population) ----
    pop0 = _seed_population(pool, side, con, mu, seed_pop, rng)
    s0 = ev_full(pop0)
    k0 = max(range(len(pop0)), key=lambda k: s0[k][0])
    incumbent, f_inc = pop0[k0], s0[k0][0]

    cd_cycles = cd_cycles if cd_cycles is not None else max(2, generations // 3)
    patience_cycles = max(1, patience // 2)
    block_gens = 2

    def block_cycle(group: str) -> bool:
        """μ+λ restricted to one gene group; accept on aggregate fitness. Returns improved."""
        nonlocal incumbent, f_inc
        pop = [incumbent] + [
            G.mutate(incumbent, pool, side, con.tech_cap, con.pp_budget, con.max_ships,
                     rng, n_fleets=con.n_fleets, genes=(group,))
            for _ in range(max(0, mu - 1))]
        scored = sorted(zip(pop, [s[0] for s in ev_full(pop)]),
                        key=lambda x: x[1], reverse=True)
        for _ in range(block_gens):
            off = [G.mutate(rng.choice(scored)[0], pool, side, con.tech_cap, con.pp_budget,
                            con.max_ships, rng, n_fleets=con.n_fleets, genes=(group,))
                   for _ in range(lam)]
            scored = sorted(scored + list(zip(off, [s[0] for s in ev_full(off)])),
                            key=lambda x: x[1], reverse=True)[:mu]
        if scored[0][1] > f_inc:
            incumbent, f_inc = scored[0]
            return True
        return False

    def loadout_cycle_fleetlocal() -> bool:
        """Per-fleet local search; each fleet picks DONE vs AVOIDED by whichever accepted
        candidate gives the better aggregate. Returns whether the incumbent improved."""
        nonlocal incumbent, f_inc
        improved = False
        K = max(4, lam // max(1, len(incumbent.fleets)))
        i = 0
        while i < len(incumbent.fleets):
            cands = [G.mutate_fleet(incumbent, i, "LOADOUT", pool, side, con.tech_cap,
                                    con.pp_budget, con.max_ships, rng, con.n_fleets)
                     for _ in range(K)]
            res = ev_full(cands)
            fid = base_id + i
            for axis in (0, 2):     # DONE (max dealt), then AVOIDED (max avoided)
                k = max(range(len(cands)),
                        key=lambda j: (res[j][0], res[j][1].get(fid, (0.0, 0.0, 0.0))[axis]))
                if res[k][0] > f_inc:
                    incumbent, f_inc = cands[k], res[k][0]
                    improved = True
                    break           # keep whichever objective gave the better aggregate
            i += 1
        return improved

    stale = 0
    for cycle in range(cd_cycles):
        imp_load = loadout_cycle_fleetlocal() if fleet_local else block_cycle("LOADOUT")
        imp_pos = block_cycle("POSITION")
        if log:                      # map cycle progress onto the 0..generations bar
            disp = max(0, round((cycle + 1) / cd_cycles * generations) - 1)
            log(disp, f_inc, f_inc)
        if imp_load or imp_pos:
            stale = 0
        else:
            stale += 1
            if stale >= patience_cycles:
                break

    return incumbent, f_inc


def stackelberg(sim, pool: P.Pool, atk_con: Constraints, def_con: Constraints,
                fixed_defender: G.Loadout, rounds: int = 4, epsilon: float = 0.05,
                mu: int = 8, lam: int = 16, gens: int = 10, replicates: int = 20,
                base_seed: int = 12345, rng: Optional[random.Random] = None,
                log: Callable = print,
                on_progress: Optional[Callable] = None,
                on_gen: Optional[Callable] = None, mpool=None) -> dict:
    rng = rng or random.Random(1)

    # per-generation hook: on_gen(round, side, gen, total, best_primary_fit, net_pp)
    # net_pp is the fitness SECONDARY (econ = PP killed - PP lost) the search maximises.
    def _gl(rnd: int, side: str):
        if not on_gen:
            return None
        return lambda g, cur, bf: on_gen(rnd, side, g + 1, gens, bf[0], bf[1])

    # Stage 1: attacker best-response vs the fixed human-set defender -> seed library.
    log("Stage 1: attacker best-response vs the fixed defender")
    a0, a0fit = best_response(sim, pool, [fixed_defender], "attacker", atk_con,
                              mu, lam, gens, replicates, base_seed, rng=rng,
                              log=_gl(0, "seed"), mpool=mpool)
    attacker_lib: List[G.Loadout] = [a0]
    log(f"  seed attacker beats the fixed defender at win-rate {a0fit[0]:.3f}")

    # Stage 4: alternate robust-defender oracle and a fresh attacker best-response.
    robust_def = fixed_defender
    defender_lib: List[G.Loadout] = []     # the robust defender found each round
    history = []
    prev_net = None
    for rnd in range(rounds):
        d_best, d_fit = best_response(sim, pool, attacker_lib, "defender", def_con,
                                      mu, lam, gens, replicates, base_seed,
                                      seed_pop=[robust_def], rng=rng,
                                      log=_gl(rnd, "defender"), mpool=mpool)
        robust_def = d_best
        defender_lib.append(d_best)
        a_best, a_fit = best_response(sim, pool, [robust_def], "attacker", atk_con,
                                      mu, lam, gens, replicates, base_seed,
                                      seed_pop=attacker_lib, rng=rng,
                                      log=_gl(rnd, "attacker"), mpool=mpool)
        attacker_lib.append(a_best)

        robust_worstcase_defwin = d_fit[0]     # defender's worst case over the gauntlet
        best_exploit_atkwin = a_fit[0]         # best new attacker's win vs robust defender
        exploit_net_pp = a_fit[1]              # and its net PP (econ = PP killed - lost)
        # Convergence is judged in net-PP terms, scaled by the combined army PP, so
        # epsilon is a fraction of "both armies' cost".
        total_pp = (G.pp_cost(a_best, pool, atk_con.tech_cap)
                    + G.pp_cost(robust_def, pool, def_con.tech_cap)) or 1
        threshold = epsilon * total_pp
        history.append({"round": rnd,
                        "robust_worstcase_defwin": robust_worstcase_defwin,
                        "best_exploit_atkwin": best_exploit_atkwin,
                        "exploit_net_pp": exploit_net_pp,
                        "net_pp_threshold": threshold,
                        "total_pp": total_pp,
                        "library_size": len(attacker_lib)})
        log(f"Round {rnd}: robust def-win={robust_worstcase_defwin:.3f}; exploit win="
            f"{best_exploit_atkwin:.3f}; exploit net PP={exploit_net_pp:.0f} "
            f"(threshold {threshold:.0f} = {epsilon:.0%} of {total_pp:.0f} PP)")
        if on_progress:
            on_progress(history, len(attacker_lib), robust_def, a_best,
                        attacker_lib, defender_lib)

        # Keep going while the attacker is still finding meaningfully better net-PP
        # exploits; stop only once the exploit's net PP stops moving round-over-round
        # (change <= epsilon x combined army PP).
        if prev_net is not None and abs(exploit_net_pp - prev_net) <= threshold:
            log("  converged (net-PP stable: round-over-round change <= threshold)")
            break
        prev_net = exploit_net_pp

    return {"robust_defender": robust_def, "attacker_library": attacker_lib,
            "defender_library": defender_lib, "history": history}
