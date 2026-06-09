/**
 * as-deploy.js — HTML5 replacement for the Archspace Java Deployment applet.
 *
 * Reads <param> elements from #as-deploy-params (injected by the C++ game
 * engine via $J_FLEET_INFO / $FLEET_INDEX_TO_ID / $J_FLEET_LIST tokens).
 * Renders a draggable fleet deployment board on #as-deploy-board (630×275
 * canvas) and, on form submit, writes each fleet's final X/Y into hidden
 * inputs so the existing .as handler receives the same POST body it always
 * expected from the applet.
 *
 * Expected <param> names (values emitted by the engine):
 *   capFleet_ID, capFleet_X, capFleet_Y, capFleet_O
 *   Fleet0_ID,   Fleet0_X,   Fleet0_Y,   Fleet0_O
 *   Fleet1_ID,   Fleet1_X,   Fleet1_Y,   Fleet1_O   (…up to FleetN_*)
 *   TARGET   — form action URL (e.g. "siege_planet_planet.as")
 *   TID      — target player ID (passed as hidden field)
 *   IMAGEDIR — image directory prefix (not used visually here, kept for
 *              compatibility in case the engine checks the POST body)
 */

(function () {
  'use strict';

  /* ── Constants ─────────────────────────────────────────────── */
  var BOARD_W = 630;
  var BOARD_H = 275;

  // Visual sizing of each fleet marker on the canvas
  var BOX_W = 80;
  var BOX_H = 36;

  // Colours — dark space theme matching the original applet
  var COLOR_BG        = '#050510';   // near-black board background
  var COLOR_GRID      = '#1a1a3a';   // subtle grid lines
  var COLOR_FLEET_BG  = '#0d2244';   // normal fleet box fill
  var COLOR_CAP_BG    = '#1a3a0d';   // capital fleet box fill (green tint)
  var COLOR_FLEET_SEL = '#1a4488';   // selected fleet highlight
  var COLOR_BORDER    = '#4477cc';   // fleet box border
  var COLOR_CAP_BORD  = '#44cc44';   // capital fleet border
  var COLOR_TEXT      = '#ccddff';   // fleet label text
  var COLOR_ID_TEXT   = '#aabbdd';   // fleet ID sub-label

  /* ── Bootstrap on DOM ready ────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    var paramsDiv = document.getElementById('as-deploy-params');
    var canvas    = document.getElementById('as-deploy-board');
    var form      = document.getElementById('as-deploy-form');

    if (!paramsDiv || !canvas || !form) {
      // Elements not present on this page — nothing to do.
      return;
    }

    var ctx = canvas.getContext('2d');

    /* ── 1. Parse <param> elements from the hidden div ──────── */
    var params = {};
    var paramEls = paramsDiv.getElementsByTagName('param');
    for (var i = 0; i < paramEls.length; i++) {
      var el = paramEls[i];
      // Attribute names in HTML are case-insensitive; normalise to upper-case
      // to match how the engine emits them (NAME=Fleet0_X etc.).
      params[el.getAttribute('name')] = el.getAttribute('value');
    }

    /* ── 2. Set form action from TARGET param ───────────────── */
    var target = params['TARGET'];
    if (target) {
      form.setAttribute('action', target);
    }

    /* ── 3. Build fleet list ────────────────────────────────── */
    // Fleet objects: { key, id, x, y, label, isCap }
    // key is the prefix used for hidden input names (capFleet or Fleet0, Fleet1…)
    var fleets = [];

    // Capital fleet
    if (params['capFleet_ID'] !== undefined) {
      fleets.push({
        key:   'capFleet',
        id:    params['capFleet_ID']    || '',
        x:     parseInt(params['capFleet_X'] || '0', 10),
        y:     parseInt(params['capFleet_Y'] || '0', 10),
        label: params['capFleet_O']     || 'Cap Fleet',
        isCap: true
      });
    }

    // Numbered fleets: Fleet0_*, Fleet1_*, …
    var idx = 0;
    while (true) {
      var prefix = 'Fleet' + idx;
      if (params[prefix + '_ID'] === undefined) break;
      fleets.push({
        key:   prefix,
        id:    params[prefix + '_ID']    || '',
        x:     parseInt(params[prefix + '_X'] || '0', 10),
        y:     parseInt(params[prefix + '_Y'] || '0', 10),
        label: params[prefix + '_O']     || ('Fleet ' + idx),
        isCap: false
      });
      idx++;
    }

    // Clamp all initial positions inside the board
    fleets.forEach(function (f) {
      f.x = clamp(f.x, 0, BOARD_W - 1);
      f.y = clamp(f.y, 0, BOARD_H - 1);
    });

    /* ── 4. Create / update hidden inputs for each fleet ──── */
    // We create them now and keep references so we can update on submit.
    var hiddenInputs = {}; // key → { xInput, yInput }

    fleets.forEach(function (f) {
      var xi = ensureHidden(form, f.key + '_X', String(f.x));
      var yi = ensureHidden(form, f.key + '_Y', String(f.y));
      hiddenInputs[f.key] = { xInput: xi, yInput: yi };
    });

    /* ── 5. Draw loop ───────────────────────────────────────── */
    function draw() {
      // Background
      ctx.fillStyle = COLOR_BG;
      ctx.fillRect(0, 0, BOARD_W, BOARD_H);

      // Subtle grid (every 50px)
      ctx.strokeStyle = COLOR_GRID;
      ctx.lineWidth = 0.5;
      for (var gx = 0; gx <= BOARD_W; gx += 50) {
        ctx.beginPath(); ctx.moveTo(gx, 0); ctx.lineTo(gx, BOARD_H); ctx.stroke();
      }
      for (var gy = 0; gy <= BOARD_H; gy += 50) {
        ctx.beginPath(); ctx.moveTo(0, gy); ctx.lineTo(BOARD_W, gy); ctx.stroke();
      }

      // Axis labels (coordinate hints)
      ctx.fillStyle = '#2a2a5a';
      ctx.font = '9px monospace';
      ctx.textAlign = 'center';
      for (var lx = 50; lx < BOARD_W; lx += 50) {
        ctx.fillText(String(lx), lx, 9);
      }
      ctx.textAlign = 'right';
      for (var ly = 50; ly < BOARD_H; ly += 50) {
        ctx.fillText(String(ly), 18, ly + 3);
      }

      // Fleet markers (draw in reverse so index-0 appears on top when overlapping)
      for (var fi = fleets.length - 1; fi >= 0; fi--) {
        drawFleet(fleets[fi], fi === dragIndex);
      }

      // Instruction text at bottom-right
      ctx.fillStyle = '#334466';
      ctx.font = '10px sans-serif';
      ctx.textAlign = 'right';
      ctx.fillText('Drag fleets to reposition, then click Submit.', BOARD_W - 6, BOARD_H - 5);
    }

    function drawFleet(f, isSelected) {
      // Box top-left anchored at fleet centre
      var bx = Math.round(f.x - BOX_W / 2);
      var by = Math.round(f.y - BOX_H / 2);

      // Clamp box so it stays fully inside canvas
      bx = clamp(bx, 0, BOARD_W - BOX_W);
      by = clamp(by, 0, BOARD_H - BOX_H);

      var bg     = isSelected ? COLOR_FLEET_SEL : (f.isCap ? COLOR_CAP_BG   : COLOR_FLEET_BG);
      var border = isSelected ? '#88aaff'        : (f.isCap ? COLOR_CAP_BORD : COLOR_BORDER);

      // Box fill
      ctx.fillStyle = bg;
      ctx.fillRect(bx, by, BOX_W, BOX_H);

      // Box border
      ctx.strokeStyle = border;
      ctx.lineWidth = isSelected ? 2 : 1;
      ctx.strokeRect(bx + 0.5, by + 0.5, BOX_W - 1, BOX_H - 1);

      // Centre dot (marks the actual coordinate point)
      ctx.fillStyle = border;
      ctx.beginPath();
      ctx.arc(f.x, f.y, 2, 0, Math.PI * 2);
      ctx.fill();

      // Fleet label (order/name)
      ctx.fillStyle = COLOR_TEXT;
      ctx.font = 'bold 11px sans-serif';
      ctx.textAlign = 'center';
      // Truncate label if too long
      var label = f.label.length > 12 ? f.label.slice(0, 11) + '…' : f.label;
      ctx.fillText(label, bx + BOX_W / 2, by + 14);

      // Fleet ID sub-label
      ctx.fillStyle = COLOR_ID_TEXT;
      ctx.font = '9px monospace';
      ctx.fillText('ID:' + f.id + (f.isCap ? ' ★' : ''), bx + BOX_W / 2, by + 26);

      // Coordinate readout
      ctx.fillStyle = '#667799';
      ctx.font = '9px monospace';
      ctx.fillText('(' + f.x + ',' + f.y + ')', bx + BOX_W / 2, by + BOX_H - 3);
    }

    /* ── 6. Drag logic ──────────────────────────────────────── */
    var dragIndex = -1;   // index into fleets[] currently being dragged
    var dragOffX  = 0;    // offset from fleet centre to mouse-down point
    var dragOffY  = 0;

    /**
     * Hit-test: returns the index of the topmost fleet whose box contains (px,py),
     * or -1 if none.  We use the same clamped box geometry as drawFleet().
     */
    function hitTest(px, py) {
      for (var hi = 0; hi < fleets.length; hi++) {
        var f  = fleets[hi];
        var bx = clamp(Math.round(f.x - BOX_W / 2), 0, BOARD_W - BOX_W);
        var by = clamp(Math.round(f.y - BOX_H / 2), 0, BOARD_H - BOX_H);
        if (px >= bx && px <= bx + BOX_W && py >= by && py <= by + BOX_H) {
          return hi;
        }
      }
      return -1;
    }

    /** Convert a mouse/touch event to canvas-local coordinates. */
    function eventPos(e) {
      var rect = canvas.getBoundingClientRect();
      var scaleX = canvas.width  / rect.width;
      var scaleY = canvas.height / rect.height;
      var clientX, clientY;
      if (e.touches && e.touches.length > 0) {
        clientX = e.touches[0].clientX;
        clientY = e.touches[0].clientY;
      } else if (e.changedTouches && e.changedTouches.length > 0) {
        clientX = e.changedTouches[0].clientX;
        clientY = e.changedTouches[0].clientY;
      } else {
        clientX = e.clientX;
        clientY = e.clientY;
      }
      return {
        x: (clientX - rect.left) * scaleX,
        y: (clientY - rect.top)  * scaleY
      };
    }

    // Mouse events
    canvas.addEventListener('mousedown', function (e) {
      e.preventDefault();
      var pos = eventPos(e);
      dragIndex = hitTest(pos.x, pos.y);
      if (dragIndex !== -1) {
        dragOffX = pos.x - fleets[dragIndex].x;
        dragOffY = pos.y - fleets[dragIndex].y;
      }
      draw();
    });

    canvas.addEventListener('mousemove', function (e) {
      if (dragIndex === -1) return;
      e.preventDefault();
      var pos = eventPos(e);
      fleets[dragIndex].x = clamp(Math.round(pos.x - dragOffX), 0, BOARD_W - 1);
      fleets[dragIndex].y = clamp(Math.round(pos.y - dragOffY), 0, BOARD_H - 1);
      draw();
    });

    canvas.addEventListener('mouseup', function (e) {
      if (dragIndex !== -1) {
        dragIndex = -1;
        draw();
      }
    });

    canvas.addEventListener('mouseleave', function () {
      if (dragIndex !== -1) {
        dragIndex = -1;
        draw();
      }
    });

    // Touch events (mobile / tablet)
    canvas.addEventListener('touchstart', function (e) {
      e.preventDefault();
      var pos = eventPos(e);
      dragIndex = hitTest(pos.x, pos.y);
      if (dragIndex !== -1) {
        dragOffX = pos.x - fleets[dragIndex].x;
        dragOffY = pos.y - fleets[dragIndex].y;
      }
      draw();
    }, { passive: false });

    canvas.addEventListener('touchmove', function (e) {
      if (dragIndex === -1) return;
      e.preventDefault();
      var pos = eventPos(e);
      fleets[dragIndex].x = clamp(Math.round(pos.x - dragOffX), 0, BOARD_W - 1);
      fleets[dragIndex].y = clamp(Math.round(pos.y - dragOffY), 0, BOARD_H - 1);
      draw();
    }, { passive: false });

    canvas.addEventListener('touchend', function () {
      dragIndex = -1;
      draw();
    });

    /* ── 7. On submit: write final coordinates into hidden inputs ── */
    form.addEventListener('submit', function () {
      fleets.forEach(function (f) {
        var inp = hiddenInputs[f.key];
        if (inp) {
          inp.xInput.value = String(f.x);
          inp.yInput.value = String(f.y);
        }
      });
      // Allow the form to submit normally (no e.preventDefault())
    });

    /* ── 8. Initial render ──────────────────────────────────── */
    draw();
  });

  /* ── Helpers ────────────────────────────────────────────────── */

  /** Clamp n to [lo, hi]. */
  function clamp(n, lo, hi) {
    return n < lo ? lo : (n > hi ? hi : n);
  }

  /**
   * Find or create a hidden <input> inside form with the given name.
   * Sets its initial value and returns the element.
   */
  function ensureHidden(form, name, value) {
    var existing = form.querySelector('input[name="' + name + '"]');
    if (existing) {
      existing.value = value;
      return existing;
    }
    var inp = document.createElement('input');
    inp.type  = 'hidden';
    inp.name  = name;
    inp.value = value;
    form.appendChild(inp);
    return inp;
  }

}());
