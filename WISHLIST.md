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
  are logged now).
- **Morale curves** per fleet (berserk / rout / retreat thresholds).
- **Status / formation / sub-status changes** as visible state (penetrate, flank,
  stand-ground, disorder, panic…).
- **Cloak / detection** transitions.
- **Admiral XP** gained per fleet.

**What it takes:** extend `CBattleRecord` with new log-line types (alongside the
existing `FL/M/F/H/D` records in
`archspace_source/archspace/src/apps/archspace/battle.cc` ~6270–6352), then parse
and visualize them in `battle-replay.js`. This is an **engine change → image
rebuild**, but stays faithful to the three-tier principle by being
**observe-only** — log more, never change the combat math.

**Good first slice:** add a checked-in synthetic battle-log fixture and a
parser-only harness for `battle-replay.js` before changing engine log output.
The fixture should cover escaped slashes in names, `FL` roster rows, `M`
movement samples, paired `F`/`H` weapon events, `D` disabled-fleet rows, and
`ENDTURN`. That gives future replay work a fast web-tier safety check, then the
engine can add richer observe-only records with less risk of breaking today's
replay grammar.

Deferred for future consideration.

---

## (add future ideas below)
