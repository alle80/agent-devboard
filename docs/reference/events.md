# Events and broadcasting

## `TodoChanged`

`Alle80\Griglia\Events\TodoChanged` is broadcast on **every** change to a todo, sub-task, question or
attachment — created, updated, state change, progress, comment, delete/restore.

| Mode | Channel |
|---|---|
| `server` | private `App.Models.User.{id}` (the list owner) |
| `local` | public `griglia.local` |

Authorise the private channel in your app:

```php
// routes/channels.php
Broadcast::channel('App.Models.User.{id}', fn ($user, $id) => (int) $user->id === (int) $id);
```

With no broadcaster configured nothing happens: failures are logged, never raised — the board keeps
working without live updates.

## Listening to it

```php
Event::listen(\Alle80\Griglia\Events\TodoChanged::class, function ($event) {
    // your own hook: metrics, chat message, webhook…
});
```

## Notifications

Closing a task (`--done`) or asking questions (`--ask`) notifies the list owner through Laravel
Notifications — in-app bell, Web Push and mail, each switchable in Settings. See
[Notifications](../features/notifications.md).
