# Upgrading

```bash
composer update alle80/griglia
php artisan migrate                                    # migrations are idempotent
php artisan vendor:publish --tag=griglia-assets --force # only in precompiled mode
```

## Versioning

The package follows [semantic versioning](https://semver.org). While it is on `0.x`, the **minor** number
is where breaking changes may appear: pin what you are comfortable with (`^0.45.0`) and read the
[CHANGELOG](https://github.com/alle80/griglia/blob/master/CHANGELOG.md) before bumping it — every release
documents what changed and what to do about it.

## After an upgrade

- **Published views** (`vendor:publish --tag=griglia-views`) do not update themselves: compare them with
  the package sources when a release touches the UI.
- **Precompiled assets** must be republished with `--force`, otherwise the browser keeps the old build.
- **Settings** get their new defaults from the settings migrations, so run `migrate` before using the
  new options.
- If a change does not show up, clear the caches: `php artisan config:clear && php artisan view:clear`.
