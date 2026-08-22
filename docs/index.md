---
title: A dev board for coding agents
template: home.html
hero_title: A dev board for coding agents
hero_text: >-
  Queue your requests as tasks; your coding agent takes them, asks questions, reports progress and
  closes them — and you follow the whole thing live, from the sofa or from your phone.
hero_quickstart: Quickstart
hero_documentation: Documentation
hero_meta: Laravel 12/13 · Livewire 4 · MIT · works with Claude Code, Codex CLI, Gemini CLI, …
hide:
  - navigation
  - toc
---

# What Griglia is

**Griglia** is a Laravel + Livewire board you install in your own app. One list is the channel with your
coding agent: you write requests as tasks, mark them **open to work**, and the agent — Claude Code, Codex
CLI, Gemini CLI, any CLI agent — takes them, asks when something is unclear, keeps the progress bar moving
and closes them with an answer you can read.

It is not a chat wrapper and it does not talk to any vendor API: the contract is two artisan commands and
one instructions file. Anything that speaks a shell can drive it.

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
