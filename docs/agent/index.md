# The agent side

The board never talks to a specific vendor: the contract is a CLI. Give your agent the rules of `AGENTS.md`
(Codex reads it natively; Claude Code reads `CLAUDE.md`; Gemini CLI `GEMINI.md`) and let it run:

```bash
php artisan griglia:watch                       # prints only the changes it must react to (events)
php artisan griglia:watch --agent=codex         # only events assigned to one agent
php artisan griglia:check                       # what is open to work or already taken, settings, plans
php artisan griglia:check --take=ID             # take in charge: the task turns to working (0%)
php artisan griglia:check --take=ID --progress=60 --phase="testing"
php artisan griglia:check --ask=ID --q="…" --q="…"     # pause the task with questions
php artisan griglia:check --done=ID --comment="…" [--tokens-in=N --tokens-out=N]
php artisan griglia:check --done=ID --comment="…" --outcome=alert   # done, but it needs a look (yellow row)
php artisan griglia:check --done=ID --comment="…" --outcome=blocked # something is in the way (red row)
```

`check` prints the **settings** of the `agent` and `optimization` groups at the top (commit policy, autonomy,
notifications, task mode, terse mode, …) that the agent is expected to follow, then the open tasks of the agent
list and, after them, the open tasks of the started **plans** (under a `Plan «name»` heading).

Rules worth knowing: take the task **first** (before reading/analysing), one task at a time in list order
(`task_mode=ordered`) or several independent ones (`multitasking`), never touch *waiting* items, drop a
task the moment it is stopped (and do not take it again until the user puts it back to 🟢: `--take` refuses a
stopped task, so a late `--take=ID --progress=N` cannot silently resume it),
keep the progress % and phase updated, report tokens on close when the setting asks for it, and say with
`--outcome` when a closed task is not plain sailing — it is what
[colours the row](../board/usage.md#the-colour-of-the-row) the user sees (`ok` by default, `alert`,
`blocked`).

A task born from a **resume** carries its history: `check` prints the note, the answer and the sub-tasks of
every previous step, newest first (`resumes «…»`, then `2 steps back «…»`, `3 steps back «…»`), because a
resume can itself be resumed. With `--json` the same history is in the `resume_chain` field of each task,
ordered from the closest step to the oldest one.

Statistics: every *working* interval is timed automatically; tokens are whatever the agent reports.

**A heavy session costs on every step**, because the context is re-read at every turn. The setting «suggest
clearing the session» (⚡ optimization, in thousands of tokens) is the threshold past which the agent tells
you to run `/clear` — it cannot run it for you.

## Several agents

Declare them with `GRIGLIA_AGENTS="claude:Claude Code,codex:Codex CLI"`. A list (project) has a default agent
(toolbar selector), a task may override it — from the modal header, where the selector has a row of its own
under the commands, or straight from its row in the list, where
the same selector sits on its own line under the title and doubles as the agent badge: the name of the agent
that will take the task is always visible, also when the task simply inherits the list default (the empty
option). While a task is working, the same name stays visible as a read-only badge in both the list and modal. Each agent runs `griglia:check --agent=<its key>`
(or sets `GRIGLIA_AGENT_KEY`) and sees only its tasks; `--take/--done` still work by id. The [skills](skills.md)
offered on a task are filtered the same way: only the ones its agent has installed.

`--take`, `--done` and `--ask` refuse a task that belongs to another agent, and `--take` a task the user stopped
(`--force` overrides both), `check`
prints a `🔒 busy elsewhere` line with what the others have in progress, and the 🆕 baseline is kept per
agent key. What is shared outside the board — checkouts, builds, migrations, releases — is covered in
[Two agents at once](concurrency.md).

Use the same key with `griglia:watch --agent=<key>`. With `--once`, the command also prints tasks that
were already waiting when it started, which makes it suitable for cron jobs and supervised workers;
`--no-initial` keeps baseline-only behaviour.

## See also

- [Quickstart](../getting-started/quickstart.md) — the same flow, step by step.
- [Artisan commands](../reference/commands.md) — every command and option, generated from the code.
- [Skills](skills.md) · [Agent context](context.md) · [Statistics](stats.md) · [Host scripts](scripts.md) · [Persistent workers](workers.md) · [Two agents at once](concurrency.md)
