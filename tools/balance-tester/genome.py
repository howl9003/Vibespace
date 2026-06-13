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

import copy
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


def sample_commander(rng: random.Random, is_capital: bool, race: int = 1) -> Commander:
    if is_capital:
        det, man, fc = _capital_rest(rng)
        c = Commander(bb=2, det=det, man=man, fc=fc)
    else:
        bb, det, man, fc = _zero_sum4(rng)
        c = Commander(bb=bb, det=det, man=man, fc=fc)
    c.special = rng.choice(P.SPECIAL_ABILITIES)        # universal combat specialist
    racials = P.race_racials(race)
    if racials:
        c.racial = rng.choice(racials)                 # one of the race's racial abilities
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
    """Sample a rough loadout: n_fleets fleets with diverse searched genes
    (commander / weapons / armor / devices / stance / cell). repair() then assigns
    the largest affordable hull and the ship counts that spend the PP budget, so
    hull-size and fleet-size are not free search dimensions."""
    cells = P.free_cells(side)
    rng.shuffle(cells)
    _, _, cap_cell = P.grid(side)

    lo = Loadout(race=race)
    for i in range(max(1, n_fleets)):
        lo.fleets.append(Fleet(
            design=sample_design(pool, race, tech_cap, rng),
            commander=sample_commander(rng, is_capital=(i == 0), race=race),
            command=rng.randint(0, 7),
            cell=cap_cell if i == 0 else cells[i - 1],
            ships=1, is_capital=(i == 0)))
    return lo


# ----------------------------- repair ----------------------------------------

def _hulls_by_cost(pool: P.Pool, race: int, tech_cap: int) -> List[dict]:
    return sorted(pool.hulls(race, tech_cap), key=lambda h: h["cost"])


def _largest_affordable(hulls_by_cost: List[dict], remaining: Optional[int]):
    """Largest (most expensive) hull affordable with `remaining` PP; None if broke.
    remaining=None means unlimited budget -> the biggest hull."""
    if remaining is None:
        return hulls_by_cost[-1]
    aff = [h for h in hulls_by_cost if h["cost"] <= remaining]
    return aff[-1] if aff else None


def _legalize_design(fl: Fleet, hull: dict, pool: P.Pool, race: int,
                     tech_cap: int, rng: random.Random) -> None:
    """Re-fit a fleet's weapons/armor/devices to its hull using the (tier-filtered)
    pool: weapon-slot count, distinct legal devices, a legal armor."""
    wp = [w["id"] for w in pool.weapons(race, tech_cap)]
    ns = hull["weapon_slots"]
    fl.design.weapons = ([(w if w in wp else rng.choice(wp))
                          for w in (fl.design.weapons + [0] * ns)[:ns]]
                         if wp and ns else [])
    # devices: keep the searched ones (distinct, legal), then FILL every remaining
    # device slot — a ship never leaves a device slot empty in-game.
    dev_ids = pool.device_ids(race, tech_cap)
    seen: List[int] = []
    for d in fl.design.devices:
        if d in dev_ids and d not in seen:
            seen.append(d)
    nslots = min(hull["device_slots"], len(dev_ids))
    spare = [d for d in dev_ids if d not in seen]
    rng.shuffle(spare)
    while len(seen) < nslots and spare:
        seen.append(spare.pop())
    fl.design.devices = seen[:nslots]
    armor = pool.armor_ids(race, tech_cap)
    if armor and fl.design.armor not in armor:
        fl.design.armor = rng.choice(armor)


