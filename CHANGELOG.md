# Changelog

All notable changes to `alle80/griglia` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.85.1] - 2026-08-22

### Fixed
- **`griglia:check --worker-json` printed the stalled-plan warning after the JSON document**, so the persistent
  worker failed to parse the board on every poll («Extra data») as soon as a plan had work left but nothing
  open: no session was dispatched and finished sessions were never reaped. The warning stays in the human
  output, as it already did for `--json` (task 507).
- The persistent worker reaps finished sessions before asking the board, reads the JSON document even when
  something follows it, and logs when a session ends (task 507).

### Added
- **The persistent worker updates itself without interrupting the running sessions** (task 507): when its
  script changes on disk it re-executes itself in place (same PID and lock, sessions handed over with
  `--adopt`), and `SIGHUP` drains it — no new session starts, it exits when the running ones end and the
  service manager restarts it with the current environment
  (`systemctl --user kill --signal=SIGHUP --kill-whom=main griglia-agent-worker@<key>.service`). Until now a
  code or environment change needed a `systemctl restart` that killed the sessions in progress.

## [0.85.0] - 2026-08-22

### Added
- **Question level: how many questions the agent asks before it really starts a task.** The `autonomy` setting
  becomes a five-step scale — autonomous agent, a few essential doubts, ask questions, ask many questions,
  paranoid — each with its own rules. `/settings` previews the context block of the chosen level and, on save,
  writes it into the agent context (`/context` → the generated instruction files) as a block *generated from
  Settings* (`context_blocks.key`, read-only on the page, kept across `griglia:context import`);
  `griglia:check` prints the same rules under the settings line (`❓ question level`) (task 499).

### Changed
- `agent.autonomy` values: `decide` is migrated to `autonomous`, `ask` keeps its name; `essential`, `many` and
  `paranoid` are new.

## [0.84.0] - 2026-08-22

### Added
- **Questions can offer one-tap closed answers without losing open input.** Agents pair repeated `--q` and
  `--choices="First|Second"` options; the modal shows selectable answers and always keeps free text plus
  speech-to-text available (task 481).

## [0.83.1] - 2026-08-22

### Fixed
- **Persistent workers now execute multitasking instead of only describing it.** In `multitasking` mode a worker
  runs up to `GRIGLIA_WORKER_MAX_PARALLEL` independent agent sessions (default 2), while `ordered` remains
  strictly serial. Each task is tracked and stopped independently; the board exposes scheduling mode through
  the machine-readable worker contract (task 501).

## [0.83.0] - 2026-08-22

### Added
- **Configurable response tone and length.** The agent settings now distinguish the task comment detail from
  user-facing communication, with clear, technical or conversational tones and concise, balanced or detailed
  response lengths. The default keeps technical accuracy while making jargon and formatting easier to follow
  (task 497).
## [0.82.0] - 2026-08-22

### Added
- **Usage-limit alerts for coding agents.** The host collector now reads Codex CLI rate-limit telemetry in
  local rollouts as well as Claude usage, and the board sends one notification when a working agent's window
  reaches 100%; a reset re-arms the next alert (task 490).

## [0.81.4] - 2026-08-22

### Fixed
- **The note editor now stays open at its content height during autosave.** Native content sizing makes the
  textarea independent from Livewire DOM morphs, with the JavaScript resize retained only as a fallback for
  older browsers (task 494).

## [0.81.3] - 2026-08-22

### Fixed
- **The note editor no longer collapses during autosave.** After each background save the textarea now grows
  again to fit its content, without showing the redundant «Saved» indicator that interrupted editing (task 488).
## [0.81.2] - 2026-08-22

### Fixed
- **Working tasks keep their agent badge visible.** In multi-agent boards, taking a task used to hide the agent
  selector entirely from both the row and modal. The effective agent name now remains visible in both places as
  a read-only badge while the task is working; it becomes editable again after work stops (task 482).
- **`griglia:check --take` no longer undoes a stop.** A progress update now refuses a task stopped by the user
  until it is open to work again; `--force` remains the deliberate override (task 477).
- **`griglia:check --done` leaves no open question behind.** Closing a task now clears both the question and
  open-to-work flags, matching closure from the modal (task 477).


## [0.81.1] - 2026-08-22

### Changed
- **The autosave undo button is now labelled «Cancel».** The shorter label replaces «Back to the previous
  version» in title and notes editors while preserving the same one-step restore behaviour (task 480).

## [0.81.0] - 2026-08-21

### Added
- **The worker chooses model and reasoning effort.** `griglia-agent-worker.py` accepts `--model`/`--effort`
  (env `GRIGLIA_WORKER_MODEL`, `GRIGLIA_WORKER_EFFORT`): the `claude` driver appends `--model`/`--effort`, the
  `codex` driver `--model` and `-c model_reasoning_effort="…"`, and the custom driver gets the new `{model}` and
  `{effort}` placeholders. Without them nothing changes — each CLI keeps its own default. Documented in
  «Persistent workers», EN + IT (task 475).

### Fixed
- **Worker documentation: the Claude driver's real permission mode.** «Persistent workers» announced
  `claude -p --permission-mode acceptEdits`, while the script has used `bypassPermissions` for a while (task 475).

## [0.80.3] - 2026-08-21

