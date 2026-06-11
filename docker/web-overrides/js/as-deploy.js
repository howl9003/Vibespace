/**
 * as-deploy.js — HTML5 battle-deployment board (grid edition).
 *
 * Replaces the dead Java Deployment applet. Reads the fleet <param> data the
 * engine injects into #as-deploy-params and lays the fleets out on a 5-col ×
 * 4-row default formation:
 *
 *     F F F F F
 *     F F F F F
 *     F F F F F
 *     F F C F F        (C = capital, centred near the bottom, NOT movable)
 *
 * Fleets are the original Java ship images (ship_set.gif / ship_cap.gif),
 * coloured blue (normal) and red (capital). Dragging a fleet SNAPS it to the
 * original applet's 20-unit battle grid (X 9..609, Y 226..426); the capital is
 * fixed. On submit the full POST contract the .as handler expects is written
 * (FLEET_NUMBER, capFleet_ID/O, Fleet{n}_ID/X/Y + fleet{n}_O, n from 2) with
 * positions mapped into the battle coordinate space.
 */
(function () {
  'use strict';

  var BOARD_W = 630, BOARD_H = 275;
  var R = 12;                              // hit-test radius (icons are small)
  var ICON_H = 22;                         // drawn marker height in px

  // Snap grid = the original applet's 20-unit battle squares. The battle area
  // is 600×200 (X 9..609, Y 226..426); map a unit to canvas px.
  var GX = BOARD_W / 600 * 20;             // ≈ 21 px per square (X)
  var GY = BOARD_H / 200 * 20;             // ≈ 27.5 px per square (Y)

  var COLOR_BG   = '#050510';
  var COLOR_GRID = '#191936';
  var BLUE = '#2f6fd0';   // fallback marker fill if a ship gif fails to load
  var RED  = '#cc3a34';

  document.addEventListener('DOMContentLoaded', function () {
    var paramsDiv = document.getElementById('as-deploy-params');
    var canvas    = document.getElementById('as-deploy-board');
    var form      = document.getElementById('as-deploy-form');
    if (!paramsDiv || !canvas || !form) return;
    var ctx = canvas.getContext('2d');

    /* 1. Read params (<param> nodes, with a regex fallback for the DOM quirk). */
    var params = {};
    var els = paramsDiv.getElementsByTagName('param');
    for (var i = 0; i < els.length; i++) params[els[i].getAttribute('name')] = els[i].getAttribute('value');
    if (params['Fleet1_ID'] === undefined && params['capFleet_ID'] === undefined) {
      var re = /name\s*=\s*"?([A-Za-z0-9_]+)"?\s+value\s*=\s*"?([^">]*)"?/gi, m;
      while ((m = re.exec(paramsDiv.innerHTML))) if (params[m[1]] === undefined) params[m[1]] = m[2];
    }
    var target = params['TARGET']; if (target) form.setAttribute('action', target);
    var imgDir = params['IMAGEDIR'] || '/image/as_game/fleet/';
    if (imgDir.indexOf('$') !== -1) imgDir = '/image/as_game/fleet/';

    /* 2. Build fleets (capital = index 0 → capFleet_*; rest = Fleet1..). */
    var fleets = [];
    if (params['capFleet_ID'] !== undefined)
      fleets.push({ key: 'capFleet', id: params['capFleet_ID'] || '', label: params['capFleet_O'] || 'Capital', isCap: true });
    var idx = 1;
    while (params['Fleet' + idx + '_ID'] !== undefined) {
      fleets.push({ key: 'Fleet' + idx, id: params['Fleet' + idx + '_ID'] || '', label: params['Fleet' + idx + '_O'] || ('Fleet ' + idx), isCap: false });
      idx++;
    }

    /* 3. Default formation: a compact 5-wide block of adjacent grid squares
       hugging the capital (capital fixed at the block's bottom-centre), with
       the squares closest to the capital filled first. A full 20-fleet set
       lands as the 5×4 block FFFFF / FFFFF / FFFFF / FFCFF. */
    var GCOLS = Math.max(1, Math.round(BOARD_W / GX));
    var GROWS = Math.max(1, Math.round(BOARD_H / GY));
    function snap(cx, cy) {
      var col = clamp(Math.round(cx / GX), 0, GCOLS);
      var row = clamp(Math.round(cy / GY), 0, GROWS);
      return { x: Math.round(col * GX), y: Math.round(row * GY) };
    }
    function cellXY(col, row) { return { x: Math.round(col * GX), y: Math.round(row * GY) }; }

    var capCol = Math.round(GCOLS / 2);    // bottom-centre of the board
    var capRow = GROWS - 1;
    var capPx  = cellXY(capCol, capRow);
    var nNonCap = fleets.reduce(function (n, f) { return n + (f.isCap ? 0 : 1); }, 0);

    // Candidate squares: 5 columns centred on the capital, rows stacked upward,
    // enough to hold every fleet; ordered by pixel distance to the capital so
    // the nearest squares are always used first.
    var BLOCK_W = 5, half = (BLOCK_W - 1) / 2;
    var needRows = Math.max(1, Math.ceil(nNonCap / BLOCK_W) + 1);
    var cands = [];
    for (var rr = capRow; rr > capRow - needRows && rr >= 0; rr--) {
      for (var dc = -half; dc <= half; dc++) {
        var cc = clamp(capCol + dc, 0, GCOLS);
        if (cc === capCol && rr === capRow) continue;   // the capital's own square
        var p = cellXY(cc, rr);
        cands.push({ x: p.x, y: p.y,
          d: (p.x - capPx.x) * (p.x - capPx.x) + (p.y - capPx.y) * (p.y - capPx.y) });
      }
    }
    cands.sort(function (a, b) { return a.d - b.d; });

    var ci = 0;
    fleets.forEach(function (f) {
      if (f.isCap) { f.x = capPx.x; f.y = capPx.y; }
      else { var c = cands[ci++] || capPx; f.x = c.x; f.y = c.y; }
    });

    /* 4. Ship images (original Java assets). */
    var imgFleet = new Image(), imgCap = new Image(), loaded = 0;
    function onload() { if (++loaded >= 2) draw(); }
    imgFleet.onload = imgCap.onload = imgFleet.onerror = imgCap.onerror = onload;
    imgFleet.src = imgDir + 'ship_set.gif';
    imgCap.src   = imgDir + 'ship_cap.gif';

    /* 5. Render. */
    function draw() {
      ctx.fillStyle = COLOR_BG; ctx.fillRect(0, 0, BOARD_W, BOARD_H);
      ctx.strokeStyle = COLOR_GRID; ctx.lineWidth = 1;
      for (var gx = 0; gx <= BOARD_W + 0.5; gx += GX) { var x = Math.round(gx) + 0.5; ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, BOARD_H); ctx.stroke(); }
      for (var gy = 0; gy <= BOARD_H + 0.5; gy += GY) { var y = Math.round(gy) + 0.5; ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(BOARD_W, y); ctx.stroke(); }
      for (var fi = fleets.length - 1; fi >= 0; fi--) drawFleet(fleets[fi], fi === dragIndex);
      ctx.fillStyle = '#33466a'; ctx.font = '10px sans-serif'; ctx.textAlign = 'right';
      ctx.fillText('Drag fleets to a grid square, then Deploy. (Capital is fixed.)', BOARD_W - 6, BOARD_H - 4);
    }
    function drawFleet(f, sel) {
      var img = f.isCap ? imgCap : imgFleet;
      if (sel) {                                    // drag highlight: a soft cell halo
        ctx.fillStyle = f.isCap ? 'rgba(255,120,90,.35)' : 'rgba(120,170,255,.35)';
        ctx.fillRect(Math.round(f.x - GX / 2), Math.round(f.y - GY / 2), Math.round(GX), Math.round(GY));
      }
      if (img.complete && img.naturalWidth) {       // the original Java ship icons
        var scale = ICON_H / img.naturalHeight;
        var w = img.naturalWidth * scale;
        ctx.drawImage(img, Math.round(f.x - w / 2), Math.round(f.y - ICON_H / 2), Math.round(w), ICON_H);
      } else {                                       // fallback if the gif failed to load
        ctx.fillStyle = f.isCap ? RED : BLUE;
        ctx.fillRect(Math.round(f.x - 6), Math.round(f.y - 7), 12, 14);
      }
      ctx.fillStyle = '#cdd9f2'; ctx.font = '8px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText((f.isCap ? '★' : '') + f.id, f.x, f.y + ICON_H / 2 + 7);
    }

    /* 6. Drag + snap-to-grid (capital excluded). */
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
      return { x: (t.clientX - r.left) * (canvas.width / r.width), y: (t.clientY - r.top) * (canvas.height / r.height) };
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
      fleets[dragIndex].x = clamp(Math.round(p.x - offX), 0, BOARD_W);
      fleets[dragIndex].y = clamp(Math.round(p.y - offY), 0, BOARD_H);
      draw();
    }
    function up() {
      if (dragIndex === -1) return;
      var f = fleets[dragIndex], s = snap(f.x, f.y);   // snap to nearest grid square
      f.x = s.x; f.y = s.y; dragIndex = -1; draw();
    }
    canvas.addEventListener('mousedown', down);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', up);
    canvas.addEventListener('mouseleave', up);
    canvas.addEventListener('touchstart', down, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', up);

    /* 7. Submit: the full POST contract (capital = battle-fleet 1; rest from 2),
       positions mapped into the battle coordinate space (X 9..609, Y 226..426). */
    form.addEventListener('submit', function () {
      function ex(cx) { return Math.round(9 + (cx / BOARD_W) * 600); }
      function ey(cy) { return Math.round(226 + (cy / BOARD_H) * 200); }
      ensureHidden(form, 'FLEET_NUMBER', String(fleets.length));
      var n = 2;
      fleets.forEach(function (f) {
        if (f.isCap) {
          ensureHidden(form, 'capFleet_ID', String(f.id));
          ensureHidden(form, 'capFleet_O', 'NORMAL');
        } else {
          ensureHidden(form, 'Fleet' + n + '_ID', String(f.id));
          ensureHidden(form, 'Fleet' + n + '_X', String(ex(f.x)));
          ensureHidden(form, 'Fleet' + n + '_Y', String(ey(f.y)));
          ensureHidden(form, 'fleet' + n + '_O', 'NORMAL');
          n++;
        }
      });
    });

    draw();
  });

  function clamp(n, lo, hi) { return n < lo ? lo : (n > hi ? hi : n); }
  function ensureHidden(form, name, value) {
    var e = form.querySelector('input[name="' + name + '"]');
    if (!e) { e = document.createElement('input'); e.type = 'hidden'; e.name = name; form.appendChild(e); }
    e.value = value; return e;
  }
}());
