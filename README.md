# Storm — a machine problem

A monitoring service watches equipment in buildings. Each building ("site") has units wired in a
tree: a head-end at the top, units fed from it, and more units fed from those. When a unit near the
top goes down, everything below it raises alarms too, and the operators drown in them. That is an
alarm storm.

Your program reads the equipment tree and a log of alarm events, works out which alarms are real
and which are just fallout from something upstream, and answers questions about how bad things were.

**Use any language and any libraries you like.** Open `storm.code-workspace` in Visual Studio Code
to get started.

---

## The data

Each dataset is a folder under `data/` with three CSV files (comma-separated, first line is a header).

### `topology.csv` — `unit_id,site_id,parent_unit_id`

One row per unit. `parent_unit_id` is empty for a unit at the top of its site. A unit's
**ancestors** are its parent, its parent's parent, and so on up to the top.

### `events.csv` — `ts,unit_id,kind,type`

One row per event.

- `ts` — an ISO-8601 timestamp **with a UTC offset**, for example `2026-03-01T08:30:00+08:00`
  or `2026-03-01T00:30:00Z`. Those two are the same moment.
- `kind` — the kind of problem, for example `offline`, `low_signal`, `overheat`.
- `type` — `raise` when the problem starts, `clear` when it ends.

### `queries.csv` — `site_id,from_ts,to_ts`

One row per question (see Part C).

---

## What to compute

### Part A — alarms

An **alarm** is one kind of problem on one unit, from the moment it is raised until the moment it
is cleared. It is open from its start up to, but not including, its end: an alarm from 00:10 to
00:25 is open at 00:10:00 and at 00:24:59, and is not open at 00:25:00.

### Part B — root cause

For each alarm, look at the unit's ancestors **at the moment the alarm starts**. If one or more
ancestors have an `offline` alarm open at that moment, the alarm's **root unit** is the one of
those ancestors **closest to the top of the tree**. If none has, the alarm's root unit is its own
unit, and we call the alarm **independent**.

Only ancestors count: a unit's own alarms never make it its own root cause.

### Part C — questions

Each row of `queries.csv` asks about one site during the window from `from_ts` up to, but not
including, `to_ts`. Considering **only the independent alarms on units of that site**, answer:

- `independent_count` — how many of them were open at any moment inside the window
- `peak_open` — the largest number of them open at the same moment inside the window

---

## What to write

Write two files per dataset into `out/<dataset>/`.

### `alarms.csv`

```
unit_id,kind,start,end,root_unit
```

One row per alarm, sorted by `start`, then `unit_id`, then `kind`. Times in UTC, formatted exactly
like `2026-03-01T00:30:00Z`.

### `answers.csv`

```
independent_count,peak_open
```

One row per query, in the same order as `queries.csv`.

---

## A worked example — `data/small`

The site `A` looks like this, and site `B` is just `H2` with `B1` under it:

```
H1
├── R1
│   ├── A1
│   └── A2
└── R2
    └── A3
```

`expected/small/alarms.csv` is the right answer. Reading a few rows of it:

| Alarm | Root unit | Why |
|---|---|---|
| `A1 low_signal` 00:10 → 00:25 | `A1` | Nothing above it was offline at 00:10. Independent. |
| `R1 offline` 00:20 → 00:40 | `R1` | `H1` was not offline at 00:20. Independent. |
| `A1 offline` 00:20:05 → 00:41 | `R1` | Its parent `R1` was offline when it started. |
| `R2 offline` 00:30 → 00:50 | `H1` | `H1` went offline at the same moment, 00:30:00, so it was open. |
| `A3 low_signal` 00:31 → 00:55 | `H1` | `R2` and `H1` were both offline; `H1` is closer to the top. |
| `A2 low_signal` 00:45 → 00:50 | `A2` | `H1` cleared at exactly 00:45:00, so it was no longer open. Independent. |

The independent alarms at site `A` are therefore `A1 low_signal`, `R1 offline`, `H1 offline` and
`A2 low_signal`. For the first query, site `A` from 00:00 to 01:00, all four were open at some
point, and at most two were open together (00:20 to 00:25, and 00:30 to 00:40): the answer is `4,2`.

---

## The three datasets

| Dataset | Size | What it is for |
|---|---|---|
| `data/small` | 18 events | The worked example. Expected output is in `expected/small`. |
| `data/medium` | about 140,000 events, 4,000 queries | Clean data at a realistic shape. Expected output is in `expected/medium`. Your output must match it exactly. |
| `data/large` | about 1.5 million events, 80,000 queries | **Real-world data.** No expected output. Read on. |

To compare your output with the expected output, use any diff tool, VS Code's
*Compare Selected*, or `python tools/check.py medium` if you have Python (it is optional).

### About `data/large`

This one was assembled from several exports taken from live systems, and it looks like it.
The description above tells you what a well-formed dataset means. It does **not** tell you what to
do about everything you will find in this one. That is deliberate.

- Find what is wrong or unclear in it.
- Decide what your program does about each thing, and why.
- Write each decision in `DECISIONS.md`: what you found, how often, what you chose, and what you
  would ask the people who own the data.

There is no single right answer for most of these. There are careless ones.

It is also big enough that the obvious way of doing Parts B and C will not finish in a reasonable
time. Getting it to run fast is part of the problem. Measure before you change things, and keep
the numbers.

---

## What to send back

1. Your source code, with a one-command way to run it on a dataset.
2. `out/small`, `out/medium` and `out/large`.
3. `DECISIONS.md` — as described above.
4. `NOTES.md` — short and honest:
   - how you broke the problem down, and in what order you built it
   - how each part works, and how its running time grows with the size of the input
   - your measurements on `data/large`: total time, the slowest part, your machine
   - what was too slow at first, how you found out, and what you changed
   - what you would do next with another day
   - which AI tools you used and for what
5. Your git history. Run `git init` before you start and commit as you go. We read the history.

## Ground rules

- Time: you have 72 hours. We expect four to six hours of real work. Stop there even if you are not
  done, and tell us in `NOTES.md` what is left and how you would do it. An honest, partly finished
  solution beats a complete one you cannot explain.
- AI tools are allowed. Say where you used them. In the follow-up call you will run your program on
  data you have not seen, explain any part of it we point at, and make a change to it while we
  watch. Send only code you can do that with.
- Ask us questions. Asking a good question is not a weakness; guessing silently is.
