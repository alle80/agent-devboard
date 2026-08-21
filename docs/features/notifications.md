# Notifications

The board itself notifies the list owner when the agent **closes a task** or **asks a question**. Both events
obey the `agent` settings `notify_on_done` / `notify_on_question`: turned off, the board stays silent too.
Channels (Settings → App):

- **In-app bell** — unread badge, list, click opens the task (switching list if needed), mark all read; live.
- **Web Push** — on the devices where you enabled it (Settings → Notifications → *Enable on this device*; iPhone:
  add the site to the Home screen first). Needs VAPID keys and `HasPushSubscriptions` on the user model.
  The **Diagnostics** panel shows permission, service worker, subscription and whether a push reached the device.
- **Mail** — when a mailer is configured.

Deep links `?list=ID&open=ID` open a task from a notification.

!!! tip "Two layers, one switch"
    There are two layers: the board notifies you by itself (bell, Web Push, mail) and your agent may *also*
    notify you through its own channel when it closes a task or asks something. They travel on different
    roads, but they share the same switches: `notify_on_done` and `notify_on_question` (Settings → Agent)
    are read both by the board — see `Notify::todoCompleted()` / `Notify::questionAsked()` — and by the
    agent, so switching one off silences that event on both layers.

## See also

- [Installation](../getting-started/installation.md#web-push-optional) — VAPID keys and the user model.
- [Troubleshooting](../operations/troubleshooting.md) — when a push never arrives.
