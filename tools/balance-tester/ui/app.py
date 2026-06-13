"""Streamlit UI for the Archspace balance-tester.

Two views, selected from the sidebar:
  * Configure & Run — enter/validate every scenario parameter, launch a run as a
    subprocess (python3 run.py …), watch live progress + the leading/sampled
    configurations on an in-game-style battlefield board, then jump to the report.
  * View Report — load a finished (or in-progress) run dir and render verdict,
    payoff matrix, convergence, and the graphical configuration viewer.

Backward-compatible: `streamlit run ui/app.py -- runs/win` opens that report
directly. Read-only over runs/ except that Configure & Run launches run.py.
"""

from __future__ import annotations

import os
import re
import subprocess
import sys
import time

import yaml
import streamlit as st

import render as R

# the orchestrator package (evaluator/pool/…) lives one level up from ui/
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

RUN_CWD = "/app/tools/balance-tester"
DEFAULT_DIR = "runs/latest"


def _run_dir() -> str:
    for a in sys.argv[1:]:
        if not a.startswith("-"):
            return a
    return DEFAULT_DIR


# ----------------------------- Configure & Run --------------------------------

def _side_inputs(label: str, key: str):
    st.markdown(f"**{label}**")
    race = st.selectbox("Race", range(len(R.RACES)),
                        format_func=lambda i: f"{i + 1} · {R.RACES[i]}", key=f"{key}_race")
    unlimited = st.checkbox("Unlimited PP", value=False, key=f"{key}_unl")
    pp = st.number_input("PP budget", min_value=1, value=200000, step=1000,
                         disabled=unlimited, key=f"{key}_pp")
    tech = st.number_input("Tech cap", min_value=1, value=999999, step=1, key=f"{key}_tech")
    fleets = st.number_input("Max fleets", min_value=1, max_value=20, value=3, key=f"{key}_fl")
    ships = st.number_input("Max ships / fleet", min_value=1, max_value=41, value=30,
                            key=f"{key}_sh")
    return {"race": int(race) + 1,
            "pp_budget": "unlimited" if unlimited else int(pp),
            "tech_cap": int(tech), "max_fleets": int(fleets),
            "max_ships_per_fleet": int(ships)}


def _validate(doc: dict, mode: str) -> list:
    errs = []
    for who in ("attacker", "defender"):
        s = doc[who]
        if not (1 <= s["max_fleets"] <= 20):
            errs.append(f"{who}: max_fleets must be 1..20")
        if not (1 <= s["max_ships_per_fleet"] <= 41):
            errs.append(f"{who}: max_ships_per_fleet must be 1..41")
        if s["pp_budget"] != "unlimited" and s["pp_budget"] <= 0:
            errs.append(f"{who}: pp_budget must be > 0")
        if s["tech_cap"] < 1:
            errs.append(f"{who}: tech_cap must be >= 1")
    if doc["turn_cap"] < 100:
        errs.append("turn_cap must be >= 100")
    m = doc["mode"]
    for k in ("population", "generations", "mu", "lam", "replicates"):
        if m[k] < 1:
            errs.append(f"mode.{k} must be >= 1")
    if mode == "stackelberg":
        if m["rounds"] < 1:
            errs.append("mode.rounds must be >= 1")
        if not (0.0 <= m["epsilon"] <= 1.0):
            errs.append("mode.epsilon must be 0..1")
    return errs


def _launch(doc: dict, mode: str):
    slug = re.sub(r"[^A-Za-z0-9_.-]+", "_", doc["name"]).strip("_") or "run"
    if st.session_state.get("ts_suffix", True):
        slug = f"{slug}-{time.strftime('%Y%m%d-%H%M%S')}"
    outdir = os.path.join("runs", slug)
    abs_out = os.path.join(RUN_CWD, outdir)
    os.makedirs(abs_out, exist_ok=True)
    yaml_path = os.path.join(outdir, "scenario.yaml")
    with open(os.path.join(RUN_CWD, yaml_path), "w") as f:
        yaml.safe_dump(doc, f, sort_keys=False)
    log = open(os.path.join(abs_out, "run.log"), "w")
    proc = subprocess.Popen(
        ["python3", "run.py", yaml_path, "--mode", mode, "--out", outdir],
        cwd=RUN_CWD, stdout=log, stderr=subprocess.STDOUT)
    st.session_state.proc = proc
    st.session_state.outdir = outdir
    st.session_state.launched = True
    st.session_state.done_handled = False


