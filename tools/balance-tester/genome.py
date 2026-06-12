"""Genome: a side's loadout, its random sampling + repair, and MatchSpec encoding.

A Loadout is a list of independent, heterogeneous fleets. Each fleet carries its
own design (hull/armor/weapons/devices; computer/shield/engine are pinned to the
best-available linear-ladder part), its own commander (a net-zero point vector +
abilities), its own stance, its own deploy cell, and a ship count. Sampling and
repair() guarantee legality: distinct deploy cells (capital pinned), net-zero
commander allocation (capital battle_bonus locked to +2 with AC_A), ship counts
within the commander's fleet_commanding, and a soft PP budget.
"""

from __future__ import annotations

import random
from dataclasses import dataclass, field
from typing import List, Optional, Tuple

import pool as P


# ----------------------------- genome dataclasses ----------------------------

@dataclass
class Commander:
    bb: int = 0          # battle_bonus point  [-2, 2]
    det: int = 0         # detection point
    man: int = 0         # maneuver point
    fc: int = 0          # fleet_commanding point
    special: int = -1    # 0..4 (combat specialist) or -1 = none
    racial: int = -1     # racial-ability id or -1 (deferred until pool exposes them)

    def fleet_commanding(self) -> int:
        return P.FC_VAL[self.fc]

    def resolved(self, is_capital: bool) -> dict:
        """Resolve the point vector to the MatchSpec admiral stat values."""
        adm = {
            "siege": P.BB_VAL[self.bb],
            "detection": P.DET_VAL[self.det],
            "maneuver": P.MAN_VAL[self.man],
            "fleet_commanding": P.FC_VAL[self.fc],
            "efficiency": 100,
        }
        if self.special >= 0:
            adm["special"] = self.special
        if self.racial >= 0:
            adm["racial"] = self.racial
        if is_capital:
            adm["armada"] = 0  # AC_A: broadcast to every fleet
        return adm


@dataclass
class Design:
    hull: int                       # ship-size id (4001..4010)
    armor: int                      # armor component id
    weapons: List[int]              # weapon id per slot (len = hull weapon_slots); 0 = empty
    devices: List[int]              # device ids (unique), len <= hull device_slots


@dataclass
class Fleet:
    design: Design
    commander: Commander
    command: int                    # stance 0..7 (NORMAL..STAND_GROUND)
    cell: Tuple[int, int]           # deploy (x, y)
    ships: int
    is_capital: bool = False


@dataclass
class Loadout:
    race: int
    fleets: List[Fleet] = field(default_factory=list)


# ----------------------------- sampling helpers ------------------------------

def _zero_sum4(rng: random.Random) -> List[int]:
    """4 points in [-2,2] summing to 0."""
    while True:
        v = [rng.randint(-2, 2) for _ in range(4)]
        if sum(v) == 0:
            return v


def _capital_rest(rng: random.Random) -> List[int]:
    """det/man/fc in [-2,2] summing to -2 (battle_bonus is locked to +2)."""
    while True:
        v = [rng.randint(-2, 2) for _ in range(3)]
        if sum(v) == -2:
            return v


def sample_commander(rng: random.Random, is_capital: bool) -> Commander:
    if is_capital:
        det, man, fc = _capital_rest(rng)
        c = Commander(bb=2, det=det, man=man, fc=fc)
    else:
        bb, det, man, fc = _zero_sum4(rng)
        c = Commander(bb=bb, det=det, man=man, fc=fc)
    c.special = rng.choice([-1] + P.SPECIAL_ABILITIES)
    return c


def _affordable_hulls(pool: P.Pool, race: int, tech_cap: int,
                      max_hull_cost: Optional[int]) -> List[dict]:
    hulls = pool.hulls(race, tech_cap)
    if max_hull_cost is None:
        return hulls
    aff = [h for h in hulls if h["cost"] <= max_hull_cost]
    return aff or [min(hulls, key=lambda h: h["cost"])]   # cheapest if nothing fits


def sample_design(pool: P.Pool, race: int, tech_cap: int, rng: random.Random,
                  max_hull_cost: Optional[int] = None) -> Design:
    h = rng.choice(_affordable_hulls(pool, race, tech_cap, max_hull_cost))
    weapons = [rng.choice(pool.weapons(race, tech_cap))["id"]
               for _ in range(h["weapon_slots"])]
    dev_ids = pool.device_ids(race, tech_cap)
    ndev = min(h["device_slots"], len(dev_ids))
    devices = rng.sample(dev_ids, ndev) if ndev > 0 else []
    return Design(hull=h["id"], armor=rng.choice(pool.armor_ids(race, tech_cap)),
                  weapons=weapons, devices=devices)


