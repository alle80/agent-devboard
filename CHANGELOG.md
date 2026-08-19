# Changelog

All notable changes to `alle80/agent-devboard` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.14.1] - 2026-08-19

### Added
- **Live search** box at the top of the 🧩 skills accordion (client-side, filters name/description/source
  while typing; Esc clears).

## [0.14.0] - 2026-08-19

### Added
- **Board modes** (`Alle80\Devboard\Mode`): config `devboard.mode` (`DEVBOARD_MODE`, default `server`),
  overridable from `/settings` (`app.mode`, '' = follow the config, with a warning for local).
  - **server**: authenticated users, lists per user, plus an access hook: `canAccessDevboard(): bool` on
    the user model if defined, else the Gate ability in `devboard.access_gate` if set, else any logged-in
    user (403 otherwise). Enforced by the new `DevboardAccess` middleware, which also plays the role of
    `auth` (redirect to login) — `'auth'` in `devboard.middleware` is no longer needed and is ignored.
  - **local**: no authentication at all, one **global** set of lists (no user); live updates on a public
    channel (`devboard.local_channel`); no bell / push / logout in the UI. For a board on your own machine.
- Setting `app.show_dashboard_tab` to show/hide the slide-out DASHBOARD tab.

### Changed
- Default `devboard.middleware` is now `['web']`.

## [0.13.0] - 2026-08-19

### Added
- **The board notifies you itself** (Laravel Notifications) when the agent closes a task (`--done`) or asks
  a question (`--ask`), on three switchable channels (`app` settings `notify_in_app` / `notify_webpush` /
  `notify_mail`; the events follow the existing `notify_on_done` / `notify_on_question`):
  - **In-app bell 🔔** next to the list switcher (`NotificationBell`, database channel, live via the same
    private broadcast channel): unread badge, list, click → opens the task (switching list if needed),
    «mark all as read».
  - **Web Push** on the user's devices via `laravel-notification-channels/webpush` (new dependency): service
    worker served at `/devboard-sw.js`, subscription endpoints `POST/DELETE /devboard/push-subscriptions`,
    «Enable Web Push on this device» + «Send me a test notification» in `/settings`. Needs VAPID keys
    (`php artisan webpush:vapid`) and the `HasPushSubscriptions` trait on the user model.
  - **Mail** (`toMail`) when a mailer is configured.
- Deep links `?list=ID&open=ID` (middleware `OpenFromLink`) open a task from a notification.
- Idempotent migration creating `notifications` and `push_subscriptions` when the host app lacks them.
- `Alle80\Devboard\Support\Notify`, notifications `TodoCompleted`, `QuestionAsked`, `TestNotification`.

## [0.12.2] - 2026-08-19

### Changed
- The **«+» between rows** now creates the task *at that position* (making room) and opens the modal in
  title editing, like the «New task» button — instead of the inline title form (`createNew(?int $position)`).

## [0.12.0] - 2026-08-19

### Added
- **Agent skills per task**: a catalogue of the skills the coding agent has available (imported with
  `devboard:skills-import` from a JSON list of `{name, description, source}` — file or stdin — into
  `config('devboard.skills_file')`, default `storage/app/devboard/skills.json`) is shown in the modal, under
  the Task note, as a **🧩 accordion of checkboxes**; the chosen ones are saved in `todos.skills` (JSON) and
  `devboard:check` prints `🧩 skills to activate for this task: …` so the agent invokes them. Read-only on
  completed items. Dedicated migration for existing installs. `Alle80\Devboard\Support\Skills`.

## [0.11.0] - 2026-08-19

### Added
- **⚡ Optimization** settings group (`OptimizationSettings`, group `optimization`) — switches that make the
  agent spend fewer tokens, printed by `devboard:check` as `⚡ optimization: …`:
  `compact_check` (default on: action calls `--take/--done/--ask/--progress` print only the result line,
  no settings/listing), `terse_agent` (prints `TERSE MODE ON` + the rules the agent must follow: no chat
  prose, batched commands, targeted reads, short commits/comments), `context_max_chars` (trims previous
  context in the command output; 0 = unlimited), `progress_piggyback` (progress updates only together with
  other commands), `token_report` (report tokens on close). Settings migration for existing installs.

## [0.10.0] - 2026-08-19

