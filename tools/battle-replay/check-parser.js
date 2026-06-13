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

assert.strictEqual(defender.nick, 'Home/Guard');
assert.strictEqual(defender.side, 'def');
assert.strictEqual(defender.disabledTurn, 24);
assert.strictEqual(defender.samples[2].cmd, 10);
assert.strictEqual(defender.samples[2].ships, 8);

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
assert.strictEqual(battle.eventsByTurn[24][0], '☠ Home/Guard destroyed/retreated');

console.log('battle replay parser fixture passed');
