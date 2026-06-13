"""Shared rendering for the balance-tester Streamlit UI.

IO-free-ish helpers that take already-loaded report.json / run_state.json dicts and
draw them with native Streamlit widgets plus a couple of self-contained HTML/SVG
panels (via streamlit.components.v1) — dependency-light: no pandas/plotly.

The configuration viewer reuses the in-game battle assets (ship_cap.gif /
ship_set.gif / battle_back.gif + per-race small_symbol.gif), placing fleets in the
real battlefield coordinate space (0..10000) the in-game battle-replay.js uses, so
the picture matches the game. Game names for hulls/components are shown when the
decode carries them (baked in at run time once the engine `pool` query exposes
names); otherwise it falls back to the numeric id. Commander/special/racial/armada
names come from the small static maps below (sourced from the engine's
admiral.cc / admiral.h enums).
"""

from __future__ import annotations

import base64
import html
import json
import os
from functools import lru_cache
from typing import List, Optional

import streamlit as st
import streamlit.components.v1 as components


# --- static id -> name maps (race.en order; admiral.cc / admiral.h enums) -----

RACES = ["Human", "Targoid", "Buckaneer", "Tecanoid", "Evintos",
         "Agerus", "Bosalian", "Xeloss", "Xerusian", "Xesperados"]
# lowercase folder names under image/as_game/race/<name>/
RACE_FOLDER = [r.lower() for r in RACES]

# CAdmiral::mSpecialAbilityName[] (admiral.cc) — combat specialists; -1 = none.
SPECIAL_ABILITIES = {
    0: "Engineering Specialist", 1: "Shield System Specialist",
    2: "Missile Specialist", 3: "Ballistic Expert", 4: "Energy System Specialist",
}
# CAdmiral RA_* enum (admiral.h) -> mRacialAbilityName[] (admiral.cc); -1 = none.
RACIAL_ABILITIES = {
    0: "Irrational Tactics", 1: "Intuition", 2: "Lone Wolf",
    3: "DNA Poison Replicater", 4: "Breeder Male", 5: "Clonal Double",
    6: "Xenophobic Fanatic", 7: "Mental Giant", 8: "Artifact Crystal",
    9: "Psychic Progenitor", 10: "Artifact Cooling Engine", 11: "Lying Dormant",
    12: "Missile Craters", 13: "Meteor Drones", 14: "Cyber Scan Unit",
    15: "Jury Rigger", 16: "Pattern Broadcaster", 17: "Famous Privateer",
    18: "Commerce King", 19: "Retreat Shield", 20: "Genetic Throwback",
    21: "Rigid Thinking", 22: "Scavenger", 23: "Blitzkrieg",
}
ARMADA_CLASS = {0: "A", 1: "B", 2: "C", 3: "D"}

# report.py CMD_NAMES order -> board abbreviations (as-deploy.js style)
STANCE_ABBR = {
    "NORMAL": "NRM", "FORMATION": "FRM", "PENETRATE": "PEN", "FLANK": "FLK",
    "RESERVE": "RSV", "FREE": "FRE", "ASSAULT": "ASL", "STAND_GROUND": "STG",
}

# engine stat ceilings for commander mini-bars (pool.py VAL tables)
_CMDR_MAX = {"siege": 20, "detection": 18, "maneuver": 18, "fleet_commanding": 45}


def race_name(rid: int) -> str:
    return RACES[rid - 1] if 1 <= rid <= len(RACES) else f"race {rid}"


def load_json(path: str):
    try:
        with open(path) as f:
            return json.load(f)
    except (OSError, json.JSONDecodeError):
        return None


# --- in-game image assets, inlined as data URIs -------------------------------

_ASSETS = os.path.join(os.path.dirname(__file__), "assets")
# 1x1 transparent gif so a missing asset degrades instead of breaking the page
_BLANK = ("data:image/gif;base64,"
          "R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7")


@lru_cache(maxsize=256)
def asset_uri(relpath: str) -> str:
    """Base64 data: URI for an asset under ui/assets/ (cached). Blank if absent."""
    full = os.path.join(_ASSETS, relpath)
    try:
        with open(full, "rb") as f:
            return "data:image/gif;base64," + base64.b64encode(f.read()).decode()
    except OSError:
        return _BLANK