def view_configure():
    st.subheader("Configure & Run")

    running = (st.session_state.get("proc") is not None
               and st.session_state.proc.poll() is None)

    # outside the form so toggling reruns the conditional fields
    mode = st.selectbox("Mode", ["stackelberg", "assess"], key="mode",
                        help="assess = random populations → payoff matrix (fast). "
                             "stackelberg = double-oracle search (slower).")
    mirror = st.checkbox("Defender mirrors attacker", value=True, key="mirror")
    st.checkbox("Append timestamp to run folder", value=True, key="ts_suffix")

    with st.form("scenario"):
        name = st.text_input("Scenario name", value="siege_symmetric_human")
        c1, c2 = st.columns(2)
        with c1:
            seed = st.number_input("Seed", min_value=0, value=12345, step=1)
        with c2:
            turn_cap = st.number_input("Turn cap", min_value=100, value=1800, step=100)

        ca, cd = st.columns(2)
        with ca:
            atk = _side_inputs("Attacker", "atk")
        with cd:
            if mirror:
                st.markdown("**Defender**")
                st.caption("mirrors the attacker (uncheck above to set separately)")
                dfn = None
            else:
                dfn = _side_inputs("Defender", "def")

        st.markdown("**Search**")
        s1, s2, s3 = st.columns(3)
        population = s1.number_input("Population", min_value=1, value=5, step=1)
        generations = s2.number_input("Generations", min_value=1, value=8, step=1)
        replicates = s3.number_input("Replicates", min_value=1, value=15, step=1)
        s4, s5, s6 = st.columns(3)
        mu = s4.number_input("mu (parents)", min_value=1, value=5, step=1)
        lam = s5.number_input("lam (offspring)", min_value=1, value=8, step=1)
        if mode == "stackelberg":
            rounds = s6.number_input("Rounds", min_value=1, value=3, step=1)
            epsilon = st.slider("Epsilon (convergence)", 0.0, 1.0, 0.05, 0.01)
        else:
            rounds, epsilon = 3, 0.05

        submitted = st.form_submit_button("🚀 Launch run", disabled=running)

    if submitted:
        if running:
            st.error("A run is already in progress this session — wait or cancel it.")
            return
        doc = {
            "name": name, "run": mode, "seed": int(seed), "turn_cap": int(turn_cap),
            "attacker": atk, "defender": (atk if mirror else dfn),
            "mode": {"population": int(population), "generations": int(generations),
                     "mu": int(mu), "lam": int(lam), "replicates": int(replicates),
                     "rounds": int(rounds), "epsilon": float(epsilon)},
        }
        errs = _validate(doc, mode)
        if mu > population:
            st.warning("mu > population — unusual; the oracle will resample to fill.")
        if lam < mu:
            st.warning("lam < mu — weak selection pressure.")
        if mode == "stackelberg" and rounds * generations * replicates > 4000:
            st.warning("Large search (rounds×generations×replicates) — may take a while; "
                       "give Docker enough CPU.")
        if errs:
            for e in errs:
                st.error(e)
            return
        _launch(doc, mode)
        st.rerun()

    if st.session_state.get("launched"):
        _live_panel()


@st.fragment(run_every=1.0)
def _live_panel():
    outdir = st.session_state.get("outdir")
    if not outdir:
        return
    abs_out = os.path.join(RUN_CWD, outdir)
    state = R.load_json(os.path.join(abs_out, "run_state.json"))
    report = R.load_json(os.path.join(abs_out, "report.json"))
    proc = st.session_state.get("proc")
    alive = proc is not None and proc.poll() is None

    st.divider()
    st.markdown(f"### Live run · `{outdir}`")
    if st.button("⏹ Cancel run", disabled=not alive, key="cancel"):
        try:
            proc.terminate()
            proc.wait(timeout=5)
        except Exception:
            try:
                proc.kill()
            except Exception:
                pass
        st.session_state.launched = False
        st.rerun()
        return

    R.render_progress(state)

    # leading (stackelberg) or sampled (assess) configurations, live
    leaders = (state or {}).get("leaders")
    configs = (state or {}).get("configs")
    if leaders:
        st.markdown("#### Current leaders")
        atks = leaders.get("attacker_library") or (
            [leaders["best_exploit"]] if leaders.get("best_exploit") else [])
        dfns = [d for d in [leaders.get("robust_defender")] if d]
        R.render_config_browser(atks, dfns, key="live_lead",
                                default_a=max(0, len(atks) - 1))
    elif configs:
        st.markdown("#### Sampled configurations")
        R.render_config_browser(configs.get("attackers"), configs.get("defenders"),
                                key="live_cfg")

    done = state and state.get("phase") == "done"
    if done and report and not st.session_state.get("done_handled"):
        st.session_state.done_handled = True
        st.session_state.launched = False
        st.session_state.report_dir = outdir
        st.session_state.view = "View Report"
        st.success("Run complete — opening the report.")
        st.rerun()
    elif not alive and not done:
        st.warning("The run process exited before finishing — check run.log in the run "
                   "folder.")


