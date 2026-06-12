# Archspace Balance Tester

Finds out whether a **siege** is fair *under optimized play*: it pits "agents"
(full battle configurations — ship designs, commanders, fleet composition,
deployment, tactics) against each other using the **real in-game `CBattle`
engine**, then searches for the strongest attacker exploits and the
least-exploitable defender, and reports who wins.

It is a **standalone tool** — it is *not* wired into the game image or the
`production` deploy. It links the real engine objects so the mechanics are
genuine, not a re-implementation.

## Layout

```
apps/battle-sim/        (C++)   DB-free evaluator: boots the engine without
                                MariaDB and serves a JSONL protocol on stdin/
                                stdout (pool queries + battle matches). Each
                                battle runs in a forked child (crash isolation).
tools/balance-tester/   (Py)    orchestrator:
  evaluator.py                   client that drives a battle-sim worker
  pool.py                        POOL compiler + commander/deploy constants
  genome.py                      heterogeneous-fleet loadout, sampling, repair,
                                 mutation/crossover, MatchSpec encoding
  tournament.py                  bipartite payoff matrix (CRN)
  search.py                      (mu+lambda) best-response oracle + Stackelberg
                                 double-oracle
  analysis.py                    empirical-game solve + classify (pure Python)
  scenario.py / run.py           YAML scenarios + CLI runner -> report
  report.py                      report.md + report.json
  ui/tui.py                      live terminal dashboard (stdlib only)
  ui/app.py                      optional Streamlit dashboard
```

## Build the evaluator (once)

```sh
cd archspace_source/archspace/src
sh set_platform linux
(cd libs && make)
(cd apps/archspace && make archspace)          # builds the engine objects
(cd apps/battle-sim && make -f Makefile.Linux battle-sim)
```

This produces `apps/battle-sim/battle-sim`. The Python client finds it
automatically.

## Run on Windows (or anywhere) via Docker

The engine needs `fork()` / GNU `pth` / POSIX, so there is no native Windows
`.exe` — but a Docker image runs the whole thing unchanged. On Windows use
**Docker Desktop (WSL2 backend)**; performance is close to bare-metal Linux
because battles are CPU-bound and in-memory (the only real Docker-on-Windows
slowdown — cross-boundary filesystem I/O — doesn't apply, since everything runs
inside the image). Give Docker Desktop / WSL2 plenty of cores — the search
scales with them.

```sh
# build (from the repo ROOT):
docker build -f tools/balance-tester/Dockerfile -t archspace-balance .

# run a scenario, writing the report to a host folder:
#   PowerShell:
docker run --rm -v ${PWD}\runs:/app/tools/balance-tester/runs `
    archspace-balance scenarios/siege_symmetric.yaml --mode assess --out runs/win
#   Linux / macOS / WSL:
docker run --rm -v "$PWD/runs:/app/tools/balance-tester/runs" \
    archspace-balance scenarios/siege_symmetric.yaml --mode assess --out runs/win
```

The report lands in `./runs/win/report.md`. To open a shell or run the TUI,
override the entrypoint: `docker run --rm -it --entrypoint bash archspace-balance`.

## Run (native Linux)

```sh
cd tools/balance-tester
python3 run.py scenarios/siege_symmetric.yaml --out runs/symmetric
# live dashboard in another terminal:
python3 ui/tui.py runs/symmetric
# optional rich dashboard (needs: pip install streamlit):
streamlit run ui/app.py -- runs/symmetric
```

Outputs `runs/<name>/report.md`, `report.json`, and a live `run_state.json`.

### Modes

- **assess** — evaluate random (or given) populations of attacker + defender
  loadouts, build the bipartite payoff matrix, classify the meta-game. No search.
- **stackelberg** (default) — attacker best-response vs a fixed defender seeds an
  attacker library; then alternate a **maximin-robust defender** oracle (best
  worst-case over the attacker gauntlet) with a fresh attacker best-response,
  tracking exploitability until the best new attacker can't beat the robust
  defender by more than `epsilon`.

### Scenario YAML

```yaml
name: siege_symmetric_human
run: stackelberg          # assess | stackelberg
seed: 12345
turn_cap: 1800
attacker: {race: 1, pp_budget: 200000, max_fleets: 3, max_ships_per_fleet: 30}
defender: {race: 1, pp_budget: 200000, max_fleets: 3, max_ships_per_fleet: 30}
mode:  {population: 5, generations: 8, mu: 5, lam: 8, replicates: 15,
        rounds: 3, epsilon: 0.05}
```

## What the genome optimizes (all four levers)

- **Ship design** per fleet: hull size, armor, weapons (+ devices). Computer /
  shield / engine are *pinned* to the best available part (pure upgrade ladders).
- **Fleet composition**: a list of independent, heterogeneous fleets — each its
  own design, commander, stance, deploy cell, and ship count.
- **Commander** per fleet: a net-zero point allocation over
  battle_bonus/detection/maneuver/fleet_commanding + a special ability; the
  capital commander locks battle_bonus and broadcasts armada class A.
- **Tactics**: per-fleet stance (NORMAL…STAND_GROUND) and a distinct cell on the
  real deploy grid (attacker x∈[1000,3000], defender x∈[7000,9000], step 200,
  no two same-side fleets overlapping, capital pinned).

## Faithfulness notes

- Components/weapons/devices cost **0 PP** — the budget constrains hull-size ×
  count only. Loadout quality is therefore maxed on affordable hulls.
- Battles are deterministic given `(seed, replicate)`; the tournament reuses a
  shared base seed across compared cells (Common Random Numbers).
- Hitting the `turn_cap` counts as defender-holds → attacker-fail.
- An adversarial loadout that crashes the engine is **counted** (`crashes`), not
  fatal — the forked-child fault boundary keeps the worker alive.
