# Usare la board

## Liste

Il menu delle liste (in alto a sinistra) passa da una lista all'altra, ne crea di nuove, le rinomina o le
cancella. Un piano si scrive in una pagina tutta sua — **Nuovo piano…** nello stesso menu, vedi
[Piani](../features/plans.md). La **lista dell'agente** (config `agent_list`) è il canale con l'agente; ogni
altra lista è tua (o è un piano).

## Task e stati

Ogni riga ha il pallino dello stato:

| Pallino | Stato | Chi lo imposta |
|-----|-------|-------------|
| ![in attesa](../images/state-waiting.svg){ width="18" } | in attesa | tu — l'agente non deve toccarlo |
| ![open to work](../images/state-open.svg){ width="18" } | open to work | tu — pronto per l'agente |
| ![working](../images/state-working.svg){ width="18" } | working | l'agente (`--take`) — icona animata, percentuale e fase accanto al titolo |
| ![in pausa](../images/state-paused.svg){ width="18" } | in pausa | l'agente (`--pause`) — conserva l'avanzamento; il worker dell'agente lo riprende da solo |
| ![domanda](../images/state-question.svg){ width="18" } | domanda | l'agente ha chiesto qualcosa; rispondi nel modale e fallo ripartire |
| ![fermato](../images/state-stop.svg){ width="18" } | fermato | hai toccato il badge «working» — l'agente si ferma subito |
| ![fatto](../images/state-done.svg){ width="18" } | fatto | l'agente (`--done`) o tu (spunta) |

Quando c'è un risultato dell'agente, sotto il titolo compare un riassunto automatico molto breve. Gli agenti
possono darne uno più preciso con `griglia:check --done --summary="…"`; altrimenti Griglia lo ricava dal
commento di chiusura. Serve a distinguere una sequenza di task ripresi che hanno tutti lo stesso titolo.

Tocca il pallino per passare fra *in attesa* e *open to work*, fermare l'agente o riaprire un task in pausa.

In fondo alla riga del titolo ogni riga mostra l'**id** del task (`id:510`): lo stesso `id:N` che l'agente stampa
in `griglia:check`, e il numero che usi con `--take` / `--done` o quando parli di un task. Toccalo per copiare il
numero (la targhetta dice *copiato* per un attimo, e il modale non si apre). Il numero grande a sinistra della
riga è la posizione in lista, che cambia riordinando o archiviando; l'id non cambia mai.

### Il colore della riga

Una riga che non hai ancora letto è disegnata con un **bordo colorato attorno alla scheda**, e il colore dice
quanto ha bisogno di te:

| Bordo | Significato | Da dove viene |
|--------|---------|---------------------|
| verde | fatto, niente da controllare | `--done` (senza `--outcome`, oppure `--outcome=ok`) |
| giallo | fatto, ma qualcosa va guardato | `--done --outcome=alert` |
| rosso | c'è qualcosa che blocca | `--done --outcome=blocked` |
| viola | l'agente aspetta le tue risposte | `--ask` (domande aperte) |

I quattro colori sono fissi e non derivano dall'accento del tema, e una riga evidenziata li tiene a piena
intensità: è esente sia dallo sbiadire sia dal grigio che un tema applica alle righe completate, che
altrimenti slaverebbero il bordo.

Le righe completate restano in sordina, ma i loro bottoni usano un grigio più chiaro (`--tl-done-action`) e
un'opacità più decisa, così archivia, riprendi ed elimina restano leggibili anche sui temi scuri e sugli
schermi piccoli.

Il colore è tutto il segnale: nessun badge nella riga, nessuna targhetta nel modale. Apri il task e il bordo
torna quello solito del tema; un task che chiudi tu non ha bordo colorato, perché non c'è nessun risultato da
leggere. Uno screen reader riceve comunque il significato, da un'etichetta nascosta sulla riga, e il tooltip
della riga lo scrive per esteso.

Il colore del bordo la riga se lo scrive addosso (inline), non solo attraverso le classi
`.db-attention` / `.db-att-*`. Un'applicazione che usa le viste del package da `vendor/` mentre il suo foglio
di stile è compilato da un'altra copia del package può ritrovarsi senza alcuna regola per quelle classi:
l'evidenziazione sparirebbe in silenzio. Il foglio di stile aggiunge comunque la pulsazione e si può
ri-tematizzare con `--db-att`.

### Andare avanti dopo che un task è finito

**Un task chiuso resta chiuso.** La spunta e il pallino di stato non lo riaprono: quello che l'agente ha
risposto resta come è stato risposto, e niente di già finito torna davanti.

