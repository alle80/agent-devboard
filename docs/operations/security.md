# Security

The full model, the hardening checklist and how to report a vulnerability are in
[SECURITY.md](https://github.com/alle80/griglia/blob/master/SECURITY.md). The short version:

## What the package guarantees

- **Everything is scoped to its owner.** Lists, tasks, sub-tasks, questions and attachments are always read
  through the current user's scope: there is no route that returns someone else's board.
- **Administration is a separate gate.** Settings, the agent context and theme packs are admin-only —
  `canManageDevboard()`, a Gate ability or `GRIGLIA_ADMINS`; by default only the first registered user.
  See [Access & modes](../configuration/access.md).
- **Uploads are validated**: type and size checked, images re-encoded, stored on the configured disk, served
  through the app.
- **Theme packs are treated as code**: administrator-only install, SVG refused, CSS sanitised (no `@import`,
  no external urls), caps on file size, pack size and number of entries, assets served from a sandboxed route.
- **Expensive endpoints are rate-limited** (transcription, test notification, push subscription).
- **Secrets stay in `.env`** and in the host scripts: nothing that reaches the browser or a theme pack.

## What is up to you

- Keep the board behind your app's login (server mode). **Local mode has no authentication at all**: bind it
  to `127.0.0.1`, never expose it.
- Put the app behind HTTPS — Web Push and the microphone need a secure context anyway.
- Give the agent the credentials it needs and nothing more: it runs on your machine, with your shell.

## Reporting

Please do not open a public issue for a vulnerability: the contact and the disclosure process are in
[SECURITY.md](https://github.com/alle80/griglia/blob/master/SECURITY.md).
