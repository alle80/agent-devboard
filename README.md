<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="public/images/brand/lockup-horizontal-dark.svg">
    <img src="public/images/brand/lockup-horizontal.svg" width="380" alt="Griglia">
  </picture>
</p>

# alle80/griglia

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
composer require alle80/griglia -W                   # -W: see the note below
php artisan migrate                                  # tables + settings defaults (idempotent)
php artisan storage:link                             # attachments live on the "public" disk
```

That's it: the board ships its own precompiled CSS/JS (published by composer with Laravel's
`laravel-assets` tag), so there is nothing to build. Prefer to bundle it in your own Vite build? See
[Front-end assets](#front-end-assets).

> **Why `-W`**: Web Push pulls `web-token/jwt-library`, which caps `brick/math` at `^0.17`, while a brand
> new Laravel app ships `0.18`. `-W` lets composer downgrade that one transitive dependency; without it the
> install stops with a conflict. Existing apps usually need nothing.

Routes register automatically — `/` (default theme), `/{theme}`, `/plans/new`, `/settings`, `/context`, `/stats`,
`/agents` — behind `web` plus the package's access middleware (login in server mode, none in local mode;
see [Access, administrators and modes](#access-administrators-and-modes)).
**In server mode the package needs an authenticated user** (lists belong to users), so plug it into your
app's login — or use local mode on your own machine.

Then wire up the front-end assets (below) and you're ready to [connect an agent](#connect-a-coding-agent).

---

## Connect a coding agent

One list — `config('griglia.agent_list')`, default **`dev`** — is the request channel between you
and the agent. You add todos; the agent works them. Setup is meant to be minimal:

**1 — Launch the agent inside the project directory** (Claude Code, or any agent that reads a project
`AGENTS.md`).

**2 — Give it the workflow** (once):

```bash
php artisan vendor:publish --tag=griglia-agents     # drops AGENTS.md in the project root
```

Agents read `AGENTS.md` automatically; it describes the whole protocol (states, order, questions, stop).

**3 — Start the monitor** (one command):

```bash
php artisan griglia:watch      # prints ONLY the changes the agent must react to
```

`watch` polls the list and emits a line when something needs the agent — an item goes **open to
work**, the **answers** to a paused question arrive, or a **stop** is requested. The agent then reads
and acts with `griglia:check`.

### The state of each row

The badge on each row uses the package's SVG icon set (no emoji in the UI):

| Icon | State | Meaning |
|:----:|-------|---------|
| <img src="docs/images/state-waiting.svg" width="18" alt="waiting"> | waiting | not ready — the agent leaves it alone |
| <img src="docs/images/state-open.svg" width="18" alt="open to work"> | open to work | the user released it; the agent may take it (top-down = priority) |
| <img src="docs/images/state-working.svg" width="18" alt="working"> | working | the agent took it (its first action, so you see it in real time) — with progress % and phase |
| <img src="docs/images/state-question.svg" width="18" alt="question"> | question | the agent asked something; paused until you answer in the app |
| <img src="docs/images/state-stop.svg" width="18" alt="stop"> | stop | you stopped it (tap on the working badge); the agent drops it immediately |
| <img src="docs/images/state-done.svg" width="18" alt="done"> | done | closed, with the agent's comment |

### The agent's commands (`griglia:check`)

```bash
php artisan griglia:check                # what to work on (open/working), in order; --all for everything
php artisan griglia:check --take=ID      # take it in charge → working (starts at 0%)
php artisan griglia:check --take=ID --progress=60 --phase="writing code"   # update % and phase as you go
php artisan griglia:check --ask=ID --q="…" --q="…"                         # ask, pausing it → question
php artisan griglia:check --done=ID --comment="…"                          # close it, with a note back → done
php artisan griglia:check --done=ID --comment="…" --tokens-in=N --tokens-out=N  # …recording the tokens spent
```

`griglia:check` also prints, at the top, the **behaviour settings** from `/settings` that the agent must
follow (commit policy, autonomy, notifications, verification, git flow, task order, …) and the
**Optimization** switches that cut the tokens a session spends (compact output, terse mode, context
trimming). A closed item can be **resumed** into a new linked one, carrying its context.

### Compatibility

Any CLI coding agent works — Claude Code, OpenAI Codex CLI, Gemini CLI, Aider, Cursor, … The contract is
deliberately small:

- the two commands: `griglia:check` (read/act) and `griglia:watch` (react);
- one instructions file with the same rules: `AGENTS.md` (Codex and most agents), `CLAUDE.md` (Claude Code)
  or `GEMINI.md` (Gemini);
- `GRIGLIA_AGENT_NAME` sets how the UI calls the agent.

With more than one agent, declare them and pick one per list or per task:

```bash
# .env
GRIGLIA_AGENTS="claude:Claude Code,codex:Codex CLI"
```

```bash
php artisan griglia:check --agent=codex     # each agent sees only its own tasks
```

### Plan mode

**New plan…** in the lists menu opens `/plans/new`, a page with room to write: the goal (with dictation),
an optional name and, with several agents, the one for this plan. The AI SDK splits the goal into chained
tasks (`depends_on_id`) and completing one opens the next; the chain follows the visible order (drag &
drop). The Plan bar links `/plans/{list}/edit`, where the goal can be changed and the tasks nobody has
started can be rebuilt.
Plans can be started, paused and resumed from the list bar.

- Needs `laravel/ai` + a provider key.
- Without AI configured, a single «Build the plan» task is created and the agent does the splitting.

### Speech to text

A microphone on every text field:

- with `laravel/ai` + a transcription provider (`AI_PROVIDER`/keys, `ai.default_for_transcription`) the clip
  is transcribed **server-side** (best quality);
- otherwise the browser's **Web Speech API** is used;
- setting `speech_mode`: auto / server / browser.

### Agent context (`/context`)

Turn your instructions file into switchable blocks — groups (`##`) and blocks you can toggle (single,
multi-select, whole group), edit as Markdown and reorder:

