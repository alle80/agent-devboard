# Notifications

The board itself notifies the list owner when the agent **closes a task** or **asks a question** (events follow the
`agent` settings `notify_on_done` / `notify_on_question`). Channels (Settings → App):

- **In-app bell** — unread badge, list, click opens the task (switching list if needed), mark all read; live.
- **Web Push** — on the devices where you enabled it (Settings → Notifications → *Enable on this device*; iPhone:
  add the site to the Home screen first). Needs VAPID keys and `HasPushSubscriptions` on the user model.
  The **Diagnostics** panel shows permission, service worker, subscription and whether a push reached the device.
- **Mail** — when a mailer is configured.

Deep links `?list=ID&open=ID` open a task from a notification.

!!! tip "Two layers of notifications"
    The board notifies you by itself (bell, Web Push, mail). Your agent may *also* notify you through its
    own channel when it closes a task or asks something — the two are independent, keep whichever you like.

## See also

- [Installation](../getting-started/installation.md#web-push-optional) — VAPID keys and the user model.
- [Troubleshooting](../operations/troubleshooting.md) — when a push never arrives.
