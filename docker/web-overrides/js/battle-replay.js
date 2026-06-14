/*
  battle-replay.js — HTML5 battle replay.

  Replaces the original Archspace battle-viewer Java applet (Report.class /
  report.jar) with a canvas animation + synced event ticker, parsing the
  turn-by-turn battle log the engine already writes for every battle.

  Mount point (set by src/web/war/battle_report2.html):
    <div id="battle-replay" data-log="$LOG_URL" data-player="$PLAYER_ID"></div>

  Two layouts, switchable from the controls bar and remembered in localStorage:
    - inline    : the compact 480² board with the ticker stacked below, sized to
                  fit the cramped 610px report page.
    - widescreen: a full-viewport overlay with a large board and the event ticker
                  as a tall side column — easier to read on a wide monitor. The
                  "⛶ Widescreen" button opts in; "▭ Standard view" / Esc / a click
                  on the backdrop returns. The current turn + play state carry over.

  $LOG_URL is the engine's absolute path
  (/var/archspace/data/battle/<dayIdx>/<id>); nginx serves that dir at
  /battle_log/, so we map the path and fetch it. The log format (battle.cc):
    FIELD/  ATTACKER/name/id/race   DEFENDER/name/id/race   TIME/  CAPITAL/  ALLIANCE/
    FL/owner/id/nick/admiral/class/NONE/ships/x/y/dir/cmd      (roster, once)
    M/turn/owner/id/x/y/dir/cmd/substatus/ships               (position, every 10 turns)
    F/fireid/turn/attOwner/attId/tgtOwner/tgtId/weapon/type/numFiring/hitChance
    H/fireid/turn/hits/misses/damage/sunk
    D/turn/owner/id                                            (fleet disabled)
    ENDTURN/finalTurn
  Names are escaped `/`->`\/` (CString::mark_forward_slashes); we un-escape.
*/

