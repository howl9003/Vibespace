"""Bipartite tournament substrate: payoff matrix over attacker x defender.

Every matchup is attacker(offense) vs defender(defense). Cells share a common
base seed (Common Random Numbers) so paired comparisons across the matrix have
low variance. Returns, per cell, the attacker win-rate (with Wilson CI) and the
net-PP econ, plus the raw MatchResult.
"""

from __future__ import annotations

from dataclasses import dataclass
from typing import List, Optional

import genome as G
import pool as P


@dataclass
class Cell:
    win_rate: float
    econ: float
    lo: float
    hi: float
    raw: dict


def evaluate_cell(sim, pool: P.Pool, attacker: G.Loadout, defender: G.Loadout,
                  tech_cap: int, base_seed: int, replicates: int,
                  turn_cap: int = 1800) -> Cell:
    spec = {
        "seed": base_seed, "replicates": replicates, "turn_cap": turn_cap,
        "attacker": G.encode_side(attacker, pool, tech_cap, base_id=100),
        "defender": G.encode_side(defender, pool, tech_cap, base_id=200),
    }
    r = sim.match(spec)
    return Cell(win_rate=r["win_rate"], econ=r["econ"],
                lo=r["wilson_lo"], hi=r["wilson_hi"], raw=r)


def payoff_matrix(sim, pool: P.Pool, A: List[G.Loadout], D: List[G.Loadout],
                  tech_cap: int, base_seed: int = 12345,
                  replicates: int = 20, turn_cap: int = 1800) -> List[List[Cell]]:
    """M[i][j] = attacker A[i] vs defender D[j]. Shared seed = CRN across cells."""
    return [[evaluate_cell(sim, pool, a, d, tech_cap, base_seed, replicates, turn_cap)
             for d in D] for a in A]


# --- lexicographic scoring (win-rate first, econ second) ---------------------

def score_attacker_vs_field(M: List[List[Cell]], i: int) -> tuple:
    """Attacker row i scored vs the uniform defender field: (mean win, mean econ)."""
    row = M[i]
    n = len(row) or 1
    return (sum(c.win_rate for c in row) / n, sum(c.econ for c in row) / n)


def score_defender_vs_field(M: List[List[Cell]], j: int) -> tuple:
    """Defender col j scored vs the uniform attacker field: lower attacker win is
    better for the defender, so return (mean defender-win, mean -econ)."""
    col = [M[i][j] for i in range(len(M))]
    n = len(col) or 1
    return (sum(1.0 - c.win_rate for c in col) / n, sum(-c.econ for c in col) / n)


def best_attacker(M: List[List[Cell]]) -> int:
    return max(range(len(M)), key=lambda i: score_attacker_vs_field(M, i))


def best_defender(M: List[List[Cell]]) -> int:
    cols = len(M[0]) if M else 0
    return max(range(cols), key=lambda j: score_defender_vs_field(M, j))
