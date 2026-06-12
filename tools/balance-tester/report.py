"""Report generator: report.md (human) + report.json (machine)."""

from __future__ import annotations

import json
import os
from typing import List

import genome as G
import pool as P

CMD_NAMES = ["NORMAL", "FORMATION", "PENETRATE", "FLANK", "RESERVE",
             "FREE", "ASSAULT", "STAND_GROUND"]


def decode_loadout(lo: G.Loadout, pool: P.Pool, tech_cap: int) -> dict:
    """Human-readable decode of a loadout (hulls/weapons/commander/cells)."""
    fleets = []
    for fl in lo.fleets:
        c = fl.commander
        fleets.append({
            "capital": fl.is_capital,
            "hull": fl.design.hull,
            "armor": fl.design.armor,
            "weapons": fl.design.weapons,
            "devices": fl.design.devices,
            "ships": fl.ships,
            "stance": CMD_NAMES[fl.command],
            "cell": list(fl.cell),
            "commander": {
                "siege": P.BB_VAL[c.bb], "detection": P.DET_VAL[c.det],
                "maneuver": P.MAN_VAL[c.man], "fleet_commanding": P.FC_VAL[c.fc],
                "special": c.special, "armada": "A" if fl.is_capital else "-",
                "points": [c.bb, c.det, c.man, c.fc],
            },
        })
    return {"race": lo.race, "pp_cost": G.pp_cost(lo, pool, tech_cap), "fleets": fleets}


def _fleet_line(f: dict) -> str:
    cm = f["commander"]
    cap = "*CAP* " if f["capital"] else "      "
    return (f"    {cap}{f['ships']:>2d}x hull {f['hull']} armor {f['armor']} "
            f"wpn {f['weapons']} dev {f['devices']} | {f['stance']:<12s} @{f['cell']} "
            f"| cmdr siege {cm['siege']} det {cm['detection']} man {cm['maneuver']} "
            f"fc {cm['fleet_commanding']} sp {cm['special']} arm {cm['armada']}")


def write_report(result: dict, outdir: str) -> None:
    os.makedirs(outdir, exist_ok=True)
    with open(os.path.join(outdir, "report.json"), "w") as f:
        json.dump(result, f, indent=2)
    with open(os.path.join(outdir, "report.md"), "w") as f:
        f.write(_markdown(result))


def _markdown(r: dict) -> str:
    L: List[str] = []
    sc = r["scenario"]
    L.append(f"# Balance report - {sc['name']}")
    L.append("")
    L.append(f"- mode: **{r['mode']}**")
    L.append(f"- attacker: race {sc['attacker']['race']}, budget {sc['attacker']['pp_budget']}, "
             f"<= {sc['attacker']['n_fleets']} fleets")
    L.append(f"- defender: race {sc['defender']['race']}, budget {sc['defender']['pp_budget']}, "
             f"<= {sc['defender']['n_fleets']} fleets")
    L.append("")
    L.append(f"## Verdict\n\n{r['verdict']}\n")

    an = r.get("analysis")
    if an:
        L.append("## Meta-game")
        L.append(f"- class: **{an['kind']}**")
        L.append(f"- attacker equilibrium win-rate: **{an['value']:.3f}**")
        L.append(f"- attacker Nash support: {an['attacker_support']}")
        L.append(f"- defender Nash support: {an['defender_support']}")
        L.append("")

    if r.get("matrix"):
        L.append("## Payoff matrix (attacker win-rate; rows=attackers, cols=defenders)")
        L.append("")
        cols = len(r["matrix"][0])
        L.append("| A\\D | " + " | ".join(f"D{j}" for j in range(cols)) + " |")
        L.append("|" + "----|" * (cols + 1))
        for i, row in enumerate(r["matrix"]):
            L.append(f"| A{i} | " + " | ".join(f"{v:.2f}" for v in row) + " |")
        L.append("")

    if r.get("history"):
        L.append("## Stackelberg convergence (robust rounds)")
        L.append("")
        L.append("| round | robust worst-case def-win | best new attacker exploit win | library |")
        L.append("|----|----|----|----|")
        for h in r["history"]:
            L.append(f"| {h['round']} | {h['robust_worstcase_defwin']:.3f} "
                     f"| {h['best_exploit_atkwin']:.3f} | {h['library_size']} |")
        L.append("")

    lo = r.get("loadouts", {})
    if lo.get("robust_defender"):
        L.append("## Robust defender (least-exploitable)")
        d = lo["robust_defender"]
        L.append(f"- pp_cost: {d['pp_cost']}")
        for fl in d["fleets"]:
            L.append(_fleet_line(fl))
        L.append("")
    if lo.get("attackers"):
        L.append("## Attacker library / population")
        for k, a in enumerate(lo["attackers"]):
            L.append(f"- A{k} (pp_cost {a['pp_cost']}):")
            for fl in a["fleets"]:
                L.append(_fleet_line(fl))
        L.append("")
    if lo.get("defenders"):
        L.append("## Defender population")
        for k, d in enumerate(lo["defenders"]):
            L.append(f"- D{k} (pp_cost {d['pp_cost']}):")
            for fl in d["fleets"]:
                L.append(_fleet_line(fl))
        L.append("")

    anomalies = r.get("anomalies", {})
    L.append("## Anomalies")
    L.append(f"- total engine crashes across evaluated battles: {anomalies.get('crashes', 0)}")
    L.append("")
    L.append("## Reproducibility")
    L.append(f"- base seed: {r['scenario'].get('base_seed')}")
    L.append(f"- turn cap: {r['scenario'].get('turn_cap')}")
    L.append("- PP-budget caveat: components/weapons/devices cost 0 PP; the budget")
    L.append("  constrains hull-size x count only, so loadout quality is maxed on")
    L.append("  affordable hulls (a known engine property, not a bug).")
    return "\n".join(L)
