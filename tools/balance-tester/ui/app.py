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
            rounds = s6.number_input("Rounds (max)", min_value=1, value=3, step=1)
            epsilon = st.slider("Epsilon (net-PP convergence)", 0.0, 1.0, 0.05, 0.01,
                                help="Rounds continue while the best attacker exploit's "
                                     "net PP keeps improving by more than epsilon × the "
                                     "combined army PP cost. Rounds is the max.")
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
        st.markdown("#### Current leaders — attacker library × defender library")
        atks = leaders.get("attacker_library") or (
            [leaders["best_exploit"]] if leaders.get("best_exploit") else [])
        dfns = leaders.get("defender_library") or (
            [d for d in [leaders.get("robust_defender")] if d])
        st.caption(f"attacker library: {len(atks)} · defender library: {len(dfns)}")
        R.render_config_browser(atks, dfns, key="live_lead",
                                default_a=max(0, len(atks) - 1),
                                default_d=max(0, len(dfns) - 1))
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


# ------------------------------- Loadout Lab ----------------------------------

_STANCES = ["NORMAL", "FORMATION", "PENETRATE", "FLANK", "RESERVE",
            "FREE", "ASSAULT", "STAND_GROUND"]


def _lab_pool():
    """Cached battle-sim worker + Pool for the Lab's dropdowns and validation."""
    import evaluator
    import pool as P
    sim = st.session_state.get("lab_sim")
    if sim is None or sim.proc.poll() is not None:
        sim = evaluator.BattleSim()
        st.session_state.lab_sim = sim
        st.session_state.lab_pool = P.Pool(sim)
    return st.session_state.lab_sim, st.session_state.lab_pool


def _clamp_idx(key: str, n: int):
    """Reset a stored selectbox index if the option list shrank (race/tech change)."""
    v = st.session_state.get(key)
    if isinstance(v, int) and v >= n:
        st.session_state[key] = 0


