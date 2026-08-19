# Skills

The catalogue of the agent's skills is imported with:

```bash
php artisan devboard:skills-import --file=skills.json   # or JSON on stdin: [{name, description, source}, …]
```

The task modal shows it as an accordion (with live search); the skills you tick for a task are printed by
`devboard:check` (`🧩 skills to activate for this task: …`) so the agent invokes them. The origin repository ships
`scripts/sync-skills.py` which reads Claude Code, Codex and Gemini skill folders.
