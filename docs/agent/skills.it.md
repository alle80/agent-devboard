# Skill

Il catalogo delle skill dell'agente si importa con:

```bash
php artisan griglia:skills-import --file=skills.json   # oppure JSON su stdin: [{name, description, source, agents}, …]
```

Il modale del task lo mostra come accordion (con ricerca dal vivo); le skill che spunti per un task vengono
stampate da `griglia:check` (`skills to activate for this task: …`) così l'agente le invoca. Non devi scrivere
il JSON a mano: il package porta con sé `sync-skills.py`, che legge le cartelle delle skill di Claude Code,
Codex e Gemini sulla macchina dove gira l'agente e le importa.

```bash
php artisan vendor:publish --tag=griglia-scripts   # → scripts/ nel tuo progetto
scripts/sync-skills.py                             # host: legge le cartelle e importa (--print per solo guardare)
```

Questi script girano **sull'host**, non nel container: leggono file che esistono solo lì (skill, credenziali
degli agenti, transcript). Vedi [gli script](scripts.md).

## Un catalogo, più agenti

Il formato SKILL.md è portabile — frontmatter più istruzioni in markdown, niente legato a una CLI in
particolare — ma una skill esiste solo per l'agente che se la trova sul disco: quello che sta in
`~/.claude/skills` è invisibile a Codex CLI, e le skill integrate di una CLI non si possono installare da
nessuna parte. Ogni voce porta quindi `agents`, le chiavi (da `griglia.agents`) degli agenti che possono
usarla, e il modale di un task propone solo le skill del [suo agente](index.md#piu-agenti) più quelle senza
alcun `agents` — condivise, o importate prima che questo campo esistesse. Una skill già spuntata resta
visibile anche se il task cambia agente, così puoi toglierla.

`scripts/sync-skills.py` riempie il campo in base alla cartella da cui ha letto la skill: `~/.claude/skills`,
`.claude/skills` di progetto, plugin e skill integrate → `claude`; `~/.codex/skills` e `.codex/skills` di
progetto → `codex`; `~/.gemini/skills` → `gemini`; la cartella condivisa `~/.agents/skills` → nessun vincolo.
La stessa skill trovata in due cartelle è disponibile per entrambi gli agenti.

Il JSON è un semplice elenco:

```json
[
  { "name": "tdd", "description": "Test-driven development", "source": "user", "agents": ["claude"] },
  { "name": "code-review", "description": "Review a branch", "source": "plugin", "agents": ["claude", "codex"] },
  { "name": "commit-style", "description": "How we write commits", "source": "agents" }
]
```

## Vedi anche

- [Il lato agente](index.md) — come arrivano all'agente le skill scelte.
- [Contesto dell'agente](context.md) — il file di istruzioni che ci sta dietro.
