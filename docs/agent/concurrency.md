# Two agents at once

Yes: two coding agents pointed at the same board **can** step on each other, and the places where it happens
are few and predictable. This page lists them and the rule that keeps each one safe. The short version: the
board decides *who works on what*, one checkout per agent decides *where they write*, and everything that is
shared by the machine (caches, builds, migrations, releases) is done by one agent at a time.

## What the board guarantees

Every task belongs to **one** agent: its own override (the selector on its own row in the modal header),
else the list default (toolbar
selector), else the default agent. `griglia:check --agent=<key>` lists only that agent's tasks, and the
actions refuse to touch anybody else's:

The task row keeps showing this effective agent while work is in progress, including when the task inherits
the list default rather than carrying its own override.

```console
$ php artisan griglia:check --agent=claude --take=412
«Release the package» (id:412) belongs to agent «Codex CLI», you are «Claude Code»: refusing to take it
— it is being worked on right now. Reassign it on the board (task or list agent), or re-run with --force.
```

That guard covers `--take`, `--done` and `--ask`, so a stale id in a prompt (or a worker restarted with the
wrong key) cannot silently steal, close or pause work another agent is doing. `--force` is the deliberate
way in — use it when you *are* taking over.

Two more things follow from it:

- **`🔒 busy elsewhere`** — with several agents configured, `griglia:check` prints what the others have in
  progress right now, so you can keep out of those files and branches.
- **The 🆕 baseline is per agent.** The "new since your last check" marker is stored per agent key: another
  agent running `check` no longer consumes your 🆕 markers.

Assign the work explicitly (task or list agent) whenever two agents are active: an unassigned task falls to
the default agent, so the second agent never even sees it.

## What the board cannot guarantee

Everything outside the database is shared by the host, and the board has no say in it.

| Shared resource | How they collide | Rule |
| --- | --- | --- |
| Working tree | Agent B checks out another branch while agent A is editing — and in this project the served site *is* the working tree | One checkout per agent: `git worktree add ../wt-codex -b task/…`, and point the worker at it (`--repo`, `GRIGLIA_WORKER_REPO`) |
| Branches | Stacking a task branch on another agent's tip drags unreviewed work into the PR | Branch from an updated `main` (`git fetch && git merge origin/main`), one PR per task, never on top of another task |
| Compiled assets (`public/build`) | Two Vite builds writing the same manifest | Build one at a time; each worktree builds its own copy |
| Compiled Blade cache | A view compiled from another branch is reused when the source file is older than it, and the suite fails on code that no longer exists | The package test suite empties the compiled views on the first test of the run |
| Database and migrations | One shared schema: a migration lands under the other agent's feet | Run migrations one at a time, and say so on the board while you do |
| Package release | `rsync --delete` towards the package repository would erase what the other agent released meanwhile | `release-griglia.sh` stops when the remote has versions or files the source does not have; when it stops, wait for the other agent's pull request to land, merge `main` into your branch and release again |
| Container-wide commands | `config:cache`, `route:cache`, `queue:restart`, `reverb:restart` hit every session | Treat them as global: run them when nobody else is mid-test |

## Setting up the second agent

```bash
# 1. declare both agents
GRIGLIA_AGENTS="claude:Claude Code,codex:Codex CLI"

# 2. give each one its own checkout
git worktree add ../board-codex -b task/000-scratch origin/main

# 3. one worker per agent, each on its own repository path
GRIGLIA_WORKER_REPO=/srv/board-codex \
  python3 scripts/griglia-agent-worker.py --agent=codex --driver=codex
```

Each worker already takes a lock of its own (`/tmp/griglia-agent-worker-<key>.lock`), so the same agent key
never runs twice; different keys are expected to run together.

## See also

- [The agent side](index.md) — commands, states, multi-agent scoping.
- [Persistent workers](workers.md) — running an agent as a service.
- [Artisan commands](../reference/commands.md) — every option, generated from the code.
