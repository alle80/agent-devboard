# Agent context

The instructions file your agent reads at every step (e.g. `CLAUDE.md`) can be **managed from the board**
(`/context`, administrators only):

```bash
php artisan griglia:context import --file=CLAUDE.md     # once: markdown → groups (##) and blocks
php artisan griglia:context export                       # the enabled blocks as markdown
php artisan griglia:context status
```

Each group and block has a switch; blocks can be multi-selected and enabled/disabled together, edited (Markdown
editor), added, deleted and reordered; a token estimate is shown. On the host, a small script writes the export
back to the instruction files (see `scripts/sync-context.py` in the origin repository — CLAUDE.md and AGENTS.md,
cron every minute). Token-saving switches live in Settings → Optimization.

## Keeping the original files

The switch **Generate the instruction files from the board** (top of `/context`, setting `app.context_sync`)
decides whether the host sync writes the generated files (on) or restores and leaves the **original** files alone
(off) — useful when you stop using the board: the originals apply again. Host scripts read it with
`php artisan griglia:context enabled` (prints `1`/`0`).

## See also

- [The agent side](index.md) · [Configuration & settings](../configuration/index.md)