def sample_loadout(pool: P.Pool, side: str, race: int, tech_cap: int,
                   n_fleets: int, pp_budget: Optional[int],
                   max_ships_per_fleet: int, rng: random.Random) -> Loadout:
    """Sample a legal random loadout for a side."""
    cells = P.free_cells(side)
    rng.shuffle(cells)
    _, _, cap_cell = P.grid(side)

    lo = Loadout(race=race)
    spent = 0
    for i in range(n_fleets):
        remaining = (pp_budget - spent) if pp_budget is not None else None
        if remaining is not None and remaining < 1:
            break  # out of budget
        des = sample_design(pool, race, tech_cap, rng, max_hull_cost=remaining)
        cmd = sample_commander(rng, is_capital=(i == 0))
        hull_cost = pool.hull_by_id(race, des.hull, tech_cap)["cost"]

        cap = min(cmd.fleet_commanding(), max_ships_per_fleet)
        if remaining is not None and hull_cost:
            cap = min(cap, remaining // hull_cost)
        if cap < 1:
            break
        ships = rng.randint(1, cap)
        spent += ships * hull_cost

        cell = cap_cell if i == 0 else cells[i - 1]
        lo.fleets.append(Fleet(design=des, commander=cmd, command=rng.randint(0, 7),
                               cell=cell, ships=ships, is_capital=(i == 0)))
        if pp_budget is not None and spent >= pp_budget:
            break

    if not lo.fleets:  # always field at least one affordable (cheapest) fleet
        des = sample_design(pool, race, tech_cap, rng, max_hull_cost=pp_budget)
        cmd = sample_commander(rng, is_capital=True)
        lo.fleets.append(Fleet(design=des, commander=cmd, command=5,
                               cell=cap_cell, ships=1, is_capital=True))
    return lo


# ----------------------------- repair ----------------------------------------

def repair(lo: Loadout, pool: P.Pool, side: str, tech_cap: int,
           pp_budget: Optional[int], max_ships_per_fleet: int,
           rng: random.Random) -> Loadout:
    """Re-legalize a (possibly mutated) loadout in place and return it."""
    xs, ys, cap_cell = P.grid(side)

    # exactly one capital (the first fleet)
    for i, fl in enumerate(lo.fleets):
        fl.is_capital = (i == 0)

    used = {cap_cell}
    free = P.free_cells(side)
    rng.shuffle(free)
    spent = 0
    legal_fleets: List[Fleet] = []
    for i, fl in enumerate(lo.fleets[:20]):          # hard cap 20 fleets/side
        # capital lock: battle_bonus = +2, others sum to -2
        if i == 0:
            fl.commander.bb = 2
            if fl.commander.det + fl.commander.man + fl.commander.fc != -2:
                fl.commander.det, fl.commander.man, fl.commander.fc = _capital_rest(rng)
        else:
            if (fl.commander.bb + fl.commander.det
                    + fl.commander.man + fl.commander.fc) != 0:
                fl.commander.bb, fl.commander.det, fl.commander.man, fl.commander.fc = _zero_sum4(rng)

        # budget: if this hull is unaffordable, swap to the cheapest affordable one
        h = pool.hull_by_id(lo.race, fl.design.hull, tech_cap)
        if pp_budget is not None:
            remaining = pp_budget - spent
            if h["cost"] > remaining:
                cheapest = min(pool.hulls(lo.race, tech_cap), key=lambda hh: hh["cost"])
                if cheapest["cost"] > remaining and legal_fleets:
                    continue   # can't afford anything more; drop (keep >=1 fleet)
                h = cheapest
                fl.design.hull = h["id"]

        # design legality: pinned ladder parts, weapon-slot count, distinct devices
        wp = [w["id"] for w in pool.weapons(lo.race, tech_cap)]
        ns = h["weapon_slots"]
        fl.design.weapons = [(w if w in wp else rng.choice(wp))
                             for w in (fl.design.weapons + [0] * ns)[:ns]]
        dev_ids = pool.device_ids(lo.race, tech_cap)
        seen = []
        for d in fl.design.devices:
            if d in dev_ids and d not in seen:
                seen.append(d)
        fl.design.devices = seen[:h["device_slots"]]

        # distinct deploy cell (capital pinned)
        if i == 0:
            fl.cell = cap_cell
        else:
            if fl.cell in used or fl.cell not in [(x, y) for x in xs for y in ys]:
                for c in free:
                    if c not in used:
                        fl.cell = c
                        break
            used.add(fl.cell)

        # ship count <= fleet_commanding and budget; stance in range
        cap = min(fl.commander.fleet_commanding(), max_ships_per_fleet)
        hull_cost = h["cost"]
        if pp_budget is not None and hull_cost:
            cap = min(cap, (pp_budget - spent) // hull_cost)
        fl.ships = max(1, min(fl.ships, cap)) if cap >= 1 else 1
        fl.command = fl.command % 8
        spent += fl.ships * hull_cost

        legal_fleets.append(fl)
        if pp_budget is not None and spent >= pp_budget and i >= 0:
            break

    lo.fleets = legal_fleets or lo.fleets[:1]
    lo.fleets[0].is_capital = True
    return lo


# ----------------------------- encoding --------------------------------------

def encode_side(lo: Loadout, pool: P.Pool, tech_cap: int, base_id: int) -> dict:
    """Loadout -> MatchSpec side dict (resolves weapon counts + pinned parts)."""
    comp = pool.best("COMP", lo.race, tech_cap)
    shld = pool.best("SHLD", lo.race, tech_cap)
    engn = pool.best("ENGN", lo.race, tech_cap)
    wspace = {w["id"]: w["space"] for w in pool.weapons(lo.race, tech_cap)}

    fleets = []
    for i, fl in enumerate(lo.fleets):
        h = pool.hull_by_id(lo.race, fl.design.hull, tech_cap)
        wlist = []
        for wid in fl.design.weapons:
            if wid and wid in wspace:
                n = max(1, h["slot"] // wspace[wid])
                wlist.append({"id": wid, "n": n})
        fleets.append({
            "id": base_id + i,
            "capital": fl.is_capital,
            "command": fl.command,
            "x": fl.cell[0], "y": fl.cell[1],
            "ships": fl.ships,
            "design": {"body": fl.design.hull, "armor": fl.design.armor,
                       "computer": comp, "shield": shld, "engine": engn,
                       "weapons": wlist, "devices": fl.design.devices},
            "admiral": fl.commander.resolved(fl.is_capital),
        })
    return {"race": lo.race, "fleets": fleets}


def pp_cost(lo: Loadout, pool: P.Pool, tech_cap: int) -> int:
    """Faithful side PP cost = sum ships * hull cost (components are 0 PP)."""
    return sum(fl.ships * pool.hull_by_id(lo.race, fl.design.hull, tech_cap)["cost"]
               for fl in lo.fleets)
