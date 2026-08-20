# Front-end assets

The board needs its CSS and JS (Tailwind utilities, the theme system, SortableJS, and Laravel Echo when a
broadcaster is configured). It ships them precompiled, so **by default there is nothing to do**. The mode is
`config('griglia.assets')`, env `GRIGLIA_ASSETS`.

=== "Precompiled — the default, zero build"

    Use the files shipped with the package: no npm, no Vite, nothing to configure.

    The assets are published under Laravel's own `laravel-assets` tag, which a default app republishes
    after every `composer update` — so an upgrade brings the new build with it. To do it by hand:

    ```bash
    php artisan vendor:publish --tag=griglia-assets --force   # → public/vendor/griglia/{build,images}
    ```

    `<x-griglia::assets />` then links `public/vendor/griglia/build/griglia.{css,js}`.

=== "Bundled by your app — Vite"

    Set `GRIGLIA_ASSETS=vite` and import the package sources into your own build. Worth it when you already
    use Tailwind and want one bundle, or when you restyle the board. Tailwind 4 does not scan `vendor/`, so
    declare the views as a source:

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

## See also

- [Installation](installation.md) · [Troubleshooting](../operations/troubleshooting.md)
