# Changelog

All notable changes to `alle80/griglia` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.47.1] - 2026-08-20

### Fixed
- `griglia:docs-generate` now always writes the settings page in the English base language with a neutral
  agent name, so `--check` does not depend on the locale (or the agent) of whoever runs it.

## [0.47.0] - 2026-08-20

### Added
- **`griglia:docs-generate`.** The reference pages of the documentation site are written from the code:
  every `griglia:*` command with its options, every key of `config/griglia.php` with its env variable and
  default, every setting of the three groups with label, type and help. `griglia:docs-build` runs it before
  building (`--no-generate` skips it) and `--check` fails when the committed pages are out of date, so the
  docs cannot drift from the package.
- The site's Reference section now carries those three generated pages plus the **changelog**, included
  straight from `CHANGELOG.md`. The hand-written inventory keeps only what does not exist yet (backlog).

## [0.46.0] - 2026-08-20

### Fixed
- **Server-side dictation no longer fails (or garbles the text) depending on the browser.** Browsers send
  the recording as `audio/webm;codecs=opus` (or `audio/ogg;codecs=opus`); the codec parameter made the
  provider receive the file named `audio.mp3` and answer «Audio file might be corrupted or unsupported»,
  so dictation was broken in Chrome and Firefox. The mime type is now normalised (and derived from the
  extension when the browser sends none).
- **The page language is the app locale**, not a hard-coded `it`. Beyond accessibility, browser-mode
  dictation reads it to choose the recognition language.

### Added
- **Vocabulary hint for the transcription.** A short prompt travels with the audio so names and jargon are
  transcribed properly — «con l'agente» instead of «con la gente». Translated with the locale, overridable
  with `GRIGLIA_SPEECH_PROMPT` / `config('griglia.speech_prompt')`, disabled with an empty string.

### Docs
- **Site structure.** The documentation is now organised in folders (getting started, board, agent,
  features, configuration, reference, operations, contributing) with new pages — quickstart, front-end
  assets, AI features, access & modes, artisan commands, events, upgrading, troubleshooting,
  contributing — and an `mkdocs.yml` ready to publish on GitHub Pages (`site_url`, theme overrides,
  tabbed/details extensions). `mkdocs build --strict` is green.

## [0.45.0] - 2026-08-20

### Changed
- **Settings show one group at a time on phones too, in a single column everywhere.** The section index
  born for the desktop now has a mobile counterpart: a scrollable strip of tabs above the panel, so the
  page opens on «How the agent works» instead of stacking every group into one long scroll. The
  newspaper columns introduced at xl are gone — settings are a single column at every width, with the
  control beside its label — and the container narrows accordingly (4xl from lg, 5xl from xl).

## [0.44.0] - 2026-08-20

### Changed
- **Task rows use the two-level layout everywhere.** The compact card born for phones — handle, number,
  checkbox and actions on the first line, title and badges at full width underneath — now applies at every
  width. On a wide screen the single-line row squeezed the title between the controls and the badges and
  left a hole in the middle; the row now shows long titles and every badge. Below 640px nothing changes
  beyond the roomier touch targets that were already there.

## [0.43.0] - 2026-08-20

### Changed
- **/settings reads better on desktop.** The two desktop columns are now newspaper columns
  (`xl:columns-2`): each column is read top to bottom, so related settings stay together — «Riepilogo
  serale» and «Ora del riepilogo» no longer end up in different columns as they did with the grid. And
  from `xl` every non-toggle control (selects, time and text inputs) sits under its label at the full
  column width, instead of squeezing beside it: long options such as «Branch per task + Pull Request» or
  «Task ordinati — uno alla volta, in ordine» are readable in full.

## [0.42.0] - 2026-08-20

### Fixed
- Responsive pass over the desktop work, measured at 1920, 1440, 1280, 1024 and 820 px (no horizontal
  overflow at any width — document width always equals the viewport):
  - **/settings**: the two-column layout now starts at `xl` instead of `lg`. At 1024 px the columns were
    ~340 px wide and every label wrapped after two words; and three columns at `2xl` were worse, so the
    page stays at two columns and widens to `90rem` instead. Selects get a minimum width and can take up
    to 65% of the row, so options like «Chiedi quando in dubbio» are readable.
  - **/stats**: the chart and the per-list overview are wrapped in an `<aside>` that is `display: contents`
    below `xl` (so they keep taking part in the grid) and a single cell from `xl` — this removes the empty
    gap that the implicit grid rows left between them. The stacked order on phones is preserved
    (chart → history → overview) with flex `order`.

### Changed
- `/stats` and `/settings` widen to `90rem` on `2xl` screens.

## [0.41.0] - 2026-08-20

### Changed
- Desktop heights and overflow tidied up: the scrollable panels share one height (`--db-panel-h`), and
  from `xl` the per-list overview shortens (`.db-panel-overview`) so the right column ends at the same
  height as the history table beside it. Each panel is the only scroll container in its card — no nested
  or double scrollbars — and the page keeps its own scrollbar as the single vertical one.
- `/context` and `/agents` follow the same shell as the other pages (`lg:max-w-5xl`), instead of staying
  at `max-w-3xl` on wide screens.
- `scroll-padding-top: 6rem` on `html`, so in-page anchors do not land under the fixed top bar.

## [0.40.0] - 2026-08-20