def _race_symbol(rid: int) -> str:
    folder = RACE_FOLDER[rid - 1] if 1 <= rid <= len(RACE_FOLDER) else "human"
    return asset_uri(os.path.join("image", "as_game", "race", folder, "small_symbol.gif"))


# --- name helpers (graceful fallback to id) -----------------------------------

def _hull_label(fl: dict) -> str:
    return fl.get("hull_name") or f"hull #{fl.get('hull')}"


def _named_list(ids, names) -> str:
    names = names or []
    out = []
    for i, x in enumerate(ids or []):
        nm = names[i] if i < len(names) and names[i] else None
        out.append(html.escape(nm) if nm else f"#{x}")
    return ", ".join(out) if out else "—"


# ============================ battlefield board ===============================

# board pixels; fleets live in battlefield coords x,y in [0..10000]
_BW, _BH = 660, 360


def _tx(x: int) -> float:
    return max(0, min(10000, x)) / 10000.0 * _BW


def _ty(y: int) -> float:
    # flip y like battle-replay.js (higher y renders upward)
    return (10000 - max(0, min(10000, y))) / 10000.0 * _BH


def _fleet_markers(decoded: dict, side: str) -> str:
    if not decoded:
        return ""
    cap_uri = asset_uri(os.path.join("image", "as_game", "fleet", "ship_cap.gif"))
    set_uri = asset_uri(os.path.join("image", "as_game", "fleet", "ship_set.gif"))
    tint = "#ff8844" if side == "attacker" else "#55bbff"
    out = []
    for k, fl in enumerate(decoded.get("fleets", [])):
        cx, cy = fl.get("cell", [0, 0])
        px, py = _tx(cx), _ty(cy)
        cap = fl.get("capital")
        uri = cap_uri if cap else set_uri
        w = 26 if cap else 20
        abbr = STANCE_ABBR.get(fl.get("stance", ""), fl.get("stance", "")[:3])
        label = f"{'★' if cap else 'F%d' % k} {fl.get('ships', 0)}× · {abbr}"
        out.append(
            f'<div class="fl" style="left:{px:.0f}px;top:{py:.0f}px">'
            f'<img src="{uri}" width="{w}" style="filter:drop-shadow(0 0 3px {tint})"/>'
            f'<span style="color:{tint}">{html.escape(label)}</span></div>')
    return "".join(out)


def _side_header(decoded: dict, side: str) -> str:
    if not decoded:
        return ""
    rid = decoded.get("race", 1)
    align = "left" if side == "attacker" else "right"
    color = "#ff8844" if side == "attacker" else "#55bbff"
    return (
        f'<div class="hdr" style="text-align:{align};color:{color}">'
        f'<img src="{_race_symbol(rid)}" height="16" style="vertical-align:middle"/> '
        f'<b>{html.escape(side.upper())}</b> · {html.escape(race_name(rid))} · '
        f'pp {decoded.get("pp_cost", 0):,}</div>')


def battlefield_html(atk: dict, dfn: dict) -> str:
    bg = asset_uri(os.path.join("image", "as_game", "fleet", "battle_back610.gif"))
    bg2 = asset_uri(os.path.join("image", "as_game", "fleet", "battle_back.gif"))
    bg_uri = bg if bg != _BLANK else bg2
    css = """
    <style>
      .wrap{font-family:'Times New Roman',serif;color:#ddd}
      .hdrs{display:flex;justify-content:space-between;margin:0 2px 4px}
      .hdr{font-size:13px}
      .board{position:relative;width:%dpx;height:%dpx;border:1px solid #333;
             background:#050510 center/cover no-repeat;overflow:hidden}
      .zone{position:absolute;top:0;height:100%%;opacity:.10}
      .za{left:10%%;width:20%%;background:#ff8844}
      .zd{left:70%%;width:20%%;background:#55bbff}
      .fl{position:absolute;transform:translate(-50%%,-50%%);text-align:center;
          white-space:nowrap}
      .fl span{display:block;font-size:10px;line-height:1.1;
               text-shadow:0 0 3px #000,0 0 3px #000}
      .cap{position:absolute;bottom:2px;font-size:10px;color:#888}
    </style>""" % (_BW, _BH)
    body = (
        '<div class="wrap"><div class="hdrs">'
        + _side_header(atk, "attacker") + _side_header(dfn, "defender")
        + '</div><div class="board" style="background-image:url(' + bg_uri + ')">'
        + '<div class="zone za"></div><div class="zone zd"></div>'
        + _fleet_markers(atk, "attacker") + _fleet_markers(dfn, "defender")
        + '<span class="cap" style="left:6px">attacker</span>'
        + '<span class="cap" style="right:6px">defender</span>'
        + '</div></div>')
    return css + body


