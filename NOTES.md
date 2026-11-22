# Notes

## How I broke the problem down
I read the spec and worked through the `small` example by hand (the tree and the alarm timeline). Then I built it in the order of the spec, checking each part against `expected/small` before moving on:
1. Part A: turn the event log into alarms.
2. Part B: find each alarm's root unit.
3. Part C: answer the queries.
4. Ran `medium` and confirmed it matches exactly.
5. Profiled `large` with a separate read-only script to find the data problems (see `DECISIONS.md`).

## How each part works, and how its running time grows
- **Reading and Part A.** All events are read, their times converted to UTC seconds with `strtotime`, and sorted by time. A "whiteboard" array holds each unit and kind that is currently raised. A raise writes its start time. A clear turns that start into a finished alarm. Time grows like n log n in the number of events, because of the sort.
- **Part B.** I store each unit's parent and an index of each unit's offline alarms. For each alarm I climb its ancestors and check whether one has an offline alarm open at the alarm's start (start included, end excluded). The last match while climbing is the topmost ancestor. Time grows with alarms × tree depth × offline alarms per ancestor.
- **Part C.** Independent alarms (those whose root is their own unit) are grouped by site. For each query I keep the alarms that overlap the window (`start < to` and `end > from`), clip them to the window, and sweep through the opens and closes in time order, counting the maximum open. Time grows with queries × independent alarms per site, which is the weak spot for `large`.

## Measurements on data/large

| | |
|---|---|
| Machine (CPU, memory) | AMD Ryzen 7 5700X (8 cores / 16 threads), 16 GB RAM, Windows 10 |
| Language and version | PHP `8.4.28` |
| Total time | Not measured. I did not run `solve.php` on `large`. |
| Slowest part, and its time | Not measured |
| Peak memory, if measured | Not measured |

For reference, `data/medium` (about 140,000 events, 4,000 queries) takes **5.3 seconds** and matches the expected output exactly.

## What was too slow at first, how I found out, what I changed
I did not get to the speed work. I expect Part C to be the slowest part on `large`, since it scans every independent alarm of a site for each of the 80,000 queries. `large` has about 11 times the events and 20 times the queries of `medium`. I have not measured this, so it is a prediction, not a result.

## What I would do next
1. Put the data decisions in `DECISIONS.md` into `solve.php`, with a count of skipped rows per reason.
2. Run `large` with a timer on each part separately (read, Part B, Part C), and keep the numbers.
3. Speed up Part C: sort each site's independent alarms once, then answer each query with a binary search instead of a full scan.
4. Speed up Part B if it turns out to be slow, for example by remembering each unit's list of ancestors.
5. Check the cases listed under "Not checked yet" in `DECISIONS.md`.


## AI tools I used, and for what

- **Claude Chat & ChatGPT:**
  - Helped me understand the problem by breaking it down into simpler explanations and creating diagrams, visualizers, and interactive timelines to illustrate the tree structure, alarm timeline, counting, and peak logic.
  - Provided guidance, and explanations for Parts A, B, and C. 
  - Suggested creating a profiling script (`tools/inspect.php`) for the `large` dataset. Claude provided guidance in creating the script.


- **VS Code's built-in AI:**
  - Used for code completion and assistance with identifying and fixing bugs while writing the code.