### Changed
- **/settings is one screen on desktop.** From `lg` the page splits into a sticky index on the left
  (one entry per group — agent, optimization, app, notifications, themes — with the number of settings)
  and a single panel on the right, so the 35 settings no longer stack into one very long column. Below
  `lg` the index is hidden and every group stays stacked exactly as before. New `.tl-btn-on` marks the
  selected entry.

## [0.39.0] - 2026-08-20

### Changed
- **/stats scrolls far less on desktop.** From `lg` the daily chart and the per-list overview sit side by
  side with the history below them (from `xl` the previous three-column split still applies), and the two
  long tables scroll inside their own card (`.db-panel-scroll`, capped at `min(62vh, 38rem)`) with a
  sticky header (`.db-sticky-head`) instead of stretching the page — the history has no row limit, so its
  height used to grow with the data. Phones and tablets are untouched: both rules start at `lg`.

## [0.38.1] - 2026-08-20

### Fixed
- **Invisible README header on GitHub in dark mode.** The lockup is dark ink (`#0F1720`) on transparent,
  and GitHub serves SVGs as `<img>`, so `currentColor` cannot help. Added `lockup-horizontal-dark.svg` /
  `lockup-stacked-dark.svg` (wordmark in `#E6EDF3`) and the README header now picks one with a `<picture>`
  + `media="(prefers-color-scheme: dark)"`.

## [0.38.0] - 2026-08-20

### Changed
- **Desktop layout for /settings and /stats.** Both pages were a single centred column (`max-w-xl` and
  `max-w-3xl`) with no breakpoint above `sm`, so a fullscreen desktop showed a tall strip between two
  empty margins. Now the shell widens (`lg:max-w-5xl`, `xl:max-w-7xl`) and the content spreads out:
  settings rows flow in two columns from `lg` (three from `2xl`), and from `xl` the stats page puts the
  history table on the left (two grid columns) with the daily chart and the per-list overview stacked on
  the right. Below `lg`/`xl` nothing changes — phones and tablets keep the layout they had.

## [0.37.0] - 2026-08-20

### Added
- **Archive a list.** Lists can be archived from the switcher (archive button on each row): an archived
  list leaves the menu and keeps every task. The menu has an **Archived lists** view with the count, where
  each list can be restored or deleted for good; the last active list cannot be archived, and archiving the
  current one moves the session to another list. New column `checklists.archived_at` (migration included).
- `Checklist::mineWithArchived()` and `Checklist::mineArchived()` alongside `mine()`, which now returns
  active lists only. Archived lists are skipped by `griglia:check` and `griglia:watch` when they look for
  plan lists.

## [0.36.0] - 2026-08-20

### Added
- `griglia:watch` now lists the items **already open to work** when it starts (`🟢 OPEN TO WORK (already
  waiting)`). Before, the first snapshot was only a baseline: a monitor started after the user flagged a
  task never announced it, and the agent sat idle. `--no-initial` restores the old behaviour.

### Changed
- Notification bell moved to the top right, on its own; the list switcher keeps the top left. Its dropdown
  now opens towards the left edge.
- List header: less padded card, more room under the fixed bar, and the theme claim line is only rendered
  when the theme sets one (`slate` no longer says "todo").
- Task modal: the theme icon is gone from the header — the title in the body carries the modal.

### Fixed
- **Black screen after uploading a picture.** The lightbox lived inside the thumbnails block, which
  Livewire re-renders on every upload; the teleported overlay stayed behind in `<body>` covering the page.
  State and overlay now live on the section itself, outside the re-rendered block.
- **Cut-off modal header on mobile with the keyboard open.** The full-screen panel used `height: 100dvh`,
  which some browsers do not recalculate when the virtual keyboard resizes the viewport; it now fills
  `.modal-shell` (`height: 100%`) and the header is sticky.
- The fixed top bar respects `env(safe-area-inset-top)` on notched phones.

## [0.35.0] - 2026-08-20

### Changed
- **Chrome dressed by the theme.** The list switcher, the notification bell and their dropdowns used to
  carry a hard-coded look (black borders, white paper, emerald hovers, system font) on every theme. They
  now take paper, border, radius, shadow and font from the current theme through new shared classes
  `.tl-btn` (+ `.tl-btn-sm`, `.tl-btn-icon`, `.tl-btn-ghost`, `.tl-btn-danger`), `.tl-menu`,
  `.tl-menu-item`, `.tl-menu-label`, `.tl-menu-sep` and `.tl-meter`. Themes can fine-tune them with
  `--tl-chrome-bg`, `--tl-chrome-hover`, `--tl-menu-bg` (set for `slate`).
- **List header.** The brand logo no longer flanks the list title — the title stands alone. The counter
  is now a line plus a hairline progress meter (`.tl-meter`), the same device used per list inside the
  switcher, so header and menu read as one system.
- The list menu shows a per-list progress meter and a `done/total` count on the button itself.

### Removed
- **Style switcher.** The floating `Style` menu (component `x-griglia::style-switcher`) is gone: the style
  is chosen in `/settings` (`app.default_style`). Themes stay reachable by their own routes.

## [0.34.1] - 2026-08-20

### Changed
- Install docs: `composer require alle80/griglia -W` (Web Push pulls `web-token/jwt-library`, which caps
  `brick/math` at `^0.17` while a fresh Laravel app ships `0.18`), plus a note explaining why.

## [0.34.0] - 2026-08-20

### Changed — BREAKING: the package is now **Griglia**

Everything that carried the old name has been renamed. Nothing else changed: no logic, no database schema,
no settings values — an existing installation keeps its data.