(function () {
  var mount = document.getElementById('battle-replay');
  if (!mount) return;

  var logPath = mount.getAttribute('data-log') || '';
  var viewerId = mount.getAttribute('data-player') || '';
  var imgBase = mount.getAttribute('data-img') || '';   // optional $IMAGE_SERVER_URL

  // race id -> image-folder name (src/script/race.en Number()s; folders under
  // /image/as_game/race/<name>/small_symbol.gif)
  var RACE_NAMES = {
    1: 'human', 2: 'targoid', 3: 'buckaneer', 4: 'tecanoid', 5: 'evintos',
    6: 'agerus', 7: 'bosalian', 8: 'xeloss', 9: 'xerusian', 10: 'xesperados'
  };
  function raceLogo(raceId) {
    var name = RACE_NAMES[raceId];
    if (!name) return null;
    // Production collapses image assets to this origin (/image/...), and the
    // battle-report page doesn't define IMAGE_SERVER_URL, so data-img can arrive
    // as the literal unsubstituted token — ignore that and fall back to /image.
    var base = (imgBase && imgBase.indexOf('$') === -1) ? imgBase : '';
    return base + '/image/as_game/race/' + name + '/small_symbol.gif';
  }

  // Map the engine's filesystem path to the web route nginx serves.
  var m = logPath.match(/\/battle\/(.+)$/);
  var logUrl = m ? '/battle_log/' + m[1] : null;

  // ---- colors / sizing ----------------------------------------------------
  var ATT = '#ff8844', ATT_DIM = '#7a3a1c';   // attacker (orange)
  var DEF = '#55bbff', DEF_DIM = '#1c4a6e';   // defender (cyan)
  var CW = 480, CH = 480;                       // inline canvas size (square: the arena is 10000×10000)
  var FIELD = 10000;                            // battlefield is clamped to 0..10000 on both axes
  var BTN = 'background:#16243a;color:#cde;border:1px solid #2a4a6a;' +
            'border-radius:4px;padding:3px 12px;cursor:pointer;';
  var WIDE_KEY = 'as.battleReplay.wide';        // remembered layout opt-in
  function prefWide() { try { return localStorage.getItem(WIDE_KEY) === '1'; } catch (e) { return false; } }
  function setPrefWide(v) { try { localStorage.setItem(WIDE_KEY, v ? '1' : '0'); } catch (e) {} }
  // CBattleFleet morale-break statuses (battle.h enum): each flickers + tints the
  // icon and stamps a label on it (Ro=Rout, Rt=Retreat to keep them distinct; the
  // full name is also shown in the text label).
  var STATUS_FX = {
    8:  { ch: 'B',  name: 'BERSERK',  color: '#ff8a00' },
    9:  { ch: 'D',  name: 'DISORDER', color: '#b066ff' },
    10: { ch: 'Ro', name: 'ROUT',     color: '#ffd633' },
    11: { ch: 'Rt', name: 'RETREAT',  color: '#5bc0ff' },
    12: { ch: 'P',  name: 'PANIC',    color: '#ff5577' }
  };

  function el(tag, css, html) {
    var e = document.createElement(tag);
    if (css) e.style.cssText = css;
    if (html != null) e.innerHTML = html;
    return e;
  }
  function notice(msg) {
    mount.innerHTML = '';
    mount.appendChild(el('div',
      'width:' + CW + 'px;max-width:100%;margin:0 auto;background:#050510;' +
      'border:1px solid #223355;color:#889;font:13px sans-serif;padding:28px 24px;' +
      'box-sizing:border-box;text-align:center;', msg));
  }

  var parser = window.ArchspaceBattleReplayParser;
  if (!parser || typeof parser.parse !== 'function') {
    notice('Battle replay parser is not available.');
    return;
  }
  var parse = parser.parse;
  var num = parser.num;

  if (!logUrl) { notice('Battle replay is not available for this report.'); return; }

  // fleet state (interpolated) at turn t, or null if not yet present / gone
  function stateAt(fl, t) {
    if (fl.disabledTurn != null && t >= fl.disabledTurn) return null;
    var s = fl.samples, n = s.length;
    if (!n) return null;
    if (t <= s[0].turn) return { x: s[0].x, y: s[0].y, dir: s[0].dir, ships: s[0].ships, cmd: s[0].cmd };
    for (var i = 0; i < n - 1; i++) {
      if (t >= s[i].turn && t <= s[i + 1].turn) {
        var a = s[i], b = s[i + 1], span = (b.turn - a.turn) || 1, f = (t - a.turn) / span;
        return { x: a.x + (b.x - a.x) * f, y: a.y + (b.y - a.y) * f,
                 dir: a.dir, ships: a.ships, cmd: a.cmd };
      }
    }
    var last = s[n - 1];
    return { x: last.x, y: last.y, dir: last.dir, ships: last.ships, cmd: last.cmd };
  }

  // ---- combatant header ---------------------------------------------------
  // One side panel: race logo + "Name(serial)" + a FLEETS / SHIPS line whose
  // numbers update per turn (returns the stats element so buildHeader can
  // refresh it as fleets are disabled and ships are sunk).
  function sidePanel(name, serial, raceId, accent, align) {
    var box = el('div', 'flex:1;min-width:0;display:flex;flex-direction:column;' +
      'gap:2px;text-align:' + align + ';');
    var titleRow = el('div', 'display:flex;align-items:center;gap:5px;' +
      'justify-content:' + (align === 'right' ? 'flex-end' : 'flex-start') + ';');
    var logo = raceLogo(raceId);
    var logoImg = logo ? '<img src="' + logo + '" alt="" ' +
      'style="height:16px;vertical-align:middle;flex:none;">' : '';
    var label = (name || 'Unknown').replace(/</g, '&lt;') +
      (serial ? '(' + serial + ')' : '');
    var nameSpan = '<span style="color:' + accent + ';font:bold 13px serif;' +
      'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + label + '</span>';
    titleRow.innerHTML = align === 'right' ? (nameSpan + logoImg) : (logoImg + nameSpan);
    box.appendChild(titleRow);
    var statsEl = el('div', 'font:11px serif;color:#9ab;');
    box.appendChild(statsEl);
    box._stats = statsEl;
    return box;
  }
  function fmtStats(fleets, ships) {
    return '<span style="color:#667;">FLEETS</span> ' + fleets +
           ' &nbsp; <span style="color:#667;">SHIPS</span> ' + ships;
  }
  function buildHeader(B) {
    var hdr = el('div', 'display:flex;align-items:flex-start;gap:8px;' +
      'background:#070713;border:1px solid #223355;border-bottom:none;' +
      'padding:6px 10px;box-sizing:border-box;');
    var att = sidePanel(B.attackerName, B.attackerId, B.attackerRace, ATT, 'left');
    hdr.appendChild(att);
    hdr.appendChild(el('div',
      'flex:none;align-self:center;color:#cdd;font:bold 13px serif;' +
      'text-align:center;padding:0 6px;white-space:nowrap;',
      (B.field || 'Battle').replace(/</g, '&lt;')));
    var def = sidePanel(B.defenderName, B.defenderId, B.defenderRace, DEF, 'right');
    hdr.appendChild(def);

    // per-side fleet lists for live tallies (ships sunk / fleets destroyed)
    var sides = { att: [], def: [] };
    for (var k in B.fleets) sides[B.fleets[k].side].push(B.fleets[k]);
    function tally(list, t) {
      var fleets = 0, ships = 0;
      for (var i = 0; i < list.length; i++) {
        var st = stateAt(list[i], t);
        if (st) { fleets++; ships += st.ships; }
      }
      return { fleets: fleets, ships: ships };
    }
    function update(t) {
      var a = tally(sides.att, t), d = tally(sides.def, t);
      att._stats.innerHTML = fmtStats(a.fleets, a.ships);
      def._stats.innerHTML = fmtStats(d.fleets, d.ships);
    }
    return { el: hdr, update: update };
  }

  // ---- one replay UI instance --------------------------------------------
  // Build a full replay UI (header, canvas, controls, ticker) into `host`.
  //   opts.wide       : widescreen layout (board left, ticker as a tall side rail)
  //   opts.cw         : square canvas size in px
  //   opts.modeBtn    : { label, onClick } for the layout-switch button
  //   opts.turn       : turn to start at (carried over when switching layout)
  //   opts.playing    : resume playing immediately
  // Returns { dispose, getTurn, isPlaying }.
  function renderInto(B, host, opts) {
    var cw = opts.cw, ch = opts.cw;          // arena is square
    var wide = !!opts.wide;
    // Side-rail (summary) width is fixed up front so the pane is full size from
    // turn 0 — never shrink-to-fit on the log text (which would start narrow and
    // widen as longer attack lines stream in).
    var railW = Math.max(260, Math.min(380, Math.round(cw * 0.6)));
    var STAGE_GAP = 10;
    host.innerHTML = '';

    // Fixed total width in widescreen (board + gap + rail) so nothing reflows as
    // the ticker fills; inline stays its single-column width.
    var wrap = el('div', (wide
      ? 'width:' + (cw + STAGE_GAP + railW) + 'px;'
      : 'width:' + cw + 'px;') + 'max-width:100%;margin:0 auto;text-align:left;');
    host.appendChild(wrap);

    // ---- combatant header: race logo, name(serial), fleets & ships per side --
    var header = buildHeader(B);
    wrap.appendChild(header.el);

    // stage: widescreen lays the board and ticker side by side; inline stacks them.
    var stage = el('div', wide ? 'display:flex;gap:' + STAGE_GAP + 'px;align-items:stretch;' : '');
    wrap.appendChild(stage);
    var leftcol = wide ? el('div', 'display:flex;flex-direction:column;flex:none;') : stage;
    if (wide) stage.appendChild(leftcol);

    var canvas = el('canvas', 'display:block;background:#04040c;border:1px solid #223355;' +
      (wide ? 'width:' + cw + 'px;height:' + ch + 'px;' : 'width:100%;'));
    canvas.width = cw; canvas.height = ch;
    leftcol.appendChild(canvas);
    var ctx = canvas.getContext('2d');

    // controls
    var bar = el('div', 'display:flex;align-items:center;gap:8px;margin:8px 0;' +
      'font:12px sans-serif;color:#9ab;');
    var playBtn = el('button', BTN, '▶ Play');
    var slider = el('input'); slider.type = 'range'; slider.min = 0; slider.max = B.endTurn || 1;
    slider.value = 0; slider.style.cssText = 'flex:1;';
    var turnLbl = el('span', 'min-width:96px;text-align:right;', 'Turn 0 / ' + B.endTurn);
    var speedSel = el('select', 'background:#16243a;color:#cde;border:1px solid #2a4a6a;border-radius:4px;padding:2px;');
    [['1×', 1], ['2×', 2], ['4×', 4], ['8×', 8]].forEach(function (o) {
      var op = el('option', '', o[0]); op.value = o[1]; if (o[1] === 2) op.selected = true; speedSel.appendChild(op);
    });
    var modeBtn = el('button', BTN, opts.modeBtn.label);
    modeBtn.onclick = opts.modeBtn.onClick;
    bar.appendChild(playBtn); bar.appendChild(slider); bar.appendChild(speedSel);
    bar.appendChild(turnLbl); bar.appendChild(modeBtn);
    leftcol.appendChild(bar);

    // ticker — below the board (inline) or a tall side rail (widescreen). The rail
    // has a FIXED width/height so the summary pane is full size from the start and
    // doesn't grow as longer attack lines stream in; long lines wrap inside it.
    var ticker = el('div', (wide
        ? 'flex:none;width:' + railW + 'px;height:' + (ch + 44) + 'px;'
        : 'height:120px;') +
      'overflow-y:auto;overflow-wrap:break-word;background:#05050f;border:1px solid #223355;' +
      'font:12px/1.5 monospace;color:#9ab;padding:6px 10px;box-sizing:border-box;');
    if (wide) stage.appendChild(ticker); else wrap.appendChild(ticker);

    var fleetList = []; for (var k in B.fleets) fleetList.push(B.fleets[k]);

    // Fixed full-arena projection. Depth (engine X) is horizontal: attacker (low X)
    // left, defender (high X) right. Lateral (engine Y) is vertical and FLIPPED so
    // engine Y increases upward — this matches both deploy boards (attacker's right
    // -> bottom, defender's right -> top) and pins engine Y=5000 (the engagement
    // line where both capitals sit) to the exact vertical centre.
    function tx(x) { return x / FIELD * cw; }
    function ty(y) { return (FIELD - y) / FIELD * ch; }

    function drawFleet(st, fl) {
      var x = tx(st.x), y = ty(st.y);
      var r = Math.max(4, Math.min(16, 3 + Math.sqrt(st.ships || 1) * 1.6));
      var col = fl.side === 'att' ? ATT : DEF;
      // morale-break status: flicker the marker (keeping its side colour so blue
      // fleets flicker blue and orange flicker orange), stamp its letter on the
      // icon, and show the full name in the label.
      var fx = STATUS_FX[st.cmd];
      var alpha = 1, tag = '';
      if (fx) {
        alpha = 0.3 + 0.7 * Math.abs(Math.sin(Date.now() / 120));
        tag = ' ⚠ ' + fx.name;
      }
      var rad = (st.dir || 0) * Math.PI / 180;
      ctx.save(); ctx.globalAlpha = alpha; ctx.translate(x, y); ctx.rotate(-rad);   // -rad: ty flips the y axis (cy ∝ −y)
      ctx.fillStyle = col; ctx.beginPath();
      ctx.moveTo(r, 0); ctx.lineTo(-r * 0.7, r * 0.7); ctx.lineTo(-r * 0.7, -r * 0.7);
      ctx.closePath(); ctx.fill();
      ctx.restore();
      if (fx) {   // status label, black, upright and centered on the icon
        ctx.save();
        ctx.globalAlpha = alpha;
        ctx.font = 'bold 10px sans-serif';
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.lineWidth = 2.5; ctx.strokeStyle = 'rgba(255,255,255,0.85)';  // light halo for legibility
        ctx.strokeText(fx.ch, x, y);
        ctx.fillStyle = '#000'; ctx.fillText(fx.ch, x, y);
        ctx.restore();
      }
      ctx.save();
      ctx.globalAlpha = fx ? alpha : 1;
      ctx.fillStyle = fx ? col : '#7d8aa0';
      ctx.font = '9px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText(fl.nick + ' (' + st.ships + ')' + tag, x, y - r - 3);
      ctx.restore();
    }

    function render(t) {
      ctx.clearRect(0, 0, cw, ch);
      // fire lines for this turn
      var fires = B.firesByTurn[Math.round(t)] || [];
      for (var i = 0; i < fires.length; i++) {
        var fr = fires[i], a = B.fleets[fr.from], d = B.fleets[fr.to];
        if (!a || !d) continue;
        var sa = stateAt(a, t), sd = stateAt(d, t);
        if (!sa || !sd) continue;
        ctx.strokeStyle = fr.hits > 0 ? (fr.sunk > 0 ? '#ffee66' : '#88ff99') : 'rgba(150,150,170,0.35)';
        ctx.lineWidth = fr.hits > 0 ? 1.6 : 0.7;
        ctx.beginPath(); ctx.moveTo(tx(sa.x), ty(sa.y)); ctx.lineTo(tx(sd.x), ty(sd.y)); ctx.stroke();
      }
      // fleets
      for (var j = 0; j < fleetList.length; j++) {
        var st = stateAt(fleetList[j], t);
        if (st) drawFleet(st, fleetList[j]);
      }
    }

    // ticker: rebuild up to turn t (cheap; endTurn is small enough)
    var lastTickTurn = -1;
    function renderTicker(t) {
      t = Math.round(t);
      if (t === lastTickTurn) return;
      lastTickTurn = t;
      var html = '';
      for (var tt = 0; tt <= t; tt++) {
        var evs = B.eventsByTurn[tt];
        if (!evs) continue;
        for (var e = 0; e < evs.length; e++) {
          html += '<div><span style="color:#566">T' + tt + '</span> ' +
                  evs[e].replace(/</g, '&lt;') + '</div>';
        }
      }
      ticker.innerHTML = html || '<div style="color:#566">No fleet engaged this battle.</div>';
      ticker.scrollTop = ticker.scrollHeight;
    }

    var cur = 0, playing = false, raf = null, acc = 0, last = 0;
    // is any visible fleet in a rout/panic state at turn t? (drives the flicker)
    function anyAbnormal(t) {
      for (var i = 0; i < fleetList.length; i++) {
        var st = stateAt(fleetList[i], t);
        if (st && STATUS_FX[st.cmd]) return true;
      }
      return false;
    }
    // standalone repaint loop so rout/panic markers keep flickering while paused
    var flickerReq = null;
    function flicker() {
      flickerReq = null;
      if (playing) return;                 // playback's own tick repaints during play
      render(cur);
      if (anyAbnormal(cur)) flickerReq = requestAnimationFrame(flicker);
    }
    function ensureFlicker() { if (!flickerReq && !playing && anyAbnormal(cur)) flickerReq = requestAnimationFrame(flicker); }
    function setTurn(t) {
      cur = Math.max(0, Math.min(B.endTurn, t));
      slider.value = cur;
      turnLbl.textContent = 'Turn ' + Math.round(cur) + ' / ' + B.endTurn;
      render(cur); renderTicker(cur); header.update(cur);
      ensureFlicker();
    }
    function tick(ts) {
      if (!playing) return;
      if (!last) last = ts;
      var dt = (ts - last) / 1000; last = ts;
      acc += dt * num(speedSel.value) * 12; // ~12 turns/sec at 1x
      if (acc >= 1) { setTurn(cur + Math.floor(acc)); acc -= Math.floor(acc); }
      else render(cur);                      // repaint between advances so rout/panic still flickers
      if (cur >= B.endTurn) { stop(); return; }
      raf = requestAnimationFrame(tick);
    }
    function play() { if (playing) return; if (cur >= B.endTurn) setTurn(0); playing = true; last = 0; acc = 0; playBtn.innerHTML = '❚❚ Pause'; raf = requestAnimationFrame(tick); }
    function stop() { playing = false; if (raf) cancelAnimationFrame(raf); playBtn.innerHTML = '▶ Play'; ensureFlicker(); }
    playBtn.onclick = function () { playing ? stop() : play(); };
    slider.oninput = function () { stop(); setTurn(num(slider.value)); };

    setTurn(opts.turn || 0);
    if (opts.playing) play();

    return {
      // cancel every pending rAF so a layout switch never leaks a repaint loop.
      dispose: function () {
        playing = false;
        if (raf) cancelAnimationFrame(raf);
        if (flickerReq) cancelAnimationFrame(flickerReq);
      },
      getTurn: function () { return cur; },
      isPlaying: function () { return playing; }
    };
  }

  // ---- layout controller (inline <-> widescreen overlay) ------------------
  var disposeActive = null;   // tears down whatever viewer is currently live

  // Square board size for the overlay: as big as the viewport allows while
  // leaving room for the header/controls (height) and the side ticker (width).
  function wideSize() {
    var byH = window.innerHeight - 170;
    var byW = window.innerWidth - 360;
    return Math.max(340, Math.min(720, byH, byW));
  }

  function showInline(B, turn, playing) {
    if (disposeActive) { disposeActive(); disposeActive = null; }
    var h = renderInto(B, mount, {
      wide: false, cw: CW, turn: turn, playing: playing,
      modeBtn: {
        label: '⛶ Widescreen',
        onClick: function () { setPrefWide(true); showWide(B, h.getTurn(), h.isPlaying()); }
      }
    });
    disposeActive = h.dispose;
  }

  function showWide(B, turn, playing) {
    if (disposeActive) { disposeActive(); disposeActive = null; }

    var backdrop = el('div', 'position:fixed;inset:0;z-index:99998;background:rgba(2,2,10,0.93);' +
      'display:flex;align-items:center;justify-content:center;padding:2vh;box-sizing:border-box;overflow:auto;');
    var panel = el('div', 'position:relative;background:#070713;border:1px solid #2a4a6a;border-radius:6px;' +
      'padding:16px 18px;box-shadow:0 10px 50px rgba(0,0,0,.75);max-width:97vw;max-height:96vh;overflow:auto;text-align:center;');
    backdrop.appendChild(panel);
    document.body.appendChild(backdrop);

    var h = renderInto(B, panel, {
      wide: true, cw: wideSize(), turn: turn, playing: playing,
      modeBtn: { label: '▭ Standard view', onClick: close }
    });

    var closeBtn = el('button', 'position:absolute;top:6px;right:8px;z-index:1;background:transparent;' +
      'color:#9ab;border:none;font:18px/1 sans-serif;cursor:pointer;', '✕');
    closeBtn.onclick = close;
    panel.appendChild(closeBtn);

    function onKey(e) { if (e.key === 'Escape' || e.keyCode === 27) close(); }
    document.addEventListener('keydown', onKey);
    backdrop.addEventListener('mousedown', function (e) { if (e.target === backdrop) close(); });

    function close() { setPrefWide(false); showInline(B, h.getTurn(), h.isPlaying()); }

    disposeActive = function () {
      document.removeEventListener('keydown', onKey);
      h.dispose();
      if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
    };
  }

  // ---- fetch + go ---------------------------------------------------------
  notice('Loading battle replay…');
  fetch(logUrl, { credentials: 'same-origin' })
    .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.text(); })
    .then(function (text) {
      if (!text || text.indexOf('FL/') === -1) {
        notice('This battle replay is no longer available (logs are kept for a limited time).');
        return;
      }
      var B = parse(text);
      // Honour the remembered opt-in: open straight into widescreen if chosen.
      if (prefWide()) showWide(B, 0, false); else showInline(B, 0, false);
    })
    .catch(function () {
      notice('This battle replay is no longer available (logs are kept for a limited time).');
    });
})();
