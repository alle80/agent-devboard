# Il lato agente

La board non parla mai con un fornitore preciso: il contratto è una CLI. Dai al tuo agente le regole di
`AGENTS.md` (Codex lo legge nativamente; Claude Code legge `CLAUDE.md`; Gemini CLI `GEMINI.md`) e lascialo
lavorare:

```bash
php artisan griglia:watch                       # stampa solo i cambiamenti a cui deve reagire (eventi)
php artisan griglia:watch --agent=codex         # solo gli eventi assegnati a un agente
php artisan griglia:check                       # cosa è open to work o già preso, impostazioni, piani
php artisan griglia:check --take=ID             # presa in carico: il task passa a working (0%)
php artisan griglia:check --take=ID --progress=60 --phase="testando"
php artisan griglia:check --ask=ID --q="Quale?" --choices="Prima|Seconda"     # mette il task in pausa con delle domande
php artisan griglia:check --done=ID --comment="…" [--tokens-in=N --tokens-out=N]
php artisan griglia:check --done=ID --comment="…" --outcome=alert   # fatto, ma va guardato (riga gialla)
php artisan griglia:check --done=ID --comment="…" --outcome=blocked # c'è qualcosa che blocca (riga rossa)
```

Quando possibile, l’agente propone scelte chiuse brevi con `--choices` (ripetuto nello stesso ordine di `--q`). Nel modale diventano risposte selezionabili con un tocco, ma restano sempre disponibili il campo di testo libero e il microfono speech-to-text. Senza opzioni si omette il `--choices` corrispondente.

`check` stampa in testa le **impostazioni** dei gruppi `agent` e `optimization` (politica dei commit,
grado di domande, notifiche, modalità di lavoro, modalità stringata, …) che l'agente deve rispettare, poi le
regole del **grado di domande** scelto (`❓ question level: …` — quante domande fare prima di iniziare; lo stesso
blocco che la board scrive nel [contesto dell'agente](context.md)), poi i task aperti della lista dell'agente e,
dopo di quelli, i task aperti dei **piani** avviati (sotto un titolo `Plan «nome»`).

Regole che vale la pena conoscere: prendere il task **per primo** (prima di leggere e analizzare), un task
alla volta nell'ordine della lista (`task_mode=ordered`) oppure più task indipendenti insieme
(`multitasking`), non toccare mai gli elementi *in attesa*, mollare un task nell'istante in cui viene
fermato (e non riprenderlo finché l'utente non lo rimette 🟢: `--take` rifiuta un task fermato, così un
`--take=ID --progress=N` in ritardo non può riavviarlo di nascosto), tenere aggiornate percentuale e fase, riportare i token alla chiusura quando l'impostazione lo
chiede, e dire con `--outcome` quando un task chiuso non è filato liscio — è quello che
[colora la riga](../board/usage.md#il-colore-della-riga) che vede l'utente (`ok` di default, `alert`,
`blocked`).

Un task nato da una **ripresa** si porta dietro la sua storia: `check` stampa nota, risposta e sotto-task di
ogni passo precedente, dal più recente (`resumes «…»`, poi `2 steps back «…»`, `3 steps back «…»`), perché
anche una ripresa può essere ripresa. Con `--json` la stessa storia sta nel campo `resume_chain` di ogni task,
ordinata dal passo più vicino al più vecchio.

Statistiche: ogni intervallo *working* viene cronometrato da solo; i token sono quelli che riporta l'agente.

**Una sessione pesante costa a ogni passo**, perché il contesto viene riletto a ogni turno. L'impostazione
«suggerisci di ripulire la sessione» (⚡ ottimizzazione, in migliaia di token) è la soglia oltre la quale
l'agente ti dice di lanciare `/clear` — non può farlo al posto tuo.

## Più agenti

Si dichiarano con `GRIGLIA_AGENTS="claude:Claude Code,codex:Codex CLI"`. Una lista (progetto) ha un agente di
default (selettore nella barra), un task può cambiarlo dalla propria riga o dal modale. Il nome resta sempre
visibile: durante la lavorazione diventa un badge di sola lettura sia nella lista sia nel dettaglio. Ogni agente esegue
`griglia:check --agent=<la sua chiave>` (oppure imposta `GRIGLIA_AGENT_KEY`) e vede solo i propri task;
`--take/--done` continuano a funzionare per id. Le [skill](skills.md) proposte su un task sono filtrate allo
stesso modo: solo quelle che il suo agente ha installate.

`--take`, `--done` e `--ask` rifiutano un task che appartiene a un altro agente, e `--take` un task fermato
dall'utente (`--force` forza la mano in entrambi i casi),
`check` stampa una riga `🔒 busy elsewhere` con quello che gli altri hanno in lavorazione, e la baseline 🆕 è
tenuta per chiave d'agente. Quello che si condivide fuori dalla board — checkout, build, migrazioni, rilasci —
sta in [Due agenti insieme](concurrency.md).

Con `griglia:watch --agent=<chiave>` si usa la stessa chiave. Con `--once` il comando stampa anche i task che
erano già in attesa quando è partito, il che lo rende adatto ai cron e ai worker sorvegliati; `--no-initial`
mantiene il comportamento a sola baseline.

## Vedi anche

- [Primi cinque minuti](../getting-started/quickstart.md) — lo stesso flusso, passo per passo.
- [Comandi artisan](../reference/commands.md) — ogni comando e ogni opzione, generati dal codice.
- [Skill](skills.md) · [Contesto dell'agente](context.md) · [Statistiche](stats.md) · [Script sull'host](scripts.md) · [Worker persistenti](workers.md) · [Due agenti insieme](concurrency.md)
