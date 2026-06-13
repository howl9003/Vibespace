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

# Race-specific commander racial abilities. Each race's admirals get ONE of three
# possible racial abilities (engine: admiral.cc mPossibleRacialSkill[race-1][0..2],
# RA_* enum ids; names in ui/render.py RACIAL_ABILITIES). Race id 1..10 ->
# [3 racial-ability ids].
RACE_RACIAL_SKILLS = {
    1:  [0, 1, 2],        # Human:      Irrational Tactics, Intuition, Lone Wolf
    2:  [3, 4, 5],        # Targoid:    DNA Poison Replicater, Breeder Male, Clonal Double
    3:  [2, 17, 18],      # Buckaneer:  Lone Wolf, Famous Privateer, Commerce King
    4:  [14, 15, 16],     # Tecanoid:   Cyber Scan Unit, Jury Rigger, Pattern Broadcaster
    5:  [14, 21, 22],     # Evintos:    Cyber Scan Unit, Rigid Thinking, Scavenger
    6:  [11, 12, 13],     # Agerus:     Lying Dormant, Missile Craters, Meteor Drones
    7:  [7, 19, 20],      # Bosalian:   Mental Giant, Retreat Shield, Genetic Throwback
    8:  [6, 7, 8],        # Xeloss:     Xenophobic Fanatic, Mental Giant, Artifact Crystal
    9:  [8, 21, 23],      # Xerusian:   Artifact Crystal, Rigid Thinking, Blitzkrieg
    10: [0, 9, 10],       # Xesperados: Irrational Tactics, Psychic Progenitor, Artifact Cooling Engine
}


def race_racials(race: int):
    return RACE_RACIAL_SKILLS.get(race, [])

# Restrict the searched component pool to high-tier gear: drop tier 1-3 weapons
# and armors (component `level` 1..5), keeping only level >= MIN_TIER. Narrows the
# search space to the parts a fully-teched empire would actually field. Devices and
# the pinned computer/shield/engine are unaffected.
MIN_TIER = 4

# Tier-4 weapons a tier-5 of the SAME type strictly dominates once damage, space and
# cooldown are condensed into per-slot throughput. The engine fits guns/slot =
# floor(ship.Slot / weapon.space) (blackmarket.cc:618, component.cc:861); on a Doomstar
# (Slot 2200) the slot output = guns * (dmg/shot) / cooldown comes out as:
#   Nova Torpedo (6203)          41.3 < Homing Black Hole 44.0  (AR 172<250, both no fx)
#   Anti-Matter Cannon (6305)    52.8 < Distortion Blaster 71.0 (AR 90<120, fx subset)
#   Autofire Gauss Cannon (6306) 47.1 < Distortion Blaster 71.0 (AR 90<120, fx subset)
# Each loses on throughput AND accuracy AND effects -> never worth fielding, so drop.
# Tachyon Beam stays (lowest beam throughput but unmatched AR 273); Reflexium Missile
# stays (31.0 > T5 Time-Wake 29.9, plus a unique anti-shield effect Black Hole lacks).
WEAPON_EXCLUDE = {6203, 6305, 6306}

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
        # tier 1-3 armors removed (keep level >= MIN_TIER)
        return [c["id"] for c in self.category("ARMOR", race, tc)
                if c.get("level", 99) >= MIN_TIER]

    def device_ids(self, race: int, tc: int = 999999) -> List[int]:
        return [c["id"] for c in self.category("DEV", race, tc)]

    def weapons(self, race: int, tc: int = 999999) -> List[dict]:
        # [{id, level, space}, ...] — keep level >= MIN_TIER and drop tier-4 weapons
        # a tier-5 strictly dominates on dmg/space/cooldown + accuracy + effects.
        return [c for c in self.category("WPN", race, tc)
                if c.get("level", 99) >= MIN_TIER and c["id"] not in WEAPON_EXCLUDE]

    def best(self, cat: str, race: int, tc: int = 999999) -> int:
        """Highest-level component in a category (the pinned linear-ladder part)."""
        comps = self.category(cat, race, tc)
        return max(comps, key=lambda c: c["level"])["id"] if comps else 0

    # names (present once the engine `pool` query emits them; None otherwise) -----
    def hull_name(self, hull_id: int, race: int, tc: int = 999999):
        for h in self.hulls(race, tc):
            if h["id"] == hull_id:
                return h.get("name")
        return None

    def component_name(self, cat: str, cid: int, race: int, tc: int = 999999):
        for c in self.category(cat, race, tc):
            if c["id"] == cid:
                return c.get("name")
        return None
