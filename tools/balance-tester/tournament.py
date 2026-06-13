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
    fleets: Optional[dict] = None   # {"attacker":[{id,dealt,taken,avoided}], "defender":[...]}


def evaluate_cell(sim, pool: P.Pool, attacker: G.Loadout, defender: G.Loadout,
                  tech_cap: int, base_seed: int, replicates: int,
                  turn_cap: int = 1800, conc: Optional[int] = None) -> Cell:
    spec = {
        "seed": base_seed, "replicates": replicates, "turn_cap": turn_cap,
        "attacker": G.encode_side(attacker, pool, tech_cap, base_id=100),
        "defender": G.encode_side(defender, pool, tech_cap, base_id=200),
    }
    if conc:                       # per-worker replicate cap when many cells run in parallel
        spec["conc"] = conc
    r = sim.match(spec)
    return Cell(win_rate=r["win_rate"], econ=r["econ"],
                lo=r["wilson_lo"], hi=r["wilson_hi"], raw=r, fleets=r.get("fleets"))


def fleet_damage(cell: Cell, side: str, known_ids) -> dict:
    """Per-fleet (dealt, taken, avoided) for `side` ('attacker'|'defender').

    Defaults (0,0,0) for any spec fleet id absent from the match payload (a fleet
    that neither fired nor was fired upon over the replicates). This is the dense
    per-fleet signal the inner search ranks loadout candidates by.
    """
    out = {int(i): (0.0, 0.0, 0.0) for i in known_ids}
    for f in ((cell.fleets or {}).get(side) or []):
        out[int(f["id"])] = (float(f.get("dealt", 0.0)),
                             float(f.get("taken", 0.0)),
                             float(f.get("avoided", 0.0)))
    return out


def payoff_matrix(sim, pool: P.Pool, A: List[G.Loadout], D: List[G.Loadout],
                  tech_cap: int, base_seed: int = 12345,
                  replicates: int = 20, turn_cap: int = 1800,
                  mpool=None) -> List[List[Cell]]:
    """M[i][j] = attacker A[i] vs defender D[j]. Shared seed = CRN across cells.

    With `mpool` (a workers.MatchPool), the cells are evaluated concurrently across
    worker processes (each worker runs its replicates sequentially, conc=1). The
    result is identical to the sequential version — only faster.
    """
    if mpool is None:
        return [[evaluate_cell(sim, pool, a, d, tech_cap, base_seed, replicates, turn_cap)
                 for d in D] for a in A]

    # warm the shared Pool cache single-threaded so per-cell encode_side is pure
    # (cache reads) and the worker threads never touch the main sim concurrently.
    for lo in list(A) + list(D):
        pool.get(lo.race, tech_cap)

    jobs = [(i, j) for i in range(len(A)) for j in range(len(D))]

    def run(wsim, job):
        i, j = job
        return evaluate_cell(wsim, pool, A[i], D[j], tech_cap, base_seed,
                             replicates, turn_cap, conc=1)

    flat = mpool.map(jobs, run)
    M = [[None] * len(D) for _ in range(len(A))]
    for (i, j), cell in zip(jobs, flat):
        M[i][j] = cell
    return M


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
