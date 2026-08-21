# Primi cinque minuti

Cinque minuti, da una board vuota al primo task lavorato da un agente. Diamo per scontato che il package sia
già [installato](installation.md).

## 1. Apri la board

Entra nella tua applicazione e vai su `/` (o sul tuo `route_prefix`). Trovi una lista — la **lista dell'agente**,
che di default si chiama `dev` (`config('griglia.agent_list')`). Quella lista è il canale fra te e l'agente:
tutto quello che ci metti dentro è una richiesta.

## 2. Scrivi una richiesta

Aggiungi un task con il campo in cima, poi aprilo (clic sul titolo) per metterci quello che serve all'agente:

- **nota** — i dettagli, con parole tue;
- **sotto-task** — l'elenco di cose che ti aspetti;
- **immagini** — screenshot, incollati o scattati con la fotocamera.

La riga nasce *in attesa*: l'agente non deve toccarla. Quando la richiesta è pronta, clicca il pallino per
portarla a **open to work**.

## 3. Spiega le regole al tuo agente

Pubblica il file di istruzioni e indicalo al tuo agente:

```bash
php artisan vendor:publish --tag=griglia-agents     # AGENTS.md alla radice del progetto
```

Claude Code legge `CLAUDE.md`, Codex e quasi tutti gli altri `AGENTS.md`, Gemini CLI `GEMINI.md` — stesso
contenuto; copialo o fai un symlink. Cosa dicono quelle regole sta in [Il lato agente](../agent/index.md).

## 4. Lascia lavorare l'agente

Nella cartella del progetto l'agente esegue:

```bash
php artisan griglia:check                      # cosa è open to work, più le impostazioni da rispettare
php artisan griglia:check --take=12            # presa in carico: working, la percentuale parte da 0%
php artisan griglia:check --take=12 --progress=60 --phase="scrivendo codice"
php artisan griglia:check --done=12 --comment="Cosa ho fatto e come provarlo"
```

Mentre succede, la board si aggiorna dal vivo: il pallino passa a *working*, la barra di avanzamento e la fase
si muovono, e il commento di chiusura compare sotto la nota come risposta dell'agente. Se la richiesta è
ambigua l'agente la mette in pausa con delle domande:

```bash
php artisan griglia:check --ask=12 --q="Quale dei due layout?" --q="In italiano o in inglese?"
```

Rispondi nel modale del task e premi **riparti**: il task torna *open to work*.

## 5. Tienilo in ascolto

```bash
php artisan griglia:watch      # resta aperto: stampa solo gli eventi a cui l'agente deve reagire
```

## E poi

- [Usare la board](../board/usage.md) — stati, filtri, archivio, mobile.
- [Piani](../features/plans.md) — spezzare un obiettivo in task concatenati.
- [Configurazione e impostazioni](../configuration/index.md) — come chiedere all'agente di comportarsi.
- [Panoramica delle funzioni](../features/index.md) — tutto quello che fa la board, in una pagina.