### Fixed
- **`griglia:docs-build`: half a toolchain now says so.** With `mkdocs` and Material installed but
  `mkdocs-static-i18n` missing, the command died on MkDocs' own `The "i18n" plugin is not installed`, which
  says nothing about how to repair it. The `python3 -m mkdocs` probe now also requires `mkdocs_static_i18n`,
  and a build that stops on a missing plugin prints the line that fixes it (`pip install -r
  requirements-docs.txt`, or `--docker`). Documented in «Building this documentation site», EN + IT (task 457).

## [0.80.2] - 2026-08-21

### Fixed
- **Leftover `devboard` names in the code.** The three warning logs (`Notify`, `Plan`, `SettingsPage`) were
  still prefixed `devboard:` instead of `griglia:`, the `TodoList` listeners PHPDoc named the config key
  `devboard.broadcast_channel` (it is `griglia.broadcast_channel`), the test push notification used the tag
  `devboard-test` and the consolidated migration header still called the package `agent-devboard` (task 456).

## [0.80.1] - 2026-08-21

### Added
- **Documentation: the `TodoChanged` payload** — the events reference described the channel, its
  authorisation and `Event::listen()`, but not what actually reaches the client. It now lists every field sent
  by `broadcastWith()` (`checklist_id`, `todo_id`, `title`, `state`, `source`, `deleted`, `state_changed`) with
  the possible values of `state` (`done`, `question`, `working`, `otw`, `waiting`), and shows how to listen
  with Echo in both modes and from a Livewire component via `Mode::echoListener()` (EN + IT, task 454).

### Fixed
- **`griglia:docs-generate`: one description per config key.** The comment above a block of keys was reused
  for every key of that block, so `agents` and `agent_key` — and `assets`, `vite_entries`, `assets_url` —
  read as if they did the same thing. A comment now describes only the key right below it, `config/griglia.php`
  gives each of those keys its own, and a key left with no comment is reported as a warning (an error under
  `griglia:docs-build --strict`). Reference pages regenerated, EN + IT (task 455).

## [0.80.0] - 2026-08-21

### Added
- The board search can now span every active list owned by the user. The optional **All lists** scope keeps
  the normal list-local search as the default and labels cross-list results with their source list (task 459).

### Changed
- Persistent workers now scope their duplicate-process lock by repository as well as agent, so the same agent
  can serve several Griglia applications on one computer. The systemd template accepts project-specific env
  overrides and the worker guide documents the required one-worker-per-application setup (task 458).

### Fixed
- **Documentation: the board is not a PWA** — the feature overview claimed the board was «installable as a
  PWA», but the package ships no web app manifest and `/griglia-sw.js` only handles push and
  `notificationclick`: no `fetch` handler, no offline cache. The Mobile row (EN + IT) now describes what is
  really there — a layout made for a thumb, camera attachments, Web Push — and links straight to the
  *Mobile* section of «Using the board» (task 453).
- **Documentation: the desktop dashboard has prose** — `/dashboard` (`griglia.dashboard_route`), the
  `DashboardTodoList` component, the slide-out side tab and its two settings (`show_dashboard_tab`,
  `tab_side`) only existed in the generated reference tables, and the README's route list did not mention
  the route at all. «Using the board» (EN + IT) now has a *Desktop: the dashboard* section describing the
  wider view, the theme fallback, the resizable tab and how to switch it off; the feature overview and the
  README route list link to it. A test pins the two tab settings (task 452).
- **Documentation: `notify_on_done` / `notify_on_question` govern the board's notifications too** — they read
  as instructions for the agent alone, while `Notify::todoCompleted()` / `Notify::questionAsked()` also use
  them to decide whether the bell, the Web Push and the mail go out, and the notifications page claimed the
  two layers were «independent». The help of the two settings (EN + IT, hence the generated settings
  reference) now says it is one switch for both layers, and the tip of the notifications page (EN + IT) says
  which code reads it. Behaviour unchanged; a test pins the question side of it (task 451).
- **`griglia.default_list_name` is alive again** — the config key was documented but dead: the first list of a
  new user always took its name from `griglia::t.default_list`, so changing the key did nothing.
  `Checklist::defaultName()` now prefers the configured name and falls back to the translation when the key is
  empty, which is the new default (`GRIGLIA_DEFAULT_LIST_NAME`): both behaviours keep working, and a fresh
  installation can name its first list — the agent list, for instance — before the first visit. The config
  comment, the generated config reference and the Quickstart (EN + IT) say so (task 450).
- **Documentation: the Quickstart explains how to create the agent list** — it claimed the board hands you a
  list already named `dev`. It does not: the first visit creates «My list» / «La mia lista», so
  `griglia:check`, the very first command an agent runs, stops with `No list named "dev"`. Step 1 of the
  Quickstart (EN + IT) now names the list you actually get and gives the two ways to get an agent list —
  rename it from the lists menu, or point `GRIGLIA_AGENT_LIST` / `griglia.agent_list` at a list you already
  have (plus `config:clear`). The installation page says the same thing and links to the step (task 449).
- **Documentation: the user-model hooks are documented under their current names** — `canAccessGriglia()`
  and `canManageGriglia()`. Since v0.34.0 the code looks for those first and only falls back to the
  pre-rename `canAccessDevboard()` / `canManageDevboard()`, but README, `SECURITY.md`, the access,
  installation, security and config-reference pages (EN + IT) still taught the old names, so a new
  installation would write the wrong method. The `config/griglia.php` comments and the Italian reference
  translations were corrected too (the generated reference pages come from there), and the example Gate
  ability is now `'access-griglia'`. `AdminTest` and `ModeTest` now cover the current names, the fallback
  and the precedence between them (task 448).