- **composer**: `alle80/agent-devboard` → **`alle80/griglia`** (the old package is abandoned and points here).
- **GitHub**: repository moved to `alle80/griglia` (old URLs redirect); docs, changelog links and the MkDocs
  site follow.
- **PHP namespace**: `Alle80\Devboard\*` → `Alle80\Griglia\*`; the service provider is
  `Alle80\Griglia\GrigliaServiceProvider`, the middleware `GrigliaAccess` / `GrigliaAdmin`, the notification
  base class `GrigliaNotification`.
- **Artisan commands**: `devboard:*` → **`griglia:*`** (`griglia:check`, `griglia:watch`, `griglia:context`,
  `griglia:empty-trash`, `griglia:theme-import/export`, `griglia:skills-import`,
  `griglia:agent-status-import`, `griglia:auto-archive`, `griglia:describe-images`, `griglia:docs-build`).
- **Config**: `config/devboard.php` → **`config/griglia.php`**, keys read as `config('griglia.*')`; env
  variables `DEVBOARD_*` → **`GRIGLIA_*`**.
- **Views / translations / Livewire / Blade components**: namespace `devboard::` → **`griglia::`**
  (`<x-griglia::icon>`, `<livewire:griglia::todo-list>`, `__('griglia::t.…')`).
- **Publish tags**: `devboard-config|views|lang|assets|agents` → `griglia-*`; published assets live in
  `public/vendor/griglia`, the standalone build files are `griglia.css` / `griglia.js`.
- **Routes**: `/devboard/...` → `/griglia/...` (attachments, transcribe, push subscriptions), service worker
  `/griglia-sw.js`, theme assets `/griglia-themes/...`; route names `griglia.*`.
- **User-model hooks**: `canAccessGriglia()` / `canManageGriglia()`. **Compatibility**: the old
  `canAccessDevboard()` / `canManageDevboard()` are still honoured when the new ones are absent.
- **Browser globals**: `window.grigliaPush`, `window.grigliaMic`, `window.GRIGLIA_ECHO|PUSH|SPEECH`.

**Upgrading** (only needed if you had the old package): `composer remove alle80/agent-devboard` +
`composer require alle80/griglia`; rename `config/devboard.php` to `config/griglia.php` and your `DEVBOARD_*`
env keys to `GRIGLIA_*`; re-publish the assets (`--tag=griglia-assets`) and update the import paths of
`resources/css/griglia.css` / `resources/js/griglia.js`; replace `devboard::`/`x-devboard::` with
`griglia::`/`x-griglia::` in any published views; use `griglia:*` in scripts and cron entries.

## [0.33.4] - 2026-08-20

### Changed
- The keyboard focus ring (`:focus-visible`) uses the theme accent (`--tl-accent`) instead of a fixed blue,
  so it no longer clashes on dark themes (blue fallback kept where no theme is active).

## [0.33.3] - 2026-08-20

### Changed
- README overhauled: the row-state table now uses the **real SVG icons** (new `docs/images/state-*.svg`),
  and everything from “Compatibility” on is restructured into sections with bullet lists and copyable
  command blocks; install/routes notes aligned with the current access middleware and pages.

## [0.33.2] - 2026-08-20

### Fixed
- Markdown is now rendered **everywhere it is read** in the task modal: the note of a completed task,
  the questions and their answers, and the previous note/comment shown by "resume" (they were still raw
  text; the editable note and the agent comment already rendered).

## [0.33.1] - 2026-08-20

### Fixed
- **Mobile: the virtual keyboard no longer covers the sub-task editor** (and the other modal fields):
  the viewport meta now uses `interactive-widget=resizes-content` (the keyboard shrinks `100dvh` instead of
  overlaying the modal) and, as a safety net, a focused field inside the modal body is scrolled into view
  once the keyboard has settled.

## [0.33.0] - 2026-08-20

### Changed
- **Deleting a list or a task is now a soft delete**: the rows keep their `deleted_at` and the statistics
  (time, tokens, costs, history) **survive the deletion**. Trashed lists stay selectable on `/stats`
  (marked "(deleted)"); the board, menus and CLI never show trashed items. A blank untouched new task is
  still dropped for real on close.

### Added
- `griglia:empty-trash {--days=N} {--dry-run}`: permanently purges soft-deleted lists/tasks (attachment
  files included) — that is when their statistics disappear.

## [0.32.1] - 2026-08-19

### Changed
- The board **list title (h1)** now shows the brand mark (new inline `<x-griglia::logo>` component,
  `currentColor`, so it follows the theme palette) instead of the theme icon/emoji; theme icons remain in
  the style switcher, settings and modal.

## [0.32.0] - 2026-08-19

### Added
- **Logo** («D with Check & Dot»): brand assets in `public/images/brand/` (mark in color / `currentColor` /
  black / white, rounded-square app icons light/dark, horizontal and stacked lockups, PNG 16–512) published
  with the `griglia-assets` tag. The themed layout now falls back to the brand mark (+ apple-touch icon)
  when the theme has no `icon_img`; Web Push notifications carry the mark as system icon; the MkDocs site
  and the README use the logo. Colours: Agent Green `#16A34A` (the existing accent), Devboard Ink `#0F172A`.

## [0.31.1] - 2026-08-19

### Added
- Agent context: switch **«Generate the instruction files from the board»** (`/context`, setting
  `app.context_sync`, `griglia:context enabled` for host scripts). When off, the host sync restores the original
  files and leaves them alone (the origin repo's `sync-context.py` keeps the originals in `docs/context-originals/`
  and offers `--restore` / `--backup`).

