# Installation

The package is on Packagist as [`alle80/griglia`](https://packagist.org/packages/alle80/griglia).

## Requirements

- PHP 8.3+, Laravel 12 or 13, Livewire 4, Tailwind CSS 4 (for the `vite` asset mode), `ext-gd`.
- A database (MariaDB/MySQL/SQLite), a user model with `Notifiable` (and `HasPushSubscriptions` for Web Push).
- Optional: Laravel Reverb (live updates), `laravel/ai` (image descriptions, plan builder, speech to text),
  a mailer (mail notifications).

## Steps

```bash
composer require alle80/griglia -W                  # -W: Web Push caps brick/math at ^0.17 (see the note below)
php artisan migrate                                 # tables + settings defaults
```

That is the whole installation: the board ships its **precompiled** CSS and JS, published automatically by
composer (Laravel republishes `laravel-assets` after every update), so there is nothing to build. Open `/`
and the board is there, with one list named **My list**.

One thing is still missing: the **agent list**, the list `griglia:check` reads, named after
`config('griglia.agent_list')` (`dev` by default). Rename *My list* to `dev` from the lists menu, or set
`GRIGLIA_AGENT_LIST` to the name of a list you already have — the [Quickstart](quickstart.md#1-open-the-board)
walks through it.

Optional, when you want them:

```bash
php artisan vendor:publish --tag=griglia-config     # config/griglia.php
php artisan vendor:publish --tag=griglia-agents     # AGENTS.md for your coding agent
php artisan vendor:publish --tag=griglia-assets     # re-publish the assets by hand
php artisan vendor:publish --tag=griglia-scripts    # host helpers → scripts/ (see docs/agent/scripts.md)
```

!!! note "Why `-W`"
    Web Push pulls `web-token/jwt-library`, which caps `brick/math` at `^0.17`, while a brand new
    Laravel app ships `0.18`. `-W` lets composer downgrade that single transitive dependency; without
    it the install stops with a conflict. Existing apps usually need nothing.

If instead you want the board to be part of your own Vite build (to share Tailwind with your app, or to
restyle it), set `GRIGLIA_ASSETS=vite` and follow [Front-end assets](assets.md).

Routes are registered under `griglia.route_prefix` (default: site root — `/`, `/settings`, `/stats`, …) and
protected by the package itself according to the [mode](../configuration/access.md#modes).

## Live updates (optional)

Install Laravel Reverb, set the `REVERB_*`/`VITE_REVERB_*` variables and authorise the private channel
`App.Models.User.{id}` in `routes/channels.php`. Without a broadcaster the board still works (no live refresh).

## Web Push (optional)

```bash
php artisan webpush:vapid          # VAPID keys into .env
```
Add `NotificationChannels\WebPush\HasPushSubscriptions` to your user model; users enable their devices in
**Settings → Notifications**.

## First user and administrators

Registration is up to your app. By default the **first registered user** is the board administrator (settings,
agent context, theme packs); see [Security](../operations/security.md) for `GRIGLIA_ADMINS`, `canManageGriglia()` or a Gate.

## Next

- [Quickstart](quickstart.md) — write the first request and let an agent work it.
- [Front-end assets](assets.md) — the two modes in detail (precompiled or bundled by your app).
- [Access, administrators and modes](../configuration/access.md) — who gets in, and the local mode.