## [0.79.3] - 2026-08-21

### Fixed
- Working tasks are now read-only in both the list and task modal. Stop a task first to edit its title,
  note, sub-tasks, skills or assignment, move it, archive it, delete it, or mark it completed (task 446).

## [0.79.2] - 2026-08-21
### Fixed
- **The agent select in the modal header no longer gets clipped: it has a row of its own, aligned left
  (task 440).** Squeezed among the header icons the label («Default (Claude Code)») was cut mid-word, on a
  phone above all, where it was capped at `8.5rem`. The select now sits on a full-width row under the
  commands (`.modal-cmds-agent`), so on a phone the header stacks in three rows — state with ‹ 3/7 › and
  the close button, the agent, then move/archive/delete.

## [0.79.1] - 2026-08-21
### Fixed
- Closing an editor with a click outside no longer depends on the promise returned by `$wire.set()`: the
  field is flushed and `finish*()` is called in the same tick, so Livewire pools them into one round trip
  (updates first, then the call) whatever `set()` returns.

## [0.79.0] - 2026-08-21
### Changed
- **No more «Save» and «Cancel» on the title and on the note: what is written is what is stored (task 438).**
  Both fields already saved themselves while you type (task 433), so the two buttons only added noise — and
  «Cancel» silently threw away text that had been saved for minutes. The editor now closes with `Enter` (the
  title), `Esc` or a click outside it, and as soon as the text differs from the starting one a **back to the
  previous version** button (↩, new `undo` icon) appears next to the «Saved» flag: it restores the value the
  field had when the editor was opened and stays inside the editor. Sub-tasks, which are not saved live, keep
  their ✓ and ✕.
- Livewire methods renamed accordingly: `TodoList::saveEdit()` → `finishEdit()` and `cancelEdit()` →
  `revertEdit()`; `IngredientModal::saveTitle()/saveNotes()` → `finishTitle()/finishNotes()` and
  `cancelTitle()/cancelNotes()` → `revertTitle()/revertNotes()`. Custom views that call them must be updated.
- Translations: new `revert` and `msg.reverted`, `autosave_hint` also mentions how to close the editor; the
  now unused `save_title`, `msg.renamed` and `msg.note_saved` are gone (closing an editor no longer toasts:
  with a click outside it would fire at every stray click).

## [0.78.1] - 2026-08-21
### Fixed
- **The `/settings` selects (and the number, text and time fields) save again when you change them (task 436).**
  Only the switches were still saving: with Livewire 4 the `wire:model.change` modifier alone updates the value
  in the browser without sending it to the server, so `updatedValues()` never ran and the old value stayed in
  the database — you changed «Git» to «commit on main», left the page and found «branch + PR» again. The fields
  are now bound with `wire:model.live.change`.

## [0.78.0] - 2026-08-21
### Changed
- **The title and the note of a task save themselves while you type (task 433).** No more «Save» button to
  remember: the field sends what you wrote after a short pause (600 ms for a title, 800 ms for the note) and
  when it loses the focus, and a discreet «Saved» flag appears next to it. The ✓ / **Save** button now only
  closes the editor, and since nothing is left unsaved, **Cancel** (or `Esc`) puts back the text that was
  there when the editing started. It works in the modal (title and note) and in the inline rename of a row.

## [0.77.1] - 2026-08-21
### Fixed
- **The agent selector appeared twice on every task row.** When the selector moved to a line of its own
  under the title the old copy, between the row commands, came back in the released package: a release cut
  from a tree that did not have the move re-added it next to the new one. Only the line under the title is
  left.

## [0.77.0] - 2026-08-21
### Added
- **The language of the board is chosen in `/settings` (task 432).** The App group opens with «Board
  language»: *as in the application* (the `app.locale` config, i.e. `APP_LOCALE`), English or Italiano —
  the languages the board is translated into, read from `resources/lang` (the published
  `lang/vendor/griglia` counts too, so a host application that adds a language sees it in the list).
  The choice is applied by the new `SetLocale` middleware to every board page and, being a persistent
  Livewire middleware, to the `/livewire/update` requests of modals and saves; Carbon follows, so «3 hours
  ago» is translated as well. Choosing a language redraws `/settings` already translated.
- `Alle80\Griglia\Support\Locale` — available languages, their names, the options of the selector and
  `apply()`. With no choice («as in the application») it touches nothing: a host application that sets the
  locale by itself keeps deciding.

## [0.76.0] - 2026-08-21
### Fixed
- **Speech to text lost entire dictations without saying a word (task 431).** The recorder lived inside the
  Alpine component of the mic button: any Livewire re-render of the page — a broadcast from another device,
  the agent updating a task while you dictate a new one — morphed the DOM, destroyed the component and left
  a `MediaRecorder` running that nobody would ever stop, upload or transcribe. Five minutes of talking, no
  text and no error. The dictation session now lives in the module, outside Alpine: the button re-adopts it
  after every re-render and keeps the resolver of the target field fresh, so the transcript is written into
  the field that is on the page *now* and not into a detached node. Other silent deaths fixed with it: an
  empty recording used to `return` without a word (now it says so), a failed upload threw the audio away
  (now it is kept and retried — by tapping the mic, or by itself when you come back to the tab), an expired
  session was reported as a generic failure, and errors erased themselves after four seconds.
