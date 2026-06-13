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
        pop.append(G.repair(lo, pool, side, con.tech_cap, con.pp_budget, con.max_ships, rng))
    return pop


def best_response(sim, pool: P.Pool, opponents: List[G.Loadout], side: str,
                  con: Constraints, mu: int = 8, lam: int = 16,
                  generations: int = 12, replicates: int = 20, base_seed: int = 999,
                  seed_pop: Optional[List[G.Loadout]] = None, patience: int = 4,
                  rng: Optional[random.Random] = None,
                  log: Optional[Callable] = None, mpool=None) -> Tuple[G.Loadout, Fitness]:
    rng = rng or random.Random(0)

    def ev_many(cands):
        """Evaluate a list of candidates' fitness vs the opponents. With `mpool`,
        each candidate is scored on its own worker, in parallel — same results."""
        cands = list(cands)
        if mpool is None or not cands:
            return [fitness(sim, pool, lo, opponents, side, con.tech_cap,
                            base_seed, replicates) for lo in cands]
        # warm the shared Pool cache single-threaded so worker threads only read it
        for lo in cands + list(opponents):
            pool.get(lo.race, con.tech_cap)
        return mpool.map(cands, lambda wsim, lo: fitness(
            wsim, pool, lo, opponents, side, con.tech_cap, base_seed, replicates, conc=1))

    pop0 = _seed_population(pool, side, con, mu, seed_pop, rng)
    scored = list(zip(pop0, ev_many(pop0)))
    scored.sort(key=lambda x: x[1], reverse=True)
    best = scored[0]
    stale = 0

    for gen in range(generations):
        offspring = []
        for _ in range(lam):
            if rng.random() < 0.6 and len(scored) >= 2:
                pa, pb = rng.sample(scored, 2)
                child = G.crossover(pa[0], pb[0], pool, side, con.tech_cap,
                                    con.pp_budget, con.max_ships, rng)
            else:
                child = copy.deepcopy(rng.choice(scored)[0])
            child = G.mutate(child, pool, side, con.tech_cap, con.pp_budget,
                             con.max_ships, rng)
            offspring.append(child)

        scored = sorted(scored + list(zip(offspring, ev_many(offspring))),
                        key=lambda x: x[1], reverse=True)[:mu]
        if scored[0][1] > best[1]:
            best, stale = scored[0], 0
        else:
            stale += 1
        if log:
            log(gen, scored[0][1], best[1])
        if stale >= patience:
            break

    return best


def stackelberg(sim, pool: P.Pool, atk_con: Constraints, def_con: Constraints,
                fixed_defender: G.Loadout, rounds: int = 4, epsilon: float = 0.05,
                mu: int = 8, lam: int = 16, gens: int = 10, replicates: int = 20,
                base_seed: int = 12345, rng: Optional[random.Random] = None,
                log: Callable = print,
                on_progress: Optional[Callable] = None,
                on_gen: Optional[Callable] = None, mpool=None) -> dict:
    rng = rng or random.Random(1)

    # per-generation progress hook: on_gen(round, side, gen, total_gens, best_primary_fit)
    def _gl(rnd: int, side: str):
        if not on_gen:
            return None
        return lambda g, cur, best: on_gen(rnd, side, g + 1, gens, best[0])

    # Stage 1: attacker best-response vs the fixed human-set defender -> seed library.
    log("Stage 1: attacker best-response vs the fixed defender")
    a0, a0fit = best_response(sim, pool, [fixed_defender], "attacker", atk_con,
                              mu, lam, gens, replicates, base_seed, rng=rng,
                              log=_gl(0, "seed"), mpool=mpool)
    attacker_lib: List[G.Loadout] = [a0]
    log(f"  seed attacker beats the fixed defender at win-rate {a0fit[0]:.3f}")

    # Stage 4: alternate robust-defender oracle and a fresh attacker best-response.
    robust_def = fixed_defender
    history = []
    prev_exploit = None
    for rnd in range(rounds):
        d_best, d_fit = best_response(sim, pool, attacker_lib, "defender", def_con,
                                      mu, lam, gens, replicates, base_seed,
                                      seed_pop=[robust_def], rng=rng,
                                      log=_gl(rnd, "defender"), mpool=mpool)
        robust_def = d_best
        a_best, a_fit = best_response(sim, pool, [robust_def], "attacker", atk_con,
                                      mu, lam, gens, replicates, base_seed,
                                      seed_pop=attacker_lib, rng=rng,
                                      log=_gl(rnd, "attacker"), mpool=mpool)
        attacker_lib.append(a_best)

        robust_worstcase_defwin = d_fit[0]     # defender's worst case over the gauntlet
        best_exploit_atkwin = a_fit[0]         # best new attacker's win vs robust defender
        history.append({"round": rnd,
                        "robust_worstcase_defwin": robust_worstcase_defwin,
                        "best_exploit_atkwin": best_exploit_atkwin,
                        "library_size": len(attacker_lib)})
        log(f"Round {rnd}: robust defender worst-case def-win={robust_worstcase_defwin:.3f}; "
            f"best new attacker exploit win={best_exploit_atkwin:.3f}")
        if on_progress:
            on_progress(history, len(attacker_lib), robust_def, a_best, attacker_lib)

        # Converged if the best new attacker barely beats the robust defender, or the
        # exploit stopped improving round-over-round.
        if best_exploit_atkwin <= epsilon or (
                prev_exploit is not None and best_exploit_atkwin <= prev_exploit + epsilon
                and best_exploit_atkwin >= prev_exploit - epsilon):
            log("  converged (exploitability gate)")
            break
        prev_exploit = best_exploit_atkwin

    return {"robust_defender": robust_def, "attacker_library": attacker_lib,
            "history": history}
