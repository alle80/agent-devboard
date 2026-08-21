# Sviluppo

## Il repository

```
config/         griglia.php (pubblicato nell'applicazione ospite)
database/       migrazioni (tabelle + default delle impostazioni)
docs/           questo sito (vedi «Costruire questo sito»)
resources/      viste, sorgenti css/js, lang/{en,it}, pacchetto di temi di esempio
routes/         le rotte del package
src/            componenti Livewire, modelli, comandi console, classi di supporto
tests/          orchestra/testbench + phpunit
```

## Lavorarci

```bash
composer install
vendor/bin/phpunit                 # testbench, sqlite in memoria
vendor/bin/testbench serve         # un'applicazione Laravel nuda con il package montato
npm install && npm run build       # asset precompilati → public/build
php artisan griglia:docs-build     # il sito della documentazione
```

La suite copre migrazioni, delimitazione per utente, i componenti Livewire, `griglia:check` /
`griglia:watch`, il registro dei temi e i pacchetti zip, l'allineamento delle traduzioni fra `en` e `it` e
l'evento di broadcast. GitHub Actions la esegue su PHP 8.3 e 8.4.

## Rilasciare

Versionamento semantico; ogni modifica va in `CHANGELOG.md` (Keep a Changelog, con una sezione **Security**
quando serve). È il tag `vX.Y.Z` su GitHub quello che Packagist pubblica — quindi il tag è il rilascio.
Ricompila gli asset precompilati prima di taggare, quando sono cambiati CSS/JS o le viste.

Vedi anche [Contribuire](contributing.md) e [Costruire questo sito](docs-site.md).