def view_loadout_lab():
    import genome as G
    import pool as P
    import report as RPT

    st.subheader("Loadout Lab")
    st.caption("Hand-build a defender, see every component stat, and validate it against a "
               "finished run's attacker library.")

    default = st.session_state.get("report_dir") or _run_dir()
    run_dir = st.sidebar.text_input("Attacker-library run dir", default, key="lab_run")
    abs_dir = run_dir if os.path.isabs(run_dir) else os.path.join(RUN_CWD, run_dir)
    report = R.load_json(os.path.join(abs_dir, "report.json"))
    lox = (report or {}).get("loadouts", {})
    lib = lox.get("attackers_encoded") or []
    lib_dec = lox.get("attackers") or []
    sc = (report or {}).get("scenario", {})
    if not lib:
        st.info("Choose a finished run directory whose report.json has an attacker library "
                "(loadouts.attackers_encoded) — run something in Configure & Run first.")
        return
    st.caption(f"Attacker library: **{len(lib)}** loadout(s) from `{run_dir}`")

    sim, pool = _lab_pool()

    base_seed = int(sc.get("base_seed", sc.get("seed", 12345)))
    turn_cap = int(sc.get("turn_cap", 1800))
    cc = st.columns(4)
    race = cc[0].selectbox("Defender race", range(len(R.RACES)),
                           format_func=lambda i: f"{i + 1} · {R.RACES[i]}", key="lab_race") + 1
    n_fleets = int(cc[1].number_input("Fleets", 1, 12, 2, key="lab_nf"))
    replicates = int(cc[2].number_input("Replicates", 1, 100,
                                        int(sc.get("replicates", 12)), key="lab_rep"))
    tech_cap = int(cc[3].number_input("Tech cap", 1, 9999999,
                                      int(sc.get("defender", {}).get("tech_cap", 999999)),
                                      key="lab_tc"))

    hulls = sorted(pool.hulls(race, tech_cap), key=lambda h: h["cost"])
    weapons = pool.weapons(race, tech_cap)
    armors = [pool.armor_by_id(race, a, tech_cap) for a in pool.armor_ids(race, tech_cap)]
    racials = P.race_racials(race)
    if not weapons or not armors:
        st.error("No tier-4/5 weapons or armor for this race/tech cap.")
        return

    fleets, cells_used = [], []
    for i in range(n_fleets):
        cap = (i == 0)
        with st.expander(f"{'★ Capital fleet' if cap else 'Fleet %d' % i}", expanded=cap):
            _clamp_idx(f"lab_f{i}_hull", len(hulls))
            _clamp_idx(f"lab_f{i}_w", len(weapons))
            _clamp_idx(f"lab_f{i}_a", len(armors))
            hk = st.selectbox(
                "Hull", range(len(hulls)), index=len(hulls) - 1, key=f"lab_f{i}_hull",
                format_func=lambda k: "%s · class %s · %s PP · %sW/%sD" % (
                    hulls[k]["name"], hulls[k]["class"], format(hulls[k]["cost"], ","),
                    hulls[k]["weapon_slots"], hulls[k]["device_slots"]))
            h = hulls[hk]
            wk = st.selectbox("Weapon (homogeneous across all slots)", range(len(weapons)),
                              format_func=lambda k: weapons[k]["name"], key=f"lab_f{i}_w")
            w = weapons[wk]
            st.caption("⚔ " + R.weapon_stat_line(w))
            ak = st.selectbox("Armor", range(len(armors)),
                              format_func=lambda k: armors[k]["name"], key=f"lab_f{i}_a")
            a = armors[ak]
            st.caption("🛡 " + R.armor_stat_line(a))

            elig = pool.eligible_devices(race, h.get("class", 1), w, a, tech_cap)
            elig_ids = [d["id"] for d in elig]
            elig_lbl = {d["id"]: "%s — %s" % (d["name"], R.device_stat_line(d)) for d in elig}
            dkey = f"lab_f{i}_dev"
            if dkey in st.session_state:        # drop now-ineligible picks
                st.session_state[dkey] = [d for d in st.session_state[dkey] if d in elig_ids]
            picks = st.multiselect("Devices (eligible only; up to %d slots)" % h["device_slots"],
                                   elig_ids, format_func=lambda d: elig_lbl.get(d, str(d)), key=dkey)
            if len(picks) > h["device_slots"]:
                st.warning("More devices than the %d slots — extra ignored." % h["device_slots"])
                picks = picks[:h["device_slots"]]

            st.markdown("Commander")
            pc = st.columns(4)
            bb = pc[0].slider("siege", -2, 2, 2 if cap else 0, key=f"lab_f{i}_bb")
            det = pc[1].slider("detect", -2, 2, 0, key=f"lab_f{i}_det")
            man = pc[2].slider("maneuver", -2, 2, -1 if cap else 0, key=f"lab_f{i}_man")
            fc = pc[3].slider("fleet cmd", -2, 2, -1 if cap else 0, key=f"lab_f{i}_fc")
            st.caption("resolved → siege %d · det %d · man %d · fc %d (Σ %+d; search uses net-zero)"
                       % (P.BB_VAL[bb], P.DET_VAL[det], P.MAN_VAL[man], P.FC_VAL[fc], bb + det + man + fc))
            ab = st.columns(3)
            sp = ab[0].selectbox("Special", P.SPECIAL_ABILITIES,
                                 format_func=lambda s: R.SPECIAL_ABILITIES.get(s, str(s)),
                                 key=f"lab_f{i}_sp")
            if racials:
                if st.session_state.get(f"lab_f{i}_rc") not in racials:
                    st.session_state.pop(f"lab_f{i}_rc", None)
                rc = ab[1].selectbox("Racial", racials,
                                     format_func=lambda s: R.RACIAL_ABILITIES.get(s, str(s)),
                                     key=f"lab_f{i}_rc")
            else:
                ab[1].caption("no racials")
                rc = -1
            stance = ab[2].selectbox("Stance", range(8), index=5,
                                     format_func=lambda s: _STANCES[s], key=f"lab_f{i}_st")

            pos = st.columns(3)
            if cap:
                cell = tuple(P.DEF_CAPITAL)
                pos[0].caption("cell: capital pinned @ %s" % (cell,))
            else:
                # value-based selectboxes (options ARE the coords) — pre-seed a distinct
                # default y per fleet so non-capital fleets don't collide on first render.
                ykey = f"lab_f{i}_y"
                st.session_state.setdefault(ykey, P.DEF_Y[min(i, len(P.DEF_Y) - 1)])
                cx = pos[0].selectbox("cell x", P.DEF_X, key=f"lab_f{i}_x")
                cy = pos[1].selectbox("cell y", P.DEF_Y, key=ykey)
                cell = (cx, cy)
            ships = int(pos[2].number_input("ships", 1, 41, 10, key=f"lab_f{i}_sh"))
            cells_used.append(cell)

            des = G.Design(hull=h["id"], armor=a["id"],
                           weapons=[w["id"]] * h["weapon_slots"], devices=list(picks))
            cmdr = G.Commander(bb=bb, det=det, man=man, fc=fc, special=sp, racial=rc)
            fleets.append(G.Fleet(design=des, commander=cmdr, command=stance,
                                  cell=cell, ships=ships, is_capital=cap))

    if len(set(cells_used)) != len(cells_used):
        st.error("Two fleets share a deploy cell — give each fleet a distinct cell.")
        return

    lo = G.Loadout(race=race, fleets=fleets)
    try:
        enc = G.encode_side(lo, pool, tech_cap, base_id=200)
        decoded_def = RPT.decode_loadout(lo, pool, tech_cap)
    except Exception as e:  # noqa: BLE001
        st.error("Could not encode the loadout: %s" % e)
        return
    st.caption("Defender PP cost: **%s**" % format(decoded_def.get("pp_cost", 0), ","))

    if st.button("✅ Validate vs attacker library", key="lab_go"):
        results = []
        with st.spinner("Running %d matchups…" % len(lib)):
            try:
                for ai, atk in enumerate(lib):
                    r = sim.match({"seed": base_seed, "replicates": replicates,
                                   "turn_cap": turn_cap, "attacker": atk, "defender": enc})
                    results.append({"label": "A%d" % ai, "def_win": 1.0 - r["win_rate"],
                                    "net_pp": -r["econ"], "fleets": r.get("fleets")})
            except Exception as e:  # noqa: BLE001
                st.error("Validation failed: %s" % e)
                return
        st.session_state.lab_out = {"results": results, "decoded_def": decoded_def}

    out = st.session_state.get("lab_out")
    if out:
        st.divider()
        st.markdown("### Validation — defender vs each attacker")
        R.render_validation_results(out["results"])
        labels = [r["label"] for r in out["results"]]
        pk = st.selectbox("Inspect matchup", range(len(labels)),
                          format_func=lambda i: labels[i], key="lab_inspect")
        chosen = out["results"][pk]
        dec_atk = lib_dec[pk] if pk < len(lib_dec) else None
        R.show_matchup(dec_atk, out["decoded_def"])
        fl = chosen.get("fleets") or {}
        d1, d2 = st.columns(2)
        with d1:
            R.render_fleet_damage(fl.get("attacker"), "Attacker " + chosen["label"])
        with d2:
            R.render_fleet_damage(fl.get("defender"), "Your defender")

    with st.expander("📖 Component & ability stats reference"):
        R.render_component_reference(pool.get(race, tech_cap)["components"])


# --------------------------------- router -------------------------------------

def main():
    st.set_page_config(page_title="Archspace Balance Tester", layout="wide")
    st.title("Archspace Balance Tester")

    if "view" not in st.session_state:
        rd = _run_dir()
        has_report = rd != DEFAULT_DIR and os.path.exists(
            os.path.join(RUN_CWD, rd, "report.json"))
        st.session_state.view = "View Report" if has_report else "Configure & Run"

    options = ["Configure & Run", "View Report", "Loadout Lab"]
    cur = st.session_state.view if st.session_state.view in options else options[0]
    view = st.sidebar.radio("View", options, index=options.index(cur), key="view")
    if view == "Configure & Run":
        view_configure()
    elif view == "View Report":
        view_report()
    else:
        view_loadout_lab()


if __name__ == "__main__":
    main()
