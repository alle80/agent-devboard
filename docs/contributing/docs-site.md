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
