#!/usr/bin/env node

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const parser = require('../../docker/web-overrides/js/battle-replay-parser.js');

const fixturePath = path.join(__dirname, 'fixtures', 'synthetic-battle.log');
const battle = parser.parse(fs.readFileSync(fixturePath, 'utf8'));

assert.deepStrictEqual(parser.fields('FL/Red\\/Wing/Laser\\/Cannon'), [
  'FL',
  'Red/Wing',
  'Laser/Cannon',
]);

assert.strictEqual(battle.field, 'Alpha/Beta Rift');
assert.strictEqual(battle.attackerName, 'Red/Wing');
assert.strictEqual(battle.defenderName, 'Blue/Guard');
assert.strictEqual(battle.attackerId, 101);
assert.strictEqual(battle.defenderId, 202);
assert.strictEqual(battle.endTurn, 30);

const attacker = battle.fleets['101:11'];
const defender = battle.fleets['202:21'];

assert.ok(attacker, 'attacker fleet should parse');
assert.ok(defender, 'defender fleet should parse');
assert.strictEqual(attacker.nick, 'First/Strike');
assert.strictEqual(attacker.admiral, 'Admiral/Ares');
assert.strictEqual(attacker.side, 'att');
assert.strictEqual(attacker.samples.length, 3);
assert.deepStrictEqual(attacker.samples.map((sample) => sample.turn), [0, 10, 20]);
assert.strictEqual(attacker.samples[2].cmd, 6);
assert.strictEqual(attacker.samples[2].substatus, 3);
assert.deepStrictEqual(attacker.stateSamples.map((sample) => sample.turn), [0, 0, 10, 20]);
assert.strictEqual(attacker.stateSamples[3].status, 6);
assert.strictEqual(attacker.stateSamples[3].substatus, 3);
assert.strictEqual(attacker.stateSamples[3].morale, 92);
assert.strictEqual(attacker.stateSamples[3].cloaked, false);
assert.deepStrictEqual(attacker.durabilitySamples.map((sample) => sample.turn), [0, 10, 20]);
assert.deepStrictEqual(attacker.durabilitySamples[2], {
  turn: 20,
  hp: 11800,
  maxHp: 12000,
  shield: 5600,
  maxShield: 6000,
  activeShips: 12,
  maxShips: 12,
});
assert.deepStrictEqual(attacker.admiralXp, [
  { turn: 30, admiral: 'Admiral/Ares', admiralId: 501, exp: 175 },
]);

assert.strictEqual(defender.nick, 'Home/Guard');
assert.strictEqual(defender.side, 'def');
assert.strictEqual(defender.disabledTurn, 24);
assert.strictEqual(defender.samples[2].cmd, 10);
assert.strictEqual(defender.samples[2].substatus, 7);
assert.strictEqual(defender.samples[2].ships, 8);
assert.deepStrictEqual(defender.stateSamples.map((sample) => sample.turn), [0, 0, 10, 20]);
assert.strictEqual(defender.stateSamples[1].cloaked, true);
assert.strictEqual(defender.stateSamples[2].detected, true);
assert.strictEqual(defender.stateSamples[2].moraleStatus, 1);
assert.strictEqual(defender.stateSamples[3].status, 10);
assert.strictEqual(defender.stateSamples[3].moraleStatus, 2);
assert.strictEqual(defender.stateSamples[3].cloaked, false);
assert.deepStrictEqual(defender.durabilitySamples.map((sample) => sample.turn), [0, 10, 20, 24]);
assert.strictEqual(defender.durabilitySamples[2].activeShips, 8);
assert.strictEqual(defender.durabilitySamples[3].hp, 0);

const fire = battle.firesByTurn[12][0];
assert.strictEqual(fire.from, '101:11');
assert.strictEqual(fire.to, '202:21');
assert.strictEqual(fire.weapon, 'Laser/Cannon');
assert.strictEqual(fire.num, 4);
assert.strictEqual(fire.hits, 3);
assert.strictEqual(fire.damage, 4200);
assert.strictEqual(fire.sunk, 2);
assert.strictEqual(fire.dealt, true);
assert.deepStrictEqual(Object.keys(battle.pendingFire), []);

assert.strictEqual(
  battle.eventsByTurn[12][0],
  'First/Strike → Home/Guard: Laser/Cannon ×4 — 3 hits, 4,200 dmg, 2 sunk'
);
assert.strictEqual(battle.eventDetailsByTurn[12][0].type, 'fire');
assert.strictEqual(battle.eventDetailsByTurn[10][0].type, 'state');
assert.strictEqual(
  battle.eventsByTurn[10][0],
  'Home/Guard state: Weak morale break (70), detected'
);
assert.strictEqual(
  battle.eventsByTurn[20][0],
  'First/Strike state: status Assault, maneuver Charging'
);
assert.strictEqual(
  battle.eventsByTurn[20][1],
  'Home/Guard state: status Rout, maneuver Turning to border, Morale break (42), decloaked'
);
assert.strictEqual(battle.eventsByTurn[24][0], '☠ Home/Guard destroyed/retreated');
assert.strictEqual(battle.eventDetailsByTurn[24][0].type, 'destroyed');
assert.strictEqual(battle.eventsByTurn[30][0], 'First/Strike admiral Admiral/Ares gained 175 XP');
assert.strictEqual(battle.eventDetailsByTurn[30][0].type, 'xp');

const legacy = parser.parse([
  'FIELD/Legacy',
  'ATTACKER/Old Attacker/1/1',
  'DEFENDER/Old Defender/2/2',
  'FL/1/1/Old Fleet/Old Admiral/Old Class/NONE/3/100/200/0/1',
  'M/10/1/1/200/300/0/1/0/3',
  'Z/unknown/row/type',
  'ENDTURN/10',
].join('\n'));
assert.strictEqual(legacy.endTurn, 10);
assert.ok(legacy.fleets['1:1'], 'legacy fleet should parse without richer rows');
assert.deepStrictEqual(legacy.fleets['1:1'].durabilitySamples, []);
assert.deepStrictEqual(legacy.fleets['1:1'].admiralXp, []);
assert.strictEqual(legacy.fleets['1:1'].stateSamples.length, 1);

const partial = parser.parse([
  'FIELD/Partial',
  'ATTACKER/A/1/1',
  'DEFENDER/D/2/2',
  'FL/1/1/F/A/C/NONE/1/0/0/0/1',
  'S/5/1/1/6',
  'Y/5/1/1/250/500',
  'M/not-a-number/999/999',
  'ENDTURN/5',
].join('\n'));
assert.strictEqual(partial.fleets['1:1'].stateSamples[1].status, 6);
assert.strictEqual(partial.fleets['1:1'].stateSamples[1].substatus, 0);
assert.deepStrictEqual(partial.fleets['1:1'].durabilitySamples[0], {
  turn: 5,
  hp: 250,
  maxHp: 500,
  shield: 0,
  maxShield: 0,
  activeShips: 0,
  maxShips: 0,
});

console.log('battle replay parser fixture passed');
