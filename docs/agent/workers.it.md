# Worker persistenti

Un terminale interattivo o una chat non restano vivi per sempre. Un **worker persistente** gira sotto il
gestore dei servizi dell'host, interroga Griglia e avvia una nuova sessione non interattiva dell'agente ogni
volta che c'è del lavoro assegnato. Chiudere il terminale, il browser o la sessione originale dell'agente non
lo ferma.

Griglia distribuisce il worker e un template di servizio utente systemd insieme agli altri script per l'host:

```bash
php artisan vendor:publish --tag=griglia-scripts
```

Il worker è neutro rispetto al fornitore, tutto attorno al contratto della board: ogni istanza usa la propria
chiave d'agente con `griglia:check --agent=<chiave>`, il proprio lock e gli stessi stati dei task. Ci sono
driver di lancio integrati per **Codex CLI** e **Claude Code**; un template di argv in JSON collega un'altra
CLI senza passare da una shell.

## Installare il servizio utente systemd

Copia l'esempio e sostituisci `/absolute/path/to/project` in entrambe le righe con il percorso assoluto vero
del progetto:

```bash
mkdir -p ~/.config/systemd/user
cp scripts/systemd/griglia-agent-worker@.service.example \
  ~/.config/systemd/user/griglia-agent-worker@.service
sed -i 's#/absolute/path/to/project#/srv/my-project#g' \
  ~/.config/systemd/user/griglia-agent-worker@.service
systemctl --user daemon-reload
```

Abilita un'istanza per ogni agente configurato. Il nome dell'istanza è la chiave dell'agente in Griglia:

```bash
systemctl --user enable --now griglia-agent-worker@codex.service
systemctl --user enable --now griglia-agent-worker@claude.service
```

### Più applicazioni sullo stesso PC

Serve **un worker per applicazione e per agente**: ogni worker interroga una sola board e avvia l'agente nella
directory del relativo progetto. Il lock include automaticamente repository e chiave agente, quindi due
applicazioni possono usare entrambe `codex` senza bloccarsi tra loro.

Per ogni applicazione copia il template con un prefisso unità univoco e sostituisci il percorso. Per esempio:

```bash
cp app-one/scripts/systemd/griglia-agent-worker@.service.example \
  ~/.config/systemd/user/griglia-agent-worker-app-one@.service
sed -i 's#/absolute/path/to/project#/srv/app-one#g' \
  ~/.config/systemd/user/griglia-agent-worker-app-one@.service

cp app-two/scripts/systemd/griglia-agent-worker@.service.example \
  ~/.config/systemd/user/griglia-agent-worker-app-two@.service
sed -i 's#/absolute/path/to/project#/srv/app-two#g' \
  ~/.config/systemd/user/griglia-agent-worker-app-two@.service

systemctl --user daemon-reload
systemctl --user enable --now griglia-agent-worker-app-one@codex.service
systemctl --user enable --now griglia-agent-worker-app-two@codex.service
```

La configurazione comune dell'agente resta in `~/.config/griglia-worker/codex.env`. Le impostazioni specifiche
del progetto possono sovrascriverla in
`~/.config/griglia-worker/griglia-agent-worker-app-one-codex.env` (schema `%p-%i.env` della unità). In
particolare ogni applicazione Docker deve indicare un `GRIGLIA_WORKER_CONTAINER` diverso; per il trasporto
locale imposta invece `GRIGLIA_WORKER_REPO` e, se serve, `GRIGLIA_WORKER_PHP`.

`codex` invoca `codex exec --approve-for-me`; `claude` invoca `claude -p --permission-mode bypassPermissions`.
L'unit aggiunge `%h/.local/bin` al `PATH`, il posto solito dei launcher installati dall'utente. Se
`command -v codex` o `command -v claude` indicano un'altra cartella, metti una riga `PATH=...` completa in
`~/.config/griglia-worker/<chiave-agente>.env`.

Per guardare il servizio e seguirne l'output:

```bash
systemctl --user status griglia-agent-worker@codex.service
journalctl --user -u griglia-agent-worker@codex.service -f
```

Per tenere in piedi i servizi utente dopo il logout e farli partire all'avvio, abilita una volta il lingering:

```bash
loginctl enable-linger "$USER"
loginctl show-user "$USER" -p Linger   # atteso: Linger=yes
```

## Configurazione

Ogni istanza legge, se c'è, `~/.config/griglia-worker/<chiave-agente>.env`:

```dotenv
GRIGLIA_WORKER_DRIVER=codex
GRIGLIA_WORKER_INTERVAL=10
GRIGLIA_WORKER_RETRY_DELAY=30
GRIGLIA_WORKER_MAX_PARALLEL=2
GRIGLIA_WORKER_TRANSPORT=docker
GRIGLIA_WORKER_CONTAINER=laravel-dev-app
GRIGLIA_WORKER_REPO=/srv/my-project
```

Il trasporto Docker è quello di default ed esegue `docker exec <container> php artisan`. Se Laravel gira
direttamente sull'host del worker, usa il trasporto locale; Artisan parte con il repository come cartella di
lavoro, così Docker non compare da nessuna parte nel giro:

```dotenv
GRIGLIA_WORKER_TRANSPORT=local
GRIGLIA_WORKER_PHP=/usr/bin/php8.4
GRIGLIA_WORKER_REPO=/srv/my-project
```

