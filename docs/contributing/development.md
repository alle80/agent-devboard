# Development

```bash
composer install
vendor/bin/phpunit                 # orchestra/testbench, sqlite in memory
npm install && npm run build       # precompiled assets (public/build)
```

Releases follow SemVer; every change is listed in `CHANGELOG.md` (Keep a Changelog, with a **Security**
section when relevant). Tags `vX.Y.Z` on GitHub feed Packagist.
