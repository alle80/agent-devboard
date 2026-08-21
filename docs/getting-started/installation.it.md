# Installazione

Il package sta su Packagist come [`alle80/griglia`](https://packagist.org/packages/alle80/griglia).

## Requisiti

- PHP 8.3+, Laravel 12 o 13, Livewire 4, Tailwind CSS 4 (per la modalità asset `vite`), `ext-gd`.
- Un database (MariaDB/MySQL/SQLite), un modello utente con `Notifiable` (e `HasPushSubscriptions` per il Web Push).
- Facoltativi: Laravel Reverb (aggiornamenti dal vivo), `laravel/ai` (descrizione delle immagini, costruttore
  di piani, dettatura vocale), un mailer (notifiche via mail).

## Passi

```bash
composer require alle80/griglia -W                  # -W: il Web Push tiene brick/math a ^0.17 (vedi la nota sotto)
php artisan migrate                                 # tabelle + valori di default delle impostazioni
```

L'installazione è tutta qui: la board porta con sé il proprio CSS e JS **precompilati**, pubblicati in automatico
da composer (Laravel ripubblica `laravel-assets` dopo ogni aggiornamento), quindi non c'è niente da compilare.
Apri `/` e la board è lì.

Facoltativi, quando ti servono:

```bash
php artisan vendor:publish --tag=griglia-config     # config/griglia.php
php artisan vendor:publish --tag=griglia-agents     # AGENTS.md per il tuo agente
php artisan vendor:publish --tag=griglia-assets     # ripubblica gli asset a mano
php artisan vendor:publish --tag=griglia-scripts    # script per l'host → scripts/ (vedi docs/agent/scripts.md)
```

!!! note "Perché `-W`"
    Il Web Push tira dentro `web-token/jwt-library`, che tiene `brick/math` a `^0.17`, mentre un'applicazione
    Laravel appena creata ha la `0.18`. `-W` lascia che composer retroceda quell'unica dipendenza indiretta;
    senza, l'installazione si ferma con un conflitto. Le applicazioni già avviate di solito non hanno bisogno di niente.

Se invece vuoi che la board faccia parte della tua build Vite (per condividere Tailwind con la tua applicazione,
o per rivestirla), imposta `GRIGLIA_ASSETS=vite` e segui [Asset front-end](assets.md).

Le rotte sono registrate sotto `griglia.route_prefix` (default: la radice del sito — `/`, `/settings`, `/stats`, …)
e protette dal package stesso secondo la [modalità](../configuration/access.md#modalita).

## Aggiornamenti dal vivo (facoltativo)

Installa Laravel Reverb, imposta le variabili `REVERB_*`/`VITE_REVERB_*` e autorizza il canale privato
`App.Models.User.{id}` in `routes/channels.php`. Senza un broadcaster la board funziona lo stesso (niente
aggiornamento dal vivo).

## Web Push (facoltativo)

```bash
php artisan webpush:vapid          # chiavi VAPID nel .env
```
Aggiungi `NotificationChannels\WebPush\HasPushSubscriptions` al tuo modello utente; ogni utente abilita i propri
dispositivi da **Impostazioni → Notifiche**.

## Primo utente e amministratori

La registrazione è affare della tua applicazione. Per impostazione predefinita il **primo utente registrato** è
l'amministratore della board (impostazioni, contesto dell'agente, pacchetti di temi); vedi
[Sicurezza](../operations/security.md) per `GRIGLIA_ADMINS`, `canManageDevboard()` o un Gate.

## E poi

- [Primi cinque minuti](quickstart.md) — scrivi la prima richiesta e falla lavorare a un agente.
- [Asset front-end](assets.md) — le due modalità in dettaglio (precompilati o compilati dalla tua applicazione).
- [Accessi, amministratori e modalità](../configuration/access.md) — chi entra, e la modalità locale.
