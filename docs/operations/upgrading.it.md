# Aggiornare

```bash
composer update alle80/griglia
php artisan migrate                                    # le migrazioni sono idempotenti
php artisan vendor:publish --tag=griglia-assets --force # solo in modalità precompilata
```

## Versioni

Il package segue il [versionamento semantico](https://semver.org). Finché resta sullo `0.x`, è il numero
**minor** il posto dove possono comparire cambiamenti che rompono: fissa il vincolo che ti fa stare tranquillo
(`^0.45.0`) e leggi il
[CHANGELOG](https://github.com/alle80/griglia/blob/master/CHANGELOG.md) prima di alzarlo — ogni rilascio
documenta cosa è cambiato e cosa farci.

## Dopo un aggiornamento

- **Le viste pubblicate** (`vendor:publish --tag=griglia-views`) non si aggiornano da sole: confrontale con i
  sorgenti del package quando un rilascio tocca l'interfaccia.
- **Gli asset precompilati** vanno ripubblicati con `--force`, altrimenti il browser tiene la build vecchia.
- **Le impostazioni** prendono i nuovi valori di default dalle migrazioni delle settings, quindi lancia
  `migrate` prima di usare le opzioni nuove.
- Se una modifica non si vede, svuota le cache: `php artisan config:clear && php artisan view:clear`.

## Disco privato degli allegati (0.71.0)

Il default di `GRIGLIA_ATTACHMENTS_DISK` è passato da `public` a `local`. Le installazioni che hanno già
pubblicato `config/griglia.php` tengono il valore pubblicato: rivedilo in modo esplicito. I nuovi upload sul
disco privato richiedono `GRIGLIA_ATTACHMENTS_VIA_CONTROLLER=true` (il default) e sono raggiungibili solo
attraverso la rotta degli allegati di Griglia, autenticata e delimitata al proprietario. Non esporre
`storage/app/private` con un alias del web server.

I file già salvati su `public` non vengono spostati in automatico. O tieni `GRIGLIA_ATTACHMENTS_DISK=public`
per un po', oppure sposta `attachments/` dalla vecchia radice del disco al disco privato configurato prima di
cambiare. Dopo aver cambiato l'ambiente lancia `php artisan config:clear`; il symlink pubblico `storage` non
serve per gli allegati privati e si può togliere, se nessun'altra parte dell'applicazione lo usa.
