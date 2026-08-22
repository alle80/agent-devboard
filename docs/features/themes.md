# Themes

Generic themes are CSS-variable skins (`.theme-<slug>`); the built-in one is `slate`, more can be registered in
config (`themes`) or in code (`Themes::registerTheme`). **Theme packs** are zips (`theme.json` + `theme.css` +
images/fonts) installed from Settings → Themes (administrators only) or with:

Select the active theme in **Settings → App → Theme**. The main board stays at `/` (under the configured route prefix); themes do not create `/<slug>` board routes. The wider desktop board remains exclusively at `/dashboard` (or `dashboard_route`).

```bash
php artisan griglia:theme-import pack.zip
php artisan griglia:theme-export slug --css-from=…
```

Packs are treated as untrusted content: no SVG, CSS sanitised (no `@import`/external urls), size and entry
limits, assets served sandboxed.

## Writing a pack

A pack is a zip with `theme.json` (slug, label, version, author, optional fonts), `theme.css` with a single
`.theme-<slug> { --tl-…: … }` block, and an optional `images/` folder. The quickest start is to export an
existing theme and edit it:

```bash
php artisan griglia:theme-export slate --css-from=resources/css/app.css
```

A sample pack (`pollon`) ships in `resources/themes/` of the repository.

## See also

- [Security](../operations/security.md) — why packs are treated as untrusted content.
- [Front-end assets](../getting-started/assets.md) — where the theme CSS is loaded from.
