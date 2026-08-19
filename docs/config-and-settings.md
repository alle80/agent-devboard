# Configurations & settings — inventory and backlog

Two different things, kept apart on purpose:

- **Configuration** (`config/devboard.php`, env) — decided by the *developer* who installs the package: paths, routes,
  models, integrations. Read once per request, needs a deploy/`config:cache` to change, never edited from the UI.
- **Settings** (spatie/laravel-settings, `/settings`) — decided by the *user* at run time, stored in the DB, changed
  from the UI with immediate effect. Three groups: `agent` (how the agent works), `optimization` (token saving),
  `app` (behaviour of the board).

Template for every entry: **name** · type · default · what it does · status (**current** / **future**) · priority (P1 high,
P2 medium, P3 low) · implementation notes (for future ones).

## 1. Configurations — current

| Key (`devboard.*`) | Type / default | Purpose |
|---|---|---|
| `route_prefix` | string, `''` (`DEVBOARD_ROUTE_PREFIX`) | URL prefix of the package pages |
| `agent_name` | string, `Agent` (`DEVBOARD_AGENT_NAME`) | how the UI calls the agent (Claude, Codex, …) |
| `mode` | `server`\|`local` (`DEVBOARD_MODE`) | auth + per-user lists vs no auth + global lists (overridable from settings) |
| `access_gate` | string\|null (`DEVBOARD_ACCESS_GATE`) | Gate ability checked in server mode (after `canAccessDevboard()`) |
| `middleware` | array, `['web']` | middleware of the package routes (`DevboardAccess` is always appended) |
| `local_channel` | string, `devboard.local` | public broadcast channel used in local mode |
| `register_routes` | bool, `true` | register the package routes at all |
| `home_route` | bool, `true` | register `/` showing the default theme |
| `dashboard_route` | string\|false, `/dashboard` | wide desktop view + slide-out tab |
| `default_theme` | string, `slate` | fallback generic theme |
| `themes` | array | extra generic themes defined in code |
| `user_model` | class, `App\Models\User` | owner of the lists / notifiable |
| `attachments_disk` | string, `public` | filesystem disk of the images |
| `agent_list` | string, `dev` (`DEVBOARD_AGENT_LIST`) | the list used as request channel with the agent |
| `default_list_name` | string | name of the first list created for a user |
| `broadcast_channel` | string, `App.Models.User.{id}` | private channel for live updates |
| `agent_status_file` | path | snapshot of the agents' plan/usage (`/agents`) |
| `skills_file` | path | catalogue of the agent's skills |
| `assets` / `vite_entries` / `assets_url` | `vite`\|`precompiled` | how CSS/JS are served |
| `echo.*` | key/host/port/scheme | runtime Echo (Reverb) client config |
| `fonts_url` | string | web-fonts provider prefix (`''` = none) |

## 2. Settings — current

**agent**: `commit_after_task`, `push_after_commit`, `autonomy` (ask\|decide), `notify_on_done`, `notify_on_question`,
`verify_before_close`, `comment_detail` (short\|detailed), `git_flow` (main\|branch_pr), `daily_summary` + `daily_summary_time`,
`check_subtasks_on_done`, `task_mode` (ordered\|multitasking).
**optimization**: `compact_check`, `terse_agent`, `context_max_chars`, `progress_piggyback`, `token_report`.
**app**: `default_style`, `title_max_length`, `auto_archive_days`, `ai_describe_images`, `ai_image_provider`, `ai_image_model`,
`toast_console_changes`, `tab_side`, `mode` (override), `show_dashboard_tab`, `speech_mode`, `cost_per_m_in`, `cost_per_m_out`,
`cost_currency`, `notify_in_app`, `notify_webpush`, `notify_mail`.

## 3. Configurations — future (with implementation path)

