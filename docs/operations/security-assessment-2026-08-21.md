# Security assessment — 2026-08-21

## Executive summary

The assessment found **no critical or high-severity vulnerability** in the reviewed package release
`v0.70.1` (`c47e825`). Authentication, tenant scoping, administrative authorization, Markdown rendering, uploads,
theme packs and costly HTTP endpoints all have explicit controls backed by tests.

Two defence-in-depth issues remain: the default attachment disk can make files reachable outside the
authorized controller on a conventional Laravel installation, and three inline JavaScript configuration
objects use raw `json_encode()` instead of Blade's script-safe JSON encoder. They are ranked below.

## Scope and method

The review covered package PHP, Blade and JavaScript sources, routes, configuration, Composer/npm manifests,
host scripts, migrations, tests, documentation, and build/release guidance. It included:

- manual data-flow review of authentication, authorization, tenant scoping, uploads, Markdown, theme ZIPs,
  Web Push, transcription, filesystem access, configuration and secrets;
- searches for dangerous execution/deserialization primitives and credential-shaped strings;
- `composer audit --locked` and `npm audit --omit=dev --audit-level=low`;
- the focused PHPUnit security/regression set (38 tests, 216 assertions).

This is a source-level assessment, not a penetration test of a deployed host. Reverse-proxy headers, host
permissions, production environment values and infrastructure exposure therefore remain operator concerns.

## Findings

### GRSEC-01 — Public attachment disk can bypass controller authorization

| Attribute | Assessment |
| --- | --- |
| Severity | Medium |
| Impact | High (confidentiality of uploaded images) |
| Likelihood | Low to medium |
| Priority | P1 |

**Evidence.** `config/griglia.php` defaults `attachments_disk` to `public`, while the application URL normally
uses the owner-scoped `AttachmentController`. On a standard Laravel host with `public/storage` linked, the same
object may also be fetched directly as `/storage/attachments/<todo-id>/<ulid>`, where the controller's
`Checklist::mine()` check and private response headers do not run. The ULID reduces guessing but is not an
authorization boundary.

**Remediation.** Change the package default to a non-public disk (`local`) in the next compatibility window.
Until then, deployments should set `GRIGLIA_ATTACHMENTS_DISK=local`, retain
`GRIGLIA_ATTACHMENTS_VIA_CONTROLLER=true`, and ensure no web-server alias exposes that disk. Add a regression
test for the secure default when it changes.

### GRSEC-02 — Raw JSON in inline script blocks lacks script-context hardening

| Attribute | Assessment |
| --- | --- |
| Severity | Low |
| Impact | Medium (cross-site scripting if an encoded value becomes attacker-controlled) |
| Likelihood | Low |
| Priority | P2 |

**Evidence.** `resources/views/components/assets.blade.php` emits the I18N, speech and push objects with
unescaped `{!! json_encode(...) !!}`. Current values come from trusted translations, routes, CSRF state and
configuration, so no direct attacker-controlled exploit path was found. The neighbouring Echo object already
uses Blade's `@json`, which escapes HTML-significant characters for a script context.

**Remediation.** Render all four objects consistently with `@json` (or `Js::from`) and test payloads containing
`</script>`, quotes and Unicode separators. A strict Content Security Policy with nonces is also recommended at
the host layer.

## Controls verified

- Server mode authenticates package routes through persistent Livewire middleware; admin pages additionally
  check `Admin::allows()` during every component request.
- List/task operations are scoped through `Checklist::mine()`, the current list, or an equivalent owner check;
  attachment responses return 404 across tenants.
- Image uploads verify server-detected MIME and pixel count before decoding; JPEG/PNG are re-encoded. Theme ZIPs
  reject traversal, executable extensions, SVG, oversized entries/archives and unsafe CSS references.
- Markdown strips raw HTML and rejects unsafe links. Public theme assets use an extension allow-list,
  `nosniff`, and sandbox CSP headers.
- Push endpoints use an HTTPS host allow-list and rate limits; transcription is authenticated, size-limited,
  rate-limited and does not return provider errors to the browser.
- No credential-shaped values were found in tracked package sources. AI/VAPID/agent secrets are resolved from
  host configuration rather than persisted in board data.
- Composer and npm reported no known vulnerable dependencies at assessment time.

## Recommended order

1. P1: move the default attachment storage to a private disk and document the upgrade impact.
2. P2: replace raw inline JSON serialization and add adversarial rendering tests.
3. Operational: keep `APP_DEBUG=false`, HTTPS and proxy security headers; explicitly configure admins and never
   expose local mode, as required by the existing [security policy](security.md).
