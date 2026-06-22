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

  var STATUS_LABELS = {
    0: 'Normal',
    1: 'Formation',
    2: 'Penetrate',
    3: 'Flank',
    4: 'Reserve',
    5: 'Free',
    6: 'Assault',
    7: 'Stand ground',
    8: 'Berserk',
    9: 'Disorder',
    10: 'Rout',
    11: 'Retreat',
    12: 'Panic',
    13: 'Annihilated',
    14: 'Annihilated this turn',
    15: 'Retreated this turn',
    16: 'Retreated'
  };
  var SUBSTATUS_LABELS = {
    0: 'None',
    1: 'Turning to center',
    2: 'Penetrating',
    3: 'Charging',
    4: 'Moving straight',
    5: 'Turning forward',
    6: 'Turning backward',
    7: 'Turning to border'
  };
  var MORALE_LABELS = {
    0: 'Normal morale',
    1: 'Weak morale break',
    2: 'Morale break',
    3: 'Complete morale break'
  };

  function label(map, id, fallback) {
    return map.hasOwnProperty(id) ? map[id] : fallback + ' ' + id;
  }

  function stateChangeParts(prev, next) {
    var parts = [];
    if (!prev) return parts;
    if (prev.status !== next.status) {
      parts.push('status ' + label(STATUS_LABELS, next.status, 'status'));
    }
    if (prev.substatus !== next.substatus) {
      parts.push(next.substatus ?
        'maneuver ' + label(SUBSTATUS_LABELS, next.substatus, 'substatus') :
        'maneuver clear');
    }
    if ((prev.moraleStatus != null || next.moraleStatus > 0) &&
        prev.moraleStatus !== next.moraleStatus) {
      parts.push(label(MORALE_LABELS, next.moraleStatus, 'morale') +
        (next.morale != null ? ' (' + next.morale + ')' : ''));
    }
    if (prev.detected !== next.detected) {
      parts.push(next.detected ? 'detected' : 'lost detection');
    }
    if (prev.cloaked !== next.cloaked) {
      parts.push(next.cloaked ? 'cloaked' : 'decloaked');
    }
    return parts;
  }

  function parse(text) {
    var B = {
      field: '', attackerId: null, defenderId: null, endTurn: 0,
      attackerName: '', defenderName: '', attackerRace: 0, defenderRace: 0,
      fleets: {},            // key "owner:id" -> fleet
      firesByTurn: {},       // turn -> [fire]
      eventsByTurn: {},      // turn -> [string]  (ticker)
      eventDetailsByTurn: {}, // turn -> [{ type, text }] for filtered ticker UI
      pendingFire: {}        // fireid -> fire (awaiting its H line)
    };
    function fleet(owner, id) { return B.fleets[owner + ':' + id]; }
    function ev(turn, s, type) {
      (B.eventsByTurn[turn] = B.eventsByTurn[turn] || []).push(s);
      (B.eventDetailsByTurn[turn] = B.eventDetailsByTurn[turn] || []).push({
        type: type || 'info',
        text: s
      });
    }
    function addStateSample(fl, state) {
      var prev = fl.stateSamples.length ? fl.stateSamples[fl.stateSamples.length - 1] : null;
      fl.stateSamples.push(state);
      var changes = stateChangeParts(prev, state);
      if (state.turn > 0 && changes.length) {
        ev(state.turn, fl.nick + ' state: ' + changes.join(', '), 'state');
      }
    }

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
            samples: [{ turn: 0, x: num(f[8]), y: num(f[9]), dir: num(f[10]), ships: num(f[7]), cmd: num(f[11]), substatus: 0 }],
            stateSamples: [{ turn: 0, status: num(f[11]), substatus: 0, morale: null, moraleStatus: null, detected: false, cloaked: false }],
            durabilitySamples: [],
            admiralXp: [],
            disabledTurn: null
          };
          break;
        }
        case 'M': {
          // M/turn/owner/id/x/y/dir/cmd/substatus/ships
          var t = num(f[1]), fl = fleet(num(f[2]), num(f[3]));
          if (fl) fl.samples.push({ turn: t, x: num(f[4]), y: num(f[5]), dir: num(f[6]), ships: num(f[9]), cmd: num(f[7]), substatus: num(f[8]) });
          B.endTurn = Math.max(B.endTurn, t);
          break;
        }
        case 'S': {
          // S/turn/owner/id/status/substatus/morale/moraleStatus/detected/cloaked
          var st = {
            turn: num(f[1]),
            status: num(f[4]),
            substatus: num(f[5]),
            morale: num(f[6]),
            moraleStatus: num(f[7]),
            detected: num(f[8]) !== 0,
            cloaked: num(f[9]) !== 0
          };
          var fls = fleet(num(f[2]), num(f[3]));
          if (fls) addStateSample(fls, st);
          B.endTurn = Math.max(B.endTurn, st.turn);
          break;
        }
        case 'Y': {
          // Y/turn/owner/id/hp/maxHp/shield/maxShield/activeShips/maxShips
          var ty = num(f[1]), fly = fleet(num(f[2]), num(f[3]));
          if (fly) {
            fly.durabilitySamples.push({
              turn: ty,
              hp: num(f[4]),
              maxHp: num(f[5]),
              shield: num(f[6]),
              maxShield: num(f[7]),
              activeShips: num(f[8]),
              maxShips: num(f[9])
            });
          }
          B.endTurn = Math.max(B.endTurn, ty);
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
               (fire2.sunk ? ', ' + fire2.sunk + ' sunk' : ''), 'fire');
            delete B.pendingFire[num(f[1])];
          }
          break;
        }
        case 'D': {
          // D/turn/owner/id
          var t2 = num(f[1]), fl2 = fleet(num(f[2]), num(f[3]));
          if (fl2 && fl2.disabledTurn == null) {
            fl2.disabledTurn = t2;
            ev(t2, '☠ ' + fl2.nick + ' destroyed/retreated', 'destroyed');
          }
          B.endTurn = Math.max(B.endTurn, t2);
          break;
        }
        case 'X': {
          // X/turn/owner/id/admiral/admiralId/exp
          var tx = num(f[1]), flx = fleet(num(f[2]), num(f[3]));
          var admiralName = f[4] || (flx ? flx.admiral : 'Admiral');
          var admiralId = num(f[5]);
          var exp = num(f[6]);
          if (flx) {
            flx.admiralXp.push({ turn: tx, admiral: admiralName, admiralId: admiralId, exp: exp });
          }
          if (exp > 0) {
            ev(tx, (flx ? flx.nick : 'Fleet ' + num(f[3])) + ' admiral ' +
              admiralName + ' gained ' + comma(exp) + ' XP', 'xp');
          }
          B.endTurn = Math.max(B.endTurn, tx);
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
      fl3.stateSamples.sort(function (a, b) { return a.turn - b.turn; });
      fl3.durabilitySamples.sort(function (a, b) { return a.turn - b.turn; });
      fl3.admiralXp.sort(function (a, b) { return a.turn - b.turn; });
    }
    return B;
  }

  return {
    fields: fields,
    num: num,
    parse: parse
  };
}));
