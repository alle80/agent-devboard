# Host scripts

Part of the board lives outside the container: the skills the agent has installed, its credentials, the
transcript of the session. Four small Python helpers read all that **on the machine where the agent runs** and
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

They need `python3` and the `docker` CLI when the app runs in a container (`GRIGLIA_CONTAINER`, default
`laravel-dev-app`; `GRIGLIA_USER`, default `www-data`). Without Docker, call the artisan commands directly:
every script also has a `--print` / `--check` mode that just shows what it would send.

## Where they think they are

Each script needs the root of your project (the instruction files, the transcripts folder). It takes it from
`GRIGLIA_PROJECT_ROOT` if set; otherwise from its own position — the parent of the `scripts/` folder it sits in,
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