## [0.31.0] - 2026-08-19

### Added
- **Multi-agent**: config `devboard.agents` (`GRIGLIA_AGENTS="claude:Claude Code,codex:Codex CLI"`) declares
  the active agents; each list (project) has a **default agent** (selector in the toolbar) and each task may
  **override** it (selector in the modal header, chip on the row). `griglia:check --agent=<key>` (default:
  `GRIGLIA_AGENT_KEY` / first configured) lists only that agent's tasks and prints `{agent: key}` per row; a
  single configured agent keeps today's behaviour. `Alle80\Griglia\Agent::all()/effective()`, columns
  `checklists.agent`, `todos.agent`.

## [0.30.2] - 2026-08-19

### Changed
- Plan lists: the chain **follows the drag & drop order** (each task depends on the one above it after a reorder
  or an insert in the middle), so the execution order is always the visible order.

## [0.30.1] - 2026-08-19

### Changed
- Wider task modal on desktop (2xl/3xl, taller body); phones unchanged (full screen).

## [0.30.0] - 2026-08-19

### Added
- **Documentation site**: Markdown docs in `docs/` (installation, usage, agent side, plans, notifications, context,
  skills, statistics & agents status, themes, configuration, security, development) with `mkdocs.yml` (Material for
  MkDocs) and the command **`griglia:docs-build`** (`--out`, `--serve`, `--docker`, `--strict`) that builds the
  static HTML site, with clear errors when MkDocs is missing or the build fails.
- `SECURITY.md` (reporting, security model, hardening checklist) and a Security section in the README.

## [0.29.4] - 2026-08-19

### Added
- Local mode: a persistent **banner** on every page («no authentication — bind to localhost») and README notes
  (bind address, trustProxies).

### Changed
- (monorepo) repository hygiene for the open-source release: root `vendor/` untracked, compose parametrised
  (`APP_URL`, `BACKUP_DIR`), personal paths/domains scrubbed from README/scripts/.env.example, root LICENSE (MIT),
  third-party artwork removed.

## [0.29.3] - 2026-08-19

### Security
- Image uploads: the pixel count is checked **before decoding** (max 40 megapixel) — no decompression bombs.
- Attachments are served by an **authorised route** (`/devboard/attachments/{id}`, only users of that list;
  `nosniff` + sandbox CSP), so `attachments_disk` can be a private disk; `attachments_via_controller=false`
  restores direct public URLs.

## [0.29.2] - 2026-08-19

### Security
- Web Push subscriptions accept only **https endpoints of known push services** (`devboard.push_allowed_hosts`,
  wildcards; empty = any https host) — closes the blind SSRF.
- **Rate limits** on the expensive endpoints (`devboard.rate_limits`: transcribe 10/min, notification test
  5/min, push subscriptions 30/min; per-route buckets).
- Generic error messages to the browser for transcription and theme install failures (details in the log).

## [0.29.1] - 2026-08-19

### Security
- Theme packs hardened: **SVG no longer accepted** (scriptable); `theme.css` is **sanitised** on install
  (`@import`, external `url()`/`src()`/`image-set()`, non-image `data:`, `expression()`, `behavior`,
  `-moz-binding` removed — relative urls and inline images kept); extraction **limits** (5 MB per file, 20 MB per
  pack, 200 entries) checked on the declared size before inflating; `icon_img` must be inside the pack; theme
  assets are served with `X-Content-Type-Options: nosniff` and a sandboxing `Content-Security-Policy`.

## [0.29.0] - 2026-08-19

### Security
- **Admin boundary**: `/settings` and `/context` (settings, agent context, theme packs) are now restricted to
  administrators — `Alle80\Griglia\Admin`: `canManageDevboard(): bool` on the user model, else Gate
  `devboard.admin_gate`, else `devboard.admins` (ids/e-mails, `GRIGLIA_ADMINS`), else the **first registered
  user** only; middleware `GrigliaAdmin` (also persistent on Livewire updates) + defensive `boot()` in the
  components; menu links hidden to non-admins. Local mode: everybody.
- Switching the board to **local mode from the UI is refused** unless the app runs in the `local` environment
  or `devboard.allow_local_from_ui` is on (a stale `local` override in the settings is ignored too).

### Added
- `docs/config-and-settings.md`: inventory of current configurations and settings, template, and the prioritised
  backlog of future ones (with implementation notes).

## [0.28.0] - 2026-08-19

### Added
- **Agents status** (`/agents`, link in the lists menu): plan and usage windows of the coding agents — used %,
  remaining %, progress bar with ok/high/almost exhausted/over-the-limit levels, reset countdown, $ limits and
  extra usage when exposed, «updated … / stale» meta; empty, not-configured and error states; auto-refresh
  every minute. Data come from a snapshot imported with `griglia:agent-status-import` (the origin repo ships
  `scripts/agent-status.py`: it reads the Claude Code OAuth credentials on the host and sends only
  percentages). `Alle80\Griglia\Support\AgentStatus`, Livewire `AgentsPage`, config `agent_status_file`.

## [0.27.1] - 2026-08-19

### Added
- `/stats`: the list selector also offers **All lists** and **All plans** (aggregated history with the list
  name on each row).

## [0.27.0] - 2026-08-19

