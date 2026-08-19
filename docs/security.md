# Security

See the package [SECURITY.md](https://github.com/alle80/agent-devboard/blob/master/SECURITY.md) for the security
model, the hardening checklist and how to report a vulnerability. In short: everything is scoped to the owner;
settings/context/themes are admin-only; theme packs, uploads and Web Push endpoints are validated; expensive
endpoints are rate-limited; secrets never leave `.env`/the host scripts.
