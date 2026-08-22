---
title: An open-source task board for coding agents
template: home.html
hero_title: An open-source task board for coding agents
hero_text: >-
  Griglia gives developers and CLI coding agents a shared, observable workflow for requests, questions,
  progress and results. It runs in your Laravel application and stays under your control.
hero_quickstart: Quickstart
hero_documentation: Documentation
hero_meta: Laravel 12/13 · Livewire 4 · MIT · works with Claude Code, Codex CLI, Gemini CLI, …
hide:
  - navigation
  - toc
---

# What Griglia does

**Griglia** is a Laravel + Livewire task board for developers who use coding agents. You write a request,
add notes, sub-tasks or screenshots, and decide when it is ready. A CLI agent can then claim it, ask
questions, report its current phase and close it with a recorded result.

The board keeps that exchange visible and persistent. It can coordinate several lists and agents, turn a
goal into an ordered plan, notify you when input is needed, and retain working time, token and cost data.

<div class="grid cards" markdown>

-   **A flow you can see**

    ---

    Waiting → open to work → working → done, with questions, stop and resume. Every state is a dot on the
    row: you always know what the agent is allowed to touch, and what it is doing right now.

    [Using the board](board/usage.md)

-   **A CLI contract, not an integration**

    ---

    `griglia:check` to read and act, `griglia:watch` to react. Progress %, phase, questions, tokens and
    the closing comment all travel through those two commands.

    [The agent side](agent/index.md)

-   **Plans from a prompt**

    ---

    Turn a goal into a chain of tasks: closing one opens the next, so a long job runs on its own while
    you watch it advance.

    [Plans](features/plans.md)

-   **It reaches you**

    ---

    Live updates between devices, an in-app bell, Web Push on your phone and mail — so a question does not
    sit there for an hour.

    [Notifications](features/notifications.md)

-   **Yours to look at**

    ---

    A theme system with installable packs, a settings page that tells the agent how to behave, statistics
    with working time, tokens and cost.

    [Themes](features/themes.md) · [Statistics](agent/stats.md)

-   **Small to install**

    ---

    One composer package, one migration, precompiled assets if you do not want a build step. English and
    Italian included.

    [Installation](getting-started/installation.md)

</div>

## Why use it instead of a CLI session alone?

A terminal session is effective while one developer is watching it, but its task state is usually local and
temporary. Griglia adds a durable control plane around the CLI agent without replacing it:

- requests have an explicit queue and permission to start;
- questions, progress and results remain attached to the task;
- work can continue through fresh sessions or persistent workers;
- another device or team member can understand the current state without reading a terminal transcript.

The agent still edits code, runs tests and uses Git through its normal CLI tools. Griglia coordinates the work around that session.

## What Griglia is not

Griglia is not an autonomous coding model, an IDE, a chat interface or a hosted agent service. It does not send prompts to a model provider and it does not remove the need to review changes. You bring the coding agent and decide how it runs; Griglia supplies the shared task workflow.

## Open source by design

Griglia is released under the MIT licence. The board, agent protocol, migrations and documentation are available in the package source, so teams can inspect the workflow, adapt it to their Laravel application and keep operational data on infrastructure they control.

## How it works in one minute

1. You write a request in the **agent list** (default name `dev`), with notes, sub-tasks and screenshots,
   and set the dot to **open to work**.
2. The agent runs `griglia:watch` (events) and `griglia:check` (what to do), takes the task — the dot turns
   to **working** — asks **questions** when the request is ambiguous, updates progress and phase, and
   **closes** it with a comment.
3. The board shows all of it live, notifies you, and keeps the statistics of what it cost.

```bash
composer require alle80/griglia -W
php artisan migrate
php artisan vendor:publish --tag=griglia-agents    # the rules for your agent
```

[Get started in five minutes](getting-started/quickstart.md){ .md-button .md-button--primary }
[See every feature](features/index.md){ .md-button }