| # | Key | Type / default | Purpose & why it fits | Prio | Implementation |
|---|---|---|---|---|---|
| C1 | `admin_gate` / `canManageDevboard()` | string\|null | who may edit global settings, context, themes, skills (security remediation #1) | **P1** | middleware/check in SettingsPage, ContextPage, theme install; hide links; tests; README |
| C2 | `mode_lock` | bool, `false` (`DEVBOARD_MODE_LOCK`) | forbid the `app.mode` override from the UI (e.g. production) | P1 | `Mode::current()` ignores the setting when locked; hide the select; test |
| C3 | `storage_path` (base dir for `skills_file`, `agent_status_file`, context export cache) | path | one place for package runtime files | P3 | derive the two existing paths from it, keep overrides |
| C4 | `stats.price_list` defaults | array | ship default prices per model so `/stats` shows costs out of the box | P3 | seed `cost_per_m_*` from config when settings are empty |
| C5 | `locale` | string\|null | force the package locale independently of the app | P3 | `setLocale` in the service provider when set |
| C6 | `plan.provider` / `plan.model` | string\|null | which AI provider/model builds plans (today: SDK default) | P2 | pass to `PlanBuilder::prompt(provider:, model:)`; setting override (see S3) |
| C7 | `transcription.provider` / `model` | string\|null | provider for speech-to-text (today: `ai.default_for_transcription`) | P2 | pass to `Transcription::generate()` |
| C8 | `theme_packs_dir` / `allow_theme_upload` | path / bool | where zip themes live; disable uploads entirely on hardened installs | P2 | `ThemeStore` reads the dir; hide upload when disabled |
| C9 | `push.allowed_hosts` | array | allow-list of Web Push endpoints (security remediation #3) | P1 | validate in `PushSubscriptionController::store` |
| C10 | `rate_limits` (transcribe/test/push) | array of `throttle:` strings | per-install tuning of the expensive endpoints | P2 | apply in `routes/web.php` |
| C11 | `context.targets` | array, `['CLAUDE.md','AGENTS.md']` | which instruction files the sync writes (today only in the host script) | P3 | expose via `devboard:context export --target`; doc |
| C12 | `agent_status.stale_minutes` | int, 15 | staleness threshold of `/agents` | P3 | constant → config |

## 4. Settings — future (with implementation path)

| # | Group.key | Type / default | Purpose & why it fits | Prio | Implementation |
|---|---|---|---|---|---|
| S1 | `app.ai_plan_provider` / `app.ai_plan_model` | select/text | choose the model that builds plans (cost/quality) | P2 | fields + migration + `Plan::tasks()` passes them |
| S2 | `app.speech_provider` / `app.speech_model` | select/text | provider/model for transcription | P2 | fields + `TranscribeController` |
| S3 | `agent.max_parallel` | int, 2 | cap for multitasking mode (how many 🟢 at once) | P2 | printed in the settings line; agent rule |
| S4 | `agent.working_hours` | time range | the agent should not start new tasks outside the window | P3 | printed by `check`; agent rule |
| S5 | `agent.auto_pause_on_usage` | int %, 0 | pause plans when the agent's weekly usage (`/agents`) exceeds N% | P2 | `AgentStatus` hook → `plan_paused`; toast/notification |
| S6 | `app.notify_on_take` | bool, false | notification when the agent takes a task (user asked for done/question only so far) | P3 | `Notify::taken()` + `TodoTaken` notification |
| S7 | `app.digest_time` + `app.daily_digest` | time/bool | the **app** (not the agent) sends the evening summary (bell/push/mail) | P2 | scheduled command `devboard:digest` |
| S8 | `app.history_retention_days` | int, 0 | prune old completed+archived tasks (privacy/size) | P3 | extend `devboard:auto-archive` |
| S9 | `app.default_plan_length` | int, 3–12 | hint for `PlanBuilder` (number of tasks) | P3 | prompt parameter |
| S10 | `app.stats_default_period` | int days | default period of `/stats` | P3 | `StatsPage::mount` |
| S11 | `app.language` | select | UI locale per install (it/en) | P3 | middleware `setLocale` |
| S12 | `optimization.check_output_language` | select | language of `devboard:check` output (today English) | P3 | lang files for the command |

Out of scope on purpose: per-user settings (spatie settings are global; would need a different store), secrets in settings
(API keys stay in `.env`).