I nomi `GRIGLIA_WORKER_*` configurano una singola istanza. Quando mancano, il worker ripiega sulle variabili
che leggono gli altri [script sull'host](scripts.md) — `GRIGLIA_TRANSPORT`, `GRIGLIA_PHP`, `GRIGLIA_CONTAINER` —
così una scelta sola, esportata una volta per la macchina, copre sia il worker sia gli script che l'agente
lancia da sé (conteggio dei token, sincronizzazione di contesto e skill). Ogni impostazione ha anche il suo
flag, comodo per un lancio una tantum:

| Flag | Variabile d'ambiente | Default |
| --- | --- | --- |
| `--transport docker\|local` | `GRIGLIA_WORKER_TRANSPORT`, `GRIGLIA_TRANSPORT` | `docker` |
| `--container` | `GRIGLIA_WORKER_CONTAINER`, `GRIGLIA_CONTAINER` | `laravel-dev-app` |
| `--php` | `GRIGLIA_WORKER_PHP`, `GRIGLIA_PHP` | `php` |
| `--repo` | `GRIGLIA_WORKER_REPO` | cartella corrente |
| `--driver codex\|claude\|custom` | `GRIGLIA_WORKER_DRIVER` | la chiave dell'agente |
| `--interval`, `--retry-delay` | `GRIGLIA_WORKER_INTERVAL`, `GRIGLIA_WORKER_RETRY_DELAY` | `10`, `30` |
| `--max-parallel` | `GRIGLIA_WORKER_MAX_PARALLEL` | `2` |
| `--model` | `GRIGLIA_WORKER_MODEL` | il default della CLI dell'agente |
| `--effort` | `GRIGLIA_WORKER_EFFORT` | il default della CLI dell'agente |

Il driver di default è la chiave dell'agente, quindi le chiavi che si chiamano `codex` e `claude` non hanno
bisogno di alcun file env. Se la chiave è diversa, indica il driver in modo esplicito.

### Modello e livello di ragionamento

Senza altra configurazione ogni sessione usa il modello con cui è configurata la CLI dell'agente.
`GRIGLIA_WORKER_MODEL` e `GRIGLIA_WORKER_EFFORT` li scelgono per singolo worker, così l'agente della board può
girare su un modello diverso da quello delle sessioni interattive della stessa CLI:

```dotenv
GRIGLIA_WORKER_MODEL=fable
GRIGLIA_WORKER_EFFORT=max
```

Il driver `claude` li passa come `--model` e `--effort` (`low`, `medium`, `high`, `xhigh`, `max`); il driver
`codex` come `--model` e `-c model_reasoning_effort="<effort>"`. Il driver custom li riceve come segnaposto
`{model}` e `{effort}` (stringa vuota se non impostati), quindi è il template argv a decidere dove finiscono.
Il worker non valida i valori: un modello o un livello sconosciuto fallisce dentro la CLI dell'agente.

Le variabili si leggono all'avvio del worker, quindi una modifica ha effetto al prossimo
`systemctl --user restart griglia-agent-worker@<chiave-agente>.service` — che interrompe anche la sessione in
corso in quel momento.

Per Gemini CLI, Aider o un altro agente, usa il driver `custom`. L'array JSON viene eseguito direttamente (mai
attraverso una shell); `{prompt}`, `{repo}`, `{agent}`, `{model}` e `{effort}` vengono sostituiti dentro i singoli argomenti:

```dotenv
GRIGLIA_WORKER_DRIVER=custom
GRIGLIA_WORKER_COMMAND_JSON=["agent-cli","--cwd","{repo}","--prompt","{prompt}"]
```

Trasporto e driver sono indipendenti, quindi Codex, Claude e i driver personalizzati funzionano in tutte e due
le modalità. L'utente del servizio deve poter usare Docker o l'eseguibile PHP locale configurato, e la CLI
dell'agente scelto in modo non interattivo. Non usare flag che disattivano del tutto sandbox e approvazioni:
concedi solo i permessi sul progetto che servono al flusso di lavoro.

## Comportamento e prove

Il worker interroga lo stato corrente della board, quindi trova anche il lavoro già aperto prima di un riavvio.
In modalità `ordered` esegue esattamente una sessione. In modalità `multitasking` esegue fino a
`--max-parallel` sessioni (2 per default), una per task idoneo; riduci il limite quando i task possono modificare
gli stessi file. Un `flock` per coppia repository/agente impedisce worker duplicati, mentre il worker tiene
traccia di ogni processo figlio per task. Uno Stop termina solo il processo di quel task; quando un figlio esce,
il suo slot torna disponibile.

Per controllare la configurazione senza lanciare un agente:

```bash
scripts/griglia-agent-worker.py --agent=codex --driver=codex --once --dry-run
scripts/griglia-agent-worker.py --agent=codex --transport=local --php=/usr/bin/php8.4 \
  --repo=/srv/my-project --once --dry-run
```

Il comando legge la board attraverso il trasporto scelto e stampa l'argv che eseguirebbe, così un errore qui è
un problema di trasporto o di permessi, non dell'agente.

Per una prova da capo a fondo, abilita il servizio, crea un task innocuo assegnato a quell'agente e mettilo
open to work. Nel journal dovresti vedere `dispatching task <id> to <agent>` e sulla board il task dovrebbe
passare da aperto a working a fatto. Chiudere il terminale da cui hai fatto la prova non tocca il servizio
systemd.

Per disabilitare un'istanza:

```bash
systemctl --user disable --now griglia-agent-worker@codex.service
```

## Vedi anche

- [Il lato agente](index.md) — comandi, stati e ambito con più agenti.
- [Due agenti insieme](concurrency.md) — cosa condividono due worker, e come si evitano.
- [Script sull'host](scripts.md) — tutti gli script pubblicati da `griglia-scripts`.
- [Comandi artisan](../reference/commands.md) — il reference dei comandi, generato.
