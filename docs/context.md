# Agent context

The instructions file your agent reads at every step (e.g. `CLAUDE.md`) can be **managed from the board**
(`/context`, administrators only):

```bash
php artisan devboard:context import --file=CLAUDE.md     # once: markdown → groups (##) and blocks
php artisan devboard:context export                       # the enabled blocks as markdown
php artisan devboard:context status
```

Each group and block has a switch; blocks can be multi-selected and enabled/disabled together, edited (Markdown
editor), added, deleted and reordered; a token estimate is shown. On the host, a small script writes the export
back to the instruction files (see `scripts/sync-context.py` in the origin repository — CLAUDE.md and AGENTS.md,
cron every minute). Token-saving switches live in Settings → Optimization.
