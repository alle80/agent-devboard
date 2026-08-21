# Piani

Un **piano** è una lista costruita a partire da un prompt. Dal menu delle liste, **Nuovo piano…** apre una
pagina tutta sua — `/plans/new` — perché descrivere un obiettivo richiede un paragrafo, non una riga:

- **l'obiettivo** è il campo che conta: un riquadro grande, con il microfono per la dettatura e il conteggio
  dei caratteri. Ctrl/⌘+Invio costruisce il piano senza staccare le mani dalla tastiera;
- **il nome della lista** è facoltativo: lascialo vuoto e le prime parole dell'obiettivo diventano il nome;
- **l'agente** del piano si può scegliere qui, quando l'installazione ne dichiara più di uno.

Quello che scrivi viene tenuto come bozza, così se esci dalla pagina e torni lo ritrovi dov'era, e annullare
chiede conferma prima di buttarlo via. Premendo *Costruisci il piano* l'obiettivo passa all'agente
`PlanBuilder` dell'AI SDK, che lo spezza in task ordinati con note e sotto-task, **concatenati**
(`depends_on_id`), e ti riporta alla board. Senza un provider AI la lista riceve un unico task «Costruisci il
piano» per l'agente; se l'AI fallisce, non resta in giro nessuna lista fatta a metà.

## Cambiare un piano

La barra **Piano** ha il link *Modifica il piano* — `/plans/{list}/edit` — con dentro l'obiettivo originale.
Da lì puoi:

- **salvare l'obiettivo** (e il nome o l'agente della lista): i task restano esattamente come sono;
- **ricostruire i task**: vengono sostituiti solo quelli che nessuno ha iniziato. I task già fatti, presi in
  carico dall'agente o in attesa di una risposta non si toccano mai, e la conferma dice quanti ne saranno
  sostituiti.

## Far girare un piano

- **Avvia il piano** (il bottone di avvio nella barra Piano o nel menu delle liste): il primo task non ancora
  iniziato diventa *open to work*; quando è completato si apre da solo il successivo.
- **Pausa**: i task aperti tornano *in attesa* e la catena si ferma; **Riprendi** toglie la pausa e apre il
  successivo.
- I task nuovi aggiunti a una lista-piano entrano nella catena da soli; a piano finito puoi aggiungere task e
  riprendere.
- `griglia:check` / `griglia:watch` coprono anche i piani avviati (dopo la lista dell'agente).
- Se un piano ha ancora del lavoro ma non c'è niente open to work, `griglia:check` lo dice: è l'unico stato in
  cui potresti star aspettando un agente che sta aspettando te.

## Vedi anche

- [Il lato agente](../agent/index.md) — come l'agente percorre la catena.
- [Funzioni AI](ai.md) — il modello che costruisce il piano.
