# Changelog

All notable changes to `alle80/agent-devboard` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/alle80/agent-devboard/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/alle80/agent-devboard/releases/tag/v0.1.0
