# Impostazioni

<!-- Generato da `php artisan griglia:docs-generate` — non modificare a mano. -->

Le opzioni della pagina `/settings`: salvate nel database, si cambiano a runtime, senza deploy. Etichette e testi di aiuto sono quelli che mostra la pagina.

## Come lavora l'agente (`agent`)

| Impostazione | Tipo | Cosa fa |
|---|---|---|
| **Commit dopo ogni task completato** (`commit_after_task`) | bool | Alla chiusura di un task l'agente fa commit senza chiedere. Se spento, il lavoro resta non committato finché non lo chiedi. |
| **Push automatico su GitHub** (`push_after_commit`) | bool | Dopo il commit automatico fa anche il push. Se spento, il push lo chiedi tu. |
| **Autonomia** (`autonomy`) | select: `ask`, `decide` | Come si comporta quando una richiesta è ambigua. |
| **Notifica push a chiusura task** (`notify_on_done`) | bool | Notifica sul telefono quando un task viene chiuso. |
| **Notifica push per le domande** (`notify_on_question`) | bool | Notifica sul telefono quando l'agente ha una domanda. |
| **Verifica prima di chiudere** (`verify_before_close`) | bool | Screenshot mobile+desktop e test Livewire automatici prima di chiudere un task (più lento, più sicuro). |
| **Commento dell'agente** (`comment_detail`) | select: `short`, `detailed` | Quanto è dettagliata la risposta sotto la nota. |
| **Git** (`git_flow`) | select: `main`, `branch_pr` | Dove finiscono i commit di ogni task. |
| **Riepilogo serale** (`daily_summary`) | bool | Una notifica push la sera con cosa è stato chiuso in giornata. |
| **Ora del riepilogo** (`daily_summary_time`) | time | A che ora arriva il riepilogo serale. |
| **Spunta i sotto-task alla chiusura** (`check_subtasks_on_done`) | bool | Quando l'agente chiude un task, tutti i suoi sotto-task vengono spuntati. |
| **Modalità di lavoro sui task** (`task_mode`) | select: `ordered`, `multitasking` | Se l'agente lavora i task «da fare» uno alla volta in ordine, oppure più task in parallelo. |

## Ottimizzazione (`optimization`)

| Impostazione | Tipo | Cosa fa |
|---|---|---|
| **Output compatto del comando** (`compact_check`) | bool | Dopo --take/--done/--ask/--progress il comando stampa solo la riga di esito, senza impostazioni ed elenco (circa 700 caratteri risparmiati a chiamata, che altrimenti restano nella chat per tutta la sessione). |
| **Modalità stringata** (`terse_agent`) | bool | L'agente scrive quasi nulla in chat (niente spiegazioni né riepiloghi), accorpa i comandi, legge solo il necessario e non ripete i controlli. La chat diventa poco leggibile per un umano; la board (commento dell'agente, notifiche) continua a funzionare come sempre. |
| **Max caratteri di contesto** (`context_max_chars`) | int | Taglia a N caratteri note/commenti precedenti stampati dal comando (task ripresi, commenti di l'agente). 0 = nessun taglio. |
| **Percentuale solo «a bordo»** (`progress_piggyback`) | bool | l'agente aggiorna la % solo insieme ad altri comandi, mai con un passo apposta (ogni passo costa una rilettura intera del contesto). |
| **Registra i token alla chiusura** (`token_report`) | bool | Alla chiusura di un task l'agente conta e registra i token spesi (un comando in più per task). Spento = solo il tempo, niente token. |
| **Suggerisci di ripulire la sessione (migliaia di token)** (`clear_reminder_k`) | int | Il contesto viene riletto a ogni turno: oltre un certo peso ogni singolo passo costa di più. Superata questa soglia l'agente ti avvisa di lanciare /clear (non può farlo lui). 0 = mai. |

## App (`app`)

| Impostazione | Tipo | Cosa fa |
|---|---|---|
| **Stile predefinito** (`default_style`) | select | Aprendo il sito vai dritto a questo stile. |
| **Lunghezza massima titolo** (`title_max_length`) | int | Caratteri massimi per il titolo di un elemento (10–200). |
| **Archiviazione automatica (giorni)** (`auto_archive_days`) | int | I completati da più di N giorni finiscono in archivio da soli, ogni notte. 0 = mai. |
| **Descrizione AI delle immagini** (`ai_describe_images`) | bool | Ogni immagine caricata viene descritta da un modello AI per la ricerca (serve una chiave API in .env). |
| **Provider AI immagini** (`ai_image_provider`) | select | Quale provider usare per descrivere le immagini. |
| **Modello AI immagini** (`ai_image_model`) | text | Nome modello (vuoto = il più economico del provider / AI_IMAGE_MODEL). |
| **Toast per i cambi da console** (`toast_console_changes`) | bool | Avviso in pagina quando l'agente cambia lo stato di un elemento (in lavorazione, fatto, domanda). |
| **Modalità della board** (`mode`) | select | Server = serve il login, ogni utente ha le sue liste (accesso limitabile, vedi config). Local = NESSUNA autenticazione, un solo insieme di liste globali: solo per una board sulla propria macchina. Vuoto = come nel config (GRIGLIA_MODE). |
| **Linguetta laterale DASHBOARD** (`show_dashboard_tab`) | bool | Mostra la linguetta scorrevole della dashboard sul bordo della finestra (desktop). |
| **Speech to text** (`speech_mode`) | select | Auto = trascrizione sul server (AI SDK, OpenAI…) se configurata, altrimenti il browser. Server = registra e trascrive sul server (qualità migliore, funziona su ogni browser con microfono). Browser = riconoscimento del browser (gratis, niente server). |
| **Prezzo per 1M token input** (`cost_per_m_in`) | text | Usato dalle statistiche per trasformare i token in costo (es. 3 per 3 €/M). 0 = costo non mostrato. |
| **Prezzo per 1M token output** (`cost_per_m_out`) | text | Idem per i token di output (es. 15). |
| **Valuta** (`cost_currency`) | text | Simbolo o codice mostrato accanto ai costi (EUR, $, …). |
| **Genera i file di istruzioni dalla board** (`context_sync`) | bool | Se spento, la sincronizzazione sul server ripristina i CLAUDE.md / AGENTS.md originali e non li tocca più. |
| **Notifiche in-app** (`notify_in_app`) | bool | Campanella in cima alla board con quello che ha fatto l'agente (task chiuso, domanda posta), in tempo reale. |
| **Notifiche Web Push** (`notify_webpush`) | bool | Notifiche sui dispositivi dove le hai attivate (bottone qui sotto), anche ad app chiusa. iPhone: prima aggiungi l'app alla schermata Home. |
| **Notifiche via e-mail** (`notify_mail`) | bool | Anche per e-mail all'indirizzo del tuo account (serve un mailer configurato: MAIL_MAILER). |
| **Lato del pannello dashboard** (`tab_side`) | select | Da quale lato della finestra si apre il pannello a scomparsa della dashboard (desktop). |

