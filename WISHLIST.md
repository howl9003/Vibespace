# Wishlist / Future Considerations

Deferred ideas worth revisiting. Not committed work — a durable backlog so we
don't lose good ideas between sessions.

---

## Richer battle replays

The HTML5 battle replay (`docker/web-overrides/js/battle-replay.js`, shown on
`battle_report2.as`) animates what the engine logs **today**: fleet positions
(sampled every 10 turns), each weapon fire, hit results (hits/misses/damage/
ships sunk), and fleet destruction — enough for a top-down animated replay with a
synced event ticker.

A **richer** replay could additionally show data the engine computes during a
battle but currently discards from the log:

- **Per-ship HP and shield bars** over time (only fleet-level active-ship counts
  were logged originally; aggregate durability is now logged).
- **Per-turn morale curves** per fleet, beyond the sampled morale-break state.

**What it takes:** extend `CBattleRecord` with new log-line types (alongside the
existing `FL/M/F/H/D` records in
`archspace_source/archspace/src/apps/archspace/battle.cc` ~6270–6352), then parse
and visualize them in `battle-replay.js`. This is an **engine change → image
rebuild**, but stays faithful to the three-tier principle by being
**observe-only** — log more, never change the combat math.

**Completed first slice:** `tools/battle-replay/check-parser.js` and
`tools/battle-replay/fixtures/synthetic-battle.log` now provide the parser-only
safety check before changing engine log output. The fixture covers escaped
slashes in names, `FL` roster rows, `M` movement samples, paired `F`/`H` weapon
events, `D` disabled-fleet rows, and `ENDTURN`.

**Completed richer-state slice:** engine battle logs now include `S/` fleet-state
snapshots beside roster and movement records. The parser and HTML5 replay use
those snapshots to surface status/sub-status, morale-break state, cloak, and
detection transitions without changing combat math.

**Completed XP slice:** engine battle logs now include `X/` admiral XP award
records at battle resolution. The parser stores those records per fleet and the
HTML5 replay ticker shows the awarded XP alongside the end-of-battle events.

**Completed durability slice:** engine battle logs now include compact `Y/`
durability snapshots with aggregate HP, shield, and active/max ship counts. The
parser stores those records per fleet, and the HTML5 replay can draw HP/shield
bars plus optional movement trails and filtered event categories.

**Next slice:** if replay work continues, consider true per-ship compact
durability bands or a morale graph panel. Keep these observe-only and gated by
parser fixtures before changing engine log output.

Deferred for future consideration.

---

## (add future ideas below)