### Added
- **Per-todo statistics**: agent **working time** and **tokens**. Every 🔧 interval is timed automatically
  (from `--take` to `--done`/`--ask`/a user stop; time spent waiting for answers is not counted) into
  `todos.work_seconds` (+ `working_since` for the open interval). Tokens are reported by the agent with
  the new `devboard:check --tokens-in=N --tokens-out=N` options (with `--take`/`--done`/`--ask`;
  cumulative per todo, `todos.tokens_in` / `tokens_out`). Partially completed items keep their stats.
- The modal shows a **📊 Stats** line (⏱ time — live while working — and 🪙 tokens in/out);
  `devboard:check` prints `⏱ working since … (Xm this interval)` on working items and `📊 …` on
  completed ones / when closing. Dedicated migration for existing installs.
- `Todo::workSeconds()`, `hasStats()`, `statsLine()`, `formatDuration()`, `formatTokens()`.

## [0.9.3] - 2026-08-19

### Fixed
- The **progress percentage** was never visible in practice: `devboard:check --take=ID` left `progress`
  at `null` unless `--progress` was passed, so a working todo showed the spinning icon but no `N%`.
  Now `--take` always shows a percentage: the given `--progress`, else the current value, else **0%**.
  Re-running `--take=ID --progress=N` updates it (live via Reverb); `--done` still clears it.
- The progress bar has a faint **track** and a minimum width, so 0% is visible as an empty bar.

### Changed
- `devboard:check` prints `[N%]` after the title of a working todo and `— N%` when taking in charge.

## [0.9.2] - 2026-08-19

### Fixed
- The multitasking **warning** in `/settings` now shows/hides instantly when the mode changes
  (Alpine `x-show`), instead of waiting for a server re-render.

## [0.9.1] - 2026-08-19

### Added
- Setting **`task_mode`** (`agent` group): `ordered` = one task at a time in list order (default),
  `multitasking` = the agent may take several 🟢 tasks at once if independent. Shown in the settings
  summary printed by `devboard:check`, with an inline warning in `/settings` for multitasking.

## [0.9.0] - 2026-08-19

### Added
- **Animated icon** on the working todo: the working state badge (gear) spins continuously.
- **Progress percentage**: `devboard:check --take=ID --progress=N` (0–100) shows `N%` next to the
  title and a thin progress bar under the row; `--done` clears it. New `todos.progress` column
  (dedicated migration for existing installs).

## [0.8.1] - 2026-08-19

### Changed
- In the modal, the editable **title** is the first field of the body (above "Task"), no longer in the
  header; the header keeps the theme icon, the state badge + commands and the close button.

## [0.8.0] - 2026-08-19

### Added
- **Unseen results**: when the agent completes a todo (`devboard:check --done`), the row stays
  highlighted (pulsing accent outline + "result" badge) until the user opens it; opening clears it
  (live too). New `todos.result_seen` column (dedicated migration for existing installs).

## [0.7.3] - 2026-08-19

### Fixed
- The **New task** button still failed to open the modal when the list already had items: the modal
  lacked a stable `wire:key`, so it was re-created (losing its open state) when the list re-rendered
  after adding the new row. Added the key.

## [0.7.2] - 2026-08-19

### Changed
- The markdown editor **textarea auto-resizes** to its content (no manual dragging).
- The markdown **editor (toolbar) is now on sub-tasks too** (add and edit), not only the Task field.

## [0.7.1] - 2026-08-19

### Fixed
- The **New task** button created the task but the modal stayed closed: the list created the todo and
  then dispatched `open-ingredients` to the child modal, and that server-side dispatch was lost when
  the list re-rendered. The modal now creates-and-opens the task itself via a client dispatch
  (`open-new-task`), so it opens reliably.

## [0.7.0] - 2026-08-19

### Added
- **Markdown** in the **Task** description and in **sub-tasks**: an editor toolbar
  (`<x-devboard::md-editor>` — bold, italic, code, code block, list, quote, link, table, separator)
  and **safe rendering** — GitHub-flavoured (tables, task lists, autolinks), with raw HTML stripped and
  unsafe links blocked, via `league/commonmark` (`Alle80\Devboard\Support\Markdown`). The agent's
  comment is rendered as Markdown too.

### Added (dependency)
- `league/commonmark ^2.4`.

## [0.6.3] - 2026-08-19

