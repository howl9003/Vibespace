# Battle Replay Tools

Parser fixture check:
```sh
node tools/battle-replay/check-parser.js
```

The fixture exercises the current battle-log grammar used by
`docker/web-overrides/js/battle-replay.js`: escaped slashes, fleet roster rows,
movement samples, paired fire/hit rows, disabled fleets, and `ENDTURN`.
