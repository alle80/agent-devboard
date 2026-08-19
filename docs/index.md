# Agent Devboard

**Agent Devboard** is a Laravel + Livewire board that lets you queue work for a coding agent (Claude Code, Codex CLI,
Gemini CLI, …) and follow it in real time: lists of tasks with notes, sub-tasks, images and questions; a small CLI
contract (`devboard:check` / `devboard:watch`) the agent drives; live updates, notifications, plans built from a
prompt, statistics, an agent-context manager and a theme system.

## How it works in one minute

1. You write requests as tasks in the **agent list** (default name `dev`) and mark them **open to work** 🟢.
2. The agent runs `php artisan devboard:watch` (events) and `devboard:check` (what to do), takes a task (🔧),
   asks questions (❓) when needed, updates progress/phase, and closes it with a comment (✔).
3. The board shows everything live (Reverb/Echo), notifies you (bell, Web Push, mail) and keeps statistics.

## Where to go next

- [Installation](installation.md) — requirements, composer, migrations, assets, Reverb.
- [Using the board](usage.md) — lists, states, modal, filters, mobile.
- [The agent side](agent.md) — the CLI contract and the rules the agent follows.
- [Plans](plans.md), [Notifications](notifications.md), [Agent context](context.md), [Skills](skills.md),
  [Statistics & agents status](stats.md), [Themes](themes.md).
- [Configuration & settings](configuration.md), [Security](security.md), [Development](development.md),
  [Building this site](docs-site.md).
