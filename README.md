# alle80/agent-devboard

A **dev board for coding agents** on Laravel 12/13 + Livewire 4. You queue requests as todos; a
coding agent (Claude Code, …) takes them, asks questions, and closes them — driven from the app.

**Includes**

- Agent workflow: _open to work → working → done_, with questions, stop and resume
- Multiple lists per user · sub-tasks · notes
- Image attachments (upload / camera / paste) with optional AI descriptions for search
- Archive · state filters · free-text search
- Live updates between devices (any Laravel broadcaster, e.g. Reverb)
- A theme system (built-in **Slate** theme + installable zip packs) and a settings page
- English base language with an Italian translation

> Extracted from the original app at https://github.com/alle80/laravel-dev.

---

## Requirements

- PHP 8.3+ · Laravel 12 or 13 · Livewire 4 · Tailwind CSS 4 (Vite) in the host app
- `ext-gd` (image resizing) · `spatie/laravel-settings` (installed automatically)
- Optional: `laravel/ai` (AI image descriptions) · a broadcaster like `laravel/reverb` (live updates)

## Install

```bash
composer require alle80/agent-devboard
php artisan migrate                                  # tables + settings defaults (idempotent)
php artisan storage:link                             # attachments live on the "public" disk
php artisan vendor:publish --tag=devboard-assets     # precompiled build & theme assets
```

Routes register automatically — `/` (default theme), `/{theme}`, `/settings` — behind `web` + `auth`.
**The package needs an authenticated user** (lists belong to users), so plug it into your app's login.

Then wire up the front-end assets (below) and you're ready to [connect an agent](#connect-a-coding-agent).

---

## Connect a coding agent

One list — `config('devboard.agent_list')`, default **`dev`** — is the request channel between you
and the agent. You add todos; the agent works them. Setup is meant to be minimal:

**1 — Launch the agent inside the project directory** (Claude Code, or any agent that reads a project
`AGENTS.md`).

**2 — Give it the workflow** (once):

```bash
php artisan vendor:publish --tag=devboard-agents     # drops AGENTS.md in the project root
```

Agents read `AGENTS.md` automatically; it describes the whole protocol (states, order, questions, stop).

**3 — Start the monitor** (one command):

```bash
php artisan devboard:watch      # prints ONLY the changes the agent must react to
```

`watch` polls the list and emits a line when something needs the agent — an item goes **open to
work**, the **answers** to a paused question arrive, or a **stop** is requested. The agent then reads
and acts with `devboard:check`.

### The state of each row

| Dot | State | Meaning |
|-----|-------|---------|
| ⚪ | waiting | not ready — the agent leaves it alone |
| 🟢 | open to work | the user released it; the agent may take it (top-down = priority) |
| 🔧 | working | the agent took it (its first action, so you see it in real time) |
| ❓ | question | the agent asked something; paused until you answer in the app |
| ⏹ | stop | you stopped it; the agent drops it immediately |
| ✔ | done | closed, with the agent's comment |

### The agent's commands (`devboard:check`)

```bash
php artisan devboard:check                 # what to work on (🟢/🔧), in order; --all for everything
php artisan devboard:check --take=ID       # take it in charge  → 🔧 (shows 0%)
php artisan devboard:check --take=ID --progress=60   # update the progress % on the row (re-run as you go)
php artisan devboard:check --ask=ID --q="…" --q="…"   # ask, pausing it → ❓
php artisan devboard:check --done=ID --comment="…"    # close it, with a note back to the user → ✔
php artisan devboard:check --done=ID --comment="…" --tokens-in=N --tokens-out=N   # …and record the tokens spent
```

**Configuration vs settings**: inventory, defaults and the backlog of future ones in
[`docs/config-and-settings.md`](docs/config-and-settings.md).

**Compatibility**: any CLI coding agent — Claude Code, OpenAI Codex CLI, Gemini CLI, Aider, Cursor, … — the
contract is just the `devboard:check`/`devboard:watch` commands plus `AGENTS.md` (Codex/others), `CLAUDE.md`
(Claude) or `GEMINI.md` (Gemini) carrying the same rules; `DEVBOARD_AGENT_NAME` sets how the UI calls it.

**Plan mode**: create a list «as a plan» from a prompt: the AI SDK splits the goal into chained tasks
(`depends_on_id`); completing one opens the next. Needs `laravel/ai` + a provider key (otherwise a single
«Build the plan» task is created for the agent).

**Speech to text**: a microphone on every text field. With `laravel/ai` installed and a transcription provider
configured (`AI_PROVIDER`/keys, `ai.default_for_transcription`) the clip is transcribed server-side (best
quality); otherwise the browser's Web Speech API is used. Setting `speech_mode` (auto/server/browser).

**Agent context** (`/context`): import your instructions file (e.g. CLAUDE.md) with
`php artisan devboard:context import --file=CLAUDE.md`; it becomes groups (`##`) and blocks you can switch on/off
(single, multi-select, whole group), edit and reorder; `php artisan devboard:context export` prints the enabled
context — write it back to the file from your host (see `scripts/sync-context.py` in the origin repo).

**Theme packs** are code-like content: only administrators can install them; SVG is refused, the CSS is sanitised
(no `@import`/external urls), packs are capped (5 MB/file, 20 MB, 200 files) and assets are served sandboxed.

**Administrators**: settings, agent context and theme packs are admin-only — `canManageDevboard(): bool` on your user
model, or `DEVBOARD_ADMIN_GATE=<ability>`, or `DEVBOARD_ADMINS="1,alice@example.com"`; by default only the first
registered user. Local mode from the UI needs `APP_ENV=local` or `DEVBOARD_ALLOW_LOCAL_FROM_UI=true`.

