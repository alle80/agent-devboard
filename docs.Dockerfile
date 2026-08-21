# Toolchain of the documentation site, for whoever has no Python at hand: the official Material image
# plus the extra plugins of requirements-docs.txt (the bilingual site needs mkdocs-static-i18n, which
# the official image does not ship). Built on the fly by `php artisan griglia:docs-build --docker`.
FROM squidfunk/mkdocs-material:9

COPY requirements-docs.txt /tmp/requirements-docs.txt
RUN pip install --no-cache-dir -r /tmp/requirements-docs.txt
