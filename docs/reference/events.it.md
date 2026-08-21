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

## Payload

`broadcastAs()` chiama l'evento `TodoChanged`, quindi in ascolto con Echo serve il **punto iniziale**.
`broadcastWith()` manda questi campi e nient'altro (in particolare l'id dell'utente proprietario resta
fuori dal payload: è già nel nome del canale):

| Campo | Tipo | Significato |
|---|---|---|
| `checklist_id` | int | La lista a cui appartiene il todo. |
| `todo_id` | int | Il todo che è cambiato. |
| `title` | string | Il suo titolo, al momento della modifica. |
| `state` | string | Uno fra `done`, `question`, `working`, `otw` (open to work), `waiting`. |
| `source` | string | `cli` quando la modifica arriva da artisan (es. l'agente che lancia `griglia:check`), `web` altrimenti. |
| `deleted` | bool | `true` quando il todo è appena stato cancellato. |
| `state_changed` | bool | `true` quando è cambiato lo stato del todo (`completed`, `open_to_work`, `working` o `question`), non solo il titolo o la nota. |

Salvare o cancellare un sotto-task, una domanda o un allegato trasmette il **todo padre** con il suo
stato attuale, non un payload suo: `todo_id` è sempre un todo.

La board mostra un toast solo quando `state_changed` è `true`, `source` è `cli` e l'impostazione
*Toast per i cambi da console* è accesa — il tuo ascoltatore può usarli come preferisce.

In PHP l'oggetto dell'evento espone gli stessi valori come proprietà camelCase (`$event->todoId`,
`$event->stateChanged`) più `$event->userId`, il proprietario della lista.

## Metterlo in ascolto da JavaScript

```js
// modalità server: il canale privato del proprietario della lista
window.Echo.private(`App.Models.User.${userId}`)
    .listen('.TodoChanged', (e) => {
        if (e.deleted) return removeRow(e.todo_id)
        if (e.state_changed) toast(`${e.title} → ${e.state}`)
        refreshRow(e.todo_id)
    })

// modalità local: un solo canale pubblico per tutti
window.Echo.channel('griglia.local')
    .listen('.TodoChanged', (e) => { /* stesso payload */ })
```

Dentro un componente Livewire lascia scegliere il canale giusto per la modalità corrente a
`Mode::echoListener()`:

```php
protected function getListeners(): array
{
    return [\Alle80\Griglia\Mode::echoListener() => 'onTodoChanged'];
}

public function onTodoChanged(array $event = []): void
{
    // $event['todo_id'], $event['state'], $event['source']…
}
```

## Metterlo in ascolto da PHP

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