# ============================ fleet config cards ==============================

def _bar(label: str, val, maxv: int, color: str) -> str:
    try:
        pct = max(0, min(100, round(100 * float(val) / maxv)))
    except (TypeError, ValueError):
        pct, val = 0, "—"
    return (
        f'<div class="brow"><span class="blab">{label}</span>'
        f'<span class="btrk"><span class="bfil" style="width:{pct}%;background:{color}"></span></span>'
        f'<span class="bval">{val}</span></div>')


def _commander_panel(c: dict) -> str:
    sp = c.get("special", -1)
    rc = c.get("racial", -1)
    sp_name = SPECIAL_ABILITIES.get(sp, "none") if sp is not None and sp >= 0 else "none"
    rc_name = RACIAL_ABILITIES.get(rc, "none") if rc is not None and rc >= 0 else "none"
    arm = c.get("armada", "-")
    arm_lbl = arm if isinstance(arm, str) else ARMADA_CLASS.get(arm, "-")
    pts = c.get("points", [0, 0, 0, 0])
    bars = (
        _bar("siege", c.get("siege"), _CMDR_MAX["siege"], "#ff7755")
        + _bar("detect", c.get("detection"), _CMDR_MAX["detection"], "#55bbff")
        + _bar("maneuver", c.get("maneuver"), _CMDR_MAX["maneuver"], "#88cc66")
        + _bar("fleet cmd", c.get("fleet_commanding"), _CMDR_MAX["fleet_commanding"], "#ccaa44"))
    raw = (
        f'<div class="kv">efficiency <b>{c.get("efficiency", 100)}</b> · '
        f'armada <b>{html.escape(str(arm_lbl))}</b></div>'
        f'<div class="kv">special: <b>{html.escape(sp_name)}</b></div>'
        f'<div class="kv">racial: <b>{html.escape(rc_name)}</b></div>'
        f'<div class="kv">allocation [bb,det,man,fc]: <b>{pts}</b> '
        f'→ siege {c.get("siege")}, det {c.get("detection")}, '
        f'man {c.get("maneuver")}, fc {c.get("fleet_commanding")}</div>')
    return '<div class="cmdr">' + bars + raw + '</div>'


def _fleet_card(fl: dict, idx: int) -> str:
    cap = fl.get("capital")
    pinned = fl.get("pinned") or {}
    pin_txt = ""
    if pinned:
        pin_txt = (
            '<div class="kv">pinned: '
            f'computer {html.escape(pinned.get("computer_name") or "#%s" % pinned.get("computer"))} · '
            f'shield {html.escape(pinned.get("shield_name") or "#%s" % pinned.get("shield"))} · '
            f'engine {html.escape(pinned.get("engine_name") or "#%s" % pinned.get("engine"))}</div>')
    head = (f'<div class="fhead">{"★ Capital" if cap else "Fleet %d" % idx} '
            f'· {fl.get("ships", 0)} ships · {html.escape(fl.get("stance", ""))} '
            f'· cell {fl.get("cell")}</div>')
    design = (
        '<div class="kv">hull: <b>' + html.escape(_hull_label(fl)) + '</b>'
        ' · armor: <b>' + html.escape(fl.get("armor_name") or "#%s" % fl.get("armor")) + '</b></div>'
        '<div class="kv">weapons: <b>'
        + _named_list(fl.get("weapons"), fl.get("weapon_names")) + '</b></div>'
        '<div class="kv">devices: <b>'
        + _named_list(fl.get("devices"), fl.get("device_names")) + '</b></div>'
        + pin_txt)
    return ('<div class="card' + (' capital' if cap else '') + '">'
            + head + design + _commander_panel(fl.get("commander", {})) + '</div>')


