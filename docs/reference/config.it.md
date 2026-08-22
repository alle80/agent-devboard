# File di configurazione

<!-- Generato da `php artisan griglia:docs-generate` — non modificare a mano. -->

Ogni chiave di `config/griglia.php` — pubblicalo con `php artisan vendor:publish --tag=griglia-config`.
La decide chi installa il package; le opzioni che si cambiano a runtime stanno nelle [Impostazioni](settings.md).

| Chiave | Variabile d'ambiente | Default | Cos'è |
|---|---|---|---|
| `route_prefix` | `GRIGLIA_ROUTE_PREFIX` | `''` | Prefisso URL delle pagine del package ('' = radice del sito: /, /settings, /dashboard) |
| `agent_name` | `GRIGLIA_AGENT_NAME` | `'Agent'` | Come l'interfaccia chiama l'agente (Claude, Codex, Gemini, …): etichette come «la risposta di Claude», «le skill di Claude» |
| `agents` | `GRIGLIA_AGENTS` | — | Più agenti insieme (chiave => etichetta), per esempio GRIGLIA_AGENTS="claude:Claude Code,codex:Codex CLI". Una lista (progetto) sceglie il proprio agente di default, un task può cambiarlo. Vuoto = un agente solo, chiamato `agent_name`. |
| `agent_key` | `GRIGLIA_AGENT_KEY` | — | Quale di quegli agenti gira qui: la chiave che `griglia:check` assume quando `--agent=<chiave>` non è indicato, così questa installazione vede solo i propri task. Vuoto = l'agente deve passare `--agent=` da sé. |
| `mode` | `GRIGLIA_MODE` | `'server'` | Modalità: 'server' (default) = utenti autenticati con le proprie liste; 'local' = niente autenticazione, un unico insieme di liste globali (una board sulla tua macchina). Si può scavalcare da /settings (AppSettings mode). |
| `access_gate` | `GRIGLIA_ACCESS_GATE` | — | Modalità server: chi può aprire la board. Se il modello utente ha `canAccessGriglia(): bool` decide quello (il vecchio `canAccessDevboard()`, da prima della rinomina, è ancora onorato); altrimenti questa ability del Gate (per esempio 'access-griglia') se impostata; altrimenti chiunque sia autenticato. |
| `admin_gate` | `GRIGLIA_ADMIN_GATE` | — | Modalità server: ability del Gate che decide chi può AMMINISTRARE la board (impostazioni, contesto dell'agente, pacchetti di temi). Si consulta dopo `canManageGriglia(): bool` sul modello utente (il vecchio `canManageDevboard()`, da prima della rinomina, è ancora onorato) e prima di `admins`. |
| `admins` | `GRIGLIA_ADMINS` | — | Modalità server: gli amministratori veri e propri, come id o indirizzi e-mail (GRIGLIA_ADMINS="1,alice@example.com"). Si usano quando non decidono né `canManageGriglia()` né `admin_gate`. Vuoto = solo il primo utente registrato. |
| `allow_local_from_ui` | `GRIGLIA_ALLOW_LOCAL_FROM_UI` | `false` | Permette di portare la board in modalità locale (senza autenticazione) da /settings. Spento di default: l'interfaccia accetta il cambio solo quando l'applicazione gira nell'ambiente `local`. |
| `middleware` | — | _array_ | Middleware delle rotte del package. L'autenticazione la impone il package stesso a seconda della modalità (Alle80\Griglia\Http\Middleware\GrigliaAccess), quindi 'auth' qui non serve (e viene ignorato). |
| `local_channel` | — | `'griglia.local'` | Canale di broadcast pubblico usato per gli aggiornamenti dal vivo in modalità locale |
| `register_routes` | — | `true` | Registrare o no le rotte del package (metti false per definire rotte tue con i componenti) |
| `home_route` | — | `true` | Registra una rotta home (route_prefix + '/') che mostra il tema selezionato nelle impostazioni |
| `dashboard_route` | `GRIGLIA_DASHBOARD_ROUTE` | `'/dashboard'` | Dashboard da scrivania: una vista della board più larga e più leggibile, su una rotta sua. Metti null/false per disattivare la rotta e la linguetta laterale. |
| `default_theme` | — | `'slate'` | Tema generico usato dalla rotta home e come ripiego |
| `themes` | — | _array_ | Temi generici aggiuntivi (slug => definizione, stesse chiavi di Alle80\Griglia\Themes::builtin(); uno slug integrato viene sovrascritto chiave per chiave) |
| `user_model` | `GRIGLIA_USER_MODEL` | `'App\\Models\\User'` | Modello utente proprietario delle liste |
| `attachments_disk` | `GRIGLIA_ATTACHMENTS_DISK` | `'local'` | Disco su cui salvare le immagini allegate. Il disco privato `local` è il default sicuro. |
| `attachments_via_controller` | `GRIGLIA_ATTACHMENTS_VIA_CONTROLLER` | `true` | Serve gli allegati attraverso il controller autorizzato, che rispetta il proprietario. Tienilo acceso con i dischi privati; spegnilo solo quando `attachments_disk` punta di proposito a un disco pubblico. |
| `agent_list` | `GRIGLIA_AGENT_LIST` | `'dev'` | Nome della lista usata come canale di richieste fra l'utente e l'agente (griglia:check) |
| `default_list_name` | `GRIGLIA_DEFAULT_LIST_NAME` | `''` | Nome della prima lista creata per un utente nuovo. Vuoto = il nome tradotto (griglia::t.default_list) |
| `broadcast_channel` | — | `'App.Models.User.{id}'` | Canale di broadcast privato per utente per gli aggiornamenti dal vivo ({id} = id utente); serve un broadcaster |
| `push_allowed_hosts` | — | _array_ | Web Push: host verso cui può puntare un endpoint di sottoscrizione del browser (solo https). Si possono usare i caratteri jolly. Vuoto = qualunque host https. |
| `rate_limits` | — | _array_ | Limiti di frequenza (definizioni throttle di Laravel) degli endpoint costosi |
| `agent_status_file` | `GRIGLIA_AGENT_STATUS_FILE` | `storage_path('app/griglia/agent-status.json')` | Snapshot dello stato degli agenti (piano + finestre d'uso), scritto da `griglia:agent-status-import`; mostrato in /agents |
| `skills_file` | `GRIGLIA_SKILLS_FILE` | `storage_path('app/griglia/skills.json')` | Catalogo delle skill dell'agente (JSON scritto da `griglia:skills-import`; mostrato nel modale del task) |
| `speech_prompt` | `GRIGLIA_SPEECH_PROMPT` | `null` | Suggerimento di vocabolario mandato con l'audio della dettatura (aiuta con nomi e gergo: «l'agente» invece di «la gente»). null = usa il default tradotto, '' = nessun suggerimento. |
| `speech_max_seconds` | `GRIGLIA_SPEECH_MAX_SECONDS` | `300` | Durata massima di una singola dettatura, in secondi (0 = nessun limite): al limite la registrazione viene chiusa e trascritta, invece di crescere finché l'upload o il provider la rifiutano. |
| `assets` | `GRIGLIA_ASSETS` | `'precompiled'` | Asset front-end: 'precompiled' (default) = il CSS/JS compilati dal package, pubblicati in public/vendor/griglia/build — niente da compilare nell'applicazione ospite; 'vite' = l'applicazione ospite include resources/css/griglia.css + resources/js/griglia.js nella propria build Vite (voci qui sotto) |
| `vite_entries` | — | _array_ | Con `assets` = 'vite': le voci d'ingresso dell'applicazione ospite passate a @vite() nelle pagine del package. |
| `assets_url` | — | `'/vendor/griglia/build'` | Con `assets` = 'precompiled': URL pubblico da cui viene servita la build pubblicata. |
| `echo` | — | _array_ | Configurazione a runtime del client Echo (aggiornamenti dal vivo). Chiave vuota = nessun WebSocket. |
| `fonts_url` | `GRIGLIA_FONTS_URL` | `'https://fonts.bunny.net/css?family='` | Web font dei temi: prefisso URL a cui viene passata la stringa `fonts` del tema (di default bunny.net, compatibile con Google); '' disattiva i font esterni (ospitali da te nel tuo CSS) |
