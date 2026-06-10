/*
  battle-replay.js — HTML5 battle replay.

  Replaces the original Archspace battle-viewer Java applet (Report.class /
  report.jar) with a canvas animation + synced event ticker, parsing the
  turn-by-turn battle log the engine already writes for every battle.

  Mount point (set by src/web/war/battle_report2.html):
    <div id="battle-replay" data-log="$LOG_URL" data-player="$PLAYER_ID"></div>

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

  // Map the engine's filesystem path to the web route nginx serves.
  var m = logPath.match(/\/battle\/(.+)$/);
  var logUrl = m ? '/battle_log/' + m[1] : null;

  // ---- colors / sizing ----------------------------------------------------
  var ATT = '#ff8844', ATT_DIM = '#7a3a1c';   // attacker (orange)
  var DEF = '#55bbff', DEF_DIM = '#1c4a6e';   // defender (cyan)
  var CW = 544, CH = 320;                       // canvas size

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

  if (!logUrl) { notice('Battle replay is not available for this report.'); return; }

  // ---- split on unescaped '/', then un-escape '\/' ------------------------
  function fields(line) {
    var out = [], cur = '', i = 0;
    while (i < line.length) {
      var c = line.charAt(i);
      if (c === '\\' && line.charAt(i + 1) === '/') { cur += '/'; i += 2; continue; }
      if (c === '/') { out.push(cur); cur = ''; i++; continue; }
      cur += c; i++;
    }
    out.push(cur);
    return out;
  }
  function num(x) { var n = parseInt(x, 10); return isNaN(n) ? 0 : n; }

  // ---- parse --------------------------------------------------------------
  function parse(text) {
    var B = {
      field: '', attackerId: null, defenderId: null, endTurn: 0,
      fleets: {},            // key "owner:id" -> fleet
      firesByTurn: {},       // turn -> [fire]
      eventsByTurn: {},      // turn -> [string]  (ticker)
      pendingFire: {}        // fireid -> fire (awaiting its H line)
    };
    function fleet(owner, id) { return B.fleets[owner + ':' + id]; }
    function ev(turn, s) { (B.eventsByTurn[turn] = B.eventsByTurn[turn] || []).push(s); }

    var lines = text.split('\n');
    for (var li = 0; li < lines.length; li++) {
      var line = lines[li]; if (!line) continue;
      var f = fields(line);
      switch (f[0]) {
        case 'ATTACKER': B.attackerId = num(f[2]); break;
        case 'DEFENDER': B.defenderId = num(f[2]); break;
        case 'FIELD':    B.field = f[1] || ''; break;
        case 'ENDTURN':  B.endTurn = Math.max(B.endTurn, num(f[1])); break;
        case 'FL': {
          // FL/owner/id/nick/admiral/class/NONE/ships/x/y/dir/cmd
          var owner = num(f[1]), id = num(f[2]);
          B.fleets[owner + ':' + id] = {
            owner: owner, id: id, nick: f[3] || ('Fleet ' + id),
            admiral: f[4] || '', side: null /* set after attacker/def known */,
            samples: [{ turn: 0, x: num(f[8]), y: num(f[9]), dir: num(f[10]), ships: num(f[7]) }],
            disabledTurn: null
          };
          break;
        }
        case 'M': {
          // M/turn/owner/id/x/y/dir/cmd/substatus/ships
          var t = num(f[1]), fl = fleet(num(f[2]), num(f[3]));
          if (fl) fl.samples.push({ turn: t, x: num(f[4]), y: num(f[5]), dir: num(f[6]), ships: num(f[9]) });
          B.endTurn = Math.max(B.endTurn, t);
          break;
        }
        case 'F': {
          // F/fireid/turn/attOwner/attId/tgtOwner/tgtId/weapon/type/num/hitChance
          var fire = {
            id: num(f[1]), turn: num(f[2]),
            from: num(f[3]) + ':' + num(f[4]), to: num(f[5]) + ':' + num(f[6]),
            weapon: f[7] || 'weapon', num: num(f[9]), hits: 0, sunk: 0, dealt: false
          };
          (B.firesByTurn[fire.turn] = B.firesByTurn[fire.turn] || []).push(fire);
          B.pendingFire[fire.id] = fire;
          B.endTurn = Math.max(B.endTurn, fire.turn);
          break;
        }
        case 'H': {
          // H/fireid/turn/hits/misses/damage/sunk
          var fire2 = B.pendingFire[num(f[1])];
          if (fire2) {
            fire2.hits = num(f[3]); fire2.sunk = num(f[6]); fire2.dealt = true;
            var a = B.fleets[fire2.from], d = B.fleets[fire2.to];
            ev(fire2.turn, (a ? a.nick : '?') + ' → ' + (d ? d.nick : '?') +
               ': ' + fire2.weapon + ' ×' + fire2.num + ' — ' +
               fire2.hits + ' hit' + (fire2.hits === 1 ? '' : 's') +
               (fire2.sunk ? ', ' + fire2.sunk + ' sunk' : ''));
            delete B.pendingFire[num(f[1])];
          }
          break;
        }
        case 'D': {
          // D/turn/owner/id
          var t2 = num(f[1]), fl2 = fleet(num(f[2]), num(f[3]));
          if (fl2 && fl2.disabledTurn == null) {
            fl2.disabledTurn = t2;
            ev(t2, '☠ ' + fl2.nick + ' destroyed/retreated');
          }
          B.endTurn = Math.max(B.endTurn, t2);
          break;
        }
      }
    }

    // assign sides, sort samples, compute bounds
    var minX = 1e9, minY = 1e9, maxX = -1e9, maxY = -1e9, any = false;
    for (var k in B.fleets) {
      var fl3 = B.fleets[k];
      // attacker side vs everyone else (defender + allies render as defender)
      fl3.side = (fl3.owner === B.attackerId) ? 'att' : 'def';
      fl3.samples.sort(function (a, b) { return a.turn - b.turn; });
      for (var si = 0; si < fl3.samples.length; si++) {
        var s = fl3.samples[si]; any = true;
        if (s.x < minX) minX = s.x; if (s.x > maxX) maxX = s.x;
        if (s.y < minY) minY = s.y; if (s.y > maxY) maxY = s.y;
      }
    }
    if (!any) { minX = 0; minY = 0; maxX = 10000; maxY = 10000; }
    var padX = (maxX - minX) * 0.08 + 200, padY = (maxY - minY) * 0.08 + 200;
    B.bounds = { minX: minX - padX, minY: minY - padY, maxX: maxX + padX, maxY: maxY + padY };
    return B;
  }

  // fleet state (interpolated) at turn t, or null if not yet present / gone
  function stateAt(fl, t) {
    if (fl.disabledTurn != null && t >= fl.disabledTurn) return null;
    var s = fl.samples, n = s.length;
    if (!n) return null;
    if (t <= s[0].turn) return { x: s[0].x, y: s[0].y, dir: s[0].dir, ships: s[0].ships };
    for (var i = 0; i < n - 1; i++) {
      if (t >= s[i].turn && t <= s[i + 1].turn) {
        var a = s[i], b = s[i + 1], span = (b.turn - a.turn) || 1, f = (t - a.turn) / span;
        return { x: a.x + (b.x - a.x) * f, y: a.y + (b.y - a.y) * f,
                 dir: a.dir, ships: a.ships };
      }
    }
    var last = s[n - 1];
    return { x: last.x, y: last.y, dir: last.dir, ships: last.ships };
  }

  // ---- build UI + run -----------------------------------------------------
  function build(B) {
    mount.innerHTML = '';
    var wrap = el('div', 'width:' + CW + 'px;max-width:100%;margin:0 auto;text-align:left;');
    mount.appendChild(wrap);

    var canvas = el('canvas', 'display:block;width:100%;background:#04040c;border:1px solid #223355;');
    canvas.width = CW; canvas.height = CH;
    wrap.appendChild(canvas);
    var ctx = canvas.getContext('2d');

    // controls
    var bar = el('div', 'display:flex;align-items:center;gap:8px;margin:8px 0;' +
      'font:12px sans-serif;color:#9ab;');
    var playBtn = el('button', 'background:#16243a;color:#cde;border:1px solid #2a4a6a;' +
      'border-radius:4px;padding:3px 12px;cursor:pointer;', '▶ Play');
    var slider = el('input'); slider.type = 'range'; slider.min = 0; slider.max = B.endTurn || 1;
    slider.value = 0; slider.style.cssText = 'flex:1;';
    var turnLbl = el('span', 'min-width:96px;text-align:right;', 'Turn 0 / ' + B.endTurn);
    var speedSel = el('select', 'background:#16243a;color:#cde;border:1px solid #2a4a6a;border-radius:4px;padding:2px;');
    [['1×', 1], ['2×', 2], ['4×', 4], ['8×', 8]].forEach(function (o) {
      var op = el('option', '', o[0]); op.value = o[1]; if (o[1] === 2) op.selected = true; speedSel.appendChild(op);
    });
    bar.appendChild(playBtn); bar.appendChild(slider); bar.appendChild(speedSel); bar.appendChild(turnLbl);
    wrap.appendChild(bar);

    // ticker
    var ticker = el('div', 'height:120px;overflow-y:auto;background:#05050f;border:1px solid #223355;' +
      'font:12px/1.5 monospace;color:#9ab;padding:6px 10px;');
    wrap.appendChild(ticker);

    var fleetList = []; for (var k in B.fleets) fleetList.push(B.fleets[k]);

    function tx(x) { return (x - B.bounds.minX) / (B.bounds.maxX - B.bounds.minX) * CW; }
    function ty(y) { return (y - B.bounds.minY) / (B.bounds.maxY - B.bounds.minY) * CH; }

    function drawFleet(st, fl) {
      var x = tx(st.x), y = ty(st.y);
      var r = Math.max(4, Math.min(16, 3 + Math.sqrt(st.ships || 1) * 1.6));
      var col = fl.side === 'att' ? ATT : DEF;
      var rad = (st.dir || 0) * Math.PI / 180;
      ctx.save(); ctx.translate(x, y); ctx.rotate(rad);
      ctx.fillStyle = col; ctx.beginPath();
      ctx.moveTo(r, 0); ctx.lineTo(-r * 0.7, r * 0.7); ctx.lineTo(-r * 0.7, -r * 0.7);
      ctx.closePath(); ctx.fill();
      ctx.restore();
      ctx.fillStyle = '#7d8aa0'; ctx.font = '9px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText(fl.nick + ' (' + st.ships + ')', x, y - r - 3);
    }

    function render(t) {
      ctx.clearRect(0, 0, CW, CH);
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
    function setTurn(t) {
      cur = Math.max(0, Math.min(B.endTurn, t));
      slider.value = cur;
      turnLbl.textContent = 'Turn ' + Math.round(cur) + ' / ' + B.endTurn;
      render(cur); renderTicker(cur);
    }
    function tick(ts) {
      if (!playing) return;
      if (!last) last = ts;
      var dt = (ts - last) / 1000; last = ts;
      acc += dt * num(speedSel.value) * 12; // ~12 turns/sec at 1x
      if (acc >= 1) { setTurn(cur + Math.floor(acc)); acc -= Math.floor(acc); }
      if (cur >= B.endTurn) { stop(); return; }
      raf = requestAnimationFrame(tick);
    }
    function play() { if (playing) return; if (cur >= B.endTurn) setTurn(0); playing = true; last = 0; acc = 0; playBtn.innerHTML = '❚❚ Pause'; raf = requestAnimationFrame(tick); }
    function stop() { playing = false; if (raf) cancelAnimationFrame(raf); playBtn.innerHTML = '▶ Play'; }
    playBtn.onclick = function () { playing ? stop() : play(); };
    slider.oninput = function () { stop(); setTurn(num(slider.value)); };

    setTurn(0);
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
      build(parse(text));
    })
    .catch(function () {
      notice('This battle replay is no longer available (logs are kept for a limited time).');
    });
})();