```bash
php artisan griglia:context import --file=CLAUDE.md   # file → groups/blocks
php artisan griglia:context export                    # enabled blocks → stdout
```

Write the export back to your instruction files from the host with the shipped `scripts/sync-context.py`
(`vendor:publish --tag=griglia-scripts`), which also keeps the originals and restores them when the sync is
switched off.

### Access, administrators and modes

- **Modes**: `GRIGLIA_MODE=server` (default — login required, lists per user) or `GRIGLIA_MODE=local`
  (no authentication, one global set of lists: **your own machine only** — bind to `127.0.0.1`; a banner
  reminds it on every page). Also switchable in `/settings`; enabling local from the UI needs
  `APP_ENV=local` or `GRIGLIA_ALLOW_LOCAL_FROM_UI=true`.
- **Access** (server mode): restrict who uses the board with `canAccessDevboard(): bool` on your user model
  or `GRIGLIA_ACCESS_GATE=<ability>`.
- **Administrators**: settings, agent context and theme packs are admin-only — `canManageDevboard(): bool`,
  or `GRIGLIA_ADMIN_GATE=<ability>`, or `GRIGLIA_ADMINS="1,alice@example.com"`; by default only the first
  registered user.
- **Theme packs** are code-like content: admin-only install, SVG refused, CSS sanitised (no
  `@import`/external urls), size caps (5 MB/file, 20 MB, 200 files), assets served sandboxed.

### Notifications from the board

On `--done` / `--ask` the list owner is notified by the app itself. Channels (each switchable in
`/settings`):

- **in-app bell** (database notifications);
- **Web Push** on the user's devices;
- **mail**.

```bash
php artisan webpush:vapid    # generate the VAPID keys (Web Push)
```

Add `NotificationChannels\WebPush\HasPushSubscriptions` to your user model; users enable each device in
`/settings`, where a diagnostics panel helps when pushes do not show up. The `notifications` /
`push_subscriptions` tables are created by the package migration if missing.

### Skills

Load the catalogue of the agent's skills; the task modal shows them as an accordion under the note and
`griglia:check` prints the chosen ones for the task the agent is working:

```bash
php artisan griglia:skills-import --file=skills.json   # or JSON on stdin
```

### Statistics and agents status

- **`/stats`** — completed tasks per list (or all lists / all plans) with working time, tokens and **cost**
  (price per million tokens set in Settings), per-day bars, overview of every list. Deleting a list or a
  task is a **soft delete**: statistics survive; purge for real with `php artisan griglia:empty-trash`.
- **How it is measured** — every *working* interval is timed automatically (waiting for answers excluded);
  tokens are whatever the agent reports with `--tokens-in/--tokens-out`. The modal shows a **Stats** line
  per task.
- **`/agents`** — plan + usage windows (5h / 7d, …) of your coding agents: used/remaining %, reset
  countdown, alert levels. Feed it with a JSON snapshot:

