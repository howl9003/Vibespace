"""Smoke test for the search: inner oracle improvement + a short Stackelberg run.

Run from tools/balance-tester:  python3 smoke_search.py
"""

import random
import time

from evaluator import BattleSim
from pool import Pool
import genome as G
import search as S


def main():
    rng = random.Random(7)
    race, tech_cap = 1, 999999
    con = S.Constraints(race=race, tech_cap=tech_cap, pp_budget=150_000,
                        n_fleets=3, max_ships=30)

    with BattleSim() as sim:
        pool = Pool(sim)

        # A fixed "human-set" defender to exploit / defend around.
        fixed_def = G.repair(
            G.sample_loadout(pool, "defender", race, tech_cap, 3, con.pp_budget, 30, rng),
            pool, "defender", tech_cap, con.pp_budget, 30, rng)

        # --- inner oracle: evolve an attacker vs the fixed defender ----------
        print("=== inner oracle: attacker best-response vs a fixed defender ===")
        gen_log = []
        t0 = time.time()
        best, fit = S.best_response(
            sim, pool, [fixed_def], "attacker", con,
            mu=5, lam=8, generations=8, replicates=12, base_seed=4242, rng=rng,
            log=lambda g, cur, bst: gen_log.append((g, cur[0], bst[0])))
        for g, cur, bst in gen_log:
            print(f"  gen {g}: best-this-gen win={cur:.3f}  running-best win={bst:.3f}")
        print(f"  -> oracle win-rate vs fixed defender: {fit[0]:.3f}  ({time.time()-t0:.1f}s)")
        improved = gen_log[-1][2] >= gen_log[0][2]
        print(f"  monotone improvement: {'YES' if improved else 'NO'}")

        # --- staged Stackelberg (short) -------------------------------------
        print("\n=== Stackelberg double-oracle (2 robust rounds) ===")
        t0 = time.time()
        out = S.stackelberg(
            sim, pool, atk_con=con, def_con=con, fixed_defender=fixed_def,
            rounds=2, epsilon=0.05, mu=5, lam=8, gens=6, replicates=12,
            base_seed=4242, rng=rng, log=print)
        print(f"\n  attacker library size: {len(out['attacker_library'])}")
        print(f"  robust-defender trajectory (worst-case def-win should not fall):")
        for h in out["history"]:
            print(f"    round {h['round']}: robust_worstcase_defwin={h['robust_worstcase_defwin']:.3f} "
                  f"best_exploit_atkwin={h['best_exploit_atkwin']:.3f}")
        print(f"  ({time.time()-t0:.1f}s)")
        print("smoke search OK")


if __name__ == "__main__":
    main()
