# Impostazioni da fare e note di progetto

Due cose diverse, tenute separate di proposito:

- **Configurazione** (`config/griglia.php`, env) — la decide lo *sviluppatore* che installa il package:
  percorsi, rotte, modelli, integrazioni. Letta una volta per richiesta, per cambiarla serve un
  deploy/`config:cache`, non si tocca mai dall'interfaccia.
- **Impostazioni** (spatie/laravel-settings, `/settings`) — le decide l'*utente* a runtime, stanno nel
  database, si cambiano dall'interfaccia con effetto immediato. Tre gruppi: `agent` (come lavora l'agente),
  `optimization` (risparmio di token), `app` (comportamento della board).

Quello che esiste oggi è generato dal codice: vedi [File di configurazione](config.md) e
[Impostazioni](settings.md). Questa pagina è quello che *non* c'è ancora — l'elenco delle cose da fare, con la
strada da percorrere per ognuna.

Schema di ogni voce: **nome** · tipo · default · cosa fa · priorità (P1 alta, P2 media, P3 bassa) · note di
implementazione.

## Configurazioni — future (con la strada da fare)

| # | Chiave | Tipo / default | A cosa serve e perché ci sta | Prio | Implementazione |
|---|---|---|---|---|---|
| C1 | `admin_gate` / `canManageDevboard()` | string\|null | chi può modificare impostazioni globali, contesto, temi, skill (rimedio di sicurezza #1) | **P1** | middleware/controllo in SettingsPage, ContextPage, installazione temi; nascondere i link; test; README |
| C2 | `mode_lock` | bool, `false` (`GRIGLIA_MODE_LOCK`) | vietare che l'interfaccia scavalchi `app.mode` (per esempio in produzione) | P1 | `Mode::current()` ignora l'impostazione quando è bloccata; nascondere la select; test |
| C3 | `storage_path` (cartella base di `skills_file`, `agent_status_file`, cache dell'export del contesto) | percorso | un posto solo per i file a runtime del package | P3 | ricavare da lì i due percorsi esistenti, tenendo gli override |
| C4 | default di `stats.price_list` | array | prezzi di default per modello, così `/stats` mostra i costi da subito | P3 | precaricare `cost_per_m_*` dalla config quando le impostazioni sono vuote |
| C5 | `locale` | string\|null | forzare la lingua del package indipendentemente dall'applicazione | P3 | `setLocale` nel service provider quando è valorizzato |
| C6 | `plan.provider` / `plan.model` | string\|null | quale provider/modello AI costruisce i piani (oggi: il default dell'SDK) | P2 | passarli a `PlanBuilder::prompt(provider:, model:)`; override da impostazione (vedi S3) |
| C7 | `transcription.provider` / `model` | string\|null | provider per la dettatura (oggi: `ai.default_for_transcription`) | P2 | passarli a `Transcription::generate()` |
| C8 | `theme_packs_dir` / `allow_theme_upload` | percorso / bool | dove stanno i temi zip; disattivare del tutto gli upload sulle installazioni irrobustite | P2 | `ThemeStore` legge la cartella; nascondere l'upload quando è disattivato |
| C9 | `push.allowed_hosts` | array | elenco degli endpoint Web Push ammessi (rimedio di sicurezza #3) | P1 | validare in `PushSubscriptionController::store` |
| C10 | `rate_limits` (transcribe/test/push) | array di stringhe `throttle:` | tarare per installazione gli endpoint costosi | P2 | applicarli in `routes/web.php` |
| C11 | `context.targets` | array, `['CLAUDE.md','AGENTS.md']` | quali file di istruzioni scrive la sincronizzazione (oggi solo nello script sull'host) | P3 | esporlo con `griglia:context export --target`; documentarlo |
| C12 | `agent_status.stale_minutes` | int, 15 | soglia di «dato vecchio» in `/agents` | P3 | da costante a config |

## Impostazioni — future (con la strada da fare)

| # | Gruppo.chiave | Tipo / default | A cosa serve e perché ci sta | Prio | Implementazione |
|---|---|---|---|---|---|
| S1 | `app.ai_plan_provider` / `app.ai_plan_model` | select/text | scegliere il modello che costruisce i piani (costo/qualità) | P2 | campi + migrazione + `Plan::tasks()` che li passa |
| S2 | `app.speech_provider` / `app.speech_model` | select/text | provider/modello per la trascrizione | P2 | campi + `TranscribeController` |
| S3 | `agent.max_parallel` | int, 2 | tetto per la modalità multitasking (quanti task aperti insieme) | P2 | stampata nella riga delle impostazioni; regola per l'agente |
| S4 | `agent.working_hours` | fascia oraria | l'agente non dovrebbe iniziare task nuovi fuori dalla finestra | P3 | stampata da `check`; regola per l'agente |
| S5 | `agent.auto_pause_on_usage` | int %, 0 | mettere in pausa i piani quando l'uso settimanale dell'agente (`/agents`) supera N% | P2 | gancio su `AgentStatus` → `plan_paused`; toast/notifica |
| S6 | `app.notify_on_take` | bool, false | notifica quando l'agente prende un task (finora l'utente ha chiesto solo chiusura/domanda) | P3 | `Notify::taken()` + notifica `TodoTaken` |
| S7 | `app.digest_time` + `app.daily_digest` | ora/bool | il riepilogo serale lo manda l'**applicazione** (non l'agente): campanella/push/mail | P2 | comando schedulato `griglia:digest` |
| S8 | `app.history_retention_days` | int, 0 | ripulire i task completati e archiviati vecchi (privacy/dimensione) | P3 | estendere `griglia:auto-archive` |
| S9 | `app.default_plan_length` | int, 3–12 | suggerimento per `PlanBuilder` (numero di task) | P3 | parametro del prompt |
| S10 | `app.stats_default_period` | int giorni | periodo di default di `/stats` | P3 | `StatsPage::mount` |
| S11 | `app.language` | select | lingua dell'interfaccia per installazione (it/en) | P3 | middleware `setLocale` |
| S12 | `optimization.check_output_language` | select | lingua dell'output di `griglia:check` (oggi inglese) | P3 | file di lingua per il comando |

Fuori perimetro di proposito: impostazioni per singolo utente (le settings di spatie sono globali; servirebbe
un altro archivio) e segreti nelle impostazioni (le chiavi API restano nel `.env`).
