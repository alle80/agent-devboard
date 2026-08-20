# The agent side

The board never talks to a specific vendor: the contract is a CLI. Give your agent the rules of `AGENTS.md`
(Codex reads it natively; Claude Code reads `CLAUDE.md`; Gemini CLI `GEMINI.md`) and let it run:

```bash
php artisan griglia:watch                       # prints only the changes it must react to (events)
php artisan griglia:check                       # what to work on (🟢/🔧), settings to follow, plans
php artisan griglia:check --take=ID             # take in charge → 🔧 (starts at 0%)
php artisan griglia:check --take=ID --progress=60 --phase="testing"
php artisan griglia:check --ask=ID --q="…" --q="…"     # pause with questions → ❓
php artisan griglia:check --done=ID --comment="…" [--tokens-in=N --tokens-out=N]
```

`check` prints the **settings** of the `agent` and `optimization` groups at the top (commit policy, autonomy,
notifications, task mode, terse mode, …) that the agent is expected to follow, then the open tasks of the agent
list and, after them, the open tasks of the started **plans** (`📐 Plan «name»`).

Rules worth knowing: take the task **first** (before reading/analysing), one task at a time in list order
(`task_mode=ordered`) or several independent ones (`multitasking`), never touch ⚪ items, stop immediately on ⏹,
keep the progress % and phase updated, report tokens on close when the setting asks for it.

Statistics: every 🔧 interval is timed automatically; tokens are whatever the agent reports.

## Several agents

Declare them with `GRIGLIA_AGENTS="claude:Claude Code,codex:Codex CLI"`. A list (project) has a default agent
(toolbar selector), a task may override it (modal header). Each agent runs `griglia:check --agent=<its key>`
(or sets `GRIGLIA_AGENT_KEY`) and sees only its tasks; `--take/--done` still work by id.
