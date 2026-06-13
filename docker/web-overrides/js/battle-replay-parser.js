/*
  Shared parser for Archspace battle replay logs.

  Browser usage:
    window.ArchspaceBattleReplayParser.parse(text)

  Node usage:
    const parser = require('../../docker/web-overrides/js/battle-replay-parser.js');
*/
(function (root, factory) {
  var api = factory();
  if (typeof module === 'object' && module.exports) {
    module.exports = api;
  } else {
    root.ArchspaceBattleReplayParser = api;
  }
}(typeof self !== 'undefined' ? self : this, function () {
  // Split on unescaped '/', then un-escape '\/'.
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

  function num(x) {
    var n = parseInt(x, 10);
    return isNaN(n) ? 0 : n;
  }

  function comma(n) {
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  function parse(text) {
    var B = {
      field: '', attackerId: null, defenderId: null, endTurn: 0,
      attackerName: '', defenderName: '', attackerRace: 0, defenderRace: 0,
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
        // ATTACKER/name/id/race ; DEFENDER/name/id/race
        case 'ATTACKER': B.attackerName = f[1] || ''; B.attackerId = num(f[2]); B.attackerRace = num(f[3]); break;
        case 'DEFENDER': B.defenderName = f[1] || ''; B.defenderId = num(f[2]); B.defenderRace = num(f[3]); break;
        case 'FIELD':    B.field = f[1] || ''; break;
        case 'ENDTURN':  B.endTurn = Math.max(B.endTurn, num(f[1])); break;
        case 'FL': {
          // FL/owner/id/nick/admiral/class/NONE/ships/x/y/dir/cmd
          var owner = num(f[1]), id = num(f[2]);
          B.fleets[owner + ':' + id] = {
            owner: owner, id: id, nick: f[3] || ('Fleet ' + id),
            admiral: f[4] || '', side: null /* set after attacker/def known */,
            samples: [{ turn: 0, x: num(f[8]), y: num(f[9]), dir: num(f[10]), ships: num(f[7]), cmd: num(f[11]) }],
            disabledTurn: null
          };
          break;
        }
        case 'M': {
          // M/turn/owner/id/x/y/dir/cmd/substatus/ships
          var t = num(f[1]), fl = fleet(num(f[2]), num(f[3]));
          if (fl) fl.samples.push({ turn: t, x: num(f[4]), y: num(f[5]), dir: num(f[6]), ships: num(f[9]), cmd: num(f[7]) });
          B.endTurn = Math.max(B.endTurn, t);
          break;
        }
        case 'F': {
          // F/fireid/turn/attOwner/attId/tgtOwner/tgtId/weapon/type/num/hitChance
          var fire = {
            id: num(f[1]), turn: num(f[2]),
            from: num(f[3]) + ':' + num(f[4]), to: num(f[5]) + ':' + num(f[6]),
            weapon: f[7] || 'weapon', num: num(f[9]), hits: 0, damage: 0, sunk: 0, dealt: false
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
            fire2.hits = num(f[3]); fire2.damage = num(f[5]); fire2.sunk = num(f[6]); fire2.dealt = true;
            var a = B.fleets[fire2.from], d = B.fleets[fire2.to];
            ev(fire2.turn, (a ? a.nick : '?') + ' → ' + (d ? d.nick : '?') +
               ': ' + fire2.weapon + ' ×' + fire2.num + ' — ' +
               fire2.hits + ' hit' + (fire2.hits === 1 ? '' : 's') +
               (fire2.damage ? ', ' + comma(fire2.damage) + ' dmg' : '') +
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

    // Assign sides + sort samples. The viewport is the fixed full battlefield
    // (0..FIELD on both axes in the renderer), so no per-battle bounds are computed.
    for (var k in B.fleets) {
      var fl3 = B.fleets[k];
      // Attacker side vs everyone else (defender + allies render as defender).
      fl3.side = (fl3.owner === B.attackerId) ? 'att' : 'def';
      fl3.samples.sort(function (a, b) { return a.turn - b.turn; });
    }
    return B;
  }

  return {
    fields: fields,
    num: num,
    parse: parse
  };
}));
