"""End-to-end smoke test: sample populations -> repair -> payoff matrix.

Run from tools/balance-tester:  python3 smoke_tournament.py
"""

import random

from evaluator import BattleSim
from pool import Pool
import genome as G
import tournament as T


def sample_pop(pool, side, race, tech_cap, n, n_fleets, budget, max_ships, rng):
    pop = []
    for _ in range(n):
        lo = G.sample_loadout(pool, side, race, tech_cap, n_fleets, budget, max_ships, rng)
        lo = G.repair(lo, pool, side, tech_cap, budget, max_ships, rng)
        pop.append(lo)
    return pop


def main():
    rng = random.Random(42)
    race, tech_cap = 1, 999999
    budget, n_fleets, max_ships = 250_000, 3, 30

    with BattleSim() as sim:
        pool = Pool(sim)
        A = sample_pop(pool, "attacker", race, tech_cap, 3, n_fleets, budget, max_ships, rng)
        D = sample_pop(pool, "defender", race, tech_cap, 3, n_fleets, budget, max_ships, rng)

        # legality sanity: distinct cells, ships <= fleet_commanding, budget
        for tag, pop, side in [("A", A, "attacker"), ("D", D, "defender")]:
            for k, lo in enumerate(pop):
                cells = [f.cell for f in lo.fleets]
                assert len(cells) == len(set(cells)), f"{tag}{k} overlapping cells"
                for f in lo.fleets:
                    assert f.ships <= f.commander.fleet_commanding(), "ships > fc"
                cost = G.pp_cost(lo, pool, tech_cap)
                assert cost <= budget, f"{tag}{k} over budget: {cost} > {budget}"
                print(f"  {tag}{k}: fleets={len(lo.fleets)} ships={[f.ships for f in lo.fleets]} "
                      f"hulls={[f.design.hull for f in lo.fleets]} pp_cost={cost} (<= {budget})")

        print("\nBuilding 3x3 payoff matrix (CRN, 25 replicates)...")
        M = T.payoff_matrix(sim, pool, A, D, tech_cap, base_seed=777, replicates=25)

        print("\nattacker win-rate  (rows=attackers, cols=defenders):")
        print("       " + "    ".join(f"D{j}" for j in range(len(D))))
        for i, row in enumerate(M):
            print(f"  A{i}: " + "  ".join(f"{c.win_rate:.2f}" for c in row))

        bi, bj = T.best_attacker(M), T.best_defender(M)
        print(f"\nbest attacker vs field: A{bi} -> {T.score_attacker_vs_field(M, bi)}")
        print(f"best defender vs field: D{bj} -> {T.score_defender_vs_field(M, bj)}")
        crashes = sum(c.raw["crashes"] for row in M for c in row)
        print(f"total crashes across matrix: {crashes}")
        print("smoke tournament OK")


if __name__ == "__main__":
    main()
