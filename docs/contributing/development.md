# Development

## The repository

```
config/         griglia.php (published to the host app)
database/       migrations (tables + settings defaults)
docs/           this site (see Building this site)
resources/      views, css/js sources, lang/{en,it}, sample theme pack
routes/         the package routes
src/            Livewire components, models, console commands, support classes
tests/          orchestra/testbench + phpunit
```

## Working on it

```bash
composer install
composer lint                      # Laravel Pint + Larastan level 5
composer test                      # testbench, sqlite in memory
vendor/bin/testbench serve         # a bare Laravel app with the package mounted
npm install && npm run build       # precompiled assets → public/build
php artisan griglia:docs-build     # the documentation site
```

The review lifecycle regression suite is in `tests/Feature/ReviewWorkflowTest.php`. It exercises both the legacy
completion path without a reviewer and the complete submit, approve, request-changes and resubmit paths, including
invalid state transitions. `tests/Feature/ReviewUiTest.php` covers assigning the optional reviewer in the task modal.

The suite covers migrations, per-user scoping, the Livewire components, `griglia:check` / `griglia:watch`,
the theme registry and zip packs, translation parity between `en` and `it`, and the broadcast event.
GitHub Actions runs it on PHP 8.3 and 8.4.

The `Todo`, `Checklist`, `Ingredient`, and `Question` models include package factories for focused tests:

```php
$list = Checklist::factory()->create();
$todo = Todo::factory()->for($list)->create();
$ingredient = Ingredient::factory()->for($todo)->create();
$question = Question::factory()->for($todo)->create();
```

The models resolve their package factory namespace directly, so no factory-name resolver is required in the host
application or in Testbench.

`composer lint` runs formatting checks first and then `vendor/bin/phpstan analyse` on `src/`. Larastan is configured
at level 5 without a baseline. The small, counted exception list in `phpstan-ignores.neon` documents framework
inference gaps individually; unmatched exceptions fail the analysis instead of silently becoming permanent debt.

## Releasing

Semantic versioning; every change goes in `CHANGELOG.md` (Keep a Changelog, with a **Security** section
when relevant). A `vX.Y.Z` tag on GitHub is what Packagist publishes — so the tag is the release. Rebuild
the precompiled assets before tagging when the CSS/JS or the views changed.

See also [Contributing](contributing.md) and [Building this site](docs-site.md).