```bash
php artisan griglia:agent-status-import --file=snapshot.json
```

(the shipped `scripts/agent-status.py` does it for Claude Code — credentials never leave the host).

### Configuration vs settings

Inventory, defaults and the backlog of future options: the generated [`docs/reference/config.md`](docs/reference/config.md) and [`docs/reference/settings.md`](docs/reference/settings.md).

---

## Front-end assets

Pick one mode.

**A — Precompiled (default, zero build).** The CSS/JS built by the package. They are published under
Laravel's own `laravel-assets` tag, so `composer update` keeps them fresh; by hand:

```bash
php artisan vendor:publish --tag=griglia-assets --force    # public/vendor/griglia/{build,images}
```

`<x-griglia::assets />` then links `public/vendor/griglia/build/griglia.{css,js}` (Tailwind
utilities, the theme system, SortableJS, and Laravel Echo when a Reverb/Pusher key is set). No npm.

**B — Bundled by your app (`GRIGLIA_ASSETS=vite`).** Import the package sources in your Vite build.
Tailwind 4 doesn't scan `vendor/`, so add an `@source`:

```css
/* resources/css/app.css */
@import 'tailwindcss';
@source '../../vendor/alle80/griglia/resources/views/**/*.blade.php';
@import '../../vendor/alle80/griglia/resources/css/griglia.css';
```

```js
// resources/js/app.js
import '../../vendor/alle80/griglia/resources/js/griglia.js';   // SortableJS + Echo (optional)
```

```bash
npm i sortablejs laravel-echo pusher-js && npm run build
```

In both modes the Echo client is configured at runtime from `config('griglia.echo')` (`VITE_REVERB_*`
/ `REVERB_*`); an empty key opens no WebSocket. Theme fonts load from `config('griglia.fonts_url')`
(bunny.net by default; set `''` to self-host). To rebuild the precompiled files after editing package
sources: `cd vendor/alle80/griglia && npm install && npm run build`.

## Configuration

```bash
php artisan vendor:publish --tag=griglia-config     # config/griglia.php
php artisan vendor:publish --tag=griglia-views      # override the Blade views
php artisan vendor:publish --tag=griglia-lang       # translations (en, it)
php artisan vendor:publish --tag=griglia-scripts    # host helpers → scripts/ (skills, context, tokens, status)
php artisan vendor:publish --tag=griglia-agents     # AGENTS.md (agent workflow)
```

`config/griglia.php` covers the route prefix and middleware, the user model, the attachments disk,
the default theme, and the **agent list** name (`agent_list`).

## Themes

The package ships a generic theme system (shared views + CSS variables per `.theme-<slug>`) with the
built-in **Slate** theme. Add more with `config('griglia.themes')` or
`Alle80\Griglia\Themes::registerTheme($slug, [...])` plus a `.theme-<slug> { --tl-… }` CSS block.
Fully custom styles (own components/views) plug in via `Themes::registerStyle()` /
`Themes::registerSkin()`.

**Installable packs (zip):** a `theme.json` + `theme.css` (+ optional `images/`). Install from
**/settings → Themes** or `php artisan griglia:theme-import pack.zip`; packs live in
`storage/app/themes/<slug>`. Export any theme as a starting point:
`php artisan griglia:theme-export slate --css-from=resources/css/app.css`. A sample pack (`pollon`)
is in `resources/themes/`.

## Live updates

Every change to a todo / sub-task / question / attachment broadcasts
`Alle80\Griglia\Events\TodoChanged` on the private channel `App.Models.User.{id}`. With no broadcaster
configured nothing happens (failures are logged, never raised).

## Development

```bash
cd packages/griglia && composer update && vendor/bin/phpunit
```

The suite (orchestra/testbench, in-memory sqlite) covers migrations, per-user scoping, the Livewire
components, `griglia:check` and `griglia:watch`, the theme registry and zip packs, translation parity
and the live event. GitHub Actions runs it on PHP 8.3 / 8.4 on every push touching the package.

## License

MIT — see [LICENSE](LICENSE).

## Documentation

Full docs: **<https://alle80.github.io/griglia/>** — sources in [`docs/`](docs/index.md) (MkDocs, Material
theme), published by GitHub Actions at every push. Build them yourself with
`php artisan griglia:docs-build` (needs `pip install mkdocs-material`, or `--docker`), preview with `--serve`.

## Security

See [`SECURITY.md`](SECURITY.md) for the security model, the hardening checklist and how to report a vulnerability.
