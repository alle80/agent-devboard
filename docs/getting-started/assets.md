# Front-end assets

The board needs its CSS and JS (Tailwind utilities, the theme system, SortableJS, and Laravel Echo when a
broadcaster is configured). Pick **one** of the two modes — `config('griglia.assets')`, env
`GRIGLIA_ASSETS`.

## A — Precompiled (zero build)

Use the files shipped with the package: no npm, no Vite.

```bash
# .env
GRIGLIA_ASSETS=precompiled
```
```bash
php artisan vendor:publish --tag=griglia-assets     # → public/vendor/griglia/{build,images}
```

`<x-griglia::assets />` then links `public/vendor/griglia/build/griglia.{css,js}`. Republish after every
package update (`--force` to overwrite).

## B — Bundled by your app (default, `assets = vite`)

Import the package sources into your own build. Tailwind 4 does not scan `vendor/`, so declare the views
as a source:

```css
/* resources/css/app.css */
@import 'tailwindcss';
@source '../../vendor/alle80/griglia/resources/views/**/*.blade.php';
@import '../../vendor/alle80/griglia/resources/css/griglia.css';
```
```js
// resources/js/app.js
import '../../vendor/alle80/griglia/resources/js/griglia.js';   // SortableJS + Echo (optional)
```
```bash
npm i sortablejs laravel-echo pusher-js && npm run build
```

## Both modes

- The Echo client is configured at runtime from `config('griglia.echo')` (`VITE_REVERB_*` / `REVERB_*`).
  An empty key opens no WebSocket at all.
- Theme fonts come from `config('griglia.fonts_url')` (bunny.net by default; set it to `''` to self-host).
- To rebuild the precompiled files after editing the package sources:
  `cd vendor/alle80/griglia && npm install && npm run build`.
