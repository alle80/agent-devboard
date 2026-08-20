# Contributing

Issues and pull requests are welcome at
[github.com/alle80/griglia](https://github.com/alle80/griglia).

## Before opening a pull request

```bash
cd packages/griglia && composer update && vendor/bin/phpunit
```

The suite (orchestra/testbench, in-memory SQLite) covers migrations, per-user scoping, the Livewire
components, `griglia:check` / `griglia:watch`, the theme registry and zip packs, translation parity and the
broadcast event. GitHub Actions runs it on PHP 8.3 and 8.4.

## What a change should carry

- **Tests** for the behaviour you add or fix.
- **Translations**: strings live in `resources/lang/en` (base) and `resources/lang/it`; a test checks that
  the two files stay in sync. Never hardcode the agent's name — use `:agent`.
- **Docs**: if the change is visible to users, update the matching page in `docs/`.
- **CHANGELOG.md**: one entry under *Unreleased*, in the Keep a Changelog format.

## Style

Follow the surrounding code: Laravel conventions, no new dependency without a reason, UI built with the
package's icon set and theme variables rather than one-off markup.

## Security

Please do not open a public issue for a vulnerability — see [Security](../operations/security.md).
