# Valutazione di sicurezza — 21-08-2026

## In sintesi

La valutazione **non ha trovato vulnerabilità critiche o gravi** nella release del package esaminata,
`v0.70.1` (`c47e825`). Autenticazione, delimitazione per proprietario, autorizzazione amministrativa,
rendering del Markdown, upload, pacchetti di temi ed endpoint HTTP costosi hanno tutti controlli espliciti,
sostenuti da test.

Restano due questioni di difesa in profondità: il disco di default degli allegati può rendere i file
raggiungibili al di fuori del controller autorizzato su un'installazione Laravel convenzionale, e tre oggetti
di configurazione JavaScript inline usano `json_encode()` grezzo invece dell'encoder JSON sicuro per gli script
di Blade. Sono ordinati qui sotto.

## Perimetro e metodo

La revisione ha coperto i sorgenti PHP, Blade e JavaScript del package, le rotte, la configurazione, i manifest
Composer/npm, gli script sull'host, le migrazioni, i test, la documentazione e le indicazioni su build e
rilascio. Ha compreso:

- revisione manuale del flusso dei dati per autenticazione, autorizzazione, delimitazione per proprietario,
  upload, Markdown, ZIP dei temi, Web Push, trascrizione, accesso al filesystem, configurazione e segreti;
- ricerche di primitive pericolose di esecuzione/deserializzazione e di stringhe con la forma di credenziali;
- `composer audit --locked` e `npm audit --omit=dev --audit-level=low`;
- l'insieme mirato di test PHPUnit di sicurezza e regressione (38 test, 216 asserzioni).

È una valutazione a livello di sorgente, non un penetration test di un host in esercizio. Header del reverse
proxy, permessi dell'host, valori dell'ambiente di produzione ed esposizione dell'infrastruttura restano
quindi in carico a chi gestisce il sistema.

## Risultati

### GRSEC-01 — Il disco pubblico degli allegati può scavalcare l'autorizzazione del controller

| Aspetto | Valutazione |
| --- | --- |
| Gravità | Media |
| Impatto | Alto (riservatezza delle immagini caricate) |
| Probabilità | Da bassa a media |
| Priorità | P1 |

**Evidenza.** In `config/griglia.php` `attachments_disk` vale `public` di default, mentre l'URL
dell'applicazione passa normalmente dall'`AttachmentController`, che rispetta il proprietario. Su un host
Laravel standard con `public/storage` collegato, lo stesso oggetto può essere scaricato anche direttamente come
`/storage/attachments/<todo-id>/<ulid>`, dove il controllo `Checklist::mine()` del controller e gli header di
risposta privati non vengono eseguiti. L'ULID rende difficile indovinare l'indirizzo, ma non è un confine di
autorizzazione.

**Rimedio.** Cambiare il default del package verso un disco non pubblico (`local`) alla prossima finestra di
compatibilità. Fino ad allora, le installazioni dovrebbero impostare `GRIGLIA_ATTACHMENTS_DISK=local`, tenere
`GRIGLIA_ATTACHMENTS_VIA_CONTROLLER=true` e assicurarsi che nessun alias del web server esponga quel disco.
Aggiungere un test di regressione sul default sicuro quando verrà cambiato.

### GRSEC-02 — JSON grezzo nei blocchi script inline, senza irrobustimento per il contesto script

| Aspetto | Valutazione |
| --- | --- |
| Gravità | Bassa |
| Impatto | Medio (cross-site scripting se un valore codificato diventasse controllabile da un attaccante) |
| Probabilità | Bassa |
| Priorità | P2 |

**Evidenza.** `resources/views/components/assets.blade.php` emette gli oggetti I18N, dettatura e push con
`{!! json_encode(...) !!}` non sfuggito. I valori attuali arrivano da traduzioni fidate, rotte, stato CSRF e
configurazione, quindi non è stata trovata alcuna via di attacco diretta. L'oggetto Echo lì accanto usa già
`@json` di Blade, che sfugge i caratteri significativi per l'HTML in un contesto script.

**Rimedio.** Emettere tutti e quattro gli oggetti in modo uniforme con `@json` (o `Js::from`) e provarli con
payload che contengono `</script>`, virgolette e separatori Unicode. A livello di host si raccomanda anche una
Content Security Policy stretta con i nonce.

## Controlli verificati

- In modalità server le rotte del package sono autenticate da middleware Livewire persistenti; le pagine di
  amministrazione controllano in più `Admin::allows()` a ogni richiesta del componente.
- Le operazioni su liste e task passano da `Checklist::mine()`, dalla lista corrente o da un controllo
  equivalente sul proprietario; le risposte degli allegati restituiscono 404 fra proprietari diversi.
- Gli upload di immagini verificano il MIME rilevato dal server e il numero di pixel prima di decodificare;
  JPEG e PNG vengono ricodificati. Gli ZIP dei temi rifiutano traversal, estensioni eseguibili, SVG, file e
  archivi fuori misura e riferimenti CSS non sicuri.
- Il Markdown toglie l'HTML grezzo e rifiuta i link non sicuri. Gli asset pubblici dei temi usano un elenco di
  estensioni ammesse, `nosniff` e header CSP di isolamento.
- Gli endpoint push usano un elenco di host HTTPS ammessi e limiti di frequenza; la trascrizione è autenticata,
  limitata nella dimensione e nella frequenza, e non restituisce al browser gli errori del provider.
- Nei sorgenti del package tracciati non sono stati trovati valori con la forma di credenziali. I segreti di
  AI, VAPID e agenti vengono risolti dalla configurazione dell'host, non salvati nei dati della board.
- Al momento della valutazione Composer e npm non segnalavano dipendenze con vulnerabilità note.

## Ordine consigliato

1. P1: spostare la memorizzazione di default degli allegati su un disco privato e documentare l'impatto
   sull'aggiornamento.
2. P2: sostituire la serializzazione JSON grezza inline e aggiungere test di rendering avversariali.
3. Esercizio: tenere `APP_DEBUG=false`, HTTPS e header di sicurezza sul proxy; configurare gli amministratori in
   modo esplicito e non esporre mai la modalità locale, come prescrive la
   [politica di sicurezza](security.md).
