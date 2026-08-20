# Feature overview

Everything the board does, in one page. Follow a link when you want the detail.

## The work itself

| | |
|---|---|
| **States and flow** | Waiting, open to work, working, question, done — plus a stop that pulls a task back from the agent. [Using the board](../board/usage.md) |
| **Tasks with substance** | Notes in Markdown, sub-tasks, image attachments (upload, camera, paste), the agent's closing comment kept apart from your own notes. [Using the board](../board/usage.md) |
| **Progress and phase** | A percentage and a short «what I am doing now» on every working task, updated by the agent as it goes. [The agent side](../agent/index.md) |
| **Questions** | The agent can pause a task with questions; you answer in the modal and send it back to work. [The agent side](../agent/index.md) |
| **Resume** | Reopen a finished task as a new one that carries the old context with it. [Using the board](../board/usage.md) |
| **Lists, archive, search** | Several lists per user, state filters, free-text search (including AI descriptions of the images), automatic archiving of old tasks. [Using the board](../board/usage.md) |

## Driving an agent

| | |
|---|---|
| **The CLI contract** | `griglia:check` and `griglia:watch`: two commands, no vendor API. [The agent side](../agent/index.md) |
| **Rules the agent follows** | The settings page tells it how to work — commit policy, autonomy, notifications, one task at a time or several, terse mode. [Configuration & settings](../configuration/index.md) |
| **Several agents** | Declare them, give each list or task its own, and each agent sees only its work. [The agent side](../agent/index.md) |
| **Skills** | Load your agent's skill catalogue and pick, per task, which ones it should use. [Skills](../agent/skills.md) |
| **Agent context** | Your instructions file as toggleable blocks, edited from the board and exported back to `AGENTS.md` / `CLAUDE.md`. [Agent context](../agent/context.md) |
| **Statistics** | Working time measured automatically, tokens reported by the agent, cost per million, per-day bars. [Statistics](../agent/stats.md) |
| **Agent status** | Plan and usage windows of your agents (used, remaining, reset countdown). [Statistics & agents status](../agent/stats.md) |

## Around it

| | |
|---|---|
| **Plans** | A prompt becomes a chain of tasks; closing one opens the next. [Plans](plans.md) |
| **Notifications** | In-app bell, Web Push on your devices, mail — each one switchable. [Notifications](notifications.md) |
| **Live updates** | Any Laravel broadcaster (Reverb, Pusher…); without one the board still works. [Events](../reference/events.md) |
| **Themes** | A theme system with CSS variables, the built-in Slate theme and installable zip packs. [Themes](themes.md) |
| **AI, optional** | Image descriptions for search, speech to text on every field, the plan builder. [AI features](ai.md) |
| **Modes** | `server` (login, lists per user) or `local` (no auth, your machine only). [Access & modes](../configuration/access.md) |
| **Mobile** | Touch targets, camera attachments, Web Push, and the board installable as a PWA. [Using the board](../board/usage.md) |
