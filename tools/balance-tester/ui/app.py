"""Optional Streamlit analysis dashboard over a run's report.json / run_state.json.

Read-only: it never calls the engine. Requires `streamlit` (pip install streamlit);
uses only Streamlit's native widgets (no pandas/plotly) to stay dependency-light.

    streamlit run ui/app.py -- runs/symmetric
"""

from __future__ import annotations

import json
import os
import sys

import streamlit as st


def _load(path):
    try:
        with open(path) as f:
            return json.load(f)
    except (OSError, json.JSONDecodeError):
        return None


def _run_dir() -> str:
    # args after `--` when launched via `streamlit run app.py -- <dir>`
    for a in sys.argv[1:]:
        if not a.startswith("-"):
            return a
    return "runs/latest"


def main():
    st.set_page_config(page_title="Archspace Balance Tester", layout="wide")
    run_dir = st.sidebar.text_input("Run directory", _run_dir())
    report = _load(os.path.join(run_dir, "report.json"))
    state = _load(os.path.join(run_dir, "run_state.json"))

    if state and state.get("phase") != "done":
        st.sidebar.warning(f"run in progress: {state.get('phase')} "
                           f"({state.get('elapsed', 0)}s)")
        st.sidebar.button("refresh")

    if not report:
        st.info("No report.json yet. Showing live state.")
        st.json(state or {})
        return

    sc = report["scenario"]
    st.title(f"Balance report — {sc['name']}")

    # --- verdict card -------------------------------------------------------
    an = report.get("analysis", {})
    c1, c2, c3 = st.columns(3)
    c1.metric("Attacker eq. win-rate", f"{an.get('value', 0):.3f}")
    c2.metric("Meta-game", an.get("kind", "?"))
    c3.metric("Engine crashes", report.get("anomalies", {}).get("crashes", 0))
    st.markdown(f"**Verdict:** {report.get('verdict', '')}")

    # --- payoff matrix ------------------------------------------------------
    M = report.get("matrix")
    if M:
        st.subheader("Payoff matrix — attacker win-rate (rows=attackers, cols=defenders)")
        header = "| A\\D | " + " | ".join(f"D{j}" for j in range(len(M[0]))) + " |\n"
        header += "|" + "---|" * (len(M[0]) + 1) + "\n"
        body = "".join("| A%d | " % i + " | ".join(f"{v:.2f}" for v in row) + " |\n"
                       for i, row in enumerate(M))
        st.markdown(header + body)

    # --- convergence --------------------------------------------------------
    hist = report.get("history") or (state or {}).get("history")
    if hist:
        st.subheader("Stackelberg convergence")
        st.line_chart({
            "best exploit (attacker win)": [h["best_exploit_atkwin"] for h in hist],
            "robust worst-case (defender win)": [h["robust_worstcase_defwin"] for h in hist],
        })

    # --- loadouts -----------------------------------------------------------
    lo = report.get("loadouts", {})
    if lo.get("robust_defender"):
        st.subheader("Robust defender (least-exploitable)")
        st.json(lo["robust_defender"])
    for label, key in [("Attacker library / population", "attackers"),
                       ("Defender population", "defenders")]:
        if lo.get(key):
            with st.expander(label):
                for k, ld in enumerate(lo[key]):
                    st.markdown(f"**#{k}** — pp_cost {ld['pp_cost']}")
                    st.json(ld)


if __name__ == "__main__":
    main()
