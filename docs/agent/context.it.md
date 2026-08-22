# Contesto dell'agente

Il file di istruzioni che il tuo agente legge a ogni passo (per esempio `CLAUDE.md`) si può **gestire dalla
board** (`/context`, solo amministratori):

```bash
php artisan griglia:context import --file=CLAUDE.md     # una volta: markdown → gruppi (##) e blocchi
php artisan griglia:context export                       # i blocchi attivi come markdown
php artisan griglia:context status
```

Ogni gruppo e ogni blocco ha un interruttore; i blocchi si possono selezionare a più a più e
attivare/disattivare insieme, modificare (editor Markdown), aggiungere, cancellare e riordinare; viene mostrata
una stima dei token. Sull'host un piccolo script riscrive l'export nei file di istruzioni:
`scripts/sync-context.py`, distribuito con il package (vedi [gli script](scripts.md) — CLAUDE.md e AGENTS.md,
cron ogni minuto). Gli interruttori per risparmiare token stanno in Impostazioni → Ottimizzazione.

## Tenere i file originali

L'interruttore **Genera i file di istruzioni dalla board** (in cima a `/context`, impostazione
`app.context_sync`) decide se la sincronizzazione sull'host scrive i file generati (acceso) oppure ripristina e
lascia stare i file **originali** (spento) — utile quando smetti di usare la board: tornano a valere gli
originali. Gli script sull'host lo leggono con `php artisan griglia:context enabled` (stampa `1`/`0`).

## Blocchi generati dalla board

Alcuni blocchi li scrive la board stessa a partire da un'impostazione e portano una chiave (`context_blocks.key`):
oggi il **grado di domande** (`question_level`, Impostazioni → Come lavora l'agente → Grado di domande). Su
`/context` mostrano *generato dalle Impostazioni* e non si modificano — cambia l'impostazione e il blocco viene
riscritto dov'è, conservando interruttore e posizione; spegnilo o eliminalo se non lo vuoi (ricompare al prossimo
salvataggio). Anche `griglia:context import` lo conserva: un file generato reimportato viene adottato (niente
doppioni), e a un file che non lo contiene il blocco viene aggiunto in coda in un gruppo suo.

## Vedi anche

- [Il lato agente](index.md) · [Configurazione e impostazioni](../configuration/index.md)
