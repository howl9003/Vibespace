/**
 * as-deploy.js — HTML5 battle-deployment board (grid edition).
 *
 * Replaces the dead Java Deployment applet. Reads the fleet <param> data the
 * engine injects into #as-deploy-params and lays the fleets out on a fixed
 * 5-column × 4-row grid:
 *
 *     F F F F F
 *     F F F F F
 *     F F F F F
 *     F F C F F        (C = capital, centred on the bottom row, NOT movable)
 *
 * Fleets are the original Java ship images (ship_set.gif / ship_cap.gif),
 * coloured blue (normal) and red (capital). Dragging a fleet snaps it to the
 * nearest grid slot (swapping with whatever is there); the capital is fixed.
 * On submit each fleet's slot X/Y is written into hidden inputs, exactly as the
 * .as handler expects.
 *
 * Engine params (capital is index 0 → capFleet_*, the rest are 1-indexed):
 *   capFleet_ID, capFleet_O ; Fleet1_ID, Fleet1_O, Fleet1_X, Fleet1_Y, … ;
 *   TARGET (form action), TID (target id), IMAGEDIR (image path prefix).
 */
(function () {
  'use strict';

  var BOARD_W = 630, BOARD_H = 275;
  var COLS = 5, ROWS = 4;
  var MARGIN_X = 70, MARGIN_Y = 40;
  var STEP_X = (BOARD_W - 2 * MARGIN_X) / (COLS - 1);
  var STEP_Y = (BOARD_H - 2 * MARGIN_Y) / (ROWS - 1);
  var R = 17;                              // marker radius
  var CAP_SLOT = (ROWS - 1) * COLS + Math.floor(COLS / 2); // bottom row, centre

  var COLOR_BG    = '#050510';
  var COLOR_GRID  = '#1b1b3a';
  var BLUE        = '#2f6fd0';
  var BLUE_HI     = '#5b9bff';
  var RED         = '#cc3a34';
  var RED_HI      = '#ff6a60';

  document.addEventListener('DOMContentLoaded', function () {
    var paramsDiv = document.getElementById('as-deploy-params');
    var canvas    = document.getElementById('as-deploy-board');
    var form      = document.getElementById('as-deploy-form');
    if (!paramsDiv || !canvas || !form) return;
    var ctx = canvas.getContext('2d');

    /* 1. Read params — <param> nodes, with a regex fallback for the DOM quirk
       where <param> outside <object> isn't always exposed as element nodes. */
    var params = {};
    var els = paramsDiv.getElementsByTagName('param');
    for (var i = 0; i < els.length; i++) {
      params[els[i].getAttribute('name')] = els[i].getAttribute('value');
    }
    if (params['Fleet1_ID'] === undefined && params['capFleet_ID'] === undefined) {
      var re = /name\s*=\s*"?([A-Za-z0-9_]+)"?\s+value\s*=\s*"?([^">]*)"?/gi, m;
      while ((m = re.exec(paramsDiv.innerHTML))) {
        if (params[m[1]] === undefined) params[m[1]] = m[2];
      }
    }

    var target = params['TARGET'];
    if (target) form.setAttribute('action', target);
    var imgDir = (params['IMAGEDIR'] || '/image/as_game/fleet/');
    if (imgDir.indexOf('$') !== -1) imgDir = '/image/as_game/fleet/'; // unsubstituted token

    /* 2. Build fleets. Capital = index 0 (capFleet_*); the rest are Fleet1.. */
    var fleets = [];
    if (params['capFleet_ID'] !== undefined) {
      fleets.push({ key: 'capFleet', id: params['capFleet_ID'] || '',
                    label: params['capFleet_O'] || 'Capital', isCap: true });
    }
    var idx = 1;
    while (params['Fleet' + idx + '_ID'] !== undefined) {
      fleets.push({ key: 'Fleet' + idx, id: params['Fleet' + idx + '_ID'] || '',
                    label: params['Fleet' + idx + '_O'] || ('Fleet ' + idx), isCap: false });
      idx++;
    }

    /* 3. Assign grid slots: capital to the fixed bottom-centre slot, the rest
       filling the remaining slots in order. Each fleet gets x/y = slot centre. */
    function slotXY(slot) {
      var col = slot % COLS, row = Math.floor(slot / COLS);
      return { x: Math.round(MARGIN_X + col * STEP_X), y: Math.round(MARGIN_Y + row * STEP_Y) };
    }
    var nextSlot = 0;
    fleets.forEach(function (f) {
      if (f.isCap) { f.slot = CAP_SLOT; }
      else { if (nextSlot === CAP_SLOT) nextSlot++; f.slot = nextSlot++; }
      var p = slotXY(f.slot); f.x = p.x; f.y = p.y;
    });

    /* 4. Hidden X/Y inputs (kept current on submit). */
    var hidden = {};
    fleets.forEach(function (f) {
      hidden[f.key] = { x: ensureHidden(form, f.key + '_X', String(f.x)),
                        y: ensureHidden(form, f.key + '_Y', String(f.y)) };
    });

    /* 5. Ship images (original Java assets), tinted by drawing under them. */
    var imgFleet = new Image(), imgCap = new Image(), loaded = 0;
    function onload() { if (++loaded >= 2) draw(); }
    imgFleet.onload = imgCap.onload = onload;
    imgFleet.onerror = imgCap.onerror = onload;   // draw anyway (fallback dot)
    imgFleet.src = imgDir + 'ship_set.gif';
    imgCap.src   = imgDir + 'ship_cap.gif';

    /* 6. Render. */
    function draw() {
      ctx.fillStyle = COLOR_BG; ctx.fillRect(0, 0, BOARD_W, BOARD_H);
      // empty grid slots (so the grid is visible, like the old applet)
      for (var s = 0; s < COLS * ROWS; s++) {
        var p = slotXY(s);
        ctx.strokeStyle = COLOR_GRID; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.arc(p.x, p.y, R, 0, Math.PI * 2); ctx.stroke();
      }
      for (var fi = fleets.length - 1; fi >= 0; fi--) drawFleet(fleets[fi], fi === dragIndex);
      ctx.fillStyle = '#33466a'; ctx.font = '10px sans-serif'; ctx.textAlign = 'right';
      ctx.fillText('Drag fleets to a grid slot, then Deploy. (Capital is fixed.)', BOARD_W - 6, BOARD_H - 5);
    }
    function drawFleet(f, sel) {
      var base = f.isCap ? RED : BLUE, hi = f.isCap ? RED_HI : BLUE_HI;
      // coloured disc
      ctx.beginPath(); ctx.arc(f.x, f.y, R, 0, Math.PI * 2);
      ctx.fillStyle = sel ? hi : base; ctx.fill();
      ctx.lineWidth = f.isCap ? 2 : 1; ctx.strokeStyle = f.isCap ? '#ffd0c0' : '#bcd4ff'; ctx.stroke();
      // ship image centred (scaled up a touch from the tiny gif)
      var img = f.isCap ? imgCap : imgFleet;
      if (img.complete && img.naturalWidth) {
        var w = img.naturalWidth * 1.6, h = img.naturalHeight * 1.6;
        ctx.drawImage(img, Math.round(f.x - w / 2), Math.round(f.y - h / 2), w, h);
      }
      // label under the marker
      ctx.fillStyle = '#cdd9f2'; ctx.font = '9px sans-serif'; ctx.textAlign = 'center';
      var lbl = (f.isCap ? '★ ' : '') + 'ID ' + f.id;
      ctx.fillText(lbl, f.x, f.y + R + 9);
    }

    /* 7. Drag + snap-to-slot (capital excluded). */
    var dragIndex = -1, offX = 0, offY = 0;
    function hitTest(px, py) {
      for (var i = 0; i < fleets.length; i++) {
        var dx = px - fleets[i].x, dy = py - fleets[i].y;
        if (dx * dx + dy * dy <= (R + 3) * (R + 3)) return i;
      }
      return -1;
    }
    function eventPos(e) {
      var r = canvas.getBoundingClientRect();
      var t = (e.touches && e.touches[0]) || (e.changedTouches && e.changedTouches[0]) || e;
      return { x: (t.clientX - r.left) * (canvas.width / r.width),
               y: (t.clientY - r.top) * (canvas.height / r.height) };
    }
    function nearestSlot(px, py) {
      var best = -1, bd = 1e9;
      for (var s = 0; s < COLS * ROWS; s++) {
        if (s === CAP_SLOT) continue;            // capital slot is reserved
        var p = slotXY(s), d = (px - p.x) * (px - p.x) + (py - p.y) * (py - p.y);
        if (d < bd) { bd = d; best = s; }
      }
      return best;
    }
    function down(e) {
      e.preventDefault();
      var p = eventPos(e), hi = hitTest(p.x, p.y);
      if (hi !== -1 && !fleets[hi].isCap) { dragIndex = hi; offX = p.x - fleets[hi].x; offY = p.y - fleets[hi].y; }
      draw();
    }
    function move(e) {
      if (dragIndex === -1) return; e.preventDefault();
      var p = eventPos(e);
      fleets[dragIndex].x = clamp(Math.round(p.x - offX), R, BOARD_W - R);
      fleets[dragIndex].y = clamp(Math.round(p.y - offY), R, BOARD_H - R);
      draw();
    }
    function up() {
      if (dragIndex === -1) return;
      var f = fleets[dragIndex];
      var slot = nearestSlot(f.x, f.y);
      var occupant = fleets.filter(function (g) { return g !== f && g.slot === slot; })[0];
      if (occupant) { occupant.slot = f.slot; var op = slotXY(occupant.slot); occupant.x = op.x; occupant.y = op.y; }
      f.slot = slot; var fp = slotXY(slot); f.x = fp.x; f.y = fp.y;
      dragIndex = -1; draw();
    }
    canvas.addEventListener('mousedown', down);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', up);
    canvas.addEventListener('mouseleave', up);
    canvas.addEventListener('touchstart', down, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', up);

    /* 8. Submit: write each fleet's final slot coordinates. */
    form.addEventListener('submit', function () {
      fleets.forEach(function (f) {
        var h = hidden[f.key]; if (h) { h.x.value = String(f.x); h.y.value = String(f.y); }
      });
    });

    draw();   // initial (images may still be loading; onload redraws)
  });

  function clamp(n, lo, hi) { return n < lo ? lo : (n > hi ? hi : n); }
  function ensureHidden(form, name, value) {
    var e = form.querySelector('input[name="' + name + '"]');
    if (e) { e.value = value; return e; }
    e = document.createElement('input'); e.type = 'hidden'; e.name = name; e.value = value;
    form.appendChild(e); return e;
  }
}());