**Local mode safety**: `DEVBOARD_MODE=local` removes authentication from every board route — run it only on your
own machine and bind the web server to `127.0.0.1` (or a firewall-protected interface); a banner reminds it on every
page. Behind a reverse proxy keep `trustProxies` limited to the proxy's address.

**Modes**: `DEVBOARD_MODE=server` (default: login, lists per user; restrict access with
`canAccessDevboard(): bool` on your user model or `DEVBOARD_ACCESS_GATE=<ability>`) or `DEVBOARD_MODE=local`
(no authentication, one global set of lists — for your own machine only). Also switchable in `/settings`.

**Notifications from the board**: on `--done` / `--ask` the list owner is notified by the app — in-app bell 🔔,
Web Push (add `NotificationChannels\WebPush\HasPushSubscriptions` to your user model and run
`php artisan webpush:vapid`; users enable their devices in `/settings`) and mail — channels switchable in
`/settings`. Tables `notifications` / `push_subscriptions` are created by the package migration if missing.

**Skills**: `php artisan devboard:skills-import --file=skills.json` (or JSON on stdin) loads the catalogue
of the agent's skills; the modal shows them under the Task note as a 🧩 accordion and the chosen ones are
printed by `devboard:check` for that task.

**Agents status** (`/agents`): plan + usage windows (5h / 7d …) of your coding agents with used/remaining %,
reset countdown and alert levels; feed it with `php artisan devboard:agent-status-import` (JSON snapshot) —
see `scripts/agent-status.py` in the origin repo for Claude Code (credentials never leave the host).

**Statistics page** (`/stats`): completed tasks per list with working time, tokens and cost (set the price
per million tokens in Settings), per-day bars, overview of all lists.

**Statistics**: every 🔧 interval is timed automatically (working time per todo, waiting for answers
excluded); tokens are whatever the agent reports with `--tokens-in/--tokens-out` (cumulative, also on
`--take`/`--ask`). The modal shows them as a **📊 Stats** line.

`devboard:check` also prints the behaviour settings from `/settings` (commit policy, autonomy,
notifications, …) that the agent is expected to follow, plus the **⚡ Optimization** switches (compact
command output, terse mode, context trimming, …) that cut the tokens an agent session spends. A closed item can be **resumed** into a new
linked one, carrying its context.

---

## Front-end assets

Pick one mode.

**A — Precompiled (zero build).** Use the CSS/JS shipped by the package:

```bash
# .env  →  DEVBOARD_ASSETS=precompiled   (or 'assets' => 'precompiled' in config/devboard.php)
php artisan vendor:publish --tag=devboard-assets     # public/vendor/devboard/{build,images}
```

`<x-devboard::assets />` then links `public/vendor/devboard/build/devboard.{css,js}` (Tailwind
utilities, the theme system, SortableJS, and Laravel Echo when a Reverb/Pusher key is set). No npm.

**B — Bundled by your app (default, `assets = vite`).** Import the package sources in your Vite build.
Tailwind 4 doesn't scan `vendor/`, so add an `@source`:

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

In both modes the Echo client is configured at runtime from `config('devboard.echo')` (`VITE_REVERB_*`
/ `REVERB_*`); an empty key opens no WebSocket. Theme fonts load from `config('devboard.fonts_url')`
(bunny.net by default; set `''` to self-host). To rebuild the precompiled files after editing package
sources: `cd vendor/alle80/agent-devboard && npm install && npm run build`.

## Configuration

```bash
php artisan vendor:publish --tag=devboard-config     # config/devboard.php
php artisan vendor:publish --tag=devboard-views      # override the Blade views
php artisan vendor:publish --tag=devboard-lang       # translations (en, it)
php artisan vendor:publish --tag=devboard-agents     # AGENTS.md (agent workflow)
```

`config/devboard.php` covers the route prefix and middleware, the user model, the attachments disk,
the default theme, and the **agent list** name (`agent_list`).

## Themes

The package ships a generic theme system (shared views + CSS variables per `.theme-<slug>`) with the
built-in **Slate** theme. Add more with `config('devboard.themes')` or
`Alle80\Devboard\Themes::registerTheme($slug, [...])` plus a `.theme-<slug> { --tl-… }` CSS block.
Fully custom styles (own components/views) plug in via `Themes::registerStyle()` /
`Themes::registerSkin()`.

**Installable packs (zip):** a `theme.json` + `theme.css` (+ optional `images/`). Install from
**/settings → 🎨 Themes** or `php artisan devboard:theme-import pack.zip`; packs live in
`storage/app/themes/<slug>`. Export any theme as a starting point:
`php artisan devboard:theme-export slate --css-from=resources/css/app.css`. A sample pack (`pollon`)
is in `resources/themes/`.

## Live updates

Every change to a todo / sub-task / question / attachment broadcasts
`Alle80\Devboard\Events\TodoChanged` on the private channel `App.Models.User.{id}`. With no broadcaster
configured nothing happens (failures are logged, never raised).

## Development

```bash
cd packages/devboard && composer update && vendor/bin/phpunit
```

The suite (orchestra/testbench, in-memory sqlite) covers migrations, per-user scoping, the Livewire
components, `devboard:check` and `devboard:watch`, the theme registry and zip packs, translation parity
and the live event. GitHub Actions runs it on PHP 8.3 / 8.4 on every push touching the package.

## License

MIT — see [LICENSE](LICENSE).