def fleet_cards_html(decoded: dict, side: str) -> str:
    css = """
    <style>
      .cwrap{font-family:'Times New Roman',serif;color:#e6e6e6}
      .cgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:10px}
      .card{border:1px solid #333;border-radius:6px;padding:8px 10px;background:#11131a}
      .card.capital{border-color:#ccaa44}
      .fhead{font-weight:bold;margin-bottom:4px;color:#ffd680}
      .kv{font-size:12px;margin:2px 0;color:#cfd3da}
      .cmdr{margin-top:6px;border-top:1px solid #2a2d36;padding-top:5px}
      .brow{display:flex;align-items:center;gap:6px;font-size:11px;margin:2px 0}
      .blab{width:64px;color:#aab}
      .btrk{flex:1;height:8px;background:#23262e;border-radius:4px;overflow:hidden}
      .bfil{display:block;height:100%}
      .bval{width:26px;text-align:right;color:#dde}
    </style>"""
    title = (f'<div style="color:#9aa;font-size:12px;margin:2px 0 6px">'
             f'{html.escape(side.upper())} configuration · '
             f'{len(decoded.get("fleets", []))} fleet(s)</div>')
    cards = "".join(_fleet_card(fl, i) for i, fl in enumerate(decoded.get("fleets", [])))
    return css + '<div class="cwrap">' + title + '<div class="cgrid">' + cards + '</div></div>'


# ============================ Streamlit panels ================================

def show_matchup(atk: Optional[dict], dfn: Optional[dict]):
    """Battlefield board + both sides' fleet cards for one attacker/defender pair."""
    components.html(battlefield_html(atk or {}, dfn or {}), height=_BH + 70, scrolling=False)
    c1, c2 = st.columns(2)
    with c1:
        components.html(fleet_cards_html(atk or {}, "attacker"), height=460, scrolling=True)
    with c2:
        components.html(fleet_cards_html(dfn or {}, "defender"), height=460, scrolling=True)


def _labels(loadouts: List[dict], tag: str) -> List[str]:
    out = []
    for i, lo in enumerate(loadouts or []):
        out.append(f"{tag}{i} · {len(lo.get('fleets', []))} fleets · pp {lo.get('pp_cost', 0):,}")
    return out


def render_config_browser(attackers, defenders, key: str,
                          default_a: int = 0, default_d: int = 0):
    """Two selectboxes (attacker x defender config) driving one matchup view."""
    attackers = attackers or []
    defenders = defenders or []
    if not attackers and not defenders:
        st.info("No configurations to display yet.")
        return
    a_labels, d_labels = _labels(attackers, "A"), _labels(defenders, "D")
    c1, c2 = st.columns(2)
    ai = c1.selectbox("Attacker config", range(len(a_labels)),
                      format_func=lambda i: a_labels[i],
                      index=min(default_a, len(a_labels) - 1) if a_labels else 0,
                      key=f"{key}_a") if a_labels else None
    di = c2.selectbox("Defender config", range(len(d_labels)),
                      format_func=lambda i: d_labels[i],
                      index=min(default_d, len(d_labels) - 1) if d_labels else 0,
                      key=f"{key}_d") if d_labels else None
    atk = attackers[ai] if ai is not None else None
    dfn = defenders[di] if di is not None else None
    show_matchup(atk, dfn)


# --- payoff matrix / verdict / convergence (from the old app.py) --------------

def render_matrix(M):
    if not M:
        return
    st.subheader("Payoff matrix — attacker win-rate (rows=attackers, cols=defenders)")
    header = "| A\\D | " + " | ".join(f"D{j}" for j in range(len(M[0]))) + " |\n"
    header += "|" + "---|" * (len(M[0]) + 1) + "\n"
    body = "".join("| A%d | " % i + " | ".join(f"{v:.2f}" for v in row) + " |\n"
                   for i, row in enumerate(M))
    st.markdown(header + body)