### Added
- **Pause / resume a plan**: the plan bar shows «Pause the plan» while it runs (open tasks go back to waiting,
  `checklists.plan_paused` stops the chain from opening the next task) and «Resume the plan» when paused /
  stopped (clears the pause and opens the next not-started task). Icon `pause`.

## [0.26.3] - 2026-08-19

### Fixed
- Lists menu navigation as a 2×2 grid («Settings» was cut on phones).

## [0.26.2] - 2026-08-19

### Changed
- Lists menu footer: text-only navigation (Stats / Context / Settings / Logout) as a 2×2 button grid under
  the user name — no icons.
- `/stats` on phones: full-width list selector, history as cards (title, date, time/tokens/cost), at most
  60 bars in the per-day chart; title without icon.

## [0.26.1] - 2026-08-19

### Added
- Plan lists: a **new task joins the chain** automatically (depends on the previous task by order), so
  after a plan is completed you can add tasks and «Resume the plan» (toolbar or ▶ in the lists menu) —
  the new ones open in sequence.

## [0.26.0] - 2026-08-19

### Added
- **Statistics & history** (`/stats`, link in the lists menu): per list (project) — KPIs (completed, working
  time with average, tokens, **cost** from a price list), per-day bars, the history of completed tasks
  (date, time, lead time, tokens in/out, cost, sub-tasks/questions/resumed-from) and an overview of all
  lists; period 7/30/90/365 days or all. `Alle80\Griglia\Support\Stats` (history/aggregate/series/
  overview/cost), Livewire `StatsPage`.
- `todos.completed_at` kept by the model (set when completed, cleared when reopened; migration backfills
  existing completed items from `updated_at`).
- Settings (App): `cost_per_m_in`, `cost_per_m_out`, `cost_currency` — price list used to turn tokens into
  cost (0 = cost not shown).

## [0.25.0] - 2026-08-19

### Changed
- **Plans are part of the agent's work**: `griglia:check` and `griglia:watch` now cover the agent list
  **plus the owner's plan lists** (built from a prompt / chained tasks); started plan tasks are listed after
  the agent list under `📐 Plan «name»`, and `--take/--done/--ask` accept their ids. Starting a plan = the
  agent works it, following the chain.
- Lists menu: a running plan shows the «working» badge instead of ▶.

## [0.24.1] - 2026-08-19

### Added
- «Start the plan» ▶ also in the lists menu, next to plan lists that are not running (switches to the list
  and opens the first not-started task).

## [0.24.0] - 2026-08-19

### Added
- **Agent phase**: `griglia:check --take=ID --progress=N --phase="writing code"` stores a short text of what
  the agent is doing (`todos.phase`, cleared on done/ask) shown next to the % in the row and in the modal,
  and printed by `griglia:check` (`[45% · writing code]`).

## [0.23.0] - 2026-08-19

### Added
- **Start a plan**: on a plan list (chained tasks or built from a prompt) the toolbar shows a «Plan» bar with
  progress (done/total) and a **Start the plan** button (→ the first not-started task becomes open to work;
  the chain opens the following ones), «Resume the plan» after a stop, «in progress» / «plan completed»
  states. `TodoList::startPlan()`, `planStatus()`; icon `play`.

## [0.22.3] - 2026-08-19

### Added
- Web Push **diagnostics** in `/settings`: permission, service worker, subscription on this device (push host),
  opened as PWA/browser, devices registered on the server; «Show a local notification» (no network) and a
  live log that confirms when a server push actually reaches the device (the service worker posts a message
  to open pages). Helps telling apart OS-level blocking from delivery problems.

## [0.22.2] - 2026-08-19

### Fixed
- Notification bell dropdown no longer overflows the screen on phones (full-width panel under the top bar).

## [0.22.1] - 2026-08-19

### Changed
- `/context` cards reorganised for phones: commands (handle, switch, select-all/rename/delete, chevron) on
  the top row, title + stats wrapping below on their own line; block rows likewise (commands row, then the
  text full width); smaller title fonts.

## [0.22.0] - 2026-08-19

### Changed
- **No emoji left in the UI**: settings labels/options/help texts, modal (agent box, questions, stats,
  skills, chain, images, sub-task checks, title pencil), rows (sub-tasks count, chain, agent reply, images),
  toasts, switchers and readonly notice all use the SVG icon set or plain words. New icons `image`, `camera`,
  `lock`, `chart`, `puzzle`, `link`, `clock`, `tasks`.

## [0.21.3] - 2026-08-19

### Changed
- Italian titles say «l'agente» (skills, questions, settings section, context) instead of naming the agent;
  the name from `agent_name` is still used in the 🤖 comment box.

## [0.21.2] - 2026-08-19

### Changed
- The top menus use the SVG icon set too: lists switcher (list/chevron, edit, close, user, context, settings,
  plan), notification bell (bell + per-kind state icons), style switcher (palette/chevron). New icons `user`,
  `logout`, `ruler`, `list`.

## [0.21.1] - 2026-08-19

### Changed
- `/settings` (and the back link of `/context`) use the SVG icon set instead of emoji: page title, section
  titles (agent / optimization / app / notifications / themes), device state, buttons. New icons: `settings`,
  `bot`, `bolt`, `board`, `bell`, `bell-off`, `palette`, `alert`, `send`, `package`, `arrow-left`.

## [0.21.0] - 2026-08-19

