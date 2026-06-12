"""POOL compiler + the fixed commander/deploy constants.

Caches `pool` queries from the evaluator (legal components + hulls per
race/tech_cap) and exposes the allele domains the genome samples from, plus the
"good commander" point-allocation tables and the verified deploy grids.
"""

from __future__ import annotations
from typing import Dict, List, Tuple

# --- commander point-allocation -> resolved stat tables (see plan) -----------
# Each allocatable stat is a small integer point in [-2, +2]; the table maps it
# to the engine stat the battle reads. The C++ getters layer racial-ability
# adjustments on top of these base values.
BB_VAL  = {-2: 5,  -1: 9,  0: 13, 1: 17, 2: 20}   # battle_bonus -> siege skill
DET_VAL = {-2: 3,  -1: 7,  0: 11, 1: 15, 2: 18}   # detection
MAN_VAL = {-2: 3,  -1: 7,  0: 11, 1: 15, 2: 18}   # maneuver
FC_VAL  = {-2: 33, -1: 35, 0: 37, 1: 39, 2: 41}   # fleet_commanding (engine cap 45)

# 5 common special abilities (combat specialists), always legal; -1 = none.
SPECIAL_ABILITIES = [0, 1, 2, 3, 4]

# --- verified deploy grids (battlefield coords; step 200) ---------------------
# Confirmed against siege_planet_result.cc / defense_plan_generic_result.cc:
# attacker on low-x, defender on high-x, shared lateral y. Capital pinned to the
# zone centre (the engine's default placement cell); others take distinct cells.
ATK_X: List[int] = list(range(1000, 3001, 200))   # 11 columns
ATK_Y: List[int] = list(range(2000, 8001, 200))   # 31 rows
DEF_X: List[int] = list(range(7000, 9001, 200))
DEF_Y: List[int] = list(range(2000, 8001, 200))
ATK_CAPITAL: Tuple[int, int] = (2000, 5000)
DEF_CAPITAL: Tuple[int, int] = (8000, 5000)


def grid(side: str) -> Tuple[List[int], List[int], Tuple[int, int]]:
    """(x-values, y-values, capital cell) for 'attacker' | 'defender'."""
    if side == "attacker":
        return ATK_X, ATK_Y, ATK_CAPITAL
    return DEF_X, DEF_Y, DEF_CAPITAL


def free_cells(side: str) -> List[Tuple[int, int]]:
    """All legal non-capital cells for a side."""
    xs, ys, cap = grid(side)
    return [(x, y) for x in xs for y in ys if (x, y) != cap]


class Pool:
    """Caches evaluator `pool` queries and exposes legal alleles."""

    def __init__(self, sim) -> None:
        self.sim = sim
        self._cache: Dict[Tuple[int, int], dict] = {}

    def get(self, race: int, tech_cap: int = 999999) -> dict:
        key = (race, tech_cap)
        if key not in self._cache:
            resp = self.sim.pool(race, tech_cap)
            if not resp.get("ok"):
                raise RuntimeError(f"pool query failed: {resp}")
            self._cache[key] = resp
        return self._cache[key]

    # hulls -------------------------------------------------------------------
    def hulls(self, race: int, tc: int = 999999) -> List[dict]:
        return self.get(race, tc)["hulls"]

    def hull_by_id(self, race: int, hull_id: int, tc: int = 999999) -> dict:
        for h in self.hulls(race, tc):
            if h["id"] == hull_id:
                return h
        raise KeyError(f"hull {hull_id} not in pool for race {race}")

    # components --------------------------------------------------------------
    def category(self, cat: str, race: int, tc: int = 999999) -> List[dict]:
        return self.get(race, tc)["components"][cat]

    def armor_ids(self, race: int, tc: int = 999999) -> List[int]:
        return [c["id"] for c in self.category("ARMOR", race, tc)]

    def device_ids(self, race: int, tc: int = 999999) -> List[int]:
        return [c["id"] for c in self.category("DEV", race, tc)]

    def weapons(self, race: int, tc: int = 999999) -> List[dict]:
        # [{id, level, space}, ...]
        return self.category("WPN", race, tc)

    def best(self, cat: str, race: int, tc: int = 999999) -> int:
        """Highest-level component in a category (the pinned linear-ladder part)."""
        comps = self.category(cat, race, tc)
        return max(comps, key=lambda c: c["level"])["id"] if comps else 0
