# Configurazione e impostazioni

Due strati, di proposito:

| | **Configurazione** | **Impostazioni** |
|---|---|---|
| Dove | `config/griglia.php`, `.env` | `/settings`, nel database |
| Chi decide | chi installa il package | chi usa la board |
| Quando cambia | al deploy (`config:cache`) | a runtime, subito |
| Cosa copre | rotte, modelli, dischi, modalità, gate, integrazioni | come lavora l'agente, risparmio di token, comportamento della board |

```bash
php artisan vendor:publish --tag=griglia-config     # config/griglia.php
php artisan vendor:publish --tag=griglia-views      # per sovrascrivere le viste Blade
php artisan vendor:publish --tag=griglia-lang       # traduzioni (en, it)
php artisan vendor:publish --tag=griglia-agents     # AGENTS.md, le regole per l'agente
```

Ogni campo di `/settings` si salva da solo, senza pulsante «Salva»: gli interruttori al clic, le select,
i numeri, i testi e gli orari appena cambiano (un toast lo conferma).

## Le impostazioni che legge l'agente

I gruppi `agent` e `optimization` non sono decorazione: `griglia:check` li stampa in testa al proprio output e
l'agente deve rispettarli — politica dei commit, autonomia, notifiche, un task alla volta o più di uno,
modalità stringata. Cambiali dalla pagina e il `griglia:check` successivo obbedisce.

## La lingua della board

La board parla le lingue in cui è tradotta — inglese (base) e italiano — e il gruppo **App** di `/settings`
si apre con la scelta:

- **Come nell'applicazione (`EN`)** — il default: la board segue `config('app.locale')`, così
  un'applicazione ospite che imposta la lingua per conto suo (un suo `SetLocale`, una preferenza per
  utente) continua a decidere lei.
- **English**, **Italiano**, … — una voce per ogni cartella di lingua trovata in `resources/lang` del
  package e in `lang/vendor/griglia` pubblicato: pubblica le tue traduzioni e compaiono qui.

La scelta vale per ogni pagina della board e per le richieste Livewire dietro modali e salvataggi, date
comprese («3 ore fa»). Non tocca la console: `griglia:check` continua a parlare all'agente in inglese.

Aggiungere una lingua è una cartella accanto a `en` e `it` — vedi
[Traduzioni](../contributing/translations.md).

## L'inventario completo

Generato dal codice, così non resta mai indietro:

- [File di configurazione](../reference/config.md) — ogni chiave, la sua variabile d'ambiente e il suo default.
- [Impostazioni](../reference/settings.md) — ogni opzione dei tre gruppi, con il testo d'aiuto della pagina.
- [Impostazioni da fare](../reference/config-and-settings.md) — quello che di proposito non c'è ancora.

Accessi, amministratori e modalità locale hanno una pagina tutta loro:
[Accessi e modalità](access.md).