### Added
- **Agent-agnostic**: config `devboard.agent_name` (`GRIGLIA_AGENT_NAME`, default «Agent») used by the UI
  labels (🤖 box, skills, questions, settings, context); `Alle80\Griglia\Agent::name()`. The Italian
  strings no longer hard-code «Claude». AGENTS.md/README: compatibility table for Claude Code, Codex CLI,
  Gemini CLI and other CLI agents (instructions file per agent, skills folders, token stats).

## [0.20.1] - 2026-08-19

### Security
- `GrigliaAccess` is registered as a **Livewire persistent middleware**: since 0.14.0 it replaces `auth` on
  the package routes, but Livewire re-applies only persistent middleware on `/livewire/update`, so component
  actions (settings, context, lists) were not re-checked for authentication/access on update requests.

## [0.20.0] - 2026-08-19

### Added
- **Plan mode**: when creating a list, «Create as a plan» + a prompt (with 🎤): the goal is split by the AI SDK
  agent `PlanBuilder` (structured output, default provider) into ordered tasks with notes and sub-tasks,
  **chained** with the new `todos.depends_on_id` — the first one is left for the user to start, each next
  one opens 🟢 automatically when the previous is completed (model hook). Chain shown in the row (⛓), in the
  modal and in `griglia:check`. `checklists.plan_prompt` keeps the prompt. Without an AI provider the list
  gets a single «Build the plan» task with the prompt (for the agent). `Alle80\Griglia\Support\Plan`
  (fakeable via `Plan::$resolver`).

## [0.19.0] - 2026-08-19

