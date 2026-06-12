"""Scenario YAML loader -> per-side constraints + mode/search parameters."""

from __future__ import annotations

from dataclasses import dataclass
from typing import Optional

import yaml

import search as S


@dataclass
class SideConstraints:
    race: int
    tech_cap: int
    pp_budget: Optional[int]
    n_fleets: int
    max_ships: int


@dataclass
class Scenario:
    name: str
    mode: str                 # "assess" | "stackelberg"
    attacker: SideConstraints
    defender: SideConstraints
    population: int
    generations: int
    mu: int
    lam: int
    replicates: int
    rounds: int
    epsilon: float
    base_seed: int
    turn_cap: int

    def constraints(self, side: str) -> S.Constraints:
        sc = self.attacker if side == "attacker" else self.defender
        return S.Constraints(race=sc.race, tech_cap=sc.tech_cap, pp_budget=sc.pp_budget,
                             n_fleets=sc.n_fleets, max_ships=sc.max_ships)


def _side(d: dict) -> SideConstraints:
    budget = d.get("pp_budget", "unlimited")
    return SideConstraints(
        race=int(d.get("race", 1)),
        tech_cap=int(d.get("tech_cap", 999999)),
        pp_budget=None if budget in (None, "unlimited") else int(budget),
        n_fleets=int(d.get("max_fleets", 3)),
        max_ships=int(d.get("max_ships_per_fleet", 30)),
    )


def load(path: str) -> Scenario:
    with open(path) as f:
        d = yaml.safe_load(f)
    m = d.get("mode", {}) or {}
    return Scenario(
        name=d.get("name", "scenario"),
        mode=d.get("run", "stackelberg"),
        attacker=_side(d.get("attacker", {})),
        defender=_side(d.get("defender", {})),
        population=int(m.get("population", 6)),
        generations=int(m.get("generations", 10)),
        mu=int(m.get("mu", 6)),
        lam=int(m.get("lam", 12)),
        replicates=int(m.get("replicates", 20)),
        rounds=int(m.get("rounds", 3)),
        epsilon=float(m.get("epsilon", 0.05)),
        base_seed=int(d.get("seed", 12345)),
        turn_cap=int(d.get("turn_cap", 1800)),
    )
