"""Live TUI dashboard (stdlib only) over a run's run_state.json / report.json.

Read-only: it never touches the engine. Renders the search's live state - phase,
elapsed, the Stackelberg convergence trajectory (robust worst-case def-win and
best-exploit attacker win per round), attacker-library size - and the final
verdict once report.json appears. Falls back to plain log lines when stdout is
not a TTY (CI / piped).

    python3 ui/tui.py runs/symmetric            # follow a live/finished run
"""

from __future__ import annotations

import json
import os
import sys
import time

ESC = "\033["


def _load(path):
    try:
        with open(path) as f:
            return json.load(f)
    except (OSError, json.JSONDecodeError):
        return None


def _bar(frac, width=24):
    frac = max(0.0, min(1.0, frac))
    n = int(round(frac * width))
    return "#" * n + "-" * (width - n)


def _render(state, report):
    L = []
    name = (state or {}).get("scenario") or (report or {}).get("scenario", {}).get("name", "?")
    L.append(f"  ARCHSPACE BALANCE TESTER - {name}")
    L.append("  " + "=" * 56)
    phase = (state or {}).get("phase", "?")
    mode = (state or {}).get("mode", "?")
    elapsed = (state or {}).get("elapsed", 0.0)
    L.append(f"  mode: {mode:<12s} phase: {phase:<16s} elapsed: {elapsed:>6.1f}s")
    if state and "library_size" in state:
        L.append(f"  attacker library: {state['library_size']}")
    L.append("")

    history = (state or {}).get("history") or (report or {}).get("history") or []
    if history:
        L.append("  Stackelberg convergence (best new attacker exploit win):")
        for h in history:
            ex = h["best_exploit_atkwin"]
            L.append(f"    round {h['round']}: exploit [{_bar(ex)}] {ex:.2f}   "
                     f"robust def-win {h['robust_worstcase_defwin']:.2f}")
        L.append("")

    if report:
        an = report.get("analysis", {})
        L.append("  RESULT")
        L.append("  " + "-" * 56)
        for line in report.get("verdict", "").replace("**", "").split(". "):
            if line.strip():
                L.append(f"  {line.strip()}")
        if an:
            L.append(f"  meta-game: {an.get('kind')}   attacker eq win-rate: {an.get('value', 0):.3f}")
        L.append(f"  crashes: {report.get('anomalies', {}).get('crashes', 0)}")
    return "\n".join(L)


def main():
    if len(sys.argv) < 2:
        print("usage: python3 ui/tui.py <run-dir>")
        sys.exit(1)
    run_dir = sys.argv[1]
    state_path = os.path.join(run_dir, "run_state.json")
    report_path = os.path.join(run_dir, "report.json")
    is_tty = sys.stdout.isatty()

    last_plain = None
    while True:
        state = _load(state_path)
        report = _load(report_path)
        frame = _render(state, report)

        if is_tty:
            sys.stdout.write(ESC + "2J" + ESC + "H")   # clear + home
            sys.stdout.write(frame + "\n")
            sys.stdout.flush()
        else:
            # plain mode: only print when something changed
            key = (state or {}).get("phase"), len((state or {}).get("history") or []), bool(report)
            if key != last_plain:
                print(frame + "\n" + "-" * 58)
                last_plain = key

        done = state and state.get("phase") == "done"
        if done and report:
            if is_tty:
                print("\n  [run complete]")
            break
        time.sleep(1.0)


if __name__ == "__main__":
    main()
