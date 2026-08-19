# Building this documentation site

The docs are plain Markdown in `docs/` with a `mkdocs.yml` (theme **Material for MkDocs**) at the package root.

## Prerequisites

```bash
pip install mkdocs-material          # Python 3.8+; brings mkdocs + the Material theme
```
or, without Python, the official Docker image `squidfunk/mkdocs-material`.

## Build

```bash
php artisan devboard:docs-build                    # → site/ (HTML)
php artisan devboard:docs-build --serve            # live preview on http://127.0.0.1:8000
php artisan devboard:docs-build --out=/var/www/docs
php artisan devboard:docs-build --docker           # uses the squidfunk/mkdocs-material image
```

The command runs `mkdocs build` (or the Docker image) from the package directory, reports a clear error when
MkDocs is missing (with the install hint) or when the build fails (stderr), and prints where the HTML went.
Equivalent without artisan: `mkdocs build` in the package root.