- Transcribing a long recording no longer times out at 90 seconds: over 2 MB of audio the call gets 180.

### Added
- The mic button shows the **elapsed time** while it records, turns amber when the microphone has been
  hearing **nothing** for twelve seconds, and keeps the error visible until you act on it. A transcript that
  finds no field to write into (modal closed mid-transcription) is kept and inserted when the field comes
  back; leaving the page mid-dictation asks for confirmation.
- `griglia.speech_max_seconds` (`GRIGLIA_SPEECH_MAX_SECONDS`, default 300, `0` = no limit): a dictation is
  closed and transcribed when it reaches the limit, instead of growing until the upload or the provider
  refuses it.
- `Alle80\Griglia\Support\Speech::frontend()` builds `window.GRIGLIA_SPEECH` (mode, endpoint, limit and
  every label the button needs): the JS reports failures on its own and cannot read the translations.
- Translations `mic_retry|empty|silent|denied|lost|expired|kept|recovered|limit`; CSS `.db-mic-warn`,
  `.db-mic-time`. Failed transcriptions are logged with size and mime, to tell a provider failure from a
  recording the browser botched.

## [0.75.0] - 2026-08-21

### Added
- **The documentation site is bilingual.** English stays the base language and Italian sits next to it as
  `page.it.md` (`mkdocs-static-i18n`, suffix structure): English at `/`, Italian at `/it/`, a language
  switcher in the header that lands on the same page, an Italian navigation, and a fallback to English for
  any page that has no translation yet. Every page of the site is translated.
- `docs/contributing/translations.md` — how a translated page works, how to write one (links, anchors, front
  matter), how the generated pages get their Italian, and how to add a third language.
- `resources/docs/reference.it.php` — Italian catalogue of the strings the reference pages take from the
  code (command and option descriptions, comments of `config/griglia.php`), keyed by the English source.
- `docs.Dockerfile` — the documentation toolchain as an image (Material plus the plugins of
  `requirements-docs.txt`), built on the fly by `griglia:docs-build --docker`: the official
  `squidfunk/mkdocs-material` image does not ship the i18n plugin.
- Tests: the Italian reference pages, the pages that must not change with the locale of whoever generates
  them, every code string being translated, and every English page having an Italian counterpart that is not
  a copy of it.

### Changed
- `griglia:docs-generate` writes each reference page in both languages (`commands.md` and `commands.it.md`,
  …). The settings page needs no catalogue: its labels and help come from the `it` translations of
  `/settings`. Strings with no translation stay in English and are listed at the end of the run.
- `griglia:docs-build --docker` now passes `--strict` when asked to, instead of silently dropping it, and
  builds the toolchain image before running.
- The `navigation.instant` theme feature is off: the i18n language switcher cannot point at the same page in
  the other language while instant loading is on.
- `requirements-docs.txt` gained `mkdocs-static-i18n`; the install hints of the troubleshooting page and of
  `griglia:docs-build` now point at the file instead of `pip install mkdocs-material`.

## [0.74.3] - 2026-08-21
### Changed
- **The agent selector of a task row now sits on a line of its own, under the title.** Squeezed between the
  row commands it crowded the icons and its label was cut after a few characters; on its own line it has room
  for the full agent name (up to `12rem`) and the row icons keep their space. The gesture does not change:
  picking an agent assigns the task, the empty option gives it back to the list default, and the name stays
  visible either way.

## [0.74.2] - 2026-08-21
### Changed
- **The task modal header now uses the whole bar.** Everything was pushed against the right edge — half the
  title bar empty, the agent name cut mid-word (`Predefinito (Claude Co…`) — because the command block
  carried `ml-auto`. State badge and ‹ `3/7` › now sit on the left edge, agent/move/archive/delete/close on
  the right, and from `md` — where the panel is wide enough — the state badge shows its name (`Working`,
  `Open to work`…) and the agent select fits its full label. Narrower than that nothing grows: the icon
  speaks for the state and, on a phone, the two-line layout of 0.69.0 is untouched.

## [0.74.1] - 2026-08-21
### Fixed
- Show the done state icon on completed rows instead of retaining their previous open-to-work icon.
## [0.74.0] - 2026-08-21
### Added
- Agent selector on the task row of the list, not only in the modal header: the same dropdown doubles as the
  agent badge, so the name of the agent that will take the task is always visible — also when the task simply
  inherits the default agent of the list.
## [0.73.3] - 2026-08-21
### Fixed
- Completed-task action buttons now use a lighter grey and stronger opacity, instead of becoming almost
  invisible when their own dimming was compounded by the completed row's opacity.
## [0.73.2] - 2026-08-21
### Fixed
- Render single newlines in Markdown notes and agent answers as HTML `<br>` elements.

## [0.73.1] - 2026-08-21

### Fixed
- **v0.73.0 was published from an incomplete tree — use this one instead.** While the release was running,
  another agent checked out a different branch in the shared working copy, so the files copied to the mirror
  were the ones from before the fix: `griglia:check` died with `Undefined variable $me` (the multi-agent
  guard kept its call sites but lost its definitions), the coloured attention border went back to its
  pre-0.72.0 shape and the outcome chip removed in 0.72.0 came back. This release ships the source the
  test suite is green on.

