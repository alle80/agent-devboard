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
vendor/bin/phpunit                 # testbench, sqlite in memory
vendor/bin/testbench serve         # a bare Laravel app with the package mounted
npm install && npm run build       # precompiled assets → public/build
php artisan griglia:docs-build     # the documentation site
```

The suite covers migrations, per-user scoping, the Livewire components, `griglia:check` / `griglia:watch`,
the theme registry and zip packs, translation parity between `en` and `it`, and the broadcast event.
GitHub Actions runs it on PHP 8.3 and 8.4.

## Releasing

Semantic versioning; every change goes in `CHANGELOG.md` (Keep a Changelog, with a **Security** section
when relevant). A `vX.Y.Z` tag on GitHub is what Packagist publishes — so the tag is the release. Rebuild
the precompiled assets before tagging when the CSS/JS or the views changed.

See also [Contributing](contributing.md) and [Building this site](docs-site.md).
