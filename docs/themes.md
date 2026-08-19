# Themes

Generic themes are CSS-variable skins (`.theme-<slug>`); the built-in one is `slate`, more can be registered in
config (`themes`) or in code (`Themes::registerTheme`). **Theme packs** are zips (`theme.json` + `theme.css` +
images/fonts) installed from Settings → Themes (administrators only) or with:

```bash
php artisan devboard:theme-import pack.zip
php artisan devboard:theme-export slug --css-from=…
```

Packs are treated as untrusted content: no SVG, CSS sanitised (no `@import`/external urls), size and entry
limits, assets served sandboxed.