## [0.73.0] - 2026-08-21
### Added
- **Two agents on the same board stay out of each other's way.** With several agents configured, every task
  already belonged to one of them, but `--take`, `--done` and `--ask` acted on any id: a stale id in a prompt,
  or a worker started with the wrong key, could steal, close or pause work another agent was doing. The three
  actions now refuse a task owned by somebody else (`--force` is the deliberate way in), `griglia:check`
  prints a `🔒 busy elsewhere` line listing what the other agents have in progress, and the "new since the
  last check" 🆕 baseline is stored per agent key instead of being consumed by whoever checks first. The new
  documentation page *Two agents at once* maps what is left outside the board — checkouts, branches, asset
  builds, migrations, releases, container-wide commands — with the rule for each.
- **The resume chain is kept whole.** A task born from «resume» can itself be resumed: until now only the
  last step travelled with it, so the agent lost the request that started the thread. `Todo::resumeChain()`
  walks the whole ancestry (cycle-safe, 20 steps at most) and it is used everywhere: `griglia:check` prints
  every previous step newest first (`resumes «…»`, `2 steps back «…»`, …) with its note, agent answer and
  sub-tasks, `griglia:check --json` carries the same history in the new `resume_chain` field, and the task
  modal lists every step inside the (still collapsed) context box, with a `+N earlier` counter.

### Fixed
- Deleting a task in the middle of a resume chain no longer breaks it: the tasks resumed from it are
  re-linked to the step before, the way a plan hands its chain over.
- **The package test suite no longer fails on a view compiled from another branch.** The compiled Blade cache
  lives in the shared testbench storage: a template compiled from another checkout was reused whenever the
  source file was older than it, and the suite died on code that did not exist any more. Every run now starts
  from an empty compiled-views directory.

### Security
- Encode all four inline JavaScript runtime configuration objects with Blade's script-safe JSON encoder,
  preventing values containing HTML end tags, quotes or Unicode line separators from terminating the script.

## [0.72.0] - 2026-08-21

### Fixed

- **The coloured border of a task now really shows up in the list.** The row painted it only through the
  `.db-attention` / `.db-att-*` rules of this stylesheet, but a host app runs the package's *views* from
  `vendor/` while its CSS comes from a build of its own: when the two copies are on different versions the
  rules are missing — or are an older version of themselves — and the highlight is simply not there. That
  is why it looked broken three releases in a row (tasks 397, 402, 406). The row now writes the colour on
  itself (`Todo::attentionColor()`, inline `border-color`/`border-width`/`opacity`/`filter`/`outline`), so
  the border survives a stale stylesheet *and* the grey filter a theme puts on completed rows. The
  stylesheet keeps the pulse and the `--db-att` variable for themes.

### Changed

- The border is the only signal an unopened result gives: the **badge next to the title and the outcome chip
  in the modal are gone** (task 415 — they were never asked for). Screen readers still get the meaning from
  a hidden label, and the row carries it as a tooltip. Removed with them: the `.db-attention-badge`,
  `.db-unseen-badge` and `.db-outcome*` rules and the `outcome_alert` / `outcome_blocked` strings. New
  strings `result_question` / `result_question_hint` describe a row with open questions.

## [0.71.0] - 2026-08-21

### Changed
- **Attachment storage is private by default.** `attachments_disk` now defaults to Laravel's `local` disk and
  all internal fallbacks match it. Keep `GRIGLIA_ATTACHMENTS_VIA_CONTROLLER=true`; the owner-scoped controller
  is the only package route that serves these objects. The upgrade guide covers existing public files.

### Fixed
- Generated command reference no longer moves Artisan's inherited global inputs between commands depending on
  which command launched the generator, so `griglia:docs-generate --check` is deterministic.

## [0.70.2] - 2026-08-21

### Documentation
- Added a source-level security assessment covering dependencies, configuration, input/output handling,
  secrets, permissions and build/release practices, with evidence and prioritized remediations.

## [0.70.1] - 2026-08-21

### Fixed

- The highlight of a task that asks for attention now colours **the border of the row's own card** instead of
  drawing a separate ring outside it, and it is no longer washed out by the look a theme gives to completed
  rows: `.tl-done` also applies `--tl-done-filter`, which is `grayscale(1)` on the slate theme, so the green,
  yellow, red and violet all reached the eye grey — the highlight looked like it was not working at all.

## [0.70.0] - 2026-08-20

### Fixed

- Plain successful results now always use a green task outline. Previously `ok` inherited the active theme’s
  accent colour, which could make the outline yellow, cyan, or indistinguishable from the ordinary card border.

## [0.69.0] - 2026-08-20

### Fixed

- **The task modal header fits a phone.** On a narrow screen the whole command row was laid out on a single
  line and clipped at the right edge: the agent selector, move, archive, delete and even the close button
  ended up off screen, so the modal could only be dismissed with the browser's back gesture. The header is
  now two groups — `nav` (state badge, ‹ position ›) and `tools` (agent, move, archive, delete) — and below
  640px `nav` stays on the first line beside the close button while `tools` wraps to a second one, with
  36px touch targets. The agent selector truncates instead of pushing the row out, and the close button
  finally carries a `title`/`aria-label`.

## [0.68.0] - 2026-08-20

### Added

- Show an automatic, very short result summary below completed task titles; agents can refine it with `griglia:check --done --summary`.


## [0.67.0] - 2026-08-20

