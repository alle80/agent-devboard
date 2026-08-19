# Changelog

All notable changes to `alle80/agent-devboard` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

$1
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

[Unreleased]: https://github.com/alle80/agent-devboard/compare/v0.8.1...HEAD
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
