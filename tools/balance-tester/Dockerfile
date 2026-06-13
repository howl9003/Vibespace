# Archspace Balance Tester — self-contained image (engine + evaluator + orchestrator).
#
# Build a Linux image that compiles the real game engine + the battle-sim
# evaluator and bundles the Python orchestrator/UI. Runs anywhere Docker runs,
# including Windows via Docker Desktop (WSL2 backend) — the engine needs
# fork()/GNU pth/POSIX, which WSL2 provides natively, so CPU performance is
# close to bare-metal Linux (battles are CPU-bound and in-memory).
#
# Build (from the repo ROOT, so the context has archspace_source/ + tools/):
#   docker build -f tools/balance-tester/Dockerfile -t archspace-balance .
#
# Run an assess scenario, writing the report to a host folder:
#   # Windows PowerShell:
#   docker run --rm -v ${PWD}\runs:/app/tools/balance-tester/runs `
#       archspace-balance scenarios/siege_symmetric.yaml --mode assess --out runs/win
#   # Linux/macOS/WSL:
#   docker run --rm -v "$PWD/runs:/app/tools/balance-tester/runs" \
#       archspace-balance scenarios/siege_symmetric.yaml --mode assess --out runs/win
#
# The search scales with cores — give Docker Desktop / WSL2 plenty
# (Settings -> Resources, or %UserProfile%\.wslconfig).

FROM ubuntu:24.04
ENV DEBIAN_FRONTEND=noninteractive TZ=UTC

# Build toolchain + the engine's runtime libs (libpth = GNU Portable Threads,
# libmariadb = MySQL/MariaDB C client the engine links but never calls DB-free)
# + Python for the orchestrator. flex/bison only matter if the committed
# libs/util/lex.cc ever needs regenerating.
RUN apt-get update && apt-get install -y --no-install-recommends \
        g++ gcc make flex bison \
        libpth-dev libmariadb-dev \
        python3 python3-yaml \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && ln -sfn /usr/include/mariadb /usr/include/mysql

WORKDIR /app

# Engine + evaluator source, and the Python orchestrator/UI. Build artifacts
# (*.o/*.a, binaries) are excluded by the repo .dockerignore, so the image
# compiles everything fresh.
COPY archspace_source/archspace/src /app/archspace_source/archspace/src
COPY tools/balance-tester           /app/tools/balance-tester

# Normalize line endings before building. A Windows checkout with
# core.autocrlf=true (the Git-for-Windows default) rewrites the repo's
# committed-LF shell scripts and Makefiles to CRLF in the working tree; since the
# build context is the working tree, those CRLF files get COPYed in, and dash
# (/bin/sh on Ubuntu) then can't parse them — set_platform's `case "$1" in`
# becomes `in\r` and fails with "Syntax error: word unexpected (expecting in)".
# Strip the trailing CR from every COPYed text file so the build is identical
# regardless of how the host cloned the repo. `grep -I` skips binaries; `-Z`/`-0`
# keep paths with spaces safe.
RUN grep -rIlZ . /app/archspace_source /app/tools \
    | xargs -0r sed -i 's/\r$//'

# Build libs -> libarchspace.a, the engine objects, then the battle-sim binary.
RUN cd /app/archspace_source/archspace/src \
    && sh set_platform linux \
    && touch libs/util/lex.cc \
    && (cd libs && make) \
    && (cd apps/archspace && make archspace) \
    && (cd apps/battle-sim && make -f Makefile.Linux battle-sim) \
    && test -x apps/battle-sim/battle-sim \
    # smoke: run from the battle-sim dir so spikeA.config's relative script
    # paths resolve (same cwd the Python client uses). Fails the build if broken.
    && (cd apps/battle-sim && ./battle-sim spikeA.config --demo 42)

# The Python client finds the binary via the repo-relative default path, which
# is preserved above. Reports/run_state land under runs/ (mount it out).
WORKDIR /app/tools/balance-tester
VOLUME ["/app/tools/balance-tester/runs"]

ENTRYPOINT ["python3", "run.py"]
CMD ["scenarios/siege_symmetric.yaml", "--mode", "assess", "--out", "runs/latest"]
