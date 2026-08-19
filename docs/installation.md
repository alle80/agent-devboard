# Installation

## Requirements

- PHP 8.3+, Laravel 12 or 13, Livewire 4, Tailwind CSS 4 (for the `vite` asset mode), `ext-gd`.
- A database (MariaDB/MySQL/SQLite), a user model with `Notifiable` (and `HasPushSubscriptions` for Web Push).
- Optional: Laravel Reverb (live updates), `laravel/ai` (image descriptions, plan builder, speech to text),
  a mailer (mail notifications).

## Steps

```bash
composer require alle80/agent-devboard
php artisan vendor:publish --tag=devboard-config     # config/devboard.php (optional)
php artisan migrate                                  # tables + settings defaults
php artisan vendor:publish --tag=devboard-agents     # AGENTS.md for your coding agent (optional)
```

Add the package assets to your Vite build (default `assets = vite`):

```css
/* resources/css/app.css */
@import '../../vendor/alle80/agent-devboard/resources/css/devboard.css';
```
```js
// resources/js/app.js
import '../../vendor/alle80/agent-devboard/resources/js/devboard.js';
```

or use the precompiled build: `DEVBOARD_ASSETS=precompiled` + `php artisan vendor:publish --tag=devboard-assets`.

Routes are registered under `devboard.route_prefix` (default: site root — `/`, `/settings`, `/stats`, …) and
protected by the package itself according to the [mode](configuration.md#modes).

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
agent context, theme packs); see [Security](security.md) for `DEVBOARD_ADMINS`, `canManageDevboard()` or a Gate.
