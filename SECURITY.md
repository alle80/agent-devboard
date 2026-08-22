# Security policy

## Reporting a vulnerability

Please **do not** open a public issue for security problems. E-mail the maintainer listed in `composer.json`
(or use GitHub's private vulnerability reporting on the repository) with a description, the affected version and,
if possible, a proof of concept. You will get an answer within a few days; fixes are published as patch releases
and noted in the `CHANGELOG.md` under **Security**.

## Security model (what the package does for you)

- **Access**: in server mode every board route requires an authenticated user (`GrigliaAccess`, also on Livewire
  update requests) and, optionally, `canAccessGriglia()` / `griglia.access_gate`. Lists, tasks, attachments and
  notifications are always scoped to the owner.
- **Administration**: global settings, the agent context and theme packs are admin-only (`Alle80\Griglia\Admin`:
  `canManageGriglia()`, `griglia.admin_gate`, `GRIGLIA_ADMINS`, or the first user).
  Switching to local mode from the UI is refused outside `APP_ENV=local`.
- **Local mode** has no authentication by design: use it only on a machine that is not exposed (banner on every page).
- **Theme packs** (zip) are treated as untrusted: no SVG, sanitised CSS (no `@import`/external urls), size and entry
  limits, assets served with `nosniff` + a sandbox CSP.
- **Uploads**: images are re-encoded with GD, checked for pixel bombs, served through an authorised route.
- **Web Push**: subscription endpoints must be https on known push services; expensive endpoints are rate-limited.
- **Markdown** is rendered with raw HTML stripped and unsafe links blocked.
- **Secrets** (API keys, VAPID, OAuth tokens of the agents) never leave `.env`/the host scripts; the board only
  receives percentages/snapshots.

## Hardening checklist for operators

- `APP_DEBUG=false`, HTTPS, security headers at the proxy (HSTS, nosniff, frame options).
- Set `GRIGLIA_ADMINS` explicitly; keep `GRIGLIA_MODE=server` on shared hosts.
- Prefer a private disk for attachments (`GRIGLIA_ATTACHMENTS_DISK=local`).
- Rotate the VAPID keys / agent credentials if a host is compromised.
