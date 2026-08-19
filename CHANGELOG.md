# Changelog

All notable changes to `alle80/agent-devboard` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.32.1] - 2026-08-19

### Changed
- The board **list title (h1)** now shows the brand mark (new inline `<x-devboard::logo>` component,
  `currentColor`, so it follows the theme palette) instead of the theme icon/emoji; theme icons remain in
  the style switcher, settings and modal.

## [0.32.0] - 2026-08-19

### Added
- **Logo** («D with Check & Dot»): brand assets in `public/images/brand/` (mark in color / `currentColor` /
  black / white, rounded-square app icons light/dark, horizontal and stacked lockups, PNG 16–512) published
  with the `devboard-assets` tag. The themed layout now falls back to the brand mark (+ apple-touch icon)
  when the theme has no `icon_img`; Web Push notifications carry the mark as system icon; the MkDocs site
  and the README use the logo. Colours: Agent Green `#16A34A` (the existing accent), Devboard Ink `#0F172A`.

## [0.31.1] - 2026-08-19

### Added
- Agent context: switch **«Generate the instruction files from the board»** (`/context`, setting
  `app.context_sync`, `devboard:context enabled` for host scripts). When off, the host sync restores the original
  files and leaves them alone (the origin repo's `sync-context.py` keeps the originals in `docs/context-originals/`
  and offers `--restore` / `--backup`).

## [0.31.0] - 2026-08-19

### Added
- **Multi-agent**: config `devboard.agents` (`DEVBOARD_AGENTS="claude:Claude Code,codex:Codex CLI"`) declares
  the active agents; each list (project) has a **default agent** (selector in the toolbar) and each task may
  **override** it (selector in the modal header, chip on the row). `devboard:check --agent=<key>` (default:
  `DEVBOARD_AGENT_KEY` / first configured) lists only that agent's tasks and prints `{agent: key}` per row; a
  single configured agent keeps today's behaviour. `Alle80\Devboard\Agent::all()/effective()`, columns
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
  MkDocs) and the command **`devboard:docs-build`** (`--out`, `--serve`, `--docker`, `--strict`) that builds the
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
  administrators — `Alle80\Devboard\Admin`: `canManageDevboard(): bool` on the user model, else Gate
  `devboard.admin_gate`, else `devboard.admins` (ids/e-mails, `DEVBOARD_ADMINS`), else the **first registered
  user** only; middleware `DevboardAdmin` (also persistent on Livewire updates) + defensive `boot()` in the
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
  every minute. Data come from a snapshot imported with `devboard:agent-status-import` (the origin repo ships
  `scripts/agent-status.py`: it reads the Claude Code OAuth credentials on the host and sends only
  percentages). `Alle80\Devboard\Support\AgentStatus`, Livewire `AgentsPage`, config `agent_status_file`.

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
  lists; period 7/30/90/365 days or all. `Alle80\Devboard\Support\Stats` (history/aggregate/series/
  overview/cost), Livewire `StatsPage`.
- `todos.completed_at` kept by the model (set when completed, cleared when reopened; migration backfills
  existing completed items from `updated_at`).
- Settings (App): `cost_per_m_in`, `cost_per_m_out`, `cost_currency` — price list used to turn tokens into
  cost (0 = cost not shown).

## [0.25.0] - 2026-08-19

### Changed
- **Plans are part of the agent's work**: `devboard:check` and `devboard:watch` now cover the agent list
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
- **Agent phase**: `devboard:check --take=ID --progress=N --phase="writing code"` stores a short text of what
  the agent is doing (`todos.phase`, cleared on done/ask) shown next to the % in the row and in the modal,
  and printed by `devboard:check` (`[45% · writing code]`).

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
- **Agent-agnostic**: config `devboard.agent_name` (`DEVBOARD_AGENT_NAME`, default «Agent») used by the UI
  labels (🤖 box, skills, questions, settings, context); `Alle80\Devboard\Agent::name()`. The Italian
  strings no longer hard-code «Claude». AGENTS.md/README: compatibility table for Claude Code, Codex CLI,
  Gemini CLI and other CLI agents (instructions file per agent, skills folders, token stats).

## [0.20.1] - 2026-08-19

### Security
- `DevboardAccess` is registered as a **Livewire persistent middleware**: since 0.14.0 it replaces `auth` on
  the package routes, but Livewire re-applies only persistent middleware on `/livewire/update`, so component
  actions (settings, context, lists) were not re-checked for authentication/access on update requests.

## [0.20.0] - 2026-08-19

### Added
- **Plan mode**: when creating a list, «Create as a plan» + a prompt (with 🎤): the goal is split by the AI SDK
  agent `PlanBuilder` (structured output, default provider) into ordered tasks with notes and sub-tasks,
  **chained** with the new `todos.depends_on_id` — the first one is left for the user to start, each next
  one opens 🟢 automatically when the previous is completed (model hook). Chain shown in the row (⛓), in the
  modal and in `devboard:check`. `checklists.plan_prompt` keeps the prompt. Without an AI provider the list
  gets a single «Build the plan» task with the prompt (for the agent). `Alle80\Devboard\Support\Plan`
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
  Speech API), `server`, `browser`. `Alle80\Devboard\Support\Speech`, `TranscribeController`,
  `window.DEVBOARD_SPEECH`. The mic shows busy/error states.

## [0.16.1] - 2026-08-19

### Fixed
- Speech to text on phones: the recognition session ends after every pause (Android/iOS); it now restarts
  keeping what was already dictated (the text was being overwritten), ignores transient errors (`no-speech`)
  and stops when the page goes to the background.

## [0.16.0] - 2026-08-19

### Added
- **Speech to text**: a microphone button (`<x-devboard::mic>`, browser Web Speech API, no server needed)
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
  code stays whole) — `php artisan devboard:context import --file=… [--replace]`. Each group and each block
  has a switch; blocks can be **multi-selected** (per block or whole group) and enabled/disabled together;
  blocks can be edited, added, deleted and reordered (drag), groups renamed/added/deleted/reordered.
  Token estimate per block/group/total. `devboard:context export` prints the enabled context as markdown
  (a host script writes it to the file), `devboard:context status` the summary. Models `ContextGroup`,
  `ContextBlock`, support `Alle80\Devboard\Support\Context`.

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