### Changed
- **Row icons** are now the SVG icon set with tooltips (a state badge coloured per state, plus
  edit / archive / restore / resume / delete), replacing the emoji.
- Removed the "done" **stamp** from completed rows.
- Removed the oversized **corner theme mascot** (the theme icon stays in the switcher and favicon).

## [0.6.2] - 2026-08-19

### Fixed
- The modal title bar printed the raw `('devboard::livewire.partials.modal-actions')` string instead of
  rendering the state badge + commands (a malformed `@include` insertion in 0.6.0).

## [0.6.1] - 2026-08-19

### Fixed
- **Live updates could silently never start.** Echo was loaded with a dynamic `import()`, which resolves
  *after* Livewire wires its `echo-private` listeners (a race that reliably lost on slower / mobile
  connections): `window.Echo` wasn't set yet, so the private channel was never subscribed and no state
  changes arrived. Echo is now imported synchronously (no-op without a broadcaster key), so the
  subscription happens before Livewire initialises.

## [0.6.0] - 2026-08-19

### Added
- Reusable inline-SVG **icon set** (`<x-devboard::icon name="…">`) in the logo (slate) line style.

### Changed
- **Modal title bar** now carries a coloured **state badge** (waiting / open / working / question / done)
  and the item **commands** — open-to-work (or resume, if done), archive, delete — as SVG icons.
- The **New task** button now creates the task and opens its modal straight in title editing; an
  untitled, untouched task is discarded on close.
- The free-text description field is relabelled **Task** across all styles.

## [0.5.1] - 2026-08-19

### Fixed
- `app.tab_side` (added in 0.5.0) is now seeded by its own settings migration, so installs that had
  already run the initial settings migration get it on `php artisan migrate` (fresh installs were fine).

## [0.5.0] - 2026-08-19

### Added
- **Desktop dashboard**: a wider, roomier view of the board on a configurable route
  (`config('devboard.dashboard_route')`, default `/dashboard`) — more readable on large screens.
- **Slide-out board tab** (Laravel-debugbar style): a handle pinned to the right or left edge opens a
  **resizable** panel that shows the dashboard on every page (desktop only). Remembers open state and
  width, respects `prefers-reduced-motion`.
- **Setting `tab_side`** (right / left) in `/settings`, and config key `dashboard_route`.

## [0.4.0] - 2026-08-19

### Added
- **`devboard:watch`** — a portable monitor for a coding agent: watches the agent list and prints
  only the changes to react to (an item going _open to work_, answers to a paused question arriving,
  a stop being requested). One command replaces harness-specific monitors. `--interval`, `--list`,
  `--once`.
- **`AGENTS.md`** shipped with the package and publishable with `php artisan vendor:publish
  --tag=devboard-agents` — the full agent protocol (states, take-first, order, questions, stop, close),
  so "connect an agent" = launch it in the project directory + read `AGENTS.md` + one `devboard:watch`.

### Changed
- README rewritten in a scannable structure, with a **Connect a coding agent** section up front.

### Fixed
- Docs referenced the pre-rename config keys (`config('todolist.agent_list')`,
  `todolist.broadcast_channel`); corrected to `devboard.*`.

## [0.3.0] - 2026-08-19

### Added
- **Slate theme icon**: the built-in **Slate** theme now ships an original SVG mark
  (`public/images/slate/slate.svg`) — a terminal window with a green `>_` prompt, drawn in the
  theme's palette — wired as its `icon_img` (shown in the style switcher and as the corner motif).

## [0.2.0] - 2026-08-19

### Changed
- **Debranded the built-in theme**: the generic **Linux** theme (with the Tux mascot) is now a
  brand-neutral **Slate** theme. The theme slug `linux` becomes `slate`, the CSS class
  `.theme-linux` becomes `.theme-slate`, and `config('devboard.default_theme')` defaults to `slate`.
  The Tux image (`public/images/linux/tux.svg`) and its terminal-flavoured copy were removed.

### Upgrade notes
- If you referenced the built-in theme by slug (`/linux`, `default_theme`/`default_style` = `linux`,
  a `.theme-linux { … }` override, or `devboard:theme-export linux`), rename it to `slate`.
- Installed theme packs and any custom themes you registered are unaffected.

## [0.1.0] - 2026-08-19

