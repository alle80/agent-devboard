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

## Texts and languages

Besides colours, a theme defines the few words the board prints: `claim` and `footer` (head and foot of the
page), `counter` («3/5 *done*»), `done_all`, `add` (the button that opens the insert form), `stamp` (on
completed tasks), `confirm` (the delete question; `:title` is replaced) and `placeholder` (insert form and
sub-tasks). Each of them can be:

- a **translation key** — the built-in Slate uses `griglia::t.theme.add`, `griglia::t.theme.placeholder`, …
  so its texts follow the [language of the board](../configuration/index.md#the-language-of-the-board)
  (a published `lang/vendor/griglia/<locale>/t.php` can reword them);
- a **literal** — used as it is, in whatever language it is written (the sample `pollon` speaks Italian only);
- a **per-locale map** — `{"en": "add", "it": "aggiungi"}`: the board picks the current language, then
  `app.fallback_locale`, then the first entry.

Texts a pack leaves out fall back to the translated ones of Slate. `griglia:theme-export` writes the keys of
a built-in theme as per-locale maps, so the exported `theme.json` is readable and stays bilingual once
imported. A `config('griglia.themes')` or `Themes::registerTheme()` entry whose slug is a built-in theme
overrides it **key by key**: `['slate' => ['icon_img' => '/images/slate.svg']]` changes the icon and keeps
the translated texts.

## See also

- [Security](../operations/security.md) — why packs are treated as untrusted content.
- [Front-end assets](../getting-started/assets.md) — where the theme CSS is loaded from.