### Added
- **The row of a fresh result is outlined in the colour of its outcome.** A completed task the user has not
  opened yet was always highlighted the same way, so «done, nothing to check» and «done, but read this»
  looked identical. `griglia:check --done` now takes `--outcome=ok|alert|blocked` (default `ok`), stored in
  the new `todos.outcome` column: green outline for a plain result, yellow when it needs a look, red when
  something is in the way, violet while the agent is waiting for answers (`--ask`). Yellow and red add a
  badge next to the title and a chip in the modal, above the agent's answer. Opening the task clears the
  highlight as before, and a task the user closes has no outcome at all. `griglia:check` prints the flag on
  closed tasks it lists, and taking a task again clears the previous outcome.

### Changed
- The row highlight classes are now `db-attention` + `db-att-<level>` (`ok`, `alert`, `blocked`,
  `question`) and the badge is `db-attention-badge`; the colour comes from the `--db-att` variable. The old
  `db-unseen` / `db-unseen-badge` selectors keep working for themes that styled them.

## [0.66.0] - 2026-08-20

### Added
- **The whole host toolchain runs without Docker.** `sync-skills.py`, `sync-context.py`, `claude-tokens.py`
  and `agent-status.py` used to call `docker exec` unconditionally, so a persistent worker on the local
  transport could poll the board but not count tokens or synchronize context. They now share the worker's
  choice: `GRIGLIA_TRANSPORT=docker|local` (default `docker`) and `GRIGLIA_PHP` for the local executable,
  which runs Artisan from the project root. The worker falls back to the same variables when its own
  `GRIGLIA_WORKER_TRANSPORT` / `GRIGLIA_WORKER_PHP` / `GRIGLIA_WORKER_CONTAINER` are unset, so one
  machine-wide setting configures everything.

### Fixed

- `claude-tokens.py` ignored `GRIGLIA_CONTAINER` and always queried the container named `laravel-dev-app`.

## [0.65.0] - 2026-08-20

### Added
- The persistent worker can poll Artisan through Docker or directly through local PHP. Select the transport
  with `GRIGLIA_WORKER_TRANSPORT=docker|local`; local installations can override `GRIGLIA_WORKER_PHP`.
  Codex, Claude and custom agent drivers remain available with either transport.

## [0.64.0] - 2026-08-20

### Added
- **Persistent agent workers are now shipped and documented.** `griglia-scripts` publishes a host worker with
  built-in Codex CLI and Claude Code drivers, a shell-free custom argv driver for other agents, per-agent locks,
  retry and Stop handling, plus a portable systemd user-service template. The new guide covers installation,
  PATH configuration, lingering across logout/reboot, smoke testing and multi-agent instances.

## [0.63.0] - 2026-08-20

### Added
- `griglia:watch --agent=<key>` applies the same effective-agent scope as `griglia:check`, including task
  overrides and list defaults, so supervised multi-agent workers do not wake for each other's jobs.
- `griglia:watch --once` prints tasks that were already waiting on its first snapshot; use `--no-initial`
  for baseline-only monitoring. This makes one-shot polling safe across worker restarts.

## [0.62.0] - 2026-08-20

### Added
- **The host helpers ship with the package.** `sync-skills.py`, `sync-context.py`, `claude-tokens.py` and
  `agent-status.py` — the scripts that fill the skill catalogue, write the agent context back to CLAUDE.md /
  AGENTS.md, count the tokens of a task and read the agent's plan — used to live only in the origin repository,
  so the docs kept pointing at a repo you do not have. They are now part of the package and land in your project
  with `php artisan vendor:publish --tag=griglia-scripts`; they find the project root on their own (or take it
  from `GRIGLIA_PROJECT_ROOT`), so they also work run straight from `vendor/alle80/griglia/scripts`.
  New documentation page: «Host scripts».

## [0.61.0] - 2026-08-20

### Changed
- **Skills that belong to the right agent.** The skill catalogue now records, for every skill, which agents can
  actually invoke it (`agents` in the import JSON, filled by `scripts/sync-skills.py` from the folder it comes
  from), and the task modal only offers the ones the agent of that task really has: no more ticking a Claude Code
  built-in for a Codex CLI task. Skills with no `agents` — the shared `~/.agents/skills` folder, or catalogues
  imported before this release — stay available to everybody, and a skill already ticked remains visible so it can
  be removed.

## [0.60.0] - 2026-08-20

### Added
- **A reminder to clear the session when it gets heavy.** New ⚡ optimization setting, «suggest clearing the
  session» (thousands of tokens, default 400, 0 = never): past that weight the agent tells you to run
  `/clear`, which only you can type. The context is re-read at every turn, so a heavy session makes every
  single step more expensive — not just the long ones.

## [0.59.1] - 2026-08-20

### Fixed

- The dead-end warning of `griglia:check` shouted at plans nobody had started yet, which are not stuck —
  they are waiting for ▶. It now talks only about plans that were started (or paused) and have nothing the
  agent may take.

## [0.59.0] - 2026-08-20

### Added
- **Code blocks in notes and answers can be copied.** A button in the corner of every block copies it (with
  a «copied» confirmation), inline code copies itself with a click, and links inside a note open in a new
  tab. Those blocks hold commands and prompts meant to be pasted somewhere else — selecting them by hand in
  a modal was the wrong way to get them out.

## [0.58.0] - 2026-08-20

### Added
- **Walking through the tasks from inside the modal.** Two arrows next to the state badge (with the position
  in the list, `3/7`) open the previous and the next task, and the ← → keys do the same when you are not
  typing. Following a plan step by step no longer means closing the modal after every task.