# ------------------------------- View Report ----------------------------------

def view_report():
    default = st.session_state.get("report_dir") or _run_dir()
    run_dir = st.sidebar.text_input("Run directory", default)
    abs_dir = run_dir if os.path.isabs(run_dir) else os.path.join(RUN_CWD, run_dir)
    report = R.load_json(os.path.join(abs_dir, "report.json"))
    state = R.load_json(os.path.join(abs_dir, "run_state.json"))

    if state and state.get("phase") != "done":
        st.warning(f"Run in progress: {state.get('phase')} ({state.get('elapsed', 0)}s)")
        if st.button("Refresh"):
            st.rerun()
        R.render_progress(state)
        return
    if not report:
        st.info("No report.json in that directory yet.")
        st.json(state or {})
        return
    R.render_report(report)
    _replay_panel(report)


def _replay_panel(report: dict):
    lo = report.get("loadouts", {})
    a_enc = lo.get("attackers_encoded") or []
    d_enc = lo.get("defenders_encoded") or []
    if not a_enc or not d_enc:
        return
    sc = report.get("scenario", {})
    st.subheader("Battle replay")
    st.caption("Reproduce one deterministic battle (attacker × defender × replicate) and "
               "play it back in the in-game battle viewer.")
    c1, c2, c3 = st.columns(3)
    ai = c1.selectbox("Attacker", range(len(a_enc)), format_func=lambda i: f"A{i}", key="rp_a")
    di = c2.selectbox("Defender", range(len(d_enc)), format_func=lambda i: f"D{i}", key="rp_d")
    reps = max(1, int(sc.get("replicates", 20)))
    k = c3.selectbox("Replicate (seed)", range(reps), key="rp_k")

    if st.button("▶ Play battle", key="rp_go"):
        import evaluator
        with st.spinner("Running the battle…"):
            try:
                with evaluator.BattleSim() as sim:
                    res = sim.replay(a_enc[ai], d_enc[di], sc.get("base_seed", 12345),
                                     int(k), sc.get("turn_cap", 1800))
            except Exception as e:  # noqa: BLE001
                st.error(f"Replay failed: {e}")
                return
        if not res.get("ok"):
            st.error(f"Replay error: {res}")
            return
        st.session_state.replay_res = {
            "res": res, "k": int(k),
            "races": [sc.get("attacker", {}).get("race", 1),
                      sc.get("defender", {}).get("race", 1)]}

    rp = st.session_state.get("replay_res")
    if rp:
        res = rp["res"]
        st.caption(f"Result: attacker **{'WINS' if res.get('win') else 'LOSES'}** · "
                   f"{res.get('turns')} turns · replicate {rp['k']}")
        R.replay_embed(res.get("log", ""), rp["races"])


# --------------------------------- router -------------------------------------

def main():
    st.set_page_config(page_title="Archspace Balance Tester", layout="wide")
    st.title("Archspace Balance Tester")

    if "view" not in st.session_state:
        rd = _run_dir()
        has_report = rd != DEFAULT_DIR and os.path.exists(
            os.path.join(RUN_CWD, rd, "report.json"))
        st.session_state.view = "View Report" if has_report else "Configure & Run"

    view = st.sidebar.radio("View", ["Configure & Run", "View Report"],
                            index=0 if st.session_state.view == "Configure & Run" else 1,
                            key="view")
    if view == "Configure & Run":
        view_configure()
    else:
        view_report()


if __name__ == "__main__":
    main()
