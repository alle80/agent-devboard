# Troubleshooting

## `composer require` stops with a conflict on `brick/math`

Web Push pulls `web-token/jwt-library`, which caps `brick/math` at `^0.17`, while a brand new Laravel app
ships `0.18`. Install with `-W` so composer may downgrade that single transitive dependency:

```bash
composer require alle80/griglia -W
```

## The board has no styles, or the drag & drop does nothing

The assets are not in place. In `precompiled` mode run
`php artisan vendor:publish --tag=griglia-assets --force`; in `vite` mode check the `@source` and the two
imports in your `app.css` / `app.js`, then `npm run build`. See [Front-end assets](../getting-started/assets.md).

## A new route or a changed config is ignored

Cached configuration or routes:

```bash
php artisan config:clear && php artisan route:clear && php artisan view:clear
```

## Nothing updates live between devices

No broadcaster is configured, or the private channel is not authorised. Set the `REVERB_*` /
`VITE_REVERB_*` variables, rebuild the front-end assets and add the `App.Models.User.{id}` channel to
`routes/channels.php`. The board works without it — just without live refresh.

## Web Push never arrives

- Generate the keys (`php artisan webpush:vapid`) and add `HasPushSubscriptions` to your user model.
- Enable the device in **Settings → Notifications**, where the diagnostics panel shows permission, service
  worker and subscription state.
- On iOS notifications only work when the app has been **added to the Home screen**.

## `griglia:docs-build` says MkDocs is missing

Install it (`pip install mkdocs-material`) or build with the official image: `--docker`. See
[Building this site](../contributing/docs-site.md).

## Attachments 404

`php artisan storage:link`, and check the disk in `config/griglia.php` (`attachments_disk`, default
`public`).
