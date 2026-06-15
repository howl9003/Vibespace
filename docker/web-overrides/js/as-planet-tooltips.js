// as-planet-tooltips.js — QoL: hover tooltips explaining what each planet
// modifier (Attribute) does. The engine renders the modifier names as plain
// BR-separated text in the planet "Attribute" cell (CPlanet::get_attribute_html),
// with no explanation of their effect. This script finds those names on the page
// and attaches a hover tooltip describing the modifier's gameplay effect.
//
// Effects are taken verbatim from the engine's CPlanet::build_control_model
// (planet.cc): change_environment/production/growth/commerce/military/research/
// spy and change_facility_cost (positive facility_cost = cheaper facility upkeep,
// since UpkeepRatio = -facility_cost). Names match CPlanet::get_attribute_description.
//
// Bind-mounted web-tier asset (restart-only). Included from the planet detail
// template; harmless on any page (it only acts on exact modifier-name text).
(function () {
  "use strict";

  var EFFECTS = {
    // --- original (both editions) ---
    "Artifact":                 "+10 research on this planet.",
    "Massive Artifact":         "+20 research on this planet.",
    "Asteroid":                 "+1 production. Military bases here are only half as effective at stopping enemy sabotage.",
    "Moon":                     "+2 military, +3 growth, +1 commerce.",
    "Radiation":                "−1 environment control. Removable by terraforming (Solar Manipulation).",
    "Severe Radiation":         "−2 environment control.",
    "Hostile Monster":          "−1 environment control. Removable by terraforming (Primitive Language).",
    "Obstinate Microbe":        "−2 environment control. Removable by terraforming (Genetic Therapy).",
    "Beautiful Landscape":      "+2 commerce, −1 spy.",
    "Black Hole":               "−2 commerce, −2 environment control, −1 production.",
    "Nebula":                   "Flavor only — no effect on planet output.",
    "Dark Nebula":              "−2 environment control, −1 commerce.",
    "Volcanic Activity":        "−1 environment control, +2 production.",
    "Intense Volcanic Activity":"−2 environment control, +5 production.",
    "Ocean":                    "+1 environment control, +3 growth — but +50% facility upkeep.",
    "Irregular Climate":        "−2 environment control.",
    "Major Space Route":        "+2 commerce.",
    "Major Space Crossroutes":  "+5 commerce here, plus +1 commerce empire-wide.",
    "Frontier Area":            "−2 commerce.",
    "Gravity Controlled":       "Cancels the gravity-mismatch penalty to environment control.",
    // --- cvs-merge (restoration edition) additions ---
    "Moon Cluster":             "+4 military, +3 growth, +5 commerce; −10% facility upkeep.",
    "Ancient Ruins":            "+2 research, +2 commerce.",
    "Ship Yard":                "+5 military.",
    "Military Stronghold":      "+15 military, −5 commerce.",
    "Maintenance Center":       "+2 commerce, +2 growth; −30% facility upkeep.",
    "Cognition Amplifier Relic":"+6 production; −30% facility upkeep.",
    "Underground Caverns":      "+1 military, +3 growth; −10% facility upkeep.",
    "Rare Ore":                 "+3 production, +2 commerce.",
    "Lost Trabotulin Library":  "+30 research, +5 military, +10 commerce."
  };

  function norm(s) { return s.replace(/ /g, " ").trim(); }

  function init() {
    // Single floating tooltip bubble, themed to match the dark UI.
    var tip = document.createElement("div");
    var s = tip.style;
    s.position = "fixed"; s.zIndex = "100000"; s.maxWidth = "260px";
    s.padding = "6px 9px"; s.background = "#111"; s.color = "#ddd";
    s.border = "1px solid #555"; s.borderRadius = "3px";
    s.font = '12px "Times New Roman", Times, serif'; s.lineHeight = "1.4";
    s.boxShadow = "0 2px 8px rgba(0,0,0,.6)"; s.pointerEvents = "none";
    s.display = "none"; s.whiteSpace = "normal";
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
    function show(e, text) { tip.textContent = text; tip.style.display = "block"; move(e); }
    function hide() { tip.style.display = "none"; }

    // Collect matching text nodes first (don't mutate the DOM during the walk).
    var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null);
    var nodes = [], n;
    while ((n = walker.nextNode())) {
      if (n.parentNode && n.parentNode.nodeName === "SCRIPT") continue;
      if (EFFECTS.hasOwnProperty(norm(n.nodeValue))) nodes.push(n);
    }

    nodes.forEach(function (node) {
      var raw = node.nodeValue;
      var name = norm(raw);
      var effect = EFFECTS[name];
      var lead = (raw.match(/^\s*/) || [""])[0];
      var trail = (raw.match(/\s*$/) || [""])[0];
      var core = raw.slice(lead.length, raw.length - trail.length);

      var span = document.createElement("span");
      span.className = "as-modtip";
      span.textContent = core;
      span.title = effect; // native fallback
      span.style.borderBottom = "1px dotted #888";
      span.style.cursor = "help";
      span.addEventListener("mouseenter", function (e) { show(e, effect); });
      span.addEventListener("mousemove", move);
      span.addEventListener("mouseleave", hide);

      var frag = document.createDocumentFragment();
      if (lead) frag.appendChild(document.createTextNode(lead));
      frag.appendChild(span);
      if (trail) frag.appendChild(document.createTextNode(trail));
      node.parentNode.replaceChild(frag, node);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
