# Configuration & settings

Two layers:

- **Configuration** (`config/devboard.php`, env) — decided by the developer: routes, models, integrations, modes,
  admin sources, rate limits, paths. Needs `config:cache` to change.
- **Settings** (`/settings`, stored in the DB) — decided by the user at run time: `agent` (how the agent works),
  `optimization` (token saving), `app` (board behaviour: default style, AI images, speech to text, notifications,
  price list, dashboard tab, mode override).

## Modes

- `DEVBOARD_MODE=server` (default): authenticated users, lists per user; access can be restricted with
  `canAccessDevboard(): bool` on the user model or `DEVBOARD_ACCESS_GATE`.
- `DEVBOARD_MODE=local`: no authentication, one global set of lists — only on your own machine (banner on every page).

The complete inventory (current and future keys, defaults, priorities) is in
[config-and-settings.md](config-and-settings.md).
