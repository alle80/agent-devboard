# Due agenti insieme

Sì: due agenti puntati sulla stessa board **possono** pestarsi i piedi, e i punti dove succede sono pochi e
prevedibili. Questa pagina li elenca, con la regola che tiene al sicuro ciascuno. In breve: la board decide
*chi lavora su cosa*, un checkout per agente decide *dove scrivono*, e tutto quello che è condiviso dalla
macchina (cache, build, migrazioni, rilasci) lo fa un agente alla volta.

## Cosa garantisce la board

Ogni task appartiene a **un** agente: quello scelto sul task (tendina su una riga sua nell'intestazione del
modale), altrimenti quello di
default della lista (selettore nella barra), altrimenti l'agente predefinito. `griglia:check --agent=<chiave>`
elenca solo i task di quell'agente, e le azioni si rifiutano di toccare quelli degli altri:

```console
$ php artisan griglia:check --agent=claude --take=412
«Release the package» (id:412) belongs to agent «Codex CLI», you are «Claude Code»: refusing to take it
— it is being worked on right now. Reassign it on the board (task or list agent), or re-run with --force.
```

Quella guardia copre `--take`, `--done` e `--ask`, così un id vecchio dentro un prompt (o un worker riavviato
con la chiave sbagliata) non può rubare, chiudere o mettere in pausa in silenzio il lavoro di un altro agente.
`--force` è la strada dichiarata — usala quando stai *davvero* subentrando.

Da qui discendono altre due cose:

- **`🔒 busy elsewhere`** — con più agenti configurati, `griglia:check` stampa quello che gli altri hanno in
  lavorazione in questo momento, così puoi stare alla larga da quei file e da quei branch.
- **La baseline 🆕 è per agente.** Il marcatore «nuovo dal tuo ultimo check» è memorizzato per chiave
  d'agente: un altro agente che lancia `check` non consuma più i tuoi 🆕.

Assegna il lavoro in modo esplicito (agente del task o della lista) ogni volta che due agenti sono attivi: un
task non assegnato finisce all'agente predefinito, quindi il secondo agente non lo vede nemmeno.

## Cosa la board non può garantire

Tutto quello che sta fuori dal database è condiviso dall'host, e la board non ha voce in capitolo.

| Risorsa condivisa | Come si scontrano | Regola |
| --- | --- | --- |
| Working tree | L'agente B fa il checkout di un altro branch mentre l'agente A sta scrivendo — e in questo progetto il sito servito *è* il working tree | Un checkout per agente: `git worktree add ../wt-codex -b task/…`, e punta lì il worker (`--repo`, `GRIGLIA_WORKER_REPO`) |
| Branch | Appoggiare il branch di un task sulla punta di un altro agente si trascina in PR del lavoro non ancora rivisto | Parti da un `main` aggiornato (`git fetch && git merge origin/main`), una PR per task, mai sopra un altro task |
| Asset compilati (`public/build`) | Due build Vite che scrivono lo stesso manifest | Compila uno alla volta; ogni worktree compila la propria copia |
| Cache delle viste Blade | Una vista compilata da un altro branch viene riusata quando il sorgente è più vecchio di lei, e la suite fallisce su codice che non esiste più | La suite di test del package svuota le viste compilate al primo test dell'esecuzione |
| Database e migrazioni | Uno schema solo per tutti: una migrazione arriva sotto i piedi dell'altro agente | Esegui le migrazioni una alla volta, e dillo sulla board mentre lo fai |
| Rilascio del package | Un `rsync --delete` verso il repository del package cancellerebbe quello che l'altro agente ha rilasciato nel frattempo | `release-griglia.sh` si ferma quando il remoto ha versioni o file che il sorgente non ha; quando si ferma, aspetta che la pull request dell'altro agente venga integrata, porta `main` dentro il tuo branch e rilascia di nuovo |
| Comandi che valgono per tutto il container | `config:cache`, `route:cache`, `queue:restart`, `reverb:restart` colpiscono ogni sessione | Trattali come globali: lanciali quando nessun altro è a metà di un test |

## Preparare il secondo agente

```bash
# 1. dichiara tutti e due gli agenti
GRIGLIA_AGENTS="claude:Claude Code,codex:Codex CLI"

# 2. dai a ciascuno il proprio checkout
git worktree add ../board-codex -b task/000-scratch origin/main

# 3. un worker per agente, ognuno sul proprio percorso di repository
GRIGLIA_WORKER_REPO=/srv/board-codex \
  python3 scripts/griglia-agent-worker.py --agent=codex --driver=codex
```

Ogni worker prende già un lock suo (`/tmp/griglia-agent-worker-<chiave>.lock`), così la stessa chiave d'agente
non gira mai due volte; chiavi diverse invece devono poter girare insieme.

## Vedi anche

- [Il lato agente](index.md) — comandi, stati, ambito con più agenti.
- [Worker persistenti](workers.md) — far girare un agente come servizio.
- [Comandi artisan](../reference/commands.md) — ogni opzione, generata dal codice.
