# Artisan commands

Everything the package adds to `php artisan`. `griglia:check` also answers to the legacy alias
`sviluppo:check`.

## The agent contract

```bash
php artisan griglia:check [options]
```

| Option | What it does |
|---|---|
| `--all` | Also completed items and items not open to work |
| `--json` | Machine-readable output |
| `--take=ID` | Take the task in charge → 🔧 (progress starts at 0%) |
| `--progress=N` `--phase="…"` | Update the progress bar and the phase text (with `--take`) |
| `--ask=ID` `--q="…"` | Pause the task with one or more questions → ❓ |
| `--done=ID` `--comment="…"` | Close it and save the agent's answer |
| `--tokens-in=N` `--tokens-out=N` | Add the tokens spent to the task statistics |
| `--agent=key` | Only the tasks of that agent (multi-agent setups) |

```bash
php artisan griglia:watch [--interval=10] [--list=dev] [--once] [--no-initial]
```

Long-running: prints only the changes the agent must react to (a task turned 🟢, answers arrived, a stop
was requested).

## Content and maintenance

| Command | What it does |
|---|---|
| `griglia:context {import\|export\|status\|enabled}` | The agent-context blocks: `--file=`, `--replace`, `--all` |
| `griglia:skills-import` | Load the agent's skill catalogue (`--file=`, or JSON on stdin) |
| `griglia:agent-status-import` | Feed `/agents` with a usage snapshot (`--file=`, or stdin) |
| `griglia:describe-images` | AI descriptions of the attachments (`--all`, `--limit=`) |
| `griglia:auto-archive` | Archive tasks completed long ago (`--dry-run`); scheduled daily at 03:30 |
| `griglia:empty-trash` | Purge soft-deleted lists/tasks for real (`--days=`, `--dry-run`) |

## Themes and docs

| Command | What it does |
|---|---|
| `griglia:theme-import pack.zip` | Install a theme pack (`--uninstall=<slug>` removes one) |
| `griglia:theme-export <slug>` | Export a theme as a starting point (`--out=`, `--css-from=`) |
| `griglia:docs-build` | Build this site (`--serve`, `--out=`, `--docker`, `--strict`) |
