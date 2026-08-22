# Comandi artisan

<!-- Generato da `php artisan griglia:docs-generate` — non modificare a mano. -->

Tutto quello che il package aggiunge a `php artisan`, preso dalle definizioni dei comandi.

## `griglia:agent-status-import`

Importa lo snapshot dello stato degli agenti (piano + finestre d'uso) mostrato in /agents

```bash
php artisan griglia:agent-status-import [--file [FILE]]
```

| Argomento / opzione | Cosa fa | Default |
|---|---|---|
| `--file` | File JSON (default: stdin) | — |

## `griglia:auto-archive`

Archivia i todo completati da più di N giorni (vedi /settings)

Alias: `todos:auto-archive`

```bash
php artisan griglia:auto-archive [--dry-run]
```

| Argomento / opzione | Cosa fa | Default |
|---|---|---|
| `--dry-run` | Mostra soltanto cosa verrebbe archiviato | flag |

## `griglia:check`

Elenca le richieste aperte della lista dell'agente (vedi la config griglia.agent_list)

Alias: `sviluppo:check`

```bash
php artisan griglia:check [--all] [--json] [--worker-json] [--take [TAKE]] [--done [DONE]] [--comment [COMMENT]] [--summary [SUMMARY]] [--progress [PROGRESS]] [--phase [PHASE]] [--outcome [OUTCOME]] [--ask [ASK]] [--q [Q]] [--choices [CHOICES]] [--tokens-in [TOKENS-IN]] [--tokens-out [TOKENS-OUT]] [--agent [AGENT]] [--force]
```

| Argomento / opzione | Cosa fa | Default |
|---|---|---|
| `--all` | Mostra anche gli elementi completati e quelli non ancora open to work | flag |
| `--json` | Output leggibile da un programma | flag |
| `--worker-json` | Task e impostazioni di pianificazione del worker in formato leggibile da un programma | flag |
| `--take` | Id del todo da mettere in lavorazione (presa in carico) | — |
| `--done` | Id del todo da segnare come completato | — |
| `--comment` | Commento dell'agente salvato sul todo di --take/--done (claude_comment) | — |
| `--summary` | Riassunto brevissimo del risultato, mostrato sotto il titolo del task (con --done) | — |
| `--progress` | Percentuale di avanzamento 0-100 mostrata sul todo in lavorazione (con --take; per aggiornarla rilancia --take=ID --progress=N). --take da solo parte da 0% | — |
| `--phase` | Testo breve su cosa sta facendo adesso l'agente (con --take; per esempio "scrivendo codice", "testando"); mostrato accanto alla % | — |
| `--outcome` | Con --done: come è andata — ok (default, niente da controllare), alert (fatto, ma qualcosa va guardato) oppure blocked (c'è qualcosa che blocca). Colora la riga finché l'utente non la apre | — |
| `--ask` | Id del todo su cui fare domande (il task si mette in pausa nello stato «domanda») | — |
| `--q` | Testo di ogni domanda, ripetibile | _array_ |
| `--choices` | Scelte chiuse separate da \| per il --q corrispondente, ripetibile; il testo libero resta disponibile | _array_ |
| `--tokens-in` | Token in ingresso spesi sul todo dall'ultimo --take (sommati alle sue statistiche; con --take/--done/--ask) | — |
| `--tokens-out` | Token in uscita spesi sul todo dall'ultimo --take (sommati alle sue statistiche; con --take/--done/--ask) | — |
| `--agent` | Solo i task di questa chiave d'agente (più agenti; default: GRIGLIA_AGENT_KEY, oppure tutti i task quando l'agente è uno solo) | — |
| `--force` | Agisci su un task che appartiene a un altro agente, oppure riprendi un task fermato dall'utente (--take/--done/--ask altrimenti lo rifiutano) | flag |

## `griglia:context`

Contesto dell'agente (file di istruzioni) come gruppi/blocchi accendibili: import, export, status

```bash
php artisan griglia:context [--file [FILE]] [--replace] [--all] [--] [<action>]
```

| Argomento / opzione | Cosa fa | Default |
|---|---|---|
| `action` | import\|export\|status\|enabled | `status` |
| `--file` | file markdown da importare (default: stdin) | — |
| `--replace` | import: cancella prima il contesto attuale | flag |
| `--all` | export: includi anche gruppi e blocchi spenti | flag |

## `griglia:describe-images`

Genera con l'AI la descrizione testuale delle immagini allegate (la usa la ricerca)

Alias: `images:describe`

```bash
php artisan griglia:describe-images [--all] [--limit [LIMIT]]
```

| Argomento / opzione | Cosa fa | Default |
|---|---|---|
| `--all` | Rigenera anche le descrizioni già presenti | flag |
| `--limit` | — | `100` |

## `griglia:docs-build`

Compila la documentazione del package come sito HTML statico con MkDocs (tema Material)

```bash
php artisan griglia:docs-build [--out [OUT]] [--serve] [--docker] [--strict] [--no-generate]
```

| Argomento / opzione | Cosa fa | Default |
|---|---|---|
| `--out` | Cartella di destinazione (default: <package>/site) | — |
| `--serve` | Lancia `mkdocs serve` (anteprima dal vivo) invece di compilare | flag |
| `--docker` | Usa Docker (immagine costruita da docs.Dockerfile) invece di un mkdocs locale | flag |
| `--strict` | Passa --strict a mkdocs (gli avvisi fanno fallire la build) | flag |
| `--no-generate` | Non rigenerare le pagine di reference prima di compilare | flag |

## `griglia:docs-generate`

Genera dal codice le pagine di reference della documentazione (comandi, config, impostazioni)

```bash
php artisan griglia:docs-generate [--out [OUT]] [--check]
```

| Argomento / opzione | Cosa fa | Default |
|---|---|---|
| `--out` | Cartella di destinazione (default: <package>/docs/reference) | — |
| `--check` | Non scrive niente; esce con 1 quando una pagina non è aggiornata | flag |

## `griglia:empty-trash`

Cancella per sempre liste e task nel cestino (le loro statistiche spariscono)

```bash
php artisan griglia:empty-trash [--days [DAYS]] [--dry-run]
```

| Argomento / opzione | Cosa fa | Default |
|---|---|---|
| `--days` | Elimina solo gli elementi cancellati da più di N giorni (0 = tutto) | `0` |
| `--dry-run` | Mostra cosa verrebbe eliminato senza cancellare niente | flag |

## `griglia:skills-import`

Importa l'elenco delle skill che l'agente può usare (mostrate nel modale del task)

```bash
php artisan griglia:skills-import [--file [FILE]]
```

| Argomento / opzione | Cosa fa | Default |
|---|---|---|
| `--file` | File JSON (default: stdin) | — |

## `griglia:theme-export`

Esporta un tema generico come pacchetto zip installabile

```bash
php artisan griglia:theme-export [--out [OUT]] [--css-from [CSS-FROM]] [--] <slug>
```

| Argomento / opzione | Cosa fa | Default |
|---|---|---|
| `slug` | Slug di un tema generico (installato, da config, registrato o integrato) | obbligatorio |
| `--out` | Zip di destinazione (default storage/app/theme-<slug>.zip) | — |
| `--css-from` | File CSS da cui estrarre le regole .theme-<slug> (per i temi definiti nel codice) | — |

## `griglia:theme-import`

Installa (o disinstalla) un pacchetto di temi in storage/app/themes

```bash
php artisan griglia:theme-import [--uninstall [UNINSTALL]] [--] <zip>
```

| Argomento / opzione | Cosa fa | Default |
|---|---|---|
| `zip` | Percorso del pacchetto di temi (zip) | obbligatorio |
| `--uninstall` | Invece di importare, disinstalla il tema con questo slug | — |

## `griglia:watch`

Sorveglia la lista dell'agente e stampa solo i cambiamenti (open to work, risposte, stop)

```bash
php artisan griglia:watch [--interval [INTERVAL]] [--list [LIST]] [--agent [AGENT]] [--once] [--no-initial]
```

| Argomento / opzione | Cosa fa | Default |
|---|---|---|
| `--interval` | Secondi fra un controllo e il successivo | `10` |
| `--list` | Nome della lista da sorvegliare (default: la config griglia.agent_list) | — |
| `--agent` | Solo gli eventi di questa chiave d'agente (default: GRIGLIA_AGENT_KEY, oppure l'agente predefinito configurato) | — |
| `--once` | Controlla una volta sola ed esce (per prove e cron) | flag |
| `--no-initial` | Non elencare, alla partenza, gli elementi già open to work | flag |

