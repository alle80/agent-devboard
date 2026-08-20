# Building this documentation site

The docs are plain Markdown in `docs/` with a `mkdocs.yml` (theme **Material for MkDocs**) at the package root.

## Prerequisites

```bash
pip install mkdocs-material          # Python 3.8+; brings mkdocs + the Material theme
```
or, without Python, the official Docker image `squidfunk/mkdocs-material`.

## Generated pages

Three pages of the Reference section are written by the package itself:

```bash
php artisan griglia:docs-generate            # → docs/reference/{commands,config,settings}.md
php artisan griglia:docs-generate --check    # fails when the committed pages are out of date (CI)
```

`griglia:docs-build` runs it before every build (`--no-generate` to skip), so the site always matches the
code. Do not edit those three files by hand.

`--check` is meant for the package repository (and its CI): run inside a host app the settings page
legitimately differs, because it lists the AI providers installed there.

## Build

```bash
php artisan griglia:docs-build                    # → site/ (HTML)
php artisan griglia:docs-build --serve            # live preview on http://127.0.0.1:8000
php artisan griglia:docs-build --out=/var/www/docs
php artisan griglia:docs-build --docker           # uses the squidfunk/mkdocs-material image
```

The command runs `mkdocs build` (or the Docker image) from the package directory, reports a clear error when
MkDocs is missing (with the install hint) or when the build fails (stderr), and prints where the HTML went.
Equivalent without artisan: `mkdocs build` in the package root.

## The whole loop, for an agent

Working on the package, the documentation is part of the change — not an afterthought:

1. **Write** the page in `docs/` (never the three generated ones: `reference/{commands,config,settings}.md`).
2. **Regenerate** what comes from the code, if you touched a command, a config key or a setting:
   `php artisan griglia:docs-generate` (from a host app) or `vendor/bin/testbench griglia:docs-generate`
   (inside the package repository).
3. **Validate**: `php artisan griglia:docs-build --strict` — warnings become errors, so a broken internal
   link or a page missing from the nav fails the build. `griglia:docs-generate --check` tells you whether
   the committed reference pages are stale; the CI runs it for you.
4. **Look at it**: `--serve` for a live preview, or build into a folder your web server already serves.
5. **Commit** `docs/`, `mkdocs.yml` and the regenerated pages together with the code change, and add the
   line to `CHANGELOG.md` — the changelog *is* a page of the site.

## Publishing

`.github/workflows/docs.yml` builds the site (`mkdocs build --strict`) and deploys it to **GitHub Pages** at
every push to `master` that touches `docs/`, `overrides/`, `mkdocs.yml` or `CHANGELOG.md`; it can also be
run by hand (*Run workflow*). The site needs no PHP to build, because the generated pages are committed —
`tests.yml` is what fails when they are out of date.

The repository must have **Settings → Pages → Source: GitHub Actions** enabled once, and `site_url` in
`mkdocs.yml` must match the published address.
