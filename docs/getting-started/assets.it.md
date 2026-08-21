# Asset front-end

Alla board servono il suo CSS e il suo JS (le utility Tailwind, il sistema dei temi, SortableJS e Laravel Echo
quando c'è un broadcaster configurato). Se li porta dietro già compilati, quindi **di default non c'è niente da
fare**. La modalità è `config('griglia.assets')`, variabile d'ambiente `GRIGLIA_ASSETS`.

=== "Precompilati — il default, zero build"

    Usa i file distribuiti con il package: niente npm, niente Vite, niente da configurare.

    Gli asset sono pubblicati sotto il tag `laravel-assets` di Laravel, che un'applicazione standard ripubblica
    dopo ogni `composer update` — così un aggiornamento porta con sé la nuova build. Per farlo a mano:

    ```bash
    php artisan vendor:publish --tag=griglia-assets --force   # → public/vendor/griglia/{build,images}
    ```

    `<x-griglia::assets />` a quel punto collega `public/vendor/griglia/build/griglia.{css,js}`.

=== "Compilati dalla tua applicazione — Vite"

    Imposta `GRIGLIA_ASSETS=vite` e importa i sorgenti del package nella tua build. Conviene se usi già Tailwind
    e vuoi un solo bundle, oppure se rivesti la board. Tailwind 4 non guarda dentro `vendor/`, quindi dichiara
    le viste come sorgente:

    ```css
    /* resources/css/app.css */
    @import 'tailwindcss';
    @source '../../vendor/alle80/griglia/resources/views/**/*.blade.php';
    @import '../../vendor/alle80/griglia/resources/css/griglia.css';
    ```
    ```js
    // resources/js/app.js
    import '../../vendor/alle80/griglia/resources/js/griglia.js';   // SortableJS + Echo (facoltativo)
    ```
    ```bash
    npm i sortablejs laravel-echo pusher-js && npm run build
    ```

## In tutte e due le modalità

- Il client Echo si configura a runtime da `config('griglia.echo')` (`VITE_REVERB_*` / `REVERB_*`).
  Con la chiave vuota non viene aperto nessun WebSocket.
- La configurazione a runtime di Echo, traduzioni, dettatura e Web Push viene emessa come JSON sicuro per uno
  script: valori che contengono tag di chiusura HTML, virgolette o separatori di riga Unicode non possono
  chiudere l'elemento `script` in cui stanno.
- I font dei temi arrivano da `config('griglia.fonts_url')` (di default bunny.net; mettilo a `''` per ospitarli
  da te).
- Per ricompilare i file precompilati dopo aver modificato i sorgenti del package:
  `cd vendor/alle80/griglia && npm install && npm run build`.

## Vedi anche

- [Installazione](installation.md) · [Se qualcosa non va](../operations/troubleshooting.md)