Per andare avanti c'è una strada sola: **riprendi** (il bottone ↻ nella riga o nel modale). Crea un task
*nuovo* subito dopo quello vecchio, con lo stesso titolo e il vecchio attaccato come contesto — nota,
risposta, sotto-task e immagini restano a un clic (il riquadro è chiuso finché non lo apri: quello che conta
adesso è quello che stai chiedendo oggi), e `griglia:check` li mostra all'agente.

Riprendere un task già ripreso conserva **tutta la catena**: il riquadro elenca ogni passo precedente, dal più
recente fino alla richiesta che ha fatto partire tutto (`+2 precedenti` accanto al titolo), e l'agente riceve
la stessa storia — così niente di quello che è stato chiesto o risposto per strada va perso. Se un anello della
catena viene cancellato, il task successivo viene riagganciato a quello prima, esattamente come nella catena
di un piano.

Nient'altro è una porta a senso unico: un task che esce dalla board (archiviato o cancellato) passa la sua
catena al task che lo precede, così un piano non resta mai in attesa di qualcosa che non arriverà, e un task
con domande aperte può essere ritirato senza rispondere — tocca il suo badge nel modale: le domande restano
registrate e il task torna in attesa.

## Il modale del task
La riga nella board e l’intestazione del modale antepongono al titolo il nome della lista (`Lista · Task`), così il contesto resta visibile anche nelle ricerche su più liste. La rinomina modifica comunque solo il titolo del task.


Titolo, nota **Task** (editor Markdown, con un microfono per la
[dettatura](../features/ai.md#dettatura-vocale-speech-to-text)), il riquadro con la risposta dell'agente, le
statistiche (tempo di lavoro, token, costo), l'accordion delle **skill** dell'agente, le immagini (upload,
fotocamera, incolla; descrizione AI quando è attiva), i sotto-task (Markdown, riordinabili), domande e
risposte, il contesto del task ripreso. Nell'intestazione: badge di stato con etichetta testuale sempre
visibile accanto all'icona (si tocca per cambiarlo), ‹ `3/7` › e l'id del task (`id:510`, si tocca per
copiare il numero; sul telefono scende sulla riga dei comandi), **sposta in un'altra lista**, archivia, elimina; su un task completato: **riprendi con
modifiche** (un nuovo
task collegato).

Quando sono configurati più agenti, l’agente selezionato compare su una riga propria sotto il titolo del task. La
select nativa si dimensiona sul nome dell’agente selezionato e non lo sostituisce mai con i puntini di sospensione;
l’unico limite resta la larghezza disponibile della riga.

Un task è in sola lettura mentre è **in lavorazione**, così la richiesta non cambia sotto i piedi
dell’agente. Tocca il badge working per fermarlo e riportarlo in attesa prima di modificarlo; poi potrai
rimetterlo open to work.

### Il salvataggio si fa da solo

Titolo e nota si salvano **mentre scrivi** — basta una pausa. Non c'è più il bottone «Salva»: quello che
c'è scritto viene salvato automaticamente. La scritta «Salvato» accanto al campo dice quando è
successo, e la modifica si chiude con `Invio` (il titolo), `Esc` o un clic fuori dal campo.

Appena il testo è diverso da quello di partenza compare il bottone **Annulla** (↩)
accanto alla scritta «Salvato»: rimette il titolo — o la nota — com'era quando hai aperto la modifica e ti
lascia dentro il campo, per continuare da lì. È un passo indietro, non uno storico: se chiudi e riapri, la
«versione precedente» diventa il testo che hai appena lasciato.

Vale anche per la rinomina al volo di una riga della lista. I sotto-task non si salvano da soli: lì i
bottoni ✓ e ✕ restano.

### Passare da un task all'altro

Il modale ha ‹ e › accanto al badge di stato, con la posizione del task nella lista (`3/7`): aprono il task
precedente e quello successivo senza chiudere il modale — è il modo di seguire un piano da un passo all'altro.
I **tasti freccia sinistra e destra** fanno lo stesso, a meno che tu non stia scrivendo in un campo.

### Copiare quello che c'è in una nota

Note e risposte dell'agente sono Markdown: gli a capo singoli si vedono come tali, un **blocco di codice ha il
bottone «copia»** nel suo angolo (comandi, prompt, frammenti), il **codice inline si copia con un clic** e i
link si aprono in una nuova scheda.
Durante la modifica di una nota il browser dimensiona il campo direttamente dal contenuto: i salvataggi in
background non possono quindi richiuderlo o nascondere le ultime righe, e restano silenziosi per non interrompere
la scrittura.

## Barra degli strumenti

Su desktop le etichette compatte, i filtri e la barra dell’editor Markdown hanno dimensioni più leggibili; su mobile restano invece più densi per lasciare
spazio ai contenuti della lista e del modale.

Ricerca a testo libero (titolo, note, commento, sotto-task, domande, descrizioni delle immagini), filtri di
stato e di agente, archivio. Con più agenti configurati il chip con l'icona del robot (**Tutti gli agenti**)
restringe la lista ai task di un solo agente; il filtro segue l'assegnazione effettiva (agente del task, altrimenti
default della lista, altrimenti agente predefinito), si combina con ricerca e filtri di stato e — come loro —
disabilita il trascinamento finché è attivo. Lo stile non cambia dopo la scelta: è il nome selezionato a indicare
il valore attivo. Attiva **Tutte le liste** accanto alla ricerca per mostrare i task di tutte le tue liste attive.
L'ambito vale per la lista senza filtri e anche per ricerca, stato e agente; ogni risultato mostra la lista di origine.
Le liste archiviate e quelle di altri utenti restano escluse. Su una lista-piano la barra **Piano** mostra l'avanzamento e i bottoni avvia/pausa (vedi
[Piani](../features/plans.md)).

