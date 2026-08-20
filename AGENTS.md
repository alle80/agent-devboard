# Griglia — instructions for the coding agent

You are a coding agent driven by an **Griglia**. The user queues work as todos in one
list (the **agent list**, `config('griglia.agent_list')`). Your job: pick them up, do them, close
them — reacting to the board in real time.

## Works with any CLI agent

The board never talks to a specific vendor: it only needs a shell where `php artisan griglia:check`
runs. Tested with **Claude Code**; works the same with **OpenAI Codex CLI**, **Gemini CLI**, Aider, Cursor
CLI, Amp, … Hook it up per agent:

| Agent | Instructions file it reads | How to connect |
|-------|----------------------------|----------------|
| Claude Code | `CLAUDE.md` | copy this file's rules into `CLAUDE.md` (or `@AGENTS.md`), then run `griglia:watch` |
| Codex CLI | `AGENTS.md` | put this file at the repo root (Codex reads it natively) |
| Gemini CLI | `GEMINI.md` | copy/`@include` this file as `GEMINI.md` |
| others | their own file | same content; the only contract is the `griglia:check` CLI below |

Skills: `griglia:skills-import` accepts any JSON list — the origin repo ships a sync script that reads
`.claude/skills`, `~/.codex/skills`, `~/.agents/skills`, `~/.gemini/skills`. Token stats: report whatever
your agent exposes with `--tokens-in/--tokens-out` (optional). The UI calls the agent by
`config('griglia.agent_name')` («Claude», «Codex», …).

## Several agents on one board

If the board lists several agents (`check` prints `🤝 agents: …`), run `griglia:check --agent=<your key>` (or
export `GRIGLIA_AGENT_KEY`) so you only see the tasks assigned to you.

## Connect (once)

```bash
php artisan griglia:watch      # in one terminal: prints only the changes you must react to
```

Then, whenever `watch` reports something (or to start), read and act with `griglia:check`.

## The dot on each row = the state

| Dot | State | What you do |
|-----|-------|-------------|
| ⚪ | waiting | **Do not touch.** Not ready for you. |
| 🟢 | open to work | Yours to take — in list order, top first. |
| 🔧 | working | You set this the instant you start. |
| ❓ | question | You asked something; paused until the user answers. |
| ⏹ | stop | The user stopped it — **stop immediately**. |
| ✔ | done | Closed with your comment. |

## The loop

1. **See the work:** `php artisan griglia:check` (only 🟢/🔧; `--all` for everything).
2. **Take it FIRST:** `php artisan griglia:check --take=ID` is your *first* action on a task —
   before reading details, exploring code, or asking. The dot turns 🔧 in real time so the user
   sees it's in progress. (`ID` = the `id:N` shown, not the row position.)
3. **Do the work.** The todo's note = details, sub-tasks = a checklist, images = screenshots. If `check`
   prints `🧩 skills to activate for this task: …`, invoke those skills (Skill tool) while working on it.
   (The catalogue comes from `griglia:skills-import`; keep it fresh from your host with a JSON list.)
   Keep the user posted: `griglia:check --take=ID --progress=N --phase="testing"` updates the percentage
   and the short «what I'm doing» text on the row (it starts at 0% when you take it).
4. **If unclear, ask:** `griglia:check --ask=ID --q="…" --q="…"` — pauses the item (❓) until the
   user answers in the app and restarts it (it comes back 🟢). Ask *after* taking.
5. **Close it:** `griglia:check --done=ID --comment="what you did / how to try it"`. The comment
   is shown to the user; never write into the user's note. If you know how many tokens you spent on
   it, add `--tokens-in=N --tokens-out=N` (also allowed on `--take`/`--ask`): the board keeps per-todo
   **stats** (working time is timed automatically while the row is 🔧).

## Rules

- **Order:** take 🟢 items top-to-bottom (the user's drag-and-drop priority). The `task_mode`
  setting (shown at the top of `check`) decides how many at once: `ordered` = **one at a time**
  (finish/close the one you took before taking another); `multitasking` = you *may* take several
  at once, but **only if they're independent** (different files/areas) — otherwise stay serial to
  avoid overlapping commits and edits to the same files.
- **A question doesn't block the others** — move to the next 🟢 while one waits for an answer.
- **Stop means stop:** if an item shows ⏹ (stopped), drop it at once and don't touch it until it
  is 🟢 again. Taking it (`--take`) clears the stop.
- **Plans:** lists built from a prompt («Create as a plan») are chained tasks; once the user starts a plan,
  `check` lists its open task under `📐 Plan «name»` after the agent list — work it like any other task
  (`--take/--done` by id); closing it opens the next one automatically.
- **Never touch ⚪ items.**
- **Save tokens:** `check` prints an `⚡ optimization:` line (the «Optimization» settings group). With
  compact output on, action calls print only their result — don't re-run `check` just to look. If it
  prints `TERSE MODE ON`, follow those rules literally (no chat prose, batched commands, targeted reads).

That's it: `watch` tells you when, `check` lets you act.
