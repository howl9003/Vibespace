"""Worker pool of battle-sim processes for cell-level parallelism.

Each worker is its own engine process with its own stdin/stdout, so one thread
per worker runs matches truly in parallel — the GIL is released while a thread
blocks on its worker's pipe / the C++ side runs the battle. Used to evaluate many
*independent* matchups at once (payoff-matrix cells, or an oracle generation's
candidates).

Determinism is unaffected: every match carries a fixed (seed, replicate) and the
reductions over cells are order-independent, so only wall-clock changes. To keep
the shared Pool cache read-only during a parallel map, warm it single-threaded
first (the callers do this).
"""

from __future__ import annotations

import os
import threading
from typing import Callable, List

from evaluator import BattleSim


def default_workers() -> int:
    """Worker count: $BALANCE_WORKERS, else the CPU count (capped at 32)."""
    env = os.environ.get("BALANCE_WORKERS")
    if env:
        try:
            return max(1, int(env))
        except ValueError:
            pass
    return max(1, min(32, os.cpu_count() or 1))


class MatchPool:
    """A fixed set of long-lived battle-sim workers with a work-stealing `map`."""

    def __init__(self, n: int = 0) -> None:
        self.n = n or default_workers()
        self.sims = [BattleSim() for _ in range(self.n)]

    def __enter__(self) -> "MatchPool":
        return self

    def __exit__(self, *exc: object) -> None:
        self.close()

    def map(self, jobs: list, fn: Callable) -> List:
        """Run fn(worker_sim, job) over jobs across the pool; results in job order.

        A worker that dies takes its current job's result to None (filter if needed).
        """
        results: List = [None] * len(jobs)
        nxt = [0]
        lock = threading.Lock()

        def loop(sim):
            while True:
                with lock:
                    i = nxt[0]
                    if i >= len(jobs):
                        return
                    nxt[0] += 1
                try:
                    results[i] = fn(sim, jobs[i])
                except Exception:  # noqa: BLE001 — one bad job shouldn't kill the run
                    results[i] = None

        threads = [threading.Thread(target=loop, args=(s,), daemon=True)
                   for s in self.sims]
        for t in threads:
            t.start()
        for t in threads:
            t.join()
        return results

    def close(self) -> None:
        for s in self.sims:
            try:
                s.close()
            except Exception:  # noqa: BLE001
                pass
