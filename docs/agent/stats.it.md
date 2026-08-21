# Statistiche e stato degli agenti

## Statistiche (`/stats`)

Per lista (o tutte le liste / tutti i piani): task completati, tempo di lavoro (somma e media sugli elementi
cronometrati), token, **costo** (listino in Impostazioni → App: prezzo per 1M di token in ingresso/uscita,
valuta), barre dei completati per giorno, lo storico dei task chiusi (data, durata, tempo totale, token, costo)
e un quadro d'insieme di tutte le liste. Periodi 7/30/90/365 giorni oppure tutto.

Cancellare una lista o un task è una **soft delete**: le statistiche sopravvivono, e le liste nel cestino
restano selezionabili su `/stats` (marcate «(deleted)»). Per liberare davvero i dati — statistiche comprese —
svuota il cestino:

```bash
php artisan griglia:empty-trash --dry-run      # cosa verrebbe eliminato
php artisan griglia:empty-trash --days=30      # solo gli elementi cancellati da più di 30 giorni
```

## Stato degli agenti (`/agents`)

Piano e finestre d'uso dei tuoi agenti (percentuale usata, percentuale che resta, conto alla rovescia del
reset, livelli ok/alto/quasi esaurito/oltre il limite). I dati vengono da uno snapshot importato con:

```bash
php artisan griglia:agent-status-import --file=snapshot.json   # {updated_at, agents:[{key,name,plan,windows:[…]}]}
```

Il package porta con sé `scripts/agent-status.py` per Claude Code e Codex CLI: legge l'uso OAuth di Claude e la telemetria locale dei rollout Codex **sull'host** e
manda solo percentuali (cron ogni 5 minuti). Lo stesso vale per i token di un task:
`scripts/claude-tokens.py --todo=ID --args`. Vedi [gli script](scripts.md).

Quando una finestra raggiunge per la prima volta il 100%, la board notifica il proprietario del task in mano
a quell’agente tramite i canali in-app, Web Push ed e-mail attivi. Gli snapshot successivi non ripetono
l’avviso: il ritorno sotto il 100% abilita quello seguente. Perché funzioni, il collector va schedulato.

## Vedi anche

- [Usare la board](../board/usage.md) — dove compaiono le statistiche del singolo task.
- [Il lato agente](index.md) — i token li riporta l'agente su `--done`.
