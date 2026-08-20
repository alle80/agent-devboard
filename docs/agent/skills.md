# Skills

The catalogue of the agent's skills is imported with:

```bash
php artisan griglia:skills-import --file=skills.json   # or JSON on stdin: [{name, description, source}, …]
```

The task modal shows it as an accordion (with live search); the skills you tick for a task are printed by
`griglia:check` (`skills to activate for this task: …`) so the agent invokes them. The origin repository ships
`scripts/sync-skills.py` which reads Claude Code, Codex and Gemini skill folders.

The JSON is a plain list:

```json
[
  { "name": "tdd", "description": "Test-driven development", "source": "user" },
  { "name": "code-review", "description": "Review a branch", "source": "plugin" }
]
```

## See also

- [The agent side](index.md) — how the chosen skills reach the agent.
- [Agent context](context.md) — the instructions file behind them.
