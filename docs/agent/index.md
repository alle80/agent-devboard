# The agent side

The board never talks to a specific vendor: the contract is a CLI. Give your agent the rules of `AGENTS.md`
(Codex reads it natively; Claude Code reads `CLAUDE.md`; Gemini CLI `GEMINI.md`) and let it run:

```bash
php artisan griglia:watch                       # prints only the changes it must react to (events)
php artisan griglia:watch --agent=codex         # only events assigned to one agent
php artisan griglia:check                       # what is open to work or already taken, settings, plans
php artisan griglia:check --take=ID             # take in charge: the task turns to working (0%)
php artisan griglia:check --take=ID --progress=60 --phase="testing"
php artisan griglia:check --pause=ID            # pause work, preserving progress and statistics
php artisan griglia:check --ask=ID --q="Which one?" --choices="First|Second" # choices align with questions
php artisan griglia:check --approve=REVIEW_ID --comment="Approved"
php artisan griglia:check --request-changes=REVIEW_ID --comment="What must change"
php artisan griglia:check --done=ID --comment="…" [--tokens-in=N --tokens-out=N]
php artisan griglia:check --done=ID --comment="…" --outcome=alert   # done, but it needs a look (yellow row)
php artisan griglia:check --done=ID --comment="…" --outcome=blocked # something is in the way (red row)
```

When an original task has an optional reviewer configured, the executor's `--done` is a **submission**, not final
completion. Griglia atomically leaves the original incomplete and creates a linked, open review attempt assigned to
that reviewer. Without a reviewer, `--done` keeps its existing meaning. Review attempts have immutable round numbers,
cannot review themselves or participate in plan/resume chains, and never release the original's plan dependants.
Reviewer decisions are explicit operations rather than an ordinary `--done`, so an outcome cannot be omitted. The
assigned reviewer must first take the review attempt, then use `--approve` or `--request-changes`; the latter requires
a comment explaining the requested work. Approval completes the attempt and original atomically and releases plan
dependants. A change request retains the review comment, reopens the original for its executor and allows a later review
round. Repeated identical decisions are safe; opposite decisions are rejected. The task modal shows the resulting state.

Agent wrappers may pass multiline comments with escaped `\n` sequences: `griglia:check` normalizes them to
real Markdown line breaks when saving the answer. The compact result summary always remains on one line.

`check` prints the **settings** of the `agent` and `optimization` groups at the top (commit policy, question
level, notifications, task mode, terse mode, …) that the agent is expected to follow, then the rules of the chosen
**question level** (`❓ question level: …` — how many questions to ask before starting; the same block the board
writes into the [agent context](context.md)), then the open tasks of the agent list and, after them, the open
tasks of the started **plans** (under a `Plan «name»` heading).

When possible, agents should offer short closed choices with `--choices` (repeat it in the same order as `--q`). The modal renders them as one-tap answers, while always retaining the free-text field and speech-to-text microphone. Omit or leave the corresponding `--choices` value empty for an open-only question.

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
When an agent must stop temporarily (for example because its usage limit was reached), `--pause=ID` closes the
current timed interval and shows the pause badge without losing progress or phase. The paused task is not offered
to other agents. The persistent worker automatically claims it again as soon as that agent has an available
session slot; a manual agent can resume it with `--take=ID`. Tapping the badge remains available when the user
wants to resume it immediately.

**A heavy session costs on every step**, because the context is re-read at every turn. The setting «suggest
clearing the session» (⚡ optimization, in thousands of tokens) is the threshold past which the agent tells
you to run `/clear` — it cannot run it for you.

## Several agents

Declare them with `GRIGLIA_AGENTS="claude:Claude Code,codex:Codex CLI"`. A list (project) has a default agent
(toolbar selector), a task may override it — from the modal header, where the selector has a row of its own
under the commands, or straight from its row in the list, where
the same selector sits on its own line under the title and doubles as the agent badge: the name of the agent
that will take the task is always visible, also when the task simply inherits the list default (the empty
option). While a task is working, the same name stays visible as a read-only badge in both the list and modal.
The toolbar can also **filter** the list by agent (the chip with the robot icon, next to the state filters): it
follows the same effective assignment and combines with the search and the state filters. Each agent runs `griglia:check --agent=<its key>`
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
