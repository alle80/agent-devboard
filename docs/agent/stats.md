# Statistics & agents status

## Statistics (`/stats`)

Per list (or all lists / all plans): completed tasks, working time (sum and average over tracked items), tokens,
**cost** (price list in Settings → App: price per 1M input/output tokens, currency), completed-per-day bars, the
history of completed tasks (date, time, lead time, tokens, cost) and an overview of every list. Periods 7/30/90/365
days or all time.

Deleting a list or a task is a **soft delete**: the statistics survive, and trashed lists stay selectable on
`/stats` (marked "(deleted)"). To really free the data — statistics included — empty the trash:

```bash
php artisan griglia:empty-trash --dry-run      # what would be purged
php artisan griglia:empty-trash --days=30      # only items deleted more than 30 days ago
```

## Agents status (`/agents`)

Plan and usage windows of your coding agents (used %, remaining %, reset countdown, levels ok/high/almost
exhausted/over the limit). Data come from a snapshot imported with:

```bash
php artisan griglia:agent-status-import --file=snapshot.json   # {updated_at, agents:[{key,name,plan,windows:[…]}]}
```

The package ships `scripts/agent-status.py` for Claude Code and Codex CLI: it reads Claude OAuth usage and Codex local rollout telemetry **on the host** and
sends only percentages (cron every 5 minutes). Same for the tokens of a task: `scripts/claude-tokens.py --todo=ID
--args`. See [the scripts](scripts.md).

## See also

- [Using the board](../board/usage.md) — where the per-task statistics show up.
- [The agent side](index.md) — the tokens are reported by the agent on `--done`.
