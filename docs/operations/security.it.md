# Sicurezza

Il modello completo, la lista di irrobustimento e come segnalare una vulnerabilità stanno in
[SECURITY.md](https://github.com/alle80/griglia/blob/master/SECURITY.md). In breve:

L'ultima revisione del codice sorgente, con le sue priorità, sta nella
[valutazione di sicurezza del 21-08-2026](security-assessment-2026-08-21.md).


## Cosa garantisce il package

- **Tutto è delimitato al proprietario.** Liste, task, sotto-task, domande e allegati si leggono sempre
  attraverso l'ambito dell'utente corrente: non c'è una rotta che restituisca la board di qualcun altro.
- **L'amministrazione è un gate separato.** Impostazioni, contesto dell'agente e pacchetti di temi sono solo
  per amministratori — `canManageDevboard()`, una ability del Gate o `GRIGLIA_ADMINS`; di default solo il primo
  utente registrato. Vedi [Accessi e modalità](../configuration/access.md).
- **Gli upload sono validati**: tipo e dimensione controllati, immagini ricodificate, salvate di default sul
  disco privato `local` e servite solo attraverso il controller che rispetta il proprietario. Tieni
  `GRIGLIA_ATTACHMENTS_VIA_CONTROLLER=true`.
- **I pacchetti di temi sono trattati come codice**: installazione solo per amministratori, SVG rifiutati, CSS
  ripulito (niente `@import`, niente url esterni), tetti su dimensione del file, dimensione del pacchetto e
  numero di file, asset serviti da una rotta isolata.
- **Gli endpoint costosi hanno un limite di frequenza** (trascrizione, notifica di prova, sottoscrizione push).
- **I segreti restano nel `.env`** e negli script sull'host: niente che arrivi al browser o a un pacchetto di
  temi.

## Cosa tocca a te

- Tieni la board dietro il login della tua applicazione (modalità server). **La modalità locale non ha alcuna
  autenticazione**: legala a `127.0.0.1`, non esporla mai.
- Metti l'applicazione dietro HTTPS — Web Push e microfono hanno comunque bisogno di un contesto sicuro.
- Dai all'agente le credenziali che gli servono e nulla di più: gira sulla tua macchina, con la tua shell.

## Segnalazioni

Per favore non aprire una issue pubblica per una vulnerabilità: il contatto e il processo di divulgazione
stanno in [SECURITY.md](https://github.com/alle80/griglia/blob/master/SECURITY.md).
