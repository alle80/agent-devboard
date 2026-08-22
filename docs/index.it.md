---
title: Una board di sviluppo per agenti
template: home.html
hero_title: Una board di sviluppo per agenti
hero_text: >-
  Metti in coda le tue richieste come task; il tuo agente le prende, fa domande, racconta come sta andando e
  le chiude — e tu segui tutto dal vivo, dal divano o dal telefono.
hero_quickstart: Primi cinque minuti
hero_documentation: Documentazione
hero_meta: Laravel 12/13 · Livewire 4 · MIT · funziona con Claude Code, Codex CLI, Gemini CLI, …
hide:
  - navigation
  - toc
---

# Cos'è Griglia

**Griglia** è una board Laravel + Livewire che installi dentro la tua applicazione. Una lista è il canale con
il tuo agente: ci scrivi le richieste come task, le segni **open to work**, e l'agente — Claude Code, Codex
CLI, Gemini CLI, una qualunque CLI — le prende, chiede quando qualcosa non è chiaro, tiene in movimento la
barra di avanzamento e le chiude con una risposta che puoi leggere.

Non è un involucro attorno a una chat e non parla con l'API di nessun fornitore: il contratto sono due comandi
artisan e un file di istruzioni. Tutto quello che sa parlare con una shell può guidarla.

<div class="grid cards" markdown>

-   **Un flusso che si vede**

    ---

    In attesa → open to work → working → fatto, con domande, stop e ripresa. Ogni stato è un pallino sulla
    riga: sai sempre cosa l'agente può toccare, e cosa sta facendo in questo momento.

    [Usare la board](board/usage.md)

-   **Un contratto CLI, non un'integrazione**

    ---

    `griglia:check` per leggere e agire, `griglia:watch` per reagire. Percentuale, fase, domande, token e il
    commento di chiusura passano tutti da quei due comandi.

    [Il lato agente](agent/index.md)

-   **Piani da un prompt**

    ---

    Trasforma un obiettivo in una catena di task: chiuderne uno apre il successivo, così un lavoro lungo va
    avanti da solo mentre tu lo guardi procedere.

    [Piani](features/plans.md)

-   **Ti viene a cercare**

    ---

    Aggiornamenti dal vivo fra dispositivi, campanella in-app, Web Push sul telefono e mail — così una domanda
    non resta lì per un'ora.

    [Notifiche](features/notifications.md)

-   **Da guardare volentieri**

    ---

    Un sistema di temi con pacchetti installabili, una pagina di impostazioni che dice all'agente come
    comportarsi, statistiche con tempo di lavoro, token e costo.

    [Temi](features/themes.md) · [Statistiche](agent/stats.md)

-   **Leggera da installare**

    ---

    Un package composer, una migrazione, asset precompilati se non vuoi una build. Inglese e italiano
    compresi.

    [Installazione](getting-started/installation.md)

</div>

## Come funziona in un minuto

1. Scrivi una richiesta nella **lista dell'agente** (di default si chiama `dev`), con note, sotto-task e
   screenshot, e porti il pallino su **open to work**.
2. L'agente esegue `griglia:watch` (gli eventi) e `griglia:check` (cosa fare), prende il task — il pallino
   passa a **working** — fa **domande** quando la richiesta è ambigua, aggiorna percentuale e fase, e lo
   **chiude** con un commento.
3. La board mostra tutto dal vivo, ti avvisa, e tiene le statistiche di quanto è costato.

```bash
composer require alle80/griglia -W
php artisan migrate
php artisan vendor:publish --tag=griglia-agents    # le regole per il tuo agente
```

[Comincia in cinque minuti](getting-started/quickstart.md){ .md-button .md-button--primary }
[Guarda tutte le funzioni](features/index.md){ .md-button }