## [0.57.0] - 2026-08-20

### Changed
- **On a resumed task, the old task's text starts collapsed.** It opened expanded, so the request you are
  writing today was pushed below a wall of what was asked the last time. It is one click away, with a
  chevron that turns — and `griglia:check` still prints it in full for the agent.

### Docs
- The pages about plans were still describing the checkbox in the lists dropdown, which moved to its own
  page three releases ago: Plans now documents `/plans/new` (goal, dictation, optional name, agent, draft)
  and `/plans/{list}/edit` (save the goal, or rebuild only the tasks nobody has started). The route list in
  the README gained `/plans/new`.

## [0.56.0] - 2026-08-20

### Changed
- **A closed task stays closed.** Unticking a completed task used to reopen it, and the agent could find in
  front of it something it had already answered. The checkbox and the state dot now refuse, and say what to
  do instead: **resume** (↻) creates a new task linked to the old one, which is the one way to carry on.
  `griglia:check --take` refuses a completed task for the same reason (it reopened it in 0.55.0).

## [0.55.0] - 2026-08-20

### Changed
- **A task with open questions is no longer a one-way street.** Its badge in the modal takes it back to
  waiting without answering (the questions stay recorded): until now the only way out of the question state
  was answering every question, even when they no longer made sense.
- **`--take` on a completed task reopens it.** The agent taking a task back left the row saying «done» while
  it was being worked on; the command now clears the completed state (and any stale question) and says so.

## [0.54.0] - 2026-08-20

### Fixed

- **No more dead ends where the agent waits and the board cannot let it through.**
  - Archiving or deleting a task of a plan used to leave the next one waiting for something that would
    never be completed. It now inherits the predecessor of the task that left — and opens right away when
    that one is already done.
  - A task assigned to an agent key that is not configured any more belonged to nobody: every agent
    filtered it out and it waited forever. Unknown keys now fall back to the default agent.
  - `griglia:check` warns when a plan still has work but nothing is open to work, and says how to get out
    (never in `--json`, which stays machine-readable).

### Changed
- **Reopening a completed task rolls the chain back.** Unticking a task of a plan puts the task it had
  opened back to waiting, unless the agent had already spent time on it — before, the agent could run ahead
  of the task you had just reopened.

## [0.53.0] - 2026-08-20

### Changed
- **Plan page, after a pass with a real browser at three widths.** The goal field now has a proper label
  (it was only a heading, so screen readers announced an unnamed textarea), the character count says what
  it counts and what the minimum is, «Cancel» is a real button instead of faint text, and the name field
  stops at a sensible width so the goal stays the biggest thing on the page.

## [0.52.1] - 2026-08-20

### Fixed

- The favicon links carry the file's date (`?v=…`): browsers keep a favicon for days, so after the logo
  changed the old one stayed in the tab even after a forced reload. A `32×32` PNG is declared next to the
  SVG for the browsers that ignore vector favicons.

## [0.52.0] - 2026-08-20

### Added
- **Editing a plan.** `/plans/{list}/edit`, reachable from the Plan bar: change the goal (and the name and
  agent of the list) without touching the tasks, or rebuild them — and only the ones nobody has started.
  Tasks already done, taken by the agent or waiting for an answer are never replaced, and the confirmation
  says how many will be.

### Changed
- **Nothing gets lost on the plan page.** What you type is kept as a draft, so leaving and coming back finds
  the text where you left it; cancelling asks first and then throws the draft away; and if the AI fails
  while building, the half-created list is removed instead of being left behind empty.

## [0.51.0] - 2026-08-20

### Changed
- **The plan form has moved out of the lists dropdown.** The checkbox and the four-row textarea are gone
  from the menu, which is back to doing one thing (a new list) plus a «New plan…» link. On the page the
  goal has the microphone next to it, a character count, and — when the install declares several agents —
  the agent of the plan, in its own section under the name.

### Docs
- **The site wears the board's own skin.** JetBrains Mono everywhere, the Slate palette (deep green
  background, `#33d17a` for headings and links, thin green borders with the 6px radius of the cards) in
  dark, its light counterpart on paper, and the header/footer in the colour of the board's chrome. The
  landing hero and the feature cards follow the same tokens.

## [0.50.0] - 2026-08-20

### Added
- **A page for building a plan** — `/plans/new`, reachable from the lists menu («New plan…»). Describing a
  goal used to mean typing a paragraph into a four-row textarea inside a dropdown that closed on any click
  outside, taking the text with it. Now there is room to write, the name of the list is optional (the first
  words of the goal become the name), Ctrl/⌘+Enter builds, and the wait for the AI has a page to live in.
  The page wears the theme skin like `/settings` and `/context`.

## [0.49.0] - 2026-08-20

### Changed
- **Installing takes three commands and no build step.** `assets` now defaults to **`precompiled`**: the
  CSS and JS shipped with the package are used as they are, and they are published under Laravel's own
  `laravel-assets` tag — which a default app republishes after every `composer update`, so an upgrade
  cannot leave a stale build behind. `composer require` + `migrate` + `storage:link` and the board works.
- The documentation now presents the two asset modes in that order: precompiled first (nothing to do),
  Vite second (for apps that want the board inside their own bundle).

