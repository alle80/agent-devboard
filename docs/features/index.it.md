# Panoramica delle funzioni

Tutto quello che fa la board, in una pagina. Segui un link quando vuoi il dettaglio.

## Il lavoro vero e proprio

| | |
|---|---|
| **Stati e flusso** | In attesa, open to work, working, domanda, fatto — più uno stop che riprende un task dalle mani dell'agente. [Usare la board](../board/usage.md) |
| **Task con sostanza** | Note in Markdown, sotto-task, immagini allegate (upload, fotocamera, incolla), il commento di chiusura dell'agente tenuto separato dalle tue note. [Usare la board](../board/usage.md) |
| **Avanzamento e fase** | Una percentuale e un breve «cosa sto facendo adesso» su ogni task in lavorazione, aggiornati dall'agente strada facendo. [Il lato agente](../agent/index.md) |
| **Domande** | L'agente può mettere in pausa un task con delle domande; tu rispondi nel modale e lo rimandi al lavoro. [Il lato agente](../agent/index.md) |
| **Riprendi** | Un task finito riparte come task nuovo, che si porta dietro il vecchio contesto. [Usare la board](../board/usage.md) |
| **Liste, archivio, ricerca** | Più liste per utente, filtri di stato, ricerca a testo libero (comprese le descrizioni AI delle immagini), archiviazione automatica dei task vecchi. [Usare la board](../board/usage.md) |

## Guidare un agente

| | |
|---|---|
| **Il contratto CLI** | `griglia:check` e `griglia:watch`: due comandi, nessuna API di un fornitore. [Il lato agente](../agent/index.md) |
| **Le regole che l'agente segue** | La pagina delle impostazioni gli dice come lavorare — politica dei commit, grado di domande, notifiche, un task alla volta o più di uno, modalità stringata. [Configurazione e impostazioni](../configuration/index.md) |
| **Più agenti** | Li dichiari, dai a ogni lista o task il suo, e ogni agente vede solo il proprio lavoro. [Il lato agente](../agent/index.md) |
| **Skill** | Carichi il catalogo delle skill del tuo agente e scegli, task per task, quali deve usare. [Skill](../agent/skills.md) |
| **Contesto dell'agente** | Il tuo file di istruzioni come blocchi accendibili, modificati dalla board ed esportati in `AGENTS.md` / `CLAUDE.md`. [Contesto dell'agente](../agent/context.md) |
| **Statistiche** | Tempo di lavoro misurato da solo, token riportati dall'agente, costo al milione, barre per giorno. [Statistiche](../agent/stats.md) |
| **Stato degli agenti** | Piano e finestre d'uso dei tuoi agenti (usato, rimasto, conto alla rovescia del reset). [Statistiche e stato degli agenti](../agent/stats.md) |

## Tutto intorno

| | |
|---|---|
| **Piani** | Un prompt diventa una catena di task; chiuderne uno apre il successivo. [Piani](plans.md) |
| **Notifiche** | Campanella in-app, Web Push sui tuoi dispositivi, mail — ognuna si accende e si spegne. [Notifiche](notifications.md) |
| **Aggiornamenti dal vivo** | Un qualunque broadcaster di Laravel (Reverb, Pusher…); senza, la board funziona lo stesso. [Eventi](../reference/events.md) |
| **Temi** | Un sistema di temi a variabili CSS, il tema Slate integrato e pacchetti zip installabili. [Temi](themes.md) |
| **AI, facoltativa** | Descrizione delle immagini per la ricerca, dettatura su ogni campo, il costruttore di piani. [Funzioni AI](ai.md) |
| **Modalità** | `server` (login, liste per utente) oppure `local` (niente autenticazione, solo la tua macchina). [Accessi e modalità](../configuration/access.md) |
| **Dashboard desktop** | Una vista più larga della board su `/dashboard`, più una linguetta a scomparsa che la apre da qualsiasi pagina. [Usare la board](../board/usage.md#desktop-la-dashboard) |
| **Mobile** | Un layout pensato per il pollice: bersagli grandi, allegati dalla fotocamera, Web Push. [Usare la board](../board/usage.md#mobile) |
