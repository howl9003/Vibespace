// as-project-tooltips.js — QoL: hover tooltips showing a project's mechanical
// effect on the project lists.
//
// The engine emits each project's effect as a data-tip="..." attribute:
//   * on the project-name span/link in the black market, the council "achieved
//     projects" list, and the domestic overview (real hover tooltips), and
//   * on the <option>s of the council "propose a project" dropdown (since options
//     can't host hover tooltips, the selected project's effect is shown just
//     below the <select> instead).
// The effect text comes from the engine (CProject::get_effect_tip, which reuses
// get_effects_string_for_html + the special-effect list), so it matches the game.
//
// Bind-mounted web-tier asset (restart-only). Included from the project pages;
// harmless elsewhere (it only acts on data-tip attributes / the PROJECT select).
(function () {
  "use strict";

  function fmt(s) {
    return (s || "")
      .replace(/<br\s*\/?>/gi, "\n")
      .replace(/[ \t]*\n[ \t]*/g, "\n")
      .replace(/\n{2,}/g, "\n")
      .trim();
  }

  function init() {
    var tip = document.createElement("div");
    var s = tip.style;
    s.position = "fixed"; s.zIndex = "100000"; s.maxWidth = "300px";
    s.padding = "6px 9px"; s.background = "#111"; s.color = "#ddd";
    s.border = "1px solid #555"; s.borderRadius = "3px";
    s.font = '12px "Times New Roman", Times, serif'; s.lineHeight = "1.4";
    s.boxShadow = "0 2px 8px rgba(0,0,0,.6)"; s.pointerEvents = "none";
    s.display = "none"; s.whiteSpace = "pre-line";
    document.body.appendChild(tip);

    function move(e) {
      var x = e.clientX + 14, y = e.clientY + 16;
      var w = tip.offsetWidth, h = tip.offsetHeight;
      if (x + w > window.innerWidth - 8) x = window.innerWidth - w - 8;
      if (y + h > window.innerHeight - 8) y = e.clientY - h - 12;
      if (x < 4) x = 4;
      if (y < 4) y = 4;
      tip.style.left = x + "px"; tip.style.top = y + "px";
    }
    function show(e, t) { tip.textContent = t; tip.style.display = "block"; move(e); }
    function hide() { tip.style.display = "none"; }

    // 1) Hover tooltips for [data-tip] project names (black market, council
    //    achieved, domestic overview).
    Array.prototype.forEach.call(document.querySelectorAll("[data-tip]"), function (el) {
      if (el.tagName === "OPTION") return; // dropdown options handled below
      var t = fmt(el.getAttribute("data-tip"));
      if (!t) return;
      el.style.cursor = "help";
      el.addEventListener("mouseenter", function (e) { show(e, t); });
      el.addEventListener("mousemove", move);
      el.addEventListener("mouseleave", hide);
    });

    // 2) Council "propose a project" dropdown: options can't host tooltips, so
    //    show the selected project's effect on a line just below the <select>.
    var sel = document.querySelector('select[name="PROJECT"]');
    if (sel) {
      var box = document.createElement("div");
      var bs = box.style;
      bs.marginTop = "4px"; bs.color = "#bbb"; bs.minHeight = "1em";
      bs.font = '12px "Times New Roman", Times, serif';
      sel.parentNode.insertBefore(box, sel.nextSibling);
      var upd = function () {
        var o = sel.options[sel.selectedIndex];
        var t = o ? fmt(o.getAttribute("data-tip")) : "";
        box.textContent = t ? ("Effect: " + t.replace(/\n/g, "; ")) : "";
      };
      sel.addEventListener("change", upd);
      upd();
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