First public release, extracted from the [laravel-dev](https://github.com/alle80/laravel-dev)
monorepo into a standalone, installable Composer package.

### Added
- **Core dev board** (Livewire 4): queue requests as todos and drive a coding agent through
  the states _open to work → working → done_, with questions, stop and resume.
- **Lists, sub-tasks, notes** scoped per user (`TodoList`, `IngredientModal`, `Checklist`).
- **Image attachments** (upload / camera / paste) with GD resizing (`ImageStore`) and optional
  **AI descriptions** for full-text search (Laravel AI SDK, any provider, no-op without keys).
- **Archive, state filters and free-text search** across titles, notes, comments, sub-tasks,
  questions and attachment descriptions.
- **Live updates** between devices via any Laravel broadcaster (e.g. Reverb): `TodoChanged`
  event on a private per-user channel, with console-vs-web source tracking and toasts.
- **Settings page** (`spatie/laravel-settings`): an `agent` group (how the assistant works) and
  an `app` group (board behaviour), read by the `devboard:check` command.
- **Theme system**: generic themes via CSS variables, a built-in **Linux** theme, and
  **installable theme packs** as zips (`ThemeStore`, `devboard:theme-import/-export`).
- **Console workflow**: `devboard:check` (alias `sviluppo:check`), `devboard:auto-archive`,
  `devboard:describe-images`, `devboard:theme-import`, `devboard:theme-export`.
- **Standalone front-end assets**: a package-owned Vite build producing precompiled
  `public/build/devboard.{css,js}` plus an Echo chunk, selectable via `<x-devboard::assets />`
  between `@vite` (bundled by the host app) and the precompiled files (`DEVBOARD_ASSETS=precompiled`);
  Echo configured at runtime from `config('devboard.echo')`.
- **Consolidated, idempotent migration** for all tables and settings defaults.
- English base language with an Italian translation.
- Test suite (orchestra/testbench, SQLite in-memory) and a GitHub Actions workflow.

### Notes
- Requires PHP 8.3+, Laravel 12 or 13, Livewire 4, Tailwind CSS 4 in the host app.
- The full pre-extraction history lives in the origin monorepo linked above.

[Unreleased]: https://github.com/alle80/agent-devboard/compare/v0.12.0...HEAD
[0.12.0]: https://github.com/alle80/agent-devboard/compare/v0.11.0...v0.12.0
[0.11.0]: https://github.com/alle80/agent-devboard/compare/v0.10.0...v0.11.0
[0.10.0]: https://github.com/alle80/agent-devboard/compare/v0.9.3...v0.10.0
[0.9.3]: https://github.com/alle80/agent-devboard/compare/v0.9.2...v0.9.3
[0.9.2]: https://github.com/alle80/agent-devboard/compare/v0.9.1...v0.9.2
[0.9.1]: https://github.com/alle80/agent-devboard/compare/v0.9.0...v0.9.1
[0.9.0]: https://github.com/alle80/agent-devboard/compare/v0.8.1...v0.9.0
[0.8.1]: https://github.com/alle80/agent-devboard/compare/v0.8.0...v0.8.1
[0.8.0]: https://github.com/alle80/agent-devboard/compare/v0.7.3...v0.8.0
[0.7.3]: https://github.com/alle80/agent-devboard/compare/v0.7.2...v0.7.3
[0.7.2]: https://github.com/alle80/agent-devboard/compare/v0.7.1...v0.7.2
[0.7.1]: https://github.com/alle80/agent-devboard/compare/v0.7.0...v0.7.1
[0.7.0]: https://github.com/alle80/agent-devboard/compare/v0.6.3...v0.7.0
[0.6.3]: https://github.com/alle80/agent-devboard/compare/v0.6.2...v0.6.3
[0.6.2]: https://github.com/alle80/agent-devboard/compare/v0.6.1...v0.6.2
[0.6.1]: https://github.com/alle80/agent-devboard/compare/v0.6.0...v0.6.1
[0.6.0]: https://github.com/alle80/agent-devboard/compare/v0.5.1...v0.6.0
[0.5.1]: https://github.com/alle80/agent-devboard/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/alle80/agent-devboard/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/alle80/agent-devboard/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/alle80/agent-devboard/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/alle80/agent-devboard/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/alle80/agent-devboard/releases/tag/v0.1.0
