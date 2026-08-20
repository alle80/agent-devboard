# Access, administrators and modes

## Modes

`GRIGLIA_MODE` (or `config('griglia.mode')`, overridable in Settings → App):

| Mode | What it means |
|---|---|
| `server` (default) | Login required. Every list belongs to a user; the board sees only yours. |
| `local` | No authentication, one global set of lists, public broadcast channel. **Your own machine only** — bind it to `127.0.0.1`. A banner reminds you on every page. |

Switching to local from the UI needs `APP_ENV=local` or `GRIGLIA_ALLOW_LOCAL_FROM_UI=true`.

## Who can use the board (server mode)

The package replaces the plain `auth` middleware with its own gate. Restrict access with either:

- `canAccessDevboard(): bool` on your user model, or
- `GRIGLIA_ACCESS_GATE=<ability>` (a Gate ability of your app).

## Who administers it

Settings, the agent context and theme packs are **administrator-only**:

- `canManageDevboard(): bool` on your user model, or
- `GRIGLIA_ADMIN_GATE=<ability>`, or
- `GRIGLIA_ADMINS="1,alice@example.com"` (ids or e-mails).

By default only the **first registered user** is an administrator.

## Theme packs are code

Installable packs are treated as untrusted content: administrator-only install, SVG refused, CSS sanitised
(no `@import`, no external urls), size caps (5 MB per file, 20 MB per pack, 200 files) and assets served
from a sandboxed route. See [Security](../operations/security.md).
