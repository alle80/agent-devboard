<?php

/*
 * Italian catalogue of the generated reference pages (`griglia:docs-generate`).
 *
 * `pages` translates the fixed parts of the pages (titles, intros, table headers); `text` translates the
 * strings that come from the code — command descriptions, option descriptions, the comments of
 * config/griglia.php — and is keyed by the English source, so a new string simply stays in English until
 * somebody translates it here (the command lists the ones still waiting). The settings page needs nothing:
 * its labels and help come from resources/lang/it/t.php, the same strings the /settings page shows.
 */

return [

    'pages' => [
        'generated' => 'Generato da `php artisan griglia:docs-generate` — non modificare a mano.',
        'the_agent' => "l'agente",

        'commands_title' => 'Comandi artisan',
        'commands_intro' => 'Tutto quello che il package aggiunge a `php artisan`, preso dalle definizioni dei comandi.',
        'alias' => 'Alias',
        'required' => 'obbligatorio',
        'flag' => 'flag',

        'config_title' => 'File di configurazione',
        'config_intro' => "Ogni chiave di `config/griglia.php` — pubblicalo con `php artisan vendor:publish --tag=griglia-config`.\n"
            .'La decide chi installa il package; le opzioni che si cambiano a runtime stanno nelle [Impostazioni](settings.md).',

        'settings_title' => 'Impostazioni',
        'settings_intro' => 'Le opzioni della pagina `/settings`: salvate nel database, si cambiano a runtime, senza deploy. '
            .'Etichette e testi di aiuto sono quelli che mostra la pagina.',

        'col_option' => 'Argomento / opzione',
        'col_what' => 'Cosa fa',
        'col_default' => 'Default',
        'col_key' => 'Chiave',
        'col_env' => "Variabile d'ambiente",
        'col_whatis' => "Cos'è",
        'col_setting' => 'Impostazione',
        'col_type' => 'Tipo',
    ],

    'text' => [

        // ----- griglia:agent-status-import
        'Imports the agents status snapshot (plan + usage windows) shown in /agents' => "Importa lo snapshot dello stato degli agenti (piano + finestre d'uso) mostrato in /agents",
        'JSON file (default: stdin)' => 'File JSON (default: stdin)',

        // ----- griglia:auto-archive
        'Archives completed todos older than N days (see /settings)' => 'Archivia i todo completati da più di N giorni (vedi /settings)',
        'Only show what would be archived' => 'Mostra soltanto cosa verrebbe archiviato',

        // ----- griglia:check
        'Lists the open requests of the agent list (see config griglia.agent_list)' => "Elenca le richieste aperte della lista dell'agente (vedi la config griglia.agent_list)",
        'Also show completed items and items not open to work' => 'Mostra anche gli elementi completati e quelli non ancora open to work',
        'Machine-readable output' => 'Output leggibile da un programma',
        'Machine-readable tasks plus worker scheduling settings' => 'Task e impostazioni di pianificazione del worker in formato leggibile da un programma',
        'Id of the todo to mark as working (take in charge)' => 'Id del todo da mettere in lavorazione (presa in carico)',
        'Id of the todo to mark as completed' => 'Id del todo da segnare come completato',
        'Id of the working todo to pause until its agent worker can resume it' => 'Id del todo in lavorazione da mettere in pausa finché il worker del suo agente può riprenderlo',
        'Id of a working review attempt to approve' => 'Id del tentativo di revisione in lavorazione da approvare',
        'Id of a working review attempt that must return to its executor' => 'Id del tentativo di revisione in lavorazione da restituire al suo esecutore',
        'Agent comment saved on the todo of --take/--done (claude_comment)' => "Commento dell'agente salvato sul todo di --take/--done (claude_comment)",
        'Agent comment saved on --take/--done/--approve/--request-changes (claude_comment)' => "Commento dell'agente salvato con --take/--done/--approve/--request-changes (claude_comment)",
        'Very short result summary shown below the task title (with --done)' => 'Riassunto brevissimo del risultato, mostrato sotto il titolo del task (con --done)',
        'Progress percentage 0-100 shown on the working todo (with --take; re-run --take=ID --progress=N to update). --take alone starts at 0%' => 'Percentuale di avanzamento 0-100 mostrata sul todo in lavorazione (con --take; per aggiornarla rilancia --take=ID --progress=N). --take da solo parte da 0%',
        'Short text of what the agent is doing now (with --take; e.g. "writing code", "testing"); shown next to the %' => 'Testo breve su cosa sta facendo adesso l\'agente (con --take; per esempio "scrivendo codice", "testando"); mostrato accanto alla %',
        'With --done: how the result feels — ok (default, nothing to check), alert (done, but something needs a look) or blocked (something is in the way). It colours the row until the user opens it' => 'Con --done: come è andata — ok (default, niente da controllare), alert (fatto, ma qualcosa va guardato) oppure blocked (c\'è qualcosa che blocca). Colora la riga finché l\'utente non la apre',
        'Id of the todo to ask questions about (the task pauses in the question state)' => 'Id del todo su cui fare domande (il task si mette in pausa nello stato «domanda»)',
        'Text of each question, repeatable' => 'Testo di ogni domanda, ripetibile',
        'Pipe-separated closed choices for the corresponding --q, repeatable; free text remains available' => 'Scelte chiuse separate da | per il --q corrispondente, ripetibile; il testo libero resta disponibile',
        'Input tokens spent on the todo since the last --take (added to its stats; with --take/--done/--ask)' => "Token in ingresso spesi sul todo dall'ultimo --take (sommati alle sue statistiche; con --take/--done/--ask)",
        'Output tokens spent on the todo since the last --take (added to its stats; with --take/--done/--ask)' => "Token in uscita spesi sul todo dall'ultimo --take (sommati alle sue statistiche; con --take/--done/--ask)",
        'Only the tasks of this agent key (multi-agent; default: GRIGLIA_AGENT_KEY, or every task when one agent)' => "Solo i task di questa chiave d'agente (più agenti; default: GRIGLIA_AGENT_KEY, oppure tutti i task quando l'agente è uno solo)",
        'Input tokens spent since the last --take (with any task action)' => "Token in ingresso spesi dall'ultimo --take (con qualsiasi azione sul task)",
        'Output tokens spent since the last --take (with any task action)' => "Token in uscita spesi dall'ultimo --take (con qualsiasi azione sul task)",
        'Act on a task that belongs to another agent, or take again a task the user stopped (--take/--done/--ask refuse it otherwise)' => "Agisci su un task che appartiene a un altro agente, oppure riprendi un task fermato dall'utente (--take/--done/--ask altrimenti lo rifiutano)",

        // ----- griglia:context
        'Agent context (instructions file) as switchable groups/blocks: import, export, status' => "Contesto dell'agente (file di istruzioni) come gruppi/blocchi accendibili: import, export, status",
        'import|export|status|enabled' => 'import|export|status|enabled',
        'markdown file for import (default: stdin)' => 'file markdown da importare (default: stdin)',
        'import: wipe the current context first' => 'import: cancella prima il contesto attuale',
        'export: include disabled groups/blocks' => 'export: includi anche gruppi e blocchi spenti',

        // ----- griglia:describe-images
        'Generates the AI text description of attached images (used by the search)' => 'Genera con l\'AI la descrizione testuale delle immagini allegate (la usa la ricerca)',
        'Also regenerate existing descriptions' => 'Rigenera anche le descrizioni già presenti',

        // ----- griglia:docs-build / docs-generate
        'Builds the package documentation as a static HTML site with MkDocs (Material theme)' => 'Compila la documentazione del package come sito HTML statico con MkDocs (tema Material)',
        'Output directory (default: <package>/site)' => 'Cartella di destinazione (default: <package>/site)',
        'Run `mkdocs serve` (live preview) instead of building' => 'Lancia `mkdocs serve` (anteprima dal vivo) invece di compilare',
        'Use the squidfunk/mkdocs-material Docker image instead of a local mkdocs' => 'Usa Docker (immagine costruita da docs.Dockerfile) invece di un mkdocs locale',
        'Pass --strict to mkdocs (warnings fail the build)' => 'Passa --strict a mkdocs (gli avvisi fanno fallire la build)',
        'Do not refresh the generated reference pages before building' => 'Non rigenerare le pagine di reference prima di compilare',
        'Generates the reference pages of the documentation (commands, config, settings) from the code' => 'Genera dal codice le pagine di reference della documentazione (comandi, config, impostazioni)',
        'Output directory (default: <package>/docs/reference)' => 'Cartella di destinazione (default: <package>/docs/reference)',
        'Do not write; exit with 1 when a page is out of date' => 'Non scrive niente; esce con 1 quando una pagina non è aggiornata',

        // ----- griglia:empty-trash
        'Permanently delete soft-deleted lists and tasks (their statistics disappear)' => 'Cancella per sempre liste e task nel cestino (le loro statistiche spariscono)',
        'Only purge items deleted more than N days ago (0 = everything)' => 'Elimina solo gli elementi cancellati da più di N giorni (0 = tutto)',
        'Show what would be purged without deleting' => 'Mostra cosa verrebbe eliminato senza cancellare niente',

        // ----- griglia:skills-import
        'Imports the list of skills the agent can use (shown in the task modal)' => "Importa l'elenco delle skill che l'agente può usare (mostrate nel modale del task)",

        // ----- griglia:theme-export / theme-import
        'Exports a generic theme as an installable zip pack' => 'Esporta un tema generico come pacchetto zip installabile',
        'Slug of a generic theme (installed, config, registered or built-in)' => 'Slug di un tema generico (installato, da config, registrato o integrato)',
        'Output zip (default storage/app/theme-<slug>.zip)' => 'Zip di destinazione (default storage/app/theme-<slug>.zip)',
        'CSS file to extract the .theme-<slug> rules from (for themes defined in code)' => 'File CSS da cui estrarre le regole .theme-<slug> (per i temi definiti nel codice)',
        'Installs (or uninstalls) a theme pack in storage/app/themes' => 'Installa (o disinstalla) un pacchetto di temi in storage/app/themes',
        'Path of the theme pack (zip)' => 'Percorso del pacchetto di temi (zip)',
        'Instead of importing, uninstall the theme with this slug' => 'Invece di importare, disinstalla il tema con questo slug',

        // ----- griglia:watch
        'Watch the agent list and print only changes (open-to-work, answers, stops)' => "Sorveglia la lista dell'agente e stampa solo i cambiamenti (open to work, risposte, stop)",
        'Seconds between polls' => 'Secondi fra un controllo e il successivo',
        'List name to watch (default: config griglia.agent_list)' => 'Nome della lista da sorvegliare (default: la config griglia.agent_list)',
        'Only events for this agent key (default: GRIGLIA_AGENT_KEY, or the default configured agent)' => "Solo gli eventi di questa chiave d'agente (default: GRIGLIA_AGENT_KEY, oppure l'agente predefinito configurato)",
        'Poll once and exit (for testing/cron)' => 'Controlla una volta sola ed esce (per prove e cron)',
        'Do not list the items already open to work when starting' => 'Non elencare, alla partenza, gli elementi già open to work',

        // ----- config/griglia.php
        "URL prefix of the package pages ('' = site root: /, /settings, /dashboard)" => "Prefisso URL delle pagine del package ('' = radice del sito: /, /settings, /dashboard)",
        'How the UI calls the coding agent (Claude, Codex, Gemini, …): labels like «Claude\'s answer», «Claude\'s skills»' => "Come l'interfaccia chiama l'agente (Claude, Codex, Gemini, …): etichette come «la risposta di Claude», «le skill di Claude»",
        'Several agents at once (key => label), e.g. GRIGLIA_AGENTS="claude:Claude Code,codex:Codex CLI". A list (project) chooses its default agent, a task may override it. Empty = a single agent named `agent_name`.' => 'Più agenti insieme (chiave => etichetta), per esempio GRIGLIA_AGENTS="claude:Claude Code,codex:Codex CLI". Una lista (progetto) sceglie il proprio agente di default, un task può cambiarlo. Vuoto = un agente solo, chiamato `agent_name`.',
        'Which of those agents is running here: the key `griglia:check` assumes when `--agent=<key>` is omitted, so that this installation sees only its own tasks. Empty = the agent must pass `--agent=` itself.' => 'Quale di quegli agenti gira qui: la chiave che `griglia:check` assume quando `--agent=<chiave>` non è indicato, così questa installazione vede solo i propri task. Vuoto = l\'agente deve passare `--agent=` da sé.',
        "Mode: 'server' (default) = authenticated users with their own lists; 'local' = no authentication, one global set of lists (a board on your own machine). Overridable from /settings (AppSettings mode)." => "Modalità: 'server' (default) = utenti autenticati con le proprie liste; 'local' = niente autenticazione, un unico insieme di liste globali (una board sulla tua macchina). Si può scavalcare da /settings (AppSettings mode).",
        "Server mode: who may open the board. If the user model has `canAccessGriglia(): bool` it decides; otherwise this Gate ability (e.g. 'access-griglia') if set; otherwise every authenticated user." => "Modalità server: chi può aprire la board. Se il modello utente ha `canAccessGriglia(): bool` decide quello; altrimenti questa ability del Gate (per esempio 'access-griglia') se impostata; altrimenti chiunque sia autenticato.",
        'Server mode: Gate ability deciding who may ADMINISTER the board (settings, agent context, theme packs). It is consulted after `canManageGriglia(): bool` on the user model and before `admins`.' => 'Modalità server: ability del Gate che decide chi può AMMINISTRARE la board (impostazioni, contesto dell\'agente, pacchetti di temi). Si consulta dopo `canManageGriglia(): bool` sul modello utente e prima di `admins`.',
        'Server mode: the administrators themselves, as ids or e-mails (GRIGLIA_ADMINS="1,alice@example.com"). Used when neither `canManageGriglia()` nor `admin_gate` decides. Empty = the first registered user only.' => 'Modalità server: gli amministratori veri e propri, come id o indirizzi e-mail (GRIGLIA_ADMINS="1,alice@example.com"). Si usano quando non decidono né `canManageGriglia()` né `admin_gate`. Vuoto = solo il primo utente registrato.',
        'Allow switching the board to local mode (no authentication) from /settings. Off by default: the override is accepted from the UI only when the app runs in the `local` environment.' => "Permette di portare la board in modalità locale (senza autenticazione) da /settings. Spento di default: l'interfaccia accetta il cambio solo quando l'applicazione gira nell'ambiente `local`.",
        "Middleware of the package routes. Authentication is enforced by the package itself according to the mode (Alle80\\Griglia\\Http\\Middleware\\GrigliaAccess), so 'auth' is not needed here (and is ignored)." => "Middleware delle rotte del package. L'autenticazione la impone il package stesso a seconda della modalità (Alle80\\Griglia\\Http\\Middleware\\GrigliaAccess), quindi 'auth' qui non serve (e viene ignorato).",
        'Public broadcast channel used for live updates in local mode' => 'Canale di broadcast pubblico usato per gli aggiornamenti dal vivo in modalità locale',
        'Register the package routes at all (set false to define your own routes with the components)' => 'Registrare o no le rotte del package (metti false per definire rotte tue con i componenti)',
        "Register a home route (route_prefix + '/') showing the theme selected in /settings" => "Registra una rotta home (route_prefix + '/') che mostra il tema selezionato nelle impostazioni",
        'Desktop dashboard: a wider, more readable view of the board on its own route. Set to null/false to disable the route and the slide-out board tab.' => 'Dashboard da scrivania: una vista della board più larga e più leggibile, su una rotta sua. Metti null/false per disattivare la rotta e la linguetta laterale.',
        'Generic theme used by the home route and as fallback' => 'Tema generico usato dalla rotta home e come ripiego',
        'Extra generic themes (slug => definition, same keys as Alle80\Griglia\Themes::builtin(); a built-in slug is overridden key by key)' => 'Temi generici aggiuntivi (slug => definizione, stesse chiavi di Alle80\Griglia\Themes::builtin(); uno slug integrato viene sovrascritto chiave per chiave)',
        'User model owning the lists' => 'Modello utente proprietario delle liste',
        'Filesystem disk for image attachments. The private `local` disk is the secure default.' => 'Disco su cui salvare le immagini allegate. Il disco privato `local` è il default sicuro.',
        'Serve attachments through the authorised, owner-scoped controller. Keep enabled for private disks; disable only when `attachments_disk` deliberately points to a publicly accessible disk.' => 'Serve gli allegati attraverso il controller autorizzato, che rispetta il proprietario. Tienilo acceso con i dischi privati; spegnilo solo quando `attachments_disk` punta di proposito a un disco pubblico.',
        'Name of the list used as request channel between the user and the coding agent (griglia:check)' => "Nome della lista usata come canale di richieste fra l'utente e l'agente (griglia:check)",
        'Name of the first list created for a new user. Empty = the translated name (griglia::t.default_list)' => 'Nome della prima lista creata per un utente nuovo. Vuoto = il nome tradotto (griglia::t.default_list)',
        'Private broadcast channel per user for live updates ({id} = user id); requires a broadcaster' => 'Canale di broadcast privato per utente per gli aggiornamenti dal vivo ({id} = id utente); serve un broadcaster',
        'Web Push: hosts a browser subscription endpoint may point to (https only). Wildcards allowed. Empty = any https host.' => 'Web Push: host verso cui può puntare un endpoint di sottoscrizione del browser (solo https). Si possono usare i caratteri jolly. Vuoto = qualunque host https.',
        'Rate limits (Laravel throttle definitions) of the expensive endpoints' => 'Limiti di frequenza (definizioni throttle di Laravel) degli endpoint costosi',
        'Agents status snapshot (plan + usage windows), written by `griglia:agent-status-import`; shown in /agents' => "Snapshot dello stato degli agenti (piano + finestre d'uso), scritto da `griglia:agent-status-import`; mostrato in /agents",
        "Catalogue of the agent's skills (JSON written by `griglia:skills-import`; shown in the task modal)" => "Catalogo delle skill dell'agente (JSON scritto da `griglia:skills-import`; mostrato nel modale del task)",
        "Vocabulary hint sent with the audio of the speech to text (helps with names and jargon: «l'agente» instead of «la gente»). null = use the translated default, '' = no hint at all." => "Suggerimento di vocabolario mandato con l'audio della dettatura (aiuta con nomi e gergo: «l'agente» invece di «la gente»). null = usa il default tradotto, '' = nessun suggerimento.",
        'Hard limit of a single dictation, in seconds (0 = no limit): when it is reached the recording is closed and transcribed, instead of growing until the upload or the provider refuses it.' => "Durata massima di una singola dettatura, in secondi (0 = nessun limite): al limite la registrazione viene chiusa e trascritta, invece di crescere finché l'upload o il provider la rifiutano.",
        "Front-end assets: 'precompiled' (default) = the CSS/JS built by the package, published in public/vendor/griglia/build — nothing to build in the host app; 'vite' = the host app bundles resources/css/griglia.css + resources/js/griglia.js in its own Vite build (entries below)" => "Asset front-end: 'precompiled' (default) = il CSS/JS compilati dal package, pubblicati in public/vendor/griglia/build — niente da compilare nell'applicazione ospite; 'vite' = l'applicazione ospite include resources/css/griglia.css + resources/js/griglia.js nella propria build Vite (voci qui sotto)",
        'With `assets` = \'vite\': the entry points of the host app passed to @vite() on the package pages.' => "Con `assets` = 'vite': le voci d'ingresso dell'applicazione ospite passate a @vite() nelle pagine del package.",
        'With `assets` = \'precompiled\': public URL where the published build is served from.' => "Con `assets` = 'precompiled': URL pubblico da cui viene servita la build pubblicata.",
        'Runtime configuration of the Echo client (live updates). Empty key = no WebSocket at all.' => 'Configurazione a runtime del client Echo (aggiornamenti dal vivo). Chiave vuota = nessun WebSocket.',
        "Web fonts of the themes: URL prefix that receives the theme's `fonts` string (bunny.net by default, Google-compatible); '' disables external fonts (self-host them in your CSS instead)" => "Web font dei temi: prefisso URL a cui viene passata la stringa `fonts` del tema (di default bunny.net, compatibile con Google); '' disattiva i font esterni (ospitali da te nel tuo CSS)",
    ],
];
