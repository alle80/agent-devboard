# Statistics & agents status

## Statistics (`/stats`)

Per list (or all lists / all plans): completed tasks, working time (sum and average over tracked items), tokens,
**cost** (price list in Settings → App: price per 1M input/output tokens, currency), completed-per-day bars, the
history of completed tasks (date, time, lead time, tokens, cost) and an overview of every list. Periods 7/30/90/365
days or all time.

## Agents status (`/agents`)

Plan and usage windows of your coding agents (used %, remaining %, reset countdown, levels ok/high/almost
exhausted/over the limit). Data come from a snapshot imported with:

```bash
php artisan devboard:agent-status-import --file=snapshot.json   # {updated_at, agents:[{key,name,plan,windows:[…]}]}
```

The origin repository ships `scripts/agent-status.py` for Claude Code: it reads the OAuth credentials **on the
host** and sends only percentages (cron every 5 minutes).