def render_convergence(history):
    if not history:
        return
    st.subheader("Stackelberg convergence")
    st.line_chart({
        "best exploit (attacker win)": [h["best_exploit_atkwin"] for h in history],
        "robust worst-case (defender win)": [h["robust_worstcase_defwin"] for h in history],
    })


def render_progress(state: dict):
    """Live phase/elapsed + convergence for an in-progress run."""
    if not state:
        st.info("Waiting for the run to start…")
        return
    c1, c2, c3 = st.columns(3)
    c1.metric("Phase", state.get("phase", "?"))
    c2.metric("Mode", state.get("mode", "?"))
    c3.metric("Elapsed", f"{state.get('elapsed', 0):.0f}s")
    if "library_size" in state:
        st.caption(f"attacker library size: {state['library_size']}")
    render_convergence(state.get("history"))


_VENDOR = os.path.join(os.path.dirname(__file__), "vendor", "battle-replay.js")


@lru_cache(maxsize=1)
def _replay_js() -> str:
    try:
        with open(_VENDOR) as f:
            return f.read()
    except OSError:
        return ""


def replay_embed(log_text: str, races) -> None:
    """Embed the unchanged in-game battle-replay.js, fed the inline log via a
    fetch shim and the race header symbols inlined as data URIs."""
    js = _replay_js()
    if not js:
        st.error("battle-replay.js asset is missing from the image.")
        return
    racemap = {}
    for rid in set(races or []):
        if 1 <= rid <= len(RACE_FOLDER):
            folder = RACE_FOLDER[rid - 1]
            racemap[folder] = _race_symbol(rid)
    shim = """
      <div id="battle-replay" data-log="/battle/inline" data-player="0" data-img=""></div>
      <script>
        window.__LOG__ = %s;
        window.__RACEMAP__ = %s;
        (function(){
          var of = window.fetch;
          window.fetch = function(u){
            if (String(u).indexOf('/battle_log/') >= 0)
              return Promise.resolve({ok:true, status:200,
                text:function(){ return Promise.resolve(window.__LOG__); }});
            return of ? of.apply(this, arguments) : Promise.reject('nofetch');
          };
          var swap = function(){
            var imgs = document.querySelectorAll('#battle-replay img[src*="/image/as_game/race/"]');
            for (var i=0;i<imgs.length;i++){
              var m = imgs[i].getAttribute('src').match(/race\\/([a-z0-9]+)\\//);
              if (m && window.__RACEMAP__[m[1]]) imgs[i].src = window.__RACEMAP__[m[1]];
            }
          };
          new MutationObserver(swap).observe(document.documentElement,{childList:true,subtree:true});
        })();
      </script>
      <style>body{background:transparent;margin:0}</style>
    """ % (json.dumps(log_text), json.dumps(racemap))
    components.html(shim + "<script>" + js + "</script>", height=770, scrolling=True)


def render_report(report: dict):
    """Full report: verdict card, meta-game, matrix, convergence, configurations."""
    sc = report.get("scenario", {})
    st.subheader(f"Balance report — {sc.get('name', '?')}")
    an = report.get("analysis", {})
    c1, c2, c3 = st.columns(3)
    c1.metric("Attacker eq. win-rate", f"{an.get('value', 0):.3f}")
    c2.metric("Meta-game", an.get("kind", "?"))
    c3.metric("Engine crashes", report.get("anomalies", {}).get("crashes", 0))
    st.markdown(f"**Verdict:** {report.get('verdict', '')}")
    render_matrix(report.get("matrix"))
    render_convergence(report.get("history"))

    st.subheader("Configurations")
    lo = report.get("loadouts", {})
    attackers = lo.get("attackers", [])
    defenders = lo.get("defenders", [])
    # default to the equilibrium / robust pairing where we can infer it
    da = (an.get("attacker_support") or [0])[0] if attackers else 0
    dd = (an.get("defender_support") or [0])[0] if defenders else 0
    if lo.get("robust_defender") and report.get("mode") == "stackelberg":
        da = max(0, len(attackers) - 1)
    render_config_browser(attackers, defenders, key="report", default_a=da, default_d=dd)

    with st.expander("Raw report JSON"):
        st.json(report)
