# Contribuire

Issue e pull request sono benvenute su
[github.com/alle80/griglia](https://github.com/alle80/griglia).

## Prima di aprire una pull request

```bash
cd packages/griglia && composer update\ncomposer lint\ncomposer test
```

La suite (orchestra/testbench, SQLite in memoria) copre migrazioni, delimitazione per utente, i componenti
Livewire, `griglia:check` / `griglia:watch`, il registro dei temi e i pacchetti zip, l'allineamento delle
traduzioni e l'evento di broadcast. GitHub Actions la esegue su PHP 8.3 e 8.4.

## Cosa deve portare con sé una modifica

- **Test** per il comportamento che aggiungi o correggi.
- **Traduzioni**: le stringhe stanno in `resources/lang/en` (base) e `resources/lang/it`; un test controlla che
  i due file restino allineati. Non scrivere mai a mano il nome dell'agente — usa `:agent`.
- **Documentazione**: se la modifica si vede, aggiorna la pagina corrispondente in `docs/` — e la sua
  traduzione italiana, vedi [Traduzioni](translations.md).
- **CHANGELOG.md**: una voce sotto *Unreleased*, nel formato Keep a Changelog.

## Stile

Esegui `composer lint` per controllare lo stile Laravel Pint e `composer test` per avviare PHPUnit. Segui il\ncodice che hai attorno: convenzioni Laravel, nessuna dipendenza nuova senza un motivo, interfaccia
costruita con il set di icone e le variabili di tema del package invece che con markup usa e getta.

## Sicurezza

Per favore non aprire una issue pubblica per una vulnerabilità — vedi [Sicurezza](../operations/security.md).
