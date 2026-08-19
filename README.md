# alle80/agent-devboard

A **dev board for coding agents** on Laravel 12/13 + Livewire 4: you queue requests as todos, the agent (Claude Code, …) takes them, asks questions, closes them — plus everything a personal todo app needs: multiple lists per user, sub-tasks, notes,
image attachments (upload / camera / paste, with optional AI descriptions for the search), archive,
filters, free-text search, live updates between devices (any Laravel broadcaster, e.g. Reverb),
themes, a settings page, and an **agent-friendly workflow** to drive a coding agent (Claude Code, …)
from the app itself.

> Extracted from the original app at https://github.com/alle80/laravel-dev (phase 1: backend,
> Livewire components, the generic theme system with the built-in **Slate** theme, English base
> language with an Italian translation).

## Requirements

- PHP 8.3+, Laravel 12 or 13, Livewire 4, Tailwind CSS 4 (Vite) in the host app
- `ext-gd` (image resizing), `spatie/laravel-settings` (installed automatically)
- Optional: `laravel/ai` (AI image descriptions), a broadcaster such as `laravel/reverb` (live updates)

## Installation

```bash
composer require alle80/agent-devboard
php artisan migrate                                  # tables + settings defaults (idempotent)
php artisan storage:link                             # attachments live on the "public" disk by default
php artisan vendor:publish --tag=devboard-assets     # precompiled build & theme assets
```

### Front-end assets: two modes

**A. Precompiled (zero build)** — use the CSS/JS built by the package: set `DEVBOARD_ASSETS=precompiled`
in `.env` (or `'assets' => 'precompiled'` in `config/devboard.php`) and publish the files:

```bash
php artisan vendor:publish --tag=devboard-assets     # public/vendor/devboard/{build,images}
```

The package layouts print `<x-devboard::assets />`, which links `public/vendor/devboard/build/devboard.css`
+ `devboard.js` (Tailwind utilities used by the package views, the theme system, SortableJS and, when a
Reverb/Pusher key is configured, Laravel Echo). Nothing to install with npm.

**B. Bundled by your app (default, `assets = vite`)** — import the package sources in your own Vite
build. Tailwind 4 does not scan `vendor/`, so add an `@source` for the package views:

```css
/* resources/css/app.css */
@import 'tailwindcss';
@source '../../vendor/alle80/agent-devboard/resources/views/**/*.blade.php';
@import '../../vendor/alle80/agent-devboard/resources/css/devboard.css';
```

```js
// resources/js/app.js
import '../../vendor/alle80/agent-devboard/resources/js/devboard.js';   // SortableJS + Echo (optional)
```

```bash
npm i sortablejs laravel-echo pusher-js && npm run build
```

`<x-devboard::assets />` then emits `@vite(config('devboard.vite_entries'))` (default
`resources/css/app.css` + `resources/js/app.js`). In both modes the Echo client is configured at runtime
from `config('devboard.echo')` (`VITE_REVERB_*` / `REVERB_*` env); with an empty key no WebSocket is opened.
Web fonts of the themes are loaded from `config('devboard.fonts_url')` (bunny.net by default; set it to
`''` to self-host them in your CSS).

To rebuild the precompiled files after changing the package sources: `cd vendor/alle80/agent-devboard && npm install && npm run build`.

Routes are registered automatically (`/` → default theme, `/{theme}`, `/settings`) behind the
`web` + `auth` middleware — the package needs an authenticated user (lists belong to users).
Publish the config to change prefix, middleware, user model, disk, default theme, agent list name:

```bash
php artisan vendor:publish --tag=devboard-config     # config/devboard.php
php artisan vendor:publish --tag=devboard-views      # override the Blade views
php artisan vendor:publish --tag=devboard-lang       # translations (en, it)
```

## Themes

The package ships the generic theme system (shared views + CSS variables per `.theme-<slug>`)
with the built-in **slate** theme. Add more generic themes in `config('devboard.themes')` or with
`Alle80\Devboard\Themes::registerTheme($slug, [...])` plus a `.theme-<slug> { --tl-… }` CSS block.
Fully custom styles (own components/views) can be plugged in with `Themes::registerStyle()` and
`Themes::registerSkin()` — see the original app for six examples (manga, Jacovitti, C64, Star Wars,
Zerocalcare, Dragon Ball).

### Installable theme packs (zip)

A pack is a zip with `theme.json` (slug, label, icon, `fonts` from bunny.net, texts, `deco` emoji,
optional `icon_img`, version, author), `theme.css` (`.theme-<slug> { --tl-… }` variables and any rule
scoped to `.theme-<slug>`) and optional `images/`. Install it from **/settings → 🎨 Themes** or with
`php artisan devboard:theme-import pack.zip`; packs live in `storage/app/themes/<slug>` and their
files are served by `/devboard-themes/{slug}/{path}`. Uninstall from the same page or with
`devboard:theme-import --uninstall=<slug>`. Export any theme as a starting point:
`php artisan devboard:theme-export slate` (for themes defined in code add `--css-from=resources/css/app.css`
to extract their CSS). A sample pack (`pollon`) is in `resources/themes/`.

## Agent workflow (`devboard:check`)

One list (config `todolist.agent_list`, default `dev`) is the request channel between the user and
a coding agent. Each row has a state dot: ⚪ waiting → 🟢 open to work (user) → 🔧 working (agent,
`--take=ID`) → ✔ done (`--done=ID --comment="…"`); the agent can ask questions (`--ask=ID --q="…"`,
state ❓) that the user answers in the modal; a 🔧 item can be stopped by the user; a closed item can
be **resumed** into a new linked one. `php artisan devboard:check` prints what the agent may work on,
in list order, plus the settings from `/settings` that describe how the agent should behave.

## Live updates

Every change of a todo / sub-task / question / attachment broadcasts `Alle80\Devboard\Events\TodoChanged`
on the private channel `App.Models.User.{id}` (config `todolist.broadcast_channel`). If no broadcaster
is configured nothing happens (failures are logged, never raised).

## Development

```bash
cd packages/devboard && composer update && vendor/bin/phpunit
```

The suite (orchestra/testbench, sqlite in memory) covers migrations, per-user scoping, the Livewire
components, `devboard:check`, the theme registry and zip packs, translations parity and the live event.
GitHub Actions runs it on PHP 8.3/8.4 at every push touching the package.

## License

MIT.