I due bottoni della visuale cambiano l'area dei task fra l'**elenco** originale e una **griglia** di card verticali.
La griglia usa una colonna su telefono, due su tablet e tre su desktop; il browser ricorda la scelta per la visita successiva.

## Desktop: la dashboard

Su uno schermo grande la board ha una seconda casa: **`/dashboard`**, la stessa lista in un contenitore più
largo e arioso (`max-w-5xl` invece di `max-w-2xl`), così titoli e note lunghe smettono di andare a capo ogni
tre parole. Sono lo stesso componente e gli stessi dati — stati, modale, filtri, trascinamento si comportano
esattamente come su `/` — cambia solo la larghezza. La pagina veste il tema corrente quando quel tema è uno dei
generici; da uno stile dedicato dell'applicazione ospite (che non ha le variabili CSS condivise) ripiega sul
tema predefinito.

Il percorso viene dalla chiave di configurazione `dashboard_route` (`GRIGLIA_DASHBOARD_ROUTE`, default
`/dashboard`): mettila a `null` e la rotta sparisce, insieme alla linguetta qui sotto.

### La linguetta laterale

Ogni pagina si porta dietro una **linguetta a scomparsa attaccata a un bordo della finestra** — una maniglia,
in stile debugbar, che apre un pannello con dentro la dashboard. Clic sulla maniglia e il pannello esce;
trascina il suo bordo interno per ridimensionarlo (da 300px fino al 70% della finestra); ⤢ apre la dashboard
intera nella scheda in cui sei; ✕ chiude il pannello. Se è aperto e quanto è largo se li ricorda il browser
(`localStorage`), quindi il pannello torna come l'hai lasciato anche nella pagina successiva. Sulla dashboard
stessa non compare, ed è **solo desktop**: sotto il breakpoint `lg` non viene proprio disegnata, perché su un
telefono non c'è spazio da regalare.

Due impostazioni in `/settings` la governano:

| Impostazione | Cosa fa |
|---|---|
| **Linguetta laterale DASHBOARD** (`show_dashboard_tab`) | Mostra o nasconde la linguetta. Spenta, la dashboard resta raggiungibile al suo indirizzo. |
| **Lato del pannello dashboard** (`tab_side`) | Su quale bordo sta la linguetta — `right` (default) o `left`. |

## Mobile

È tutto pensato per il telefono: righe su due livelli, modale a tutto schermo, pannello delle notifiche a
tutta larghezza, Web Push.

L'intestazione del modale si impila su uno schermo stretto: il badge di stato conserva l'etichetta testuale
accanto all'icona e con ‹ `3/7` › resta sulla prima riga accanto al bottone di chiusura — sempre comprensibile
e raggiungibile, qualunque cosa contenga la lista —, la tendina
dell'agente prende la riga sotto, allineata a sinistra, e gli altri comandi (sposta, archivia, elimina) stanno
sull'ultima riga, aperta a sinistra dalla targhetta dell'id (`id:510`), che lascia la prima riga così il bottone di
chiusura non va mai a capo; bersagli abbastanza grandi per un pollice. La tendina ha una riga tutta sua su qualsiasi
schermo: in mezzo alle icone la sua etichetta («Predefinito (Claude Code)») finiva tagliata. Niente è nascosto
dietro un menu, e niente esce dal bordo dello schermo.

## Vedi anche

- [Il lato agente](../agent/index.md) — cosa fa l'agente con quello che scrivi qui.
- [Piani](../features/plans.md) · [Notifiche](../features/notifications.md) · [Funzioni AI](../features/ai.md)
- [Panoramica delle funzioni](../features/index.md) — tutta la board in una pagina.
