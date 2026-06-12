/**
 * as-deploy.js — HTML5 battle-deployment board (grid edition).
 *
 * Replaces the dead Java Deployment applet. Reads the fleet <param> data the
 * engine injects into #as-deploy-params and lays the fleets out on a compact
 * block hugging the capital (capital at the vertical middle, fleets stacked in
 * front). Fleets are the original Java ship images (ship_set.gif / ship_cap.gif).
 * Dragging a fleet SNAPS it to the original applet's 20-unit battle grid
 * (X 9..609, Y 226..426); the capital is fixed.
 *
 * Per-fleet stance: right-click (desktop) or long-press (mobile) a fleet to pick
 * one of the 8 battle stances (default Formation). The chosen stance is drawn
 * under the marker and submitted as capFleet_O / fleet{n}_O.
 *
 * On submit the full POST contract the .as handler expects is written
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

  // The 8 battle stances the engine accepts
  // (CDefenseFleet::get_fleet_command_from_string). key = submit value
  // (uppercase); name = display; ab = short tag drawn under the marker.
  var STANCES = [
    { key: 'NORMAL',       name: 'Normal',       ab: 'NRM' },
    { key: 'FORMATION',    name: 'Formation',    ab: 'FRM' },
    { key: 'PENETRATE',    name: 'Penetrate',    ab: 'PEN' },
    { key: 'FLANK',        name: 'Flank',        ab: 'FLK' },
    { key: 'RESERVE',      name: 'Reserve',      ab: 'RSV' },
    { key: 'FREE',         name: 'Free',         ab: 'FRE' },
    { key: 'ASSAULT',      name: 'Assault',      ab: 'ASL' },
    { key: 'STAND_GROUND', name: 'Stand Ground', ab: 'STG' }
  ];
  var DEFAULT_ORDER = 'FORMATION';
  function isStance(k) { for (var i = 0; i < STANCES.length; i++) if (STANCES[i].key === k) return true; return false; }
  function stanceAb(k) { for (var i = 0; i < STANCES.length; i++) if (STANCES[i].key === k) return STANCES[i].ab; return k; }
  // Seed a fleet's stance from the engine's incoming order param. The engine's
  // blank default is "Normal"; treat that (and anything unrecognized) as unset
  // so new deployments default to Formation, while an explicitly-saved non-Normal
  // stance (e.g. a re-edited defense plan) is respected.
  function seedOrder(v) {
    if (!v) return DEFAULT_ORDER;
    var u = String(v).toUpperCase().replace(/\s+/g, '_');
    return (isStance(u) && u !== 'NORMAL') ? u : DEFAULT_ORDER;
  }

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

    /* 2. Build fleets (capital = index 0 → capFleet_*; rest = Fleet1..). Each
       carries an order (stance), default Formation, seeded from the param. */
    var fleets = [];
    if (params['capFleet_ID'] !== undefined)
      fleets.push({ key: 'capFleet', id: params['capFleet_ID'] || '', order: seedOrder(params['capFleet_O']), isCap: true });
    var idx = 1;
    while (params['Fleet' + idx + '_ID'] !== undefined) {
      fleets.push({ key: 'Fleet' + idx, id: params['Fleet' + idx + '_ID'] || '', order: seedOrder(params['Fleet' + idx + '_O']), isCap: false });
      idx++;
    }

    /* 3. Default formation: a compact 5-wide block of adjacent grid squares
       hugging the capital. The capital sits at the vertical middle of the grid
       (centre column) and the other fleets stack upward in front of it, with
       the squares closest to the capital filled first -- leaving the rows
       behind the capital free for the player to drag fleets into if they want. */
    var GCOLS = Math.max(1, Math.round(BOARD_W / GX));
    var GROWS = Math.max(1, Math.round(BOARD_H / GY));
    function cellOf(cx, cy) {
      return { col: clamp(Math.round(cx / GX), 0, GCOLS), row: clamp(Math.round(cy / GY), 0, GROWS) };
    }
    function cellXY(col, row) { return { x: Math.round(col * GX), y: Math.round(row * GY) }; }
    // Snap (cx,cy) to the nearest FREE grid square, treating every other fleet's
    // square (capital included) as occupied. The engine rejects a deployment with
    // two fleets sharing a square, so dropping onto a taken cell spills outward to
    // the closest empty one rather than stacking.
    function snapFree(idx, cx, cy) {
      var occ = {}, i, c;
      for (i = 0; i < fleets.length; i++) {
        if (i === idx) continue;
        c = cellOf(fleets[i].x, fleets[i].y);
        occ[c.col + ',' + c.row] = true;
      }
      var t = cellOf(cx, cy);
      if (!occ[t.col + ',' + t.row]) return cellXY(t.col, t.row);
      var maxR = Math.max(GCOLS, GROWS);
      for (var rad = 1; rad <= maxR; rad++) {
        var best = null, bestD = Infinity;
        for (var dc = -rad; dc <= rad; dc++) {
          for (var dr = -rad; dr <= rad; dr++) {
            if (Math.max(Math.abs(dc), Math.abs(dr)) !== rad) continue;   // ring only
            var col = t.col + dc, row = t.row + dr;
            if (col < 0 || col > GCOLS || row < 0 || row > GROWS) continue;
            if (occ[col + ',' + row]) continue;
            var px = col * GX - cx, py = row * GY - cy, d = px * px + py * py;
            if (d < bestD) { bestD = d; best = { col: col, row: row }; }
          }
        }
        if (best) return cellXY(best.col, best.row);
      }
      return cellXY(t.col, t.row);
    }

    var capCol = Math.round(GCOLS / 2);    // centre column, vertical middle
    var capRow = Math.round(GROWS / 2);
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
      ctx.fillText('Drag to move; right-click / long-press a fleet to set its stance.', BOARD_W - 6, BOARD_H - 4);
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
      ctx.textAlign = 'center';
      ctx.font = '8px sans-serif';  ctx.fillStyle = '#cdd9f2';
      ctx.fillText((f.isCap ? '★' : '') + f.id, f.x, f.y + ICON_H / 2 + 7);
      ctx.font = '7px sans-serif';  ctx.fillStyle = '#8fb0ff';
      ctx.fillText(stanceAb(f.order), f.x, f.y + ICON_H / 2 + 15);
    }

    /* 5b. Stance menu (right-click / long-press a fleet). One reusable popup. */
    var menu = document.createElement('div');
    menu.style.cssText = 'position:fixed;z-index:99999;display:none;min-width:122px;' +
      'background:#0b1426;border:1px solid #2f4a78;border-radius:4px;padding:3px 0;' +
      'box-shadow:0 4px 14px rgba(0,0,0,.6);font:12px/1.55 sans-serif;color:#cdd9f2;';
    document.body.appendChild(menu);
    function hideMenu() { menu.style.display = 'none'; }
    function showStanceMenu(clientX, clientY, fi) {
      var f = fleets[fi];
      menu.innerHTML = '';
      var head = document.createElement('div');
      head.textContent = (f.isCap ? '★ ' : '') + 'Fleet ' + f.id + ' — stance';
      head.style.cssText = 'padding:2px 12px 4px;color:#7f93b8;font-size:11px;border-bottom:1px solid #1d2b47;margin-bottom:3px;';
      menu.appendChild(head);
      STANCES.forEach(function (s) {
        var item = document.createElement('div');
        item.textContent = (f.order === s.key ? '✓ ' : '   ') + s.name;
        item.style.cssText = 'padding:3px 12px;cursor:pointer;white-space:nowrap;' + (f.order === s.key ? 'color:#8fb0ff;' : '');
        item.onmouseover = function () { item.style.background = '#1b2c4d'; };
        item.onmouseout  = function () { item.style.background = ''; };
        item.onclick = function (ev) { ev.stopPropagation(); f.order = s.key; hideMenu(); draw(); };
        menu.appendChild(item);
      });
      menu.style.display = 'block';
      var mw = menu.offsetWidth, mh = menu.offsetHeight;
      menu.style.left = Math.max(2, Math.min(clientX, window.innerWidth  - mw - 4)) + 'px';
      menu.style.top  = Math.max(2, Math.min(clientY, window.innerHeight - mh - 4)) + 'px';
    }
    document.addEventListener('mousedown', function (e) { if (menu.style.display === 'block' && !menu.contains(e.target)) hideMenu(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' || e.keyCode === 27) hideMenu(); });

    /* 6. Drag + snap-to-grid (capital excluded) + long-press stance menu. */
    var dragIndex = -1, offX = 0, offY = 0;
    var lpTimer = null, lpStart = null, lpFired = false;   // long-press (touch)
    function clearLP() { if (lpTimer) { clearTimeout(lpTimer); lpTimer = null; } }
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
      hideMenu();
      var p = eventPos(e), hi = hitTest(p.x, p.y);
      // Touch long-press on a marker -> stance menu (cancelled by movement/release).
      if (e.touches && hi !== -1) {
        lpFired = false; lpStart = p;
        var t = e.touches[0], cx = t.clientX, cy = t.clientY;
        clearLP();
        lpTimer = setTimeout(function () { lpFired = true; dragIndex = -1; showStanceMenu(cx, cy, hi); draw(); }, 450);
      }
      if (hi !== -1 && !fleets[hi].isCap) { dragIndex = hi; offX = p.x - fleets[hi].x; offY = p.y - fleets[hi].y; }
      draw();
    }
    function move(e) {
      if (lpTimer) {
        var pp = eventPos(e);
        if (!lpStart || Math.abs(pp.x - lpStart.x) > 6 || Math.abs(pp.y - lpStart.y) > 6) clearLP();
      }
      if (dragIndex === -1) return; e.preventDefault();
      var p = eventPos(e);
      fleets[dragIndex].x = clamp(Math.round(p.x - offX), 0, BOARD_W);
      fleets[dragIndex].y = clamp(Math.round(p.y - offY), 0, BOARD_H);
      draw();
    }
    function up() {
      clearLP();
      if (lpFired) { lpFired = false; dragIndex = -1; return; }   // long-press handled; don't snap/move
      if (dragIndex === -1) return;
      var f = fleets[dragIndex], s = snapFree(dragIndex, f.x, f.y);   // snap to nearest free grid square
      f.x = s.x; f.y = s.y; dragIndex = -1; draw();
    }
    canvas.addEventListener('mousedown', down);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', up);
    canvas.addEventListener('mouseleave', up);
    canvas.addEventListener('touchstart', down, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', up);
    // Right-click a marker -> stance menu (and suppress the browser image menu).
    canvas.addEventListener('contextmenu', function (e) {
      e.preventDefault();
      var p = eventPos(e), hi = hitTest(p.x, p.y);
      if (hi !== -1) showStanceMenu(e.clientX, e.clientY, hi);
    });

    /* 7. Submit: the full POST contract (capital = battle-fleet 1; rest from 2),
       positions mapped into the battle coordinate space (X 9..609, Y 226..426),
       each fleet's chosen stance in capFleet_O / fleet{n}_O. */
    form.addEventListener('submit', function () {
      hideMenu();
      function ex(cx) { return Math.round(9 + (cx / BOARD_W) * 600); }
      function ey(cy) { return Math.round(226 + (cy / BOARD_H) * 200); }
      ensureHidden(form, 'FLEET_NUMBER', String(fleets.length));
      var n = 2;
      fleets.forEach(function (f) {
        if (f.isCap) {
          ensureHidden(form, 'capFleet_ID', String(f.id));
          ensureHidden(form, 'capFleet_O', f.order);
        } else {
          ensureHidden(form, 'Fleet' + n + '_ID', String(f.id));
          ensureHidden(form, 'Fleet' + n + '_X', String(ex(f.x)));
          ensureHidden(form, 'Fleet' + n + '_Y', String(ey(f.y)));
          ensureHidden(form, 'fleet' + n + '_O', f.order);
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
