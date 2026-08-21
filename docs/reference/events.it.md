# Eventi e broadcasting

## `TodoChanged`

`Alle80\Griglia\Events\TodoChanged` viene trasmesso a **ogni** cambiamento di un todo, di un sotto-task, di una
domanda o di un allegato — creazione, modifica, cambio di stato, avanzamento, commento, cancellazione e
ripristino.

| Modalità | Canale |
|---|---|
| `server` | privato `App.Models.User.{id}` (il proprietario della lista) |
| `local` | pubblico `griglia.local` |

Autorizza il canale privato nella tua applicazione:

```php
// routes/channels.php
Broadcast::channel('App.Models.User.{id}', fn ($user, $id) => (int) $user->id === (int) $id);
```

Senza un broadcaster configurato non succede niente: gli errori vengono registrati nei log, mai sollevati — la
board continua a funzionare, solo senza aggiornamenti dal vivo.

## Metterlo in ascolto

```php
Event::listen(\Alle80\Griglia\Events\TodoChanged::class, function ($event) {
    // il tuo gancio: metriche, messaggio in chat, webhook…
});
```

## Notifiche

Chiudere un task (`--done`) o fare domande (`--ask`) avvisa il proprietario della lista attraverso le
Notifications di Laravel — campanella in-app, Web Push e mail, ognuna accendibile dalle Impostazioni. Vedi
[Notifiche](../features/notifications.md).

## Vedi anche

- [Installazione](../getting-started/installation.md#aggiornamenti-dal-vivo-facoltativo) — Reverb e il canale.
- [Notifiche](../features/notifications.md)