### Added
- **Move a task to another list**: a «Move to list…» menu in the modal header (the user's other lists);
  the task is appended to the target list, the source numbering is closed. `IngredientModal::moveTo()`,
  icon `move`.

## [0.18.1] - 2026-08-19

### Changed
- The modal state badge is a plain **tap toggle** (waiting ⚪ ⇄ open to work 🟢; tap while the agent works =
  stop, with confirmation) instead of a dropdown — same gesture as the dot in the row.

## [0.18.0] - 2026-08-19

### Added
- The **state badge in the modal header is a menu**: click it to set the state from there too — waiting ⚪,
  open to work 🟢, done ✔ (choosing a state while the agent works stops it, like the dot in the row;
  agent states working/question are shown but not settable). `IngredientModal::setState()`.

### Removed
- The separate «open to work» command button in the modal header (superseded by the state menu).

## [0.17.1] - 2026-08-19

### Added
- The image **lightbox** shows the **AI description** of the picture under it (or a hint when there is none);
  thumbnails carry the description as tooltip.

## [0.17.0] - 2026-08-19

### Added
- **Server-side speech to text** through the Laravel AI SDK (`Laravel\Ai\Transcription`, provider
  `ai.default_for_transcription`): the microphone button records with MediaRecorder and uploads the clip to
  `POST /devboard/transcribe`; the text comes back and is appended to the field. New `app.speech_mode`
  setting: `auto` (default: server when the SDK + a provider key are configured, else the browser's Web
  Speech API), `server`, `browser`. `Alle80\Griglia\Support\Speech`, `TranscribeController`,
  `window.GRIGLIA_SPEECH`. The mic shows busy/error states.

## [0.16.1] - 2026-08-19

### Fixed
- Speech to text on phones: the recognition session ends after every pause (Android/iOS); it now restarts
  keeping what was already dictated (the text was being overwritten), ignores transient errors (`no-speech`)
  and stops when the page goes to the background.

## [0.16.0] - 2026-08-19

### Added
- **Speech to text**: a microphone button (`<x-griglia::mic>`, browser Web Speech API, no server needed)
  in the Markdown editor toolbar (note, sub-tasks, context blocks), next to the task title field and in the
  insert-title form; what you say is appended to the field (language = page locale). Hidden when the browser
  has no speech recognition. Icon `mic`.

## [0.15.5] - 2026-08-19

### Fixed
- `/context`: the block editor spans the full width of the row (was squeezed on phones).

## [0.15.4] - 2026-08-19

### Changed
- `/context`: blocks are edited with the **Markdown editor** (toolbar + auto-growing textarea).
- **Mobile**: roomier text areas (markdown editor and context blocks: taller, 15–16px font).

## [0.15.3] - 2026-08-19

### Changed
- `/context` uses the SVG icon set everywhere (grip handles, edit/trash, select-all, enable/disable,
  chevron, title/tokens) instead of emoji. New icons: `check`, `check-all`, `ban`, `chevron`, `grip`,
  `book`, `coins`.

## [0.15.2] - 2026-08-19

### Changed
- The **state filters** in the toolbar use the SVG state icons (same as the rows: waiting / done / open /
  working «Matrix» / question) instead of the old emoji.

## [0.15.1] - 2026-08-19

### Fixed
- Context import: a «**Bold lead**» line after plain text starts a new block (paragraphs written on
  consecutive lines are split).

## [0.15.0] - 2026-08-19

### Added
- **Manageable agent context** (`/context`, link 📚 in the lists menu): the agent's instructions file is
  imported as **groups** (`##` sections) and **blocks** (bullets / paragraphs / `###` sub-sections; fenced
  code stays whole) — `php artisan griglia:context import --file=… [--replace]`. Each group and each block
  has a switch; blocks can be **multi-selected** (per block or whole group) and enabled/disabled together;
  blocks can be edited, added, deleted and reordered (drag), groups renamed/added/deleted/reordered.
  Token estimate per block/group/total. `griglia:context export` prints the enabled context as markdown
  (a host script writes it to the file), `griglia:context status` the summary. Models `ContextGroup`,
  `ContextBlock`, support `Alle80\Griglia\Support\Context`.

## [0.14.2] - 2026-08-19

### Changed
- The **working icon** is now a green «Matrix» digital-rain glyph (three dashed columns flowing down,
  glow + faint flicker) instead of the spinning gear.
- The **sub-tasks badge** (☑ n/m) is shown only when the item has sub-tasks.

### Removed
- The 💬 icon shown on rows with a note.

## [0.14.1] - 2026-08-19

### Added
- **Live search** box at the top of the 🧩 skills accordion (client-side, filters name/description/source
  while typing; Esc clears).

## [0.14.0] - 2026-08-19

### Added
- **Board modes** (`Alle80\Griglia\Mode`): config `devboard.mode` (`GRIGLIA_MODE`, default `server`),
  overridable from `/settings` (`app.mode`, '' = follow the config, with a warning for local).
  - **server**: authenticated users, lists per user, plus an access hook: `canAccessDevboard(): bool` on
    the user model if defined, else the Gate ability in `devboard.access_gate` if set, else any logged-in
    user (403 otherwise). Enforced by the new `GrigliaAccess` middleware, which also plays the role of
    `auth` (redirect to login) — `'auth'` in `devboard.middleware` is no longer needed and is ignored.
  - **local**: no authentication at all, one **global** set of lists (no user); live updates on a public
    channel (`griglia.local_channel`); no bell / push / logout in the UI. For a board on your own machine.
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
    worker served at `/griglia-sw.js`, subscription endpoints `POST/DELETE /devboard/push-subscriptions`,
    «Enable Web Push on this device» + «Send me a test notification» in `/settings`. Needs VAPID keys
    (`php artisan webpush:vapid`) and the `HasPushSubscriptions` trait on the user model.
  - **Mail** (`toMail`) when a mailer is configured.
- Deep links `?list=ID&open=ID` (middleware `OpenFromLink`) open a task from a notification.
- Idempotent migration creating `notifications` and `push_subscriptions` when the host app lacks them.
- `Alle80\Griglia\Support\Notify`, notifications `TodoCompleted`, `QuestionAsked`, `TestNotification`.

## [0.12.2] - 2026-08-19

### Changed
- The **«+» between rows** now creates the task *at that position* (making room) and opens the modal in
  title editing, like the «New task» button — instead of the inline title form (`createNew(?int $position)`).

## [0.12.0] - 2026-08-19

### Added
- **Agent skills per task**: a catalogue of the skills the coding agent has available (imported with
  `griglia:skills-import` from a JSON list of `{name, description, source}` — file or stdin — into
  `config('griglia.skills_file')`, default `storage/app/griglia/skills.json`) is shown in the modal, under
  the Task note, as a **🧩 accordion of checkboxes**; the chosen ones are saved in `todos.skills` (JSON) and
  `griglia:check` prints `🧩 skills to activate for this task: …` so the agent invokes them. Read-only on
  completed items. Dedicated migration for existing installs. `Alle80\Griglia\Support\Skills`.

## [0.11.0] - 2026-08-19

### Added
- **⚡ Optimization** settings group (`OptimizationSettings`, group `optimization`) — switches that make the
  agent spend fewer tokens, printed by `griglia:check` as `⚡ optimization: …`:
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
  the new `griglia:check --tokens-in=N --tokens-out=N` options (with `--take`/`--done`/`--ask`;
  cumulative per todo, `todos.tokens_in` / `tokens_out`). Partially completed items keep their stats.
- The modal shows a **📊 Stats** line (⏱ time — live while working — and 🪙 tokens in/out);
  `griglia:check` prints `⏱ working since … (Xm this interval)` on working items and `📊 …` on
  completed ones / when closing. Dedicated migration for existing installs.
- `Todo::workSeconds()`, `hasStats()`, `statsLine()`, `formatDuration()`, `formatTokens()`.

## [0.9.3] - 2026-08-19

### Fixed
- The **progress percentage** was never visible in practice: `griglia:check --take=ID` left `progress`
  at `null` unless `--progress` was passed, so a working todo showed the spinning icon but no `N%`.
  Now `--take` always shows a percentage: the given `--progress`, else the current value, else **0%**.
  Re-running `--take=ID --progress=N` updates it (live via Reverb); `--done` still clears it.
- The progress bar has a faint **track** and a minimum width, so 0% is visible as an empty bar.

### Changed
- `griglia:check` prints `[N%]` after the title of a working todo and `— N%` when taking in charge.

## [0.9.2] - 2026-08-19

### Fixed
- The multitasking **warning** in `/settings` now shows/hides instantly when the mode changes
  (Alpine `x-show`), instead of waiting for a server re-render.

## [0.9.1] - 2026-08-19

### Added
- Setting **`task_mode`** (`agent` group): `ordered` = one task at a time in list order (default),
  `multitasking` = the agent may take several 🟢 tasks at once if independent. Shown in the settings
  summary printed by `griglia:check`, with an inline warning in `/settings` for multitasking.

## [0.9.0] - 2026-08-19

### Added
- **Animated icon** on the working todo: the working state badge (gear) spins continuously.
- **Progress percentage**: `griglia:check --take=ID --progress=N` (0–100) shows `N%` next to the
  title and a thin progress bar under the row; `--done` clears it. New `todos.progress` column
  (dedicated migration for existing installs).

## [0.8.1] - 2026-08-19

### Changed
- In the modal, the editable **title** is the first field of the body (above "Task"), no longer in the
  header; the header keeps the theme icon, the state badge + commands and the close button.

## [0.8.0] - 2026-08-19

### Added
- **Unseen results**: when the agent completes a todo (`griglia:check --done`), the row stays
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
  (`<x-griglia::md-editor>` — bold, italic, code, code block, list, quote, link, table, separator)
  and **safe rendering** — GitHub-flavoured (tables, task lists, autolinks), with raw HTML stripped and
  unsafe links blocked, via `league/commonmark` (`Alle80\Griglia\Support\Markdown`). The agent's
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
- The modal title bar printed the raw `('griglia::livewire.partials.modal-actions')` string instead of
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
- Reusable inline-SVG **icon set** (`<x-griglia::icon name="…">`) in the logo (slate) line style.

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
  (`config('griglia.dashboard_route')`, default `/dashboard`) — more readable on large screens.
- **Slide-out board tab** (Laravel-debugbar style): a handle pinned to the right or left edge opens a
  **resizable** panel that shows the dashboard on every page (desktop only). Remembers open state and
  width, respects `prefers-reduced-motion`.
- **Setting `tab_side`** (right / left) in `/settings`, and config key `dashboard_route`.

## [0.4.0] - 2026-08-19

### Added
- **`griglia:watch`** — a portable monitor for a coding agent: watches the agent list and prints
  only the changes to react to (an item going _open to work_, answers to a paused question arriving,
  a stop being requested). One command replaces harness-specific monitors. `--interval`, `--list`,
  `--once`.
- **`AGENTS.md`** shipped with the package and publishable with `php artisan vendor:publish
  --tag=griglia-agents` — the full agent protocol (states, take-first, order, questions, stop, close),
  so "connect an agent" = launch it in the project directory + read `AGENTS.md` + one `griglia:watch`.

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
  `.theme-linux` becomes `.theme-slate`, and `config('griglia.default_theme')` defaults to `slate`.
  The Tux image (`public/images/linux/tux.svg`) and its terminal-flavoured copy were removed.

### Upgrade notes
- If you referenced the built-in theme by slug (`/linux`, `default_theme`/`default_style` = `linux`,
  a `.theme-linux { … }` override, or `griglia:theme-export linux`), rename it to `slate`.
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
  an `app` group (board behaviour), read by the `griglia:check` command.
- **Theme system**: generic themes via CSS variables, a built-in **Linux** theme, and
  **installable theme packs** as zips (`ThemeStore`, `griglia:theme-import/-export`).
- **Console workflow**: `griglia:check` (alias `sviluppo:check`), `griglia:auto-archive`,
  `griglia:describe-images`, `griglia:theme-import`, `griglia:theme-export`.
- **Standalone front-end assets**: a package-owned Vite build producing precompiled
  `public/build/devboard.{css,js}` plus an Echo chunk, selectable via `<x-griglia::assets />`
  between `@vite` (bundled by the host app) and the precompiled files (`GRIGLIA_ASSETS=precompiled`);
  Echo configured at runtime from `config('griglia.echo')`.
- **Consolidated, idempotent migration** for all tables and settings defaults.
- English base language with an Italian translation.
- Test suite (orchestra/testbench, SQLite in-memory) and a GitHub Actions workflow.

### Notes
- Requires PHP 8.3+, Laravel 12 or 13, Livewire 4, Tailwind CSS 4 in the host app.
- The full pre-extraction history lives in the origin monorepo linked above.

[Unreleased]: https://github.com/alle80/griglia/compare/v0.12.0...HEAD
[0.12.0]: https://github.com/alle80/griglia/compare/v0.11.0...v0.12.0
[0.11.0]: https://github.com/alle80/griglia/compare/v0.10.0...v0.11.0
[0.10.0]: https://github.com/alle80/griglia/compare/v0.9.3...v0.10.0
[0.9.3]: https://github.com/alle80/griglia/compare/v0.9.2...v0.9.3
[0.9.2]: https://github.com/alle80/griglia/compare/v0.9.1...v0.9.2
[0.9.1]: https://github.com/alle80/griglia/compare/v0.9.0...v0.9.1
[0.9.0]: https://github.com/alle80/griglia/compare/v0.8.1...v0.9.0
[0.8.1]: https://github.com/alle80/griglia/compare/v0.8.0...v0.8.1
[0.8.0]: https://github.com/alle80/griglia/compare/v0.7.3...v0.8.0
[0.7.3]: https://github.com/alle80/griglia/compare/v0.7.2...v0.7.3
[0.7.2]: https://github.com/alle80/griglia/compare/v0.7.1...v0.7.2
[0.7.1]: https://github.com/alle80/griglia/compare/v0.7.0...v0.7.1
[0.7.0]: https://github.com/alle80/griglia/compare/v0.6.3...v0.7.0
[0.6.3]: https://github.com/alle80/griglia/compare/v0.6.2...v0.6.3
[0.6.2]: https://github.com/alle80/griglia/compare/v0.6.1...v0.6.2
[0.6.1]: https://github.com/alle80/griglia/compare/v0.6.0...v0.6.1
[0.6.0]: https://github.com/alle80/griglia/compare/v0.5.1...v0.6.0
[0.5.1]: https://github.com/alle80/griglia/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/alle80/griglia/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/alle80/griglia/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/alle80/griglia/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/alle80/griglia/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/alle80/griglia/releases/tag/v0.1.0
