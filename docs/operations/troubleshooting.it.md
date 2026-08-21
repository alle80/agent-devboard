# Se qualcosa non va

## `composer require` si ferma con un conflitto su `brick/math`

Il Web Push tira dentro `web-token/jwt-library`, che tiene `brick/math` a `^0.17`, mentre un'applicazione
Laravel appena creata ha la `0.18`. Installa con `-W`, così composer può retrocedere quell'unica dipendenza
indiretta:

```bash
composer require alle80/griglia -W
```

## La board è senza stili, o il trascinamento non fa niente

Gli asset non sono al loro posto. In modalità `precompiled` lancia
`php artisan vendor:publish --tag=griglia-assets --force`; in modalità `vite` controlla `@source` e i due
import nel tuo `app.css` / `app.js`, poi `npm run build`. Vedi
[Asset front-end](../getting-started/assets.md).

## Una rotta nuova o una config cambiata vengono ignorate

Configurazione o rotte in cache:

```bash
php artisan config:clear && php artisan route:clear && php artisan view:clear
```

## Niente si aggiorna dal vivo fra i dispositivi

Non c'è un broadcaster configurato, oppure il canale privato non è autorizzato. Imposta le variabili
`REVERB_*` / `VITE_REVERB_*`, ricompila gli asset front-end e aggiungi il canale `App.Models.User.{id}` a
`routes/channels.php`. La board funziona anche senza — solo senza aggiornamento dal vivo.

## Il Web Push non arriva mai

- Genera le chiavi (`php artisan webpush:vapid`) e aggiungi `HasPushSubscriptions` al tuo modello utente.
- Abilita il dispositivo in **Impostazioni → Notifiche**, dove il pannello di diagnostica mostra permesso,
  service worker e stato della sottoscrizione.
- Su iOS le notifiche funzionano solo quando l'app è stata **aggiunta alla schermata Home**.

## `griglia:docs-build` dice che manca MkDocs

Installa la catena di strumenti (`pip install -r requirements-docs.txt` — Material più il plugin i18n che
serve al sito bilingue) oppure compila con Docker: `--docker`. Vedi
[Costruire questo sito](../contributing/docs-site.md).

## Gli allegati danno 404

Tieni `GRIGLIA_ATTACHMENTS_VIA_CONTROLLER=true` (il default) e controlla che `GRIGLIA_ATTACHMENTS_DISK` indichi
un disco configurato e scrivibile (`local` di default). Per il disco privato di default il symlink di storage
non serve e non viene usato.
