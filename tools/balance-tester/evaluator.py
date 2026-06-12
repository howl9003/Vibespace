"""Python client for the C++ battle-sim evaluator.

Spawns the standalone `battle-sim` binary as a long-lived worker process and
talks to it over the line-oriented JSON protocol (one request/response per
line). The engine is booted once per worker; `pool` and `match` requests are
then cheap. Each battle runs in a forked child on the C++ side, so an
adversarial loadout that crashes the engine is reported as `crashes` rather
than taking the worker down.

Build the binary first:
    cd archspace_source/archspace/src && sh set_platform linux \\
        && (cd libs && make) && (cd apps/archspace && make archspace) \\
        && (cd apps/battle-sim && make -f Makefile.Linux battle-sim)
"""

from __future__ import annotations

import json
import subprocess
from pathlib import Path
from typing import Any, Dict, Optional

# repo_root/tools/balance-tester/evaluator.py -> repo_root
_REPO = Path(__file__).resolve().parents[2]
_BIN_DIR = _REPO / "archspace_source/archspace/src/apps/battle-sim"
DEFAULT_BINARY = _BIN_DIR / "battle-sim"
DEFAULT_CONFIG = "spikeA.config"  # relative to the binary's directory


class BattleSimError(RuntimeError):
    pass


class BattleSim:
    """A single long-lived battle-sim worker process.

    Usage:
        with BattleSim() as sim:
            sim.ping()
            pool = sim.pool(race=1)
            res  = sim.match(spec)
    """

    def __init__(
        self,
        binary: Path = DEFAULT_BINARY,
        config: str = DEFAULT_CONFIG,
        cwd: Optional[Path] = None,
    ) -> None:
        self.binary = Path(binary)
        if not self.binary.exists():
            raise BattleSimError(
                f"battle-sim binary not found at {self.binary}. Build it first "
                "(see module docstring)."
            )
        # The default config uses script paths relative to the binary's dir, so
        # run the worker from there unless told otherwise.
        self.cwd = Path(cwd) if cwd else self.binary.parent
        self.config = config
        self.proc = subprocess.Popen(
            [str(self.binary), self.config],
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            stderr=subprocess.DEVNULL,
            cwd=str(self.cwd),
            text=True,
            bufsize=1,
        )

    # -- low-level RPC -------------------------------------------------------
    def _rpc(self, request: Dict[str, Any]) -> Dict[str, Any]:
        if self.proc.poll() is not None:
            raise BattleSimError(f"worker exited (code {self.proc.returncode})")
        assert self.proc.stdin and self.proc.stdout
        self.proc.stdin.write(json.dumps(request) + "\n")
        self.proc.stdin.flush()
        line = self.proc.stdout.readline()
        if not line:
            raise BattleSimError("worker closed the connection unexpectedly")
        try:
            return json.loads(line)
        except json.JSONDecodeError as exc:
            raise BattleSimError(f"bad response: {line!r}") from exc

    # -- commands ------------------------------------------------------------
    def ping(self) -> Dict[str, Any]:
        return self._rpc({"cmd": "ping"})

    def pool(self, race: int, tech_cap: int = 999999) -> Dict[str, Any]:
        """Legal components (by category) + hull table for a race/tech cap."""
        return self._rpc({"cmd": "pool", "race": race, "tech_cap": tech_cap})

    def match(self, spec: Dict[str, Any]) -> Dict[str, Any]:
        """Run a MatchSpec (attacker/defender sides) and return a MatchResult."""
        request = dict(spec)
        request["cmd"] = "match"
        return self._rpc(request)

    # -- lifecycle -----------------------------------------------------------
    def close(self) -> None:
        if self.proc.poll() is not None:
            return
        try:
            assert self.proc.stdin
            self.proc.stdin.write('{"cmd":"quit"}\n')
            self.proc.stdin.flush()
            self.proc.wait(timeout=5)
        except Exception:
            self.proc.kill()
            self.proc.wait()

    def __enter__(self) -> "BattleSim":
        return self

    def __exit__(self, *exc: object) -> None:
        self.close()


if __name__ == "__main__":
    # Smoke test: boot a worker, query a pool, run a couple of matches.
    def _design():
        return {
            "body": 4003, "armor": 5101, "computer": 5201,
            "shield": 5301, "engine": 5401,
            "weapons": [{"id": 6101, "n": 2}], "devices": [],
        }

    def _side(race, ships, x, siege=13):
        return {"race": race, "fleets": [{
            "id": 100 + x, "capital": True, "command": 5, "x": x, "y": 5000,
            "ships": ships, "design": _design(),
            "admiral": {"siege": siege, "detection": 11, "maneuver": 11,
                        "fleet_commanding": 37, "efficiency": 100},
        }]}

    with BattleSim() as sim:
        print("ping:", sim.ping())

        p1 = sim.pool(race=1)
        p7 = sim.pool(race=7)
        cat1 = {k: len(v) for k, v in p1["components"].items()}
        print(f"pool race1: {cat1}, hulls={len(p1['hulls'])}")
        print(f"pool race7 WPN={len(p7['components']['WPN'])} "
              f"(race1 WPN={len(p1['components']['WPN'])})")

        for atk_siege in (5, 13, 20):
            spec = {
                "seed": 555, "replicates": 30, "turn_cap": 1800,
                "attacker": _side(1, 12, 4500, siege=atk_siege),
                "defender": _side(1, 12, 5500, siege=13),
            }
            r = sim.match(spec)
            print(f"match atk_siege={atk_siege:2d}: win_rate={r['win_rate']:.3f} "
                  f"CI=[{r['wilson_lo']:.2f},{r['wilson_hi']:.2f}] "
                  f"completed={r['completed']} crashes={r['crashes']}")
    print("smoke test OK")