### Upgrading
- **If your app bundles the package sources in its own Vite build, set `GRIGLIA_ASSETS=vite` in `.env`**
  (or `'assets' => 'vite'` in `config/griglia.php`) before updating: the default changed under you.
  Everyone else gets the precompiled build and needs nothing.

## [0.48.1] - 2026-08-20

### Changed
- **No stray icons in the documentation.** The site used emoji for the states, the microphone and the plan
  controls: they are not part of the visual language of the board (which has its own SVG icon set) and they
  render differently on every system. The states table shows the real icons, everything else says it in
  words. The `--ask` help text and the `agent_name` config comment lost their emoji too, so the generated
  reference pages stay clean.

## [0.48.0] - 2026-08-20

### Changed
- **The favicon is always the Griglia mark.** It used to be the icon of the current theme, so the browser
  tab showed the little Slate terminal (or whatever pack was installed) instead of the logo. The tab
  identifies the application, not the skin: theme icons stay where they belong, in the style menu. A 32px
  PNG is linked next to the SVG for the browsers that ignore SVG favicons.

## [0.47.3] - 2026-08-20

### Added
- **The documentation site is published on GitHub Pages** — <https://alle80.github.io/griglia/> — by
  `.github/workflows/docs.yml` at every push to `master` that touches the docs. The test workflow now also
  fails when the generated reference pages are stale (and prints the diff), and it finally runs on `master`
  instead of a `main` branch that does not exist.

### Changed
- `griglia:docs-generate` lists the options of a setting only when they come from the translations: the AI
  providers and the installed themes depend on the installation, so they cannot live in a page that must be
  identical everywhere.

### Docs
- Site polish: the state icons of the board are shown as the real SVGs instead of emoji, every page ends
  with its «see also» links, and the thin pages (security, development, configuration, themes, skills,
  plans) were rewritten as complete pages. `config/devboard.php` was still mentioned in one of them.

## [0.47.2] - 2026-08-20

### Changed
- `griglia:docs-generate --check` explains that it is meant for the package repository: inside a host app
  the settings page legitimately differs (it lists the AI providers installed there).

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

[Unreleased]: https://github.com/alle80/griglia/compare/v0.85.0...HEAD
[0.85.0]: https://github.com/alle80/griglia/compare/v0.84.0...v0.85.0
[0.82.0]: https://github.com/alle80/griglia/compare/v0.81.4...v0.82.0
[0.80.3]: https://github.com/alle80/griglia/compare/v0.80.2...v0.80.3
[0.80.2]: https://github.com/alle80/griglia/compare/v0.80.1...v0.80.2
[0.80.1]: https://github.com/alle80/griglia/compare/v0.80.0...v0.80.1
[0.80.0]: https://github.com/alle80/griglia/compare/v0.79.1...v0.80.0
[0.79.1]: https://github.com/alle80/griglia/compare/v0.79.0...v0.79.1
[0.79.0]: https://github.com/alle80/griglia/compare/v0.78.1...v0.79.0
[0.78.1]: https://github.com/alle80/griglia/compare/v0.78.0...v0.78.1
[0.78.0]: https://github.com/alle80/griglia/compare/v0.77.1...v0.78.0
[0.77.1]: https://github.com/alle80/griglia/compare/v0.77.0...v0.77.1
[0.77.0]: https://github.com/alle80/griglia/compare/v0.76.0...v0.77.0
[0.76.0]: https://github.com/alle80/griglia/compare/v0.75.0...v0.76.0
[0.75.0]: https://github.com/alle80/griglia/compare/v0.74.3...v0.75.0
[0.74.3]: https://github.com/alle80/griglia/compare/v0.74.2...v0.74.3
[0.74.2]: https://github.com/alle80/griglia/compare/v0.74.1...v0.74.2
[0.74.1]: https://github.com/alle80/griglia/compare/v0.74.0...v0.74.1
[0.74.0]: https://github.com/alle80/griglia/compare/v0.73.3...v0.74.0
[0.73.0]: https://github.com/alle80/griglia/compare/v0.72.0...v0.73.0
[0.72.0]: https://github.com/alle80/griglia/compare/v0.71.0...v0.72.0
[0.71.0]: https://github.com/alle80/griglia/compare/v0.70.2...v0.71.0
[0.70.2]: https://github.com/alle80/griglia/compare/v0.70.1...v0.70.2
[0.70.1]: https://github.com/alle80/griglia/compare/v0.70.0...v0.70.1
[0.70.0]: https://github.com/alle80/griglia/compare/v0.69.0...v0.70.0
[0.69.0]: https://github.com/alle80/griglia/compare/v0.68.0...v0.69.0
[0.68.0]: https://github.com/alle80/griglia/compare/v0.67.0...v0.68.0
[0.67.0]: https://github.com/alle80/griglia/compare/v0.66.0...v0.67.0
[0.66.0]: https://github.com/alle80/griglia/compare/v0.65.0...v0.66.0
[0.65.0]: https://github.com/alle80/griglia/compare/v0.64.0...v0.65.0
[0.64.0]: https://github.com/alle80/griglia/compare/v0.63.0...v0.64.0
[0.63.0]: https://github.com/alle80/griglia/compare/v0.62.0...v0.63.0
[0.62.0]: https://github.com/alle80/griglia/compare/v0.61.0...v0.62.0
[0.61.0]: https://github.com/alle80/griglia/compare/v0.60.0...v0.61.0
[0.60.0]: https://github.com/alle80/griglia/compare/v0.59.1...v0.60.0
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
