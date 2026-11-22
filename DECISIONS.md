# Decisions

I profiled `data/large` with a read-only script (`tools/inspect.php`) before touching the solution.
**Status:** the decisions below are what I chose and why, but I ran out of time before putting them into `solve.php`. `solve.php` currently handles clean data only (small and medium match exactly). I have not run it on `large`.

| What I found | How often | What I would do (not yet implemented) | Why | What I would ask the data owners |
|---|---|---|---|---|
| Timestamps as Unix seconds (e.g. `1772323265`) instead of ISO-8601 | 7,508 events | Convert as UTC seconds | The values line up with the rest of the data (1772323265 is 2026-03-01 00:01:05 UTC). Dropping them would lose real events. | Is this feed always UTC? |
| `type` written as `RAISE`, `CLEAR`, `raise `, `clear `, `CLEAR `, `RAISE ` | about 10,000 events | Trim spaces and lowercase | Only case and padding differ, so the meaning is clear | Which system pads and capitalises? |
| `type` = `cleared?` | 153 events | Treat as `clear` | It reads as a clear. Dropping it would leave the alarm silently open. | Does it mean "probably cleared"? |
| Impossible timestamps such as `2026-13-45T99:00:00Z` | about 152 events | Skip the row and count it | It is a placeholder with no real time to recover | Why does the exporter write this value? |
| Rows with the wrong number of columns (missing `type`) and blank lines | 208 rows | Skip and count | Without `type` I cannot tell a raise from a clear | Are these rows truncated? |
| Header lines repeated in the middle of `events.csv` | 4 rows | Skip | The files were joined together | None |
| Events for units that are not in `topology.csv` (all from sites `S902`, `S903`, `S904`) | about 1,470 events | Skip and count | With no topology I have no parent or site, so they cannot change any answer | Are the 900-series sites test data? |

## Not checked yet
- A raise with no clear. The current code drops it without a message.
- A clear with no earlier raise. The current code ignores it.
- A raise followed by another raise for the same unit and kind.
- Events at the same second for the same unit and kind (the order of a clear and a raise).
- Loops in the topology (a unit that is its own ancestor), which would make the ancestor climb run forever.
- Queries for sites that do not exist, or with `from` after `to`.

In every case I would count each skipped or repaired row and print the counts, so that nothing is dropped silently.