"""Empirical-game analysis (pure Python; no numpy/scipy/nashpy needed).

The payoff matrix M[i][j] is the attacker (row, maximizer) win-rate vs defender
(column, minimizer) - a zero-sum game on win probability. We solve it with
fictitious play (converges in value for zero-sum games) and classify the
outcome the plan cares about: a robust pure saddle, a mixed Nash, or an
RPS-style cycle (mixed support with no pure saddle).
"""

from __future__ import annotations
from typing import List, Tuple


def _row_payoff_vs(M, col_mix, i):
    return sum(M[i][j] * col_mix[j] for j in range(len(col_mix)))


def _col_payoff_vs(M, row_mix, j):
    return sum(M[i][j] * row_mix[i] for i in range(len(row_mix)))


def fictitious_play(M: List[List[float]], iters: int = 20000) -> Tuple[float, List[float], List[float]]:
    """Return (game value, attacker mix, defender mix) for the zero-sum matrix."""
    rows, cols = len(M), len(M[0])
    rc = [0] * rows
    cc = [0] * cols
    # seed one play each so best-responses are well defined
    rc[0] = 1
    cc[0] = 1
    for _ in range(iters):
        i = max(range(rows), key=lambda i: sum(M[i][j] * cc[j] for j in range(cols)))
        rc[i] += 1
        j = min(range(cols), key=lambda j: sum(M[i2][j] * rc[i2] for i2 in range(rows)))
        cc[j] += 1
    rmix = [c / sum(rc) for c in rc]
    cmix = [c / sum(cc) for c in cc]
    value = sum(M[i][j] * rmix[i] * cmix[j] for i in range(rows) for j in range(cols))
    return value, rmix, cmix


def saddle_point(M: List[List[float]], tol: float = 1e-9):
    """Return (i, j, value) of a pure saddle if maximin == minimax, else None."""
    rows, cols = len(M), len(M[0])
    row_mins = [min(M[i]) for i in range(rows)]
    maximin = max(row_mins)
    col_maxs = [max(M[i][j] for i in range(rows)) for j in range(cols)]
    minimax = min(col_maxs)
    if abs(maximin - minimax) > tol:
        return None
    for i in range(rows):
        for j in range(cols):
            if abs(M[i][j] - maximin) <= tol and row_mins[i] == M[i][j] == col_maxs[j]:
                return (i, j, maximin)
    return None


def classify(M: List[List[float]], support_tol: float = 0.02) -> dict:
    """Solve + classify the empirical game.

    kind in {"robust-pure", "mixed-nash", "rps-cycle"}:
      - robust-pure: a pure saddle (both sides have a dominant single choice).
      - mixed-nash : mixed equilibrium, modest support.
      - rps-cycle  : mixed equilibrium with broad support on both sides and no
                     pure saddle (cyclic, rock-paper-scissors-like).
    """
    value, rmix, cmix = fictitious_play(M)
    sp = saddle_point(M)
    a_support = [i for i, p in enumerate(rmix) if p > support_tol]
    d_support = [j for j, p in enumerate(cmix) if p > support_tol]

    if sp is not None:
        kind = "robust-pure"
    elif len(a_support) >= 3 and len(d_support) >= 3:
        kind = "rps-cycle"
    else:
        kind = "mixed-nash"

    return {
        "kind": kind,
        "value": value,                 # attacker equilibrium win-rate
        "attacker_mix": rmix,
        "defender_mix": cmix,
        "attacker_support": a_support,
        "defender_support": d_support,
        "saddle": sp,
    }