def repair(lo: Loadout, pool: P.Pool, side: str, tech_cap: int,
           pp_budget: Optional[int], max_ships_per_fleet: int,
           rng: random.Random, n_fleets: int = 20) -> Loadout:
    """Re-legalize a loadout and enforce the 'spend the budget on the largest
    affordable ships' policy: each fleet takes the largest hull it can afford with
    the remaining budget, filled to capacity; fleets are added up to n_fleets and
    then grown until no leftover PP could buy another ship anywhere."""
    xs, ys, cap_cell = P.grid(side)
    n_fleets = max(1, min(n_fleets, 20))
    hbc = _hulls_by_cost(pool, lo.race, tech_cap)
    cheapest = hbc[0]["cost"]
    grid_cells = {(x, y) for x in xs for y in ys}

    lo.fleets = lo.fleets[:n_fleets] or lo.fleets[:1]
    used = {cap_cell}
    free = P.free_cells(side)
    rng.shuffle(free)

    # commander locks + stance + distinct cells (preserve the searched genes)
    for i, fl in enumerate(lo.fleets):
        fl.is_capital = (i == 0)
        if i == 0:
            fl.commander.bb = 2
            if fl.commander.det + fl.commander.man + fl.commander.fc != -2:
                fl.commander.det, fl.commander.man, fl.commander.fc = _capital_rest(rng)
            fl.cell = cap_cell
        else:
            if (fl.commander.bb + fl.commander.det
                    + fl.commander.man + fl.commander.fc) != 0:
                fl.commander.bb, fl.commander.det, fl.commander.man, fl.commander.fc = _zero_sum4(rng)
            if fl.cell in used or fl.cell not in grid_cells:
                for c in free:
                    if c not in used:
                        fl.cell = c
                        break
            used.add(fl.cell)
        fl.command %= 8
        if fl.commander.special not in P.SPECIAL_ABILITIES:   # must have a special ability
            fl.commander.special = rng.choice(P.SPECIAL_ABILITIES)
        racials = P.race_racials(lo.race)                     # and a race-legal racial ability
        if racials and fl.commander.racial not in racials:
            fl.commander.racial = rng.choice(racials)

    # largest-hull + fill: each fleet gets the biggest hull it can still afford
    remaining = pp_budget
    legal: List[Fleet] = []
    for fl in lo.fleets:
        h = _largest_affordable(hbc, remaining)
        if h is None:
            if not legal:
                h = hbc[0]            # the capital must field at least the cheapest hull
            else:
                continue              # nothing affordable left -> drop this fleet
        fl.design.hull = h["id"]
        _legalize_design(fl, h, pool, lo.race, tech_cap, rng)
        cap = min(fl.commander.fleet_commanding(), max_ships_per_fleet)
        if remaining is not None and h["cost"]:
            cap = min(cap, remaining // h["cost"])
        fl.ships = max(1, cap)
        if remaining is not None:
            remaining -= fl.ships * h["cost"]
        legal.append(fl)

    # spend the rest: add fleets (cloning the capital's genes) up to n_fleets...
    while len(legal) < n_fleets and (remaining is None or remaining >= cheapest):
        h = _largest_affordable(hbc, remaining)
        if h is None:
            break
        nf = copy.deepcopy(legal[0])
        nf.is_capital = False
        nf.commander.bb, nf.commander.det, nf.commander.man, nf.commander.fc = _zero_sum4(rng)
        nf.commander.special = rng.choice(P.SPECIAL_ABILITIES)
        nf.commander.racial = rng.choice(P.race_racials(lo.race) or [nf.commander.racial])
        nf.command = rng.randint(0, 7)
        spot = next((c for c in free if c not in used), None)
        if spot is None:
            break
        nf.cell = spot
        used.add(spot)
        nf.design.hull = h["id"]
        _legalize_design(nf, h, pool, lo.race, tech_cap, rng)
        cap = min(nf.commander.fleet_commanding(), max_ships_per_fleet)
        if remaining is not None and h["cost"]:
            cap = min(cap, remaining // h["cost"])
        nf.ships = max(1, cap)
        if remaining is not None:
            remaining -= nf.ships * h["cost"]
        legal.append(nf)

    # ...then grow existing fleets with any leftover (each keeps its own hull)
    if remaining is not None:
        changed = True
        while changed and remaining >= cheapest:
            changed = False
            for fl in legal:
                hc = pool.hull_by_id(lo.race, fl.design.hull, tech_cap)["cost"]
                capf = min(fl.commander.fleet_commanding(), max_ships_per_fleet)
                if fl.ships < capf and remaining >= hc:
                    fl.ships += 1
                    remaining -= hc
                    changed = True

    lo.fleets = legal or lo.fleets[:1]
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


# ----------------------------- genetic operators -----------------------------

def _mutate_commander(c: Commander, rng: random.Random, is_capital: bool,
                      race: int = 1) -> None:
    r = rng.random()
    if r < 0.5:
        if is_capital:
            c.bb = 2
            c.det, c.man, c.fc = _capital_rest(rng)
        else:
            c.bb, c.det, c.man, c.fc = _zero_sum4(rng)
    elif r < 0.75:
        c.special = rng.choice(P.SPECIAL_ABILITIES)   # never clear the special ability
    else:
        racials = P.race_racials(race)                # switch among the race's racials
        if racials:
            c.racial = rng.choice(racials)


def _mutate_design(d: Design, pool: P.Pool, race: int, tech_cap: int,
                   rng: random.Random) -> None:
    pick = rng.randint(0, 2)
    if pick == 0:                                   # hull (repair re-fits weapons/budget)
        d.hull = rng.choice(pool.hulls(race, tech_cap))["id"]
    elif pick == 1:                                 # armor
        d.armor = rng.choice(pool.armor_ids(race, tech_cap))
    elif d.weapons:                                 # one weapon slot
        d.weapons[rng.randrange(len(d.weapons))] = rng.choice(pool.weapons(race, tech_cap))["id"]
    if rng.random() < 0.3:                          # occasionally a device
        devs = pool.device_ids(race, tech_cap)
        if devs:
            d.devices = rng.sample(devs, min(max(1, len(d.devices)), len(devs)))


def mutate(lo: Loadout, pool: P.Pool, side: str, tech_cap: int,
           pp_budget: Optional[int], max_ships_per_fleet: int,
           rng: random.Random, rate: float = 0.35, n_fleets: int = 20) -> Loadout:
    """Per-gene resample over the searched genes, then repair(). Hull-size and
    ship counts are set by repair() (largest affordable + spend the budget), so
    mutation focuses on the genes that matter: weapons/armor/devices, commander,
    stance, and deploy cell."""
    lo = copy.deepcopy(lo)

    # structural: add or drop a (non-capital) fleet
    if rng.random() < 0.12 and len(lo.fleets) < n_fleets:
        clone = copy.deepcopy(rng.choice(lo.fleets))
        clone.is_capital = False
        lo.fleets.append(clone)
    elif rng.random() < 0.12 and len(lo.fleets) > 1:
        del lo.fleets[rng.randrange(1, len(lo.fleets))]

    xs, ys, _ = P.grid(side)
    for fl in lo.fleets:
        if rng.random() < rate:
            _mutate_design(fl.design, pool, lo.race, tech_cap, rng)
        if rng.random() < rate:
            _mutate_commander(fl.commander, rng, fl.is_capital, lo.race)
        if rng.random() < rate:
            fl.command = rng.randint(0, 7)
        if rng.random() < rate:
            fl.cell = (rng.choice(xs), rng.choice(ys))
        if rng.random() < rate:
            fl.ships = max(1, fl.ships + rng.choice([-3, -1, 1, 3]))
    return repair(lo, pool, side, tech_cap, pp_budget, max_ships_per_fleet, rng, n_fleets)


def crossover(a: Loadout, b: Loadout, pool: P.Pool, side: str, tech_cap: int,
              pp_budget: Optional[int], max_ships_per_fleet: int,
              rng: random.Random, n_fleets: int = 20) -> Loadout:
    """Uniform per-fleet crossover (whole-fleet swaps), then repair()."""
    child = Loadout(race=a.race)
    for i in range(max(len(a.fleets), len(b.fleets))):
        src = a if rng.random() < 0.5 else b
        if i < len(src.fleets):
            child.fleets.append(copy.deepcopy(src.fleets[i]))
    if not child.fleets:
        child.fleets.append(copy.deepcopy(a.fleets[0]))
    return repair(child, pool, side, tech_cap, pp_budget, max_ships_per_fleet, rng, n_fleets)
