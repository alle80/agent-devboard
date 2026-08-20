# Host scripts

Part of the board lives outside the container: the skills the agent has installed, its credentials, the
transcript of the session. The host helpers and persistent worker handle that **on the machine where the agent runs** and
push the result into the board through the artisan commands. They ship with the package:

```bash
php artisan vendor:publish --tag=griglia-scripts   # → scripts/ in your project
```

| Script | What it does | Command it feeds |
| --- | --- | --- |
| `sync-skills.py` | reads the skill folders of Claude Code, Codex CLI and Gemini CLI (plus the built-ins listed in `builtin-skills.json`) and tags each skill with the agents that can invoke it | `griglia:skills-import` |
| `sync-context.py` | writes the enabled context blocks back to `CLAUDE.md` / `AGENTS.md`, keeps the originals, `--check` tells you if they are in sync, `--import` loads a hand-written file | `griglia:context` |
| `claude-tokens.py` | sums the tokens of the session spent on a task (`--todo=ID --args` prints them ready for `griglia:check --done`) | `griglia:check` |
| `agent-status.py` | reads the agent's OAuth credentials and sends **only percentages** of the plan windows | `griglia:agent-status-import` |
| `griglia-agent-worker.py` | polls assigned work and launches Codex, Claude Code or a custom CLI; the systemd template keeps it alive | `griglia:check` |

All host scripts need `python3` and reach Artisan through one of two **transports**. The default is Docker
(`docker exec <container> php artisan`, container from `GRIGLIA_CONTAINER`, default `laravel-dev-app`); where
Laravel runs directly on the machine, set `GRIGLIA_TRANSPORT=local` and the scripts run `php artisan` from the
project root instead, with `GRIGLIA_PHP` naming the executable when it is not simply `php`:

```dotenv
GRIGLIA_TRANSPORT=local
GRIGLIA_PHP=/usr/bin/php8.4
```

So a host without Docker is enough to run the whole toolchain. The synchronization helpers provide print/check
modes; the worker instead needs access to the selected transport **and** to the agent CLI it launches. It reads
the same two variables and accepts per-instance overrides — see [Persistent workers](workers.md).

## Where they think they are

The synchronization scripts need the project root (instruction files and transcript folders). They read it from
`GRIGLIA_PROJECT_ROOT` when set; otherwise they derive it from their own position — the parent of the `scripts/` folder,
or the folder containing `vendor/` when you run it straight from `vendor/alle80/griglia/scripts/`. So both of
these work:

```bash
scripts/sync-skills.py                              # published copy
vendor/alle80/griglia/scripts/sync-skills.py        # straight from the package
GRIGLIA_PROJECT_ROOT=/srv/app scripts/sync-skills.py  # anywhere else
```

Typical cron on the host:

```cron
* * * * * cd /srv/app && scripts/sync-context.py >/dev/null 2>&1
*/5 * * * * cd /srv/app && scripts/agent-status.py >/dev/null 2>&1
```

## See also

- [Skills](skills.md) · [Agent context](context.md) · [Statistics](stats.md)
- [Artisan commands](../reference/commands.md) — the commands these scripts feed.
