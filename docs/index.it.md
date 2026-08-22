---
title: Una task board open source per agenti di coding
template: home.html
hero_title: Una task board open source per agenti di coding
hero_text: >-
  Griglia offre a sviluppatori e agenti di coding CLI un flusso condiviso e osservabile per richieste, domande,
  avanzamento e risultati. Vive nella tua applicazione Laravel e resta sotto il tuo controllo.
hero_quickstart: Primi cinque minuti
hero_documentation: Documentazione
hero_meta: Laravel 12/13 · Livewire 4 · MIT · funziona con Claude Code, Codex CLI, Gemini CLI, …
hide:
  - navigation
  - toc
---

# Cosa fa Griglia

**Griglia** è una task board Laravel + Livewire per sviluppatori che usano agenti di coding. Scrivi una richiesta,
aggiungi note, sotto-task o screenshot e decidi quando è pronta. Un agente CLI può quindi prenderla,
fare domande, comunicare la fase corrente e chiuderla con un risultato registrato.

La board rende questo scambio visibile e persistente. Può coordinare più liste e agenti,
trasformare un obiettivo in un piano ordinato, notificarti quando serve una risposta e conservare dati su tempi di lavoro, token e costi.

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

## Perché usarla invece della sola sessione CLI?

Una sessione nel terminale è efficace mentre uno sviluppatore la segue, ma lo stato del lavoro tende a
essere locale e temporaneo. Griglia aggiunge un piano di controllo persistente attorno all.agente CLI senza sostituirlo:

- le richieste hanno una coda e un'autorizzazione a partire;
- domande, avanzamento e risultati restano collegati al task;
- il lavoro può proseguire in nuove sessioni o worker persistenti;
- un altro dispositivo o membro del team può capire lo stato senza leggere il transcript del terminale.

L'agente continua a modificare codice, eseguire test e usare Git con i suoi normali strumenti CLI. Griglia coordina il lavoro intorno a quella sessione.

## Cosa non è Griglia

Griglia non è un modello di coding autonomo, un IDE, un'interfaccia chat o un servizio agentico in hosting. Non invia prompt all'API di un provider e non elimina la necessità di revisionare le modifiche. Tu porti l.agente e decidi come eseguirlo; Griglia fornisce il flusso di task condiviso.

## Open source per scelta

Griglia è distribuita con licenza MIT. Board, protocollo per gli agenti, migrazioni e documentazione sono nel sorgente del package: un team può ispezionare il flusso, adattarlo alla propria applicazione Laravel e tenere i dati operativi sull'infrastruttura che controlla.

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
