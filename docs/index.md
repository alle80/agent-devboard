# Griglia

**Griglia** is a Laravel + Livewire board that lets you queue work for a coding agent (Claude Code, Codex CLI,
Gemini CLI, …) and follow it in real time: lists of tasks with notes, sub-tasks, images and questions; a small CLI
contract (`griglia:check` / `griglia:watch`) the agent drives; live updates, notifications, plans built from a
prompt, statistics, an agent-context manager and a theme system.

## How it works in one minute

1. You write requests as tasks in the **agent list** (default name `dev`) and mark them **open to work** 🟢.
2. The agent runs `php artisan griglia:watch` (events) and `griglia:check` (what to do), takes a task (🔧),
   asks questions (❓) when needed, updates progress/phase, and closes it with a comment (✔).
3. The board shows everything live (Reverb/Echo), notifies you (bell, Web Push, mail) and keeps statistics.

## Where to go next

- [Installation](getting-started/installation.md) — requirements, composer, migrations, assets.
- [Quickstart](getting-started/quickstart.md) — from an empty board to the first task worked by an agent.
- [Using the board](board/usage.md) — lists, states, modal, filters, mobile.
- [The agent side](agent/index.md) — the CLI contract and the rules the agent follows.
- [Plans](features/plans.md), [Notifications](features/notifications.md), [Themes](features/themes.md),
  [AI features](features/ai.md).
- [Configuration](configuration/index.md), [Access & modes](configuration/access.md),
  [Command reference](reference/commands.md).
- [Security](operations/security.md), [Troubleshooting](operations/troubleshooting.md),
  [Contributing](contributing/contributing.md).
