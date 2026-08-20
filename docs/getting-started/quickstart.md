# Quickstart

Five minutes, from an empty board to the first task worked by a coding agent. It assumes the package is
already [installed](installation.md).

## 1. Open the board

Log in to your app and visit `/` (or your `route_prefix`). You get one list — the **agent list**, named
`dev` by default (`config('griglia.agent_list')`). That list is the channel between you and the agent:
everything you drop there is a request.

## 2. Write a request

Add a task with the input at the top, then open it (click the title) to add what the agent needs:

- **note** — the details, in your own words;
- **sub-tasks** — the checklist you expect;
- **images** — screenshots, pasted or taken with the camera.

The row starts as ⚪ *waiting*: the agent must not touch it. When the request is ready, click the dot to
turn it 🟢 **open to work**.

## 3. Tell your agent the rules

Publish the instructions file and point your agent at it:

```bash
php artisan vendor:publish --tag=griglia-agents     # AGENTS.md at your project root
```

Claude Code reads `CLAUDE.md`, Codex and most others `AGENTS.md`, Gemini CLI `GEMINI.md` — same content;
copy or symlink it. See [The agent side](../agent/index.md) for what those rules say.

## 4. Let the agent work

In the project directory the agent runs:

```bash
php artisan griglia:check                      # what is open to work, plus the settings to follow
php artisan griglia:check --take=12            # take it in charge  → 🔧, progress starts at 0%
php artisan griglia:check --take=12 --progress=60 --phase="writing code"
php artisan griglia:check --done=12 --comment="What I did and how to try it"
```

The board updates live while this happens: the dot turns 🔧, the progress bar and phase move, and the
closing comment shows up under the note as the agent's answer. If the request is ambiguous the agent
pauses it with questions:

```bash
php artisan griglia:check --ask=12 --q="Which of the two layouts?" --q="Italian or English?"
```

Answer them in the task modal and press **restart**: the task goes back to 🟢.

## 5. Keep it running

```bash
php artisan griglia:watch      # long-running: prints only the events the agent must react to
```

## Where to go next

- [Using the board](../board/usage.md) — states, filters, archive, mobile.
- [Plans](../features/plans.md) — split a goal into chained tasks.
- [Configuration & settings](../configuration/index.md) — how the agent is asked to behave.
- [Feature overview](../features/index.md) — everything the board does, in one page.
