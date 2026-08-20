# Settings backlog and design notes

Two different things, kept apart on purpose:

- **Configuration** (`config/griglia.php`, env) — decided by the *developer* who installs the package: paths, routes,
  models, integrations. Read once per request, needs a deploy/`config:cache` to change, never edited from the UI.
- **Settings** (spatie/laravel-settings, `/settings`) — decided by the *user* at run time, stored in the DB, changed
  from the UI with immediate effect. Three groups: `agent` (how the agent works), `optimization` (token saving),
  `app` (behaviour of the board).

What exists today is generated from the code: see [Configuration file](config.md) and [Settings](settings.md).
This page is what is *not* there yet — the backlog, with the implementation path for each entry.

Template for every entry: **name** · type · default · what it does · priority (P1 high, P2 medium, P3 low) ·
implementation notes.

## Configurations — future (with implementation path)

| # | Key | Type / default | Purpose & why it fits | Prio | Implementation |
|---|---|---|---|---|---|
| C1 | `admin_gate` / `canManageDevboard()` | string\|null | who may edit global settings, context, themes, skills (security remediation #1) | **P1** | middleware/check in SettingsPage, ContextPage, theme install; hide links; tests; README |
| C2 | `mode_lock` | bool, `false` (`GRIGLIA_MODE_LOCK`) | forbid the `app.mode` override from the UI (e.g. production) | P1 | `Mode::current()` ignores the setting when locked; hide the select; test |
| C3 | `storage_path` (base dir for `skills_file`, `agent_status_file`, context export cache) | path | one place for package runtime files | P3 | derive the two existing paths from it, keep overrides |
| C4 | `stats.price_list` defaults | array | ship default prices per model so `/stats` shows costs out of the box | P3 | seed `cost_per_m_*` from config when settings are empty |
| C5 | `locale` | string\|null | force the package locale independently of the app | P3 | `setLocale` in the service provider when set |
| C6 | `plan.provider` / `plan.model` | string\|null | which AI provider/model builds plans (today: SDK default) | P2 | pass to `PlanBuilder::prompt(provider:, model:)`; setting override (see S3) |
| C7 | `transcription.provider` / `model` | string\|null | provider for speech-to-text (today: `ai.default_for_transcription`) | P2 | pass to `Transcription::generate()` |
| C8 | `theme_packs_dir` / `allow_theme_upload` | path / bool | where zip themes live; disable uploads entirely on hardened installs | P2 | `ThemeStore` reads the dir; hide upload when disabled |
| C9 | `push.allowed_hosts` | array | allow-list of Web Push endpoints (security remediation #3) | P1 | validate in `PushSubscriptionController::store` |
| C10 | `rate_limits` (transcribe/test/push) | array of `throttle:` strings | per-install tuning of the expensive endpoints | P2 | apply in `routes/web.php` |
| C11 | `context.targets` | array, `['CLAUDE.md','AGENTS.md']` | which instruction files the sync writes (today only in the host script) | P3 | expose via `griglia:context export --target`; doc |
| C12 | `agent_status.stale_minutes` | int, 15 | staleness threshold of `/agents` | P3 | constant → config |

## Settings — future (with implementation path)

| # | Group.key | Type / default | Purpose & why it fits | Prio | Implementation |
|---|---|---|---|---|---|
| S1 | `app.ai_plan_provider` / `app.ai_plan_model` | select/text | choose the model that builds plans (cost/quality) | P2 | fields + migration + `Plan::tasks()` passes them |
| S2 | `app.speech_provider` / `app.speech_model` | select/text | provider/model for transcription | P2 | fields + `TranscribeController` |
| S3 | `agent.max_parallel` | int, 2 | cap for multitasking mode (how many 🟢 at once) | P2 | printed in the settings line; agent rule |
| S4 | `agent.working_hours` | time range | the agent should not start new tasks outside the window | P3 | printed by `check`; agent rule |
| S5 | `agent.auto_pause_on_usage` | int %, 0 | pause plans when the agent's weekly usage (`/agents`) exceeds N% | P2 | `AgentStatus` hook → `plan_paused`; toast/notification |
| S6 | `app.notify_on_take` | bool, false | notification when the agent takes a task (user asked for done/question only so far) | P3 | `Notify::taken()` + `TodoTaken` notification |
| S7 | `app.digest_time` + `app.daily_digest` | time/bool | the **app** (not the agent) sends the evening summary (bell/push/mail) | P2 | scheduled command `devboard:digest` |
| S8 | `app.history_retention_days` | int, 0 | prune old completed+archived tasks (privacy/size) | P3 | extend `griglia:auto-archive` |
| S9 | `app.default_plan_length` | int, 3–12 | hint for `PlanBuilder` (number of tasks) | P3 | prompt parameter |
| S10 | `app.stats_default_period` | int days | default period of `/stats` | P3 | `StatsPage::mount` |
| S11 | `app.language` | select | UI locale per install (it/en) | P3 | middleware `setLocale` |
| S12 | `optimization.check_output_language` | select | language of `griglia:check` output (today English) | P3 | lang files for the command |

Out of scope on purpose: per-user settings (spatie settings are global; would need a different store), secrets in settings
(API keys stay in `.env`).
