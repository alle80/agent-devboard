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

## Payload

`broadcastAs()` names the event `TodoChanged`, so Echo needs a **leading dot** when listening.
`broadcastWith()` sends these fields — nothing else (in particular the owner's user id stays out of
the payload: it is already in the channel name):

| Field | Type | Meaning |
|---|---|---|
| `checklist_id` | int | The list the todo belongs to. |
| `todo_id` | int | The todo that changed. |
| `title` | string | Its title, at the time of the change. |
| `state` | string | One of `done`, `question`, `working`, `otw` (open to work), `waiting`. |
| `source` | string | `cli` when the change comes from artisan (e.g. the agent running `griglia:check`), `web` otherwise. |
| `deleted` | bool | `true` when the todo has just been deleted. |
| `state_changed` | bool | `true` when the todo changed state (`completed`, `open_to_work`, `working` or `question`), not just its title or note. |

Saving or deleting a sub-task, a question or an attachment broadcasts the **parent todo** with its
current state, not a payload of its own: `todo_id` is always a todo.

The board itself toasts a change only when `state_changed` is `true`, `source` is `cli` and the
*Toast for console changes* setting is on — your own listener is free to use them differently.

In PHP the event object exposes the same values as camelCase properties (`$event->todoId`,
`$event->stateChanged`) plus `$event->userId`, the list owner.

## Listening from JavaScript

```js
// server mode: the private channel of the list owner
window.Echo.private(`App.Models.User.${userId}`)
    .listen('.TodoChanged', (e) => {
        if (e.deleted) return removeRow(e.todo_id)
        if (e.state_changed) toast(`${e.title} → ${e.state}`)
        refreshRow(e.todo_id)
    })

// local mode: one public channel for everybody
window.Echo.channel('griglia.local')
    .listen('.TodoChanged', (e) => { /* same payload */ })
```

Inside a Livewire component let `Mode::echoListener()` pick the right channel for the current mode:

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

## Listening from PHP

```php
Event::listen(\Alle80\Griglia\Events\TodoChanged::class, function ($event) {
    // your own hook: metrics, chat message, webhook…
});
```

## Notifications

Closing a task (`--done`) or asking questions (`--ask`) notifies the list owner through Laravel
Notifications — in-app bell, Web Push and mail, each switchable in Settings. See
[Notifications](../features/notifications.md).

## See also

- [Installation](../getting-started/installation.md#live-updates-optional) — Reverb and the channel.
- [Notifications](../features/notifications.md)
