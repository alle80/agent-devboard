# Configuration & settings

Two layers, on purpose:

| | **Configuration** | **Settings** |
|---|---|---|
| Where | `config/griglia.php`, `.env` | `/settings`, stored in the database |
| Who decides | the developer who installs the package | whoever uses the board |
| When it changes | at deploy (`config:cache`) | at run time, immediately |
| What it covers | routes, models, disks, modes, gates, integrations | how the agent works, token saving, board behaviour |

```bash
php artisan vendor:publish --tag=griglia-config     # config/griglia.php
php artisan vendor:publish --tag=griglia-views      # override the Blade views
php artisan vendor:publish --tag=griglia-lang       # translations (en, it)
php artisan vendor:publish --tag=griglia-agents     # AGENTS.md, the rules for the agent
```

## Settings the agent reads

The `agent` and `optimization` groups are not decoration: `griglia:check` prints them at the top of its
output and the agent is expected to follow them — commit policy, autonomy, notifications, one task at a
time or several, terse mode. Change them from the page and the next `griglia:check` obeys.

## The language of the board

The board speaks the languages it is translated into — English (base) and Italian — and the **App** group of
`/settings` opens with the choice:

- **As in the application (`EN`)** — the default: the board follows `config('app.locale')`, so a host
  application that sets the locale by itself (a `SetLocale` of its own, a per-user preference) keeps
  deciding.
- **English**, **Italiano**, … — one entry per language folder found in the package's `resources/lang` and in
  the published `lang/vendor/griglia`: publish your own translations and they appear here.

The choice applies to every board page and to the Livewire requests behind modals and saves, dates included
(«3 hours ago»). It does not touch the console: `griglia:check` keeps talking to the agent in English.

Adding a language is a folder next to `en` and `it` — see
[Translations](../contributing/translations.md).

## The full inventory

Generated from the code, so it never lags behind:

- [Configuration file](../reference/config.md) — every key, its environment variable and its default.
- [Settings](../reference/settings.md) — every option of the three groups, with the help text of the page.
- [Settings backlog](../reference/config-and-settings.md) — what is deliberately not there yet.

Access, administrators and the local mode have their own page: [Access & modes](access.md).
