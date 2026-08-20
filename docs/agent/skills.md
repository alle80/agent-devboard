# Skills

The catalogue of the agent's skills is imported with:

```bash
php artisan griglia:skills-import --file=skills.json   # or JSON on stdin: [{name, description, source, agents}, …]
```

The task modal shows it as an accordion (with live search); the skills you tick for a task are printed by
`griglia:check` (`skills to activate for this task: …`) so the agent invokes them. The origin repository ships
`scripts/sync-skills.py` which reads Claude Code, Codex and Gemini skill folders.

## One catalogue, several agents

The SKILL.md format is portable — frontmatter plus instructions in markdown, nothing tied to a particular CLI —
but a skill only exists for the agent that finds it on disk: what is installed in `~/.claude/skills` is invisible
to Codex CLI, and the built-in skills of a CLI cannot be installed anywhere at all. Each entry therefore carries
`agents`, the keys (from `griglia.agents`) of the agents that can use it, and the modal of a task only offers the
skills of [its agent](index.md#several-agents) plus the ones with no `agents` at all — shared, or imported before this
field existed. A skill already ticked stays visible even if the task changes agent, so you can untick it.

`scripts/sync-skills.py` fills the field from the folder it read the skill from: `~/.claude/skills`, project
`.claude/skills`, plugins and built-ins → `claude`; `~/.codex/skills` and project `.codex/skills` → `codex`;
`~/.gemini/skills` → `gemini`; the shared `~/.agents/skills` → no constraint. The same skill found in two folders
is available to both agents.

The JSON is a plain list:

```json
[
  { "name": "tdd", "description": "Test-driven development", "source": "user", "agents": ["claude"] },
  { "name": "code-review", "description": "Review a branch", "source": "plugin", "agents": ["claude", "codex"] },
  { "name": "commit-style", "description": "How we write commits", "source": "agents" }
]
```

## See also

- [The agent side](index.md) — how the chosen skills reach the agent.
- [Agent context](context.md) — the instructions file behind them.
