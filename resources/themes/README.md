# Sample theme packs

Each folder here is an installable theme pack: zip its **contents** (theme.json at the root of the
zip, or inside a single top-level folder) and install it from `/settings` → 🎨 Themes, or with
`php artisan griglia:theme-import pack.zip`. `theme.json` holds the definition (slug, label, icon,
fonts from bunny.net, texts, deco emoji, optional `icon_img` relative to the pack, version, author);
`theme.css` holds the CSS variables of the theme (`.theme-<slug> { --tl-… }`) and any extra rule
scoped to `.theme-<slug>`; images go in `images/` and are referenced with relative URLs.

Export any existing theme as a starting point: `php artisan griglia:theme-export slate`.

- `pollon/` — the "Pollon" theme of the original app (Italian texts), exported with
  `griglia:theme-export pollon --css-from=resources/css/app.css`.
