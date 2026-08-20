#!/usr/bin/env python3
"""
Legge le skill che l'agente (Claude Code, Codex CLI, Gemini CLI…) ha a disposizione su questo host e le importa nella board
(`griglia:skills-import`), così il modale del task le mostra nell'accordion «🧩 Skill».

Fonti (nell'ordine): skill di progetto (.claude/skills/*/SKILL.md), skill utente (~/.claude/skills/*/SKILL.md),
plugin installati (~/.claude/plugins/installed_plugins.json → <installPath>/skills/**/SKILL.md, nome «plugin:skill»),
più le skill built-in di Claude Code elencate in scripts/builtin-skills.json (non stanno su disco).

Il formato SKILL.md è portabile, ma una skill esiste solo per l'agente che la trova sul disco: ogni voce porta perciò
`agents` (chiavi di GRIGLIA_AGENTS che possono usarla, dedotte dalla cartella; lista vuota = tutti). ~/.agents/skills è
la cartella condivisa fra CLI diverse → nessun vincolo; la stessa skill trovata in più cartelle unisce gli agenti.

Uso:  scripts/sync-skills.py            # importa nel container (docker exec -i … griglia:skills-import)
      scripts/sync-skills.py --print    # stampa solo il JSON
"""
import glob, json, os, re, subprocess, sys


def project_root():
    """La root del progetto che usa la board: $GRIGLIA_PROJECT_ROOT, altrimenti la cartella che contiene questi
    script (<progetto>/scripts, dove li mette `vendor:publish --tag=griglia-scripts`) oppure — se si lancia lo
    script direttamente da vendor/alle80/griglia/scripts — la cartella che contiene `vendor`."""
    env = os.environ.get('GRIGLIA_PROJECT_ROOT')
    if env:
        return os.path.abspath(os.path.expanduser(env))
    here = os.path.dirname(os.path.abspath(__file__))
    parts = here.split(os.sep)
    if 'vendor' in parts:
        return os.sep.join(parts[:parts.index('vendor')]) or os.sep
    return os.path.dirname(here)


HOME = os.path.expanduser('~')
ROOT = project_root()
CONTAINER = os.environ.get('GRIGLIA_CONTAINER', 'laravel-dev-app')


def frontmatter(path):
    try:
        text = open(path, encoding='utf-8').read()
    except OSError:
        return {}
    m = re.match(r'^---\s*\n(.*?)\n---', text, re.S)
    fm = {}
    if m:
        for line in m.group(1).splitlines():
            mm = re.match(r'^(\w[\w-]*):\s*(.*)$', line)
            if mm:
                fm[mm.group(1)] = mm.group(2).strip().strip('"').strip("'")
    return fm


def skill(path, source, prefix='', agents=()):
    fm = frontmatter(path)
    name = fm.get('name') or os.path.basename(os.path.dirname(path))
    return {'name': prefix + name, 'description': fm.get('description', ''), 'source': source, 'agents': list(agents)}


def collect():
    out = {}

    def add(s):
        old = out.get(s['name'])
        if old is None:
            out[s['name']] = s
            return
        # Stessa skill trovata anche altrove: vale per entrambi gli agenti (lista vuota = per tutti)
        old['agents'] = sorted(set(old['agents']) | set(s['agents'])) if old['agents'] and s['agents'] else []

    for p in sorted(glob.glob(os.path.join(ROOT, '.claude', 'skills', '*', 'SKILL.md'))):
        add(skill(p, 'project', agents=('claude',)))
    for p in sorted(glob.glob(os.path.join(HOME, '.claude', 'skills', '*', 'SKILL.md'))):
        add(skill(p, 'user', agents=('claude',)))
    # Other CLI agents sharing the SKILL.md format: Codex CLI (~/.codex/skills, .codex/skills), the generic
    # ~/.agents/skills folder (read by several CLIs → no agent constraint), Gemini CLI (~/.gemini/skills)
    for base, label, agents in ((os.path.join(ROOT, '.codex', 'skills'), 'project (codex)', ('codex',)),
                                (os.path.join(HOME, '.codex', 'skills'), 'codex', ('codex',)),
                                (os.path.join(HOME, '.agents', 'skills'), 'agents', ()),
                                (os.path.join(HOME, '.gemini', 'skills'), 'gemini', ('gemini',))):
        for p in sorted(glob.glob(os.path.join(base, '*', 'SKILL.md'))) + sorted(glob.glob(os.path.join(base, '*', '*', 'SKILL.md'))):
            add(skill(p, label, agents=agents))
    reg = os.path.join(HOME, '.claude', 'plugins', 'installed_plugins.json')
    if os.path.isfile(reg):
        try:
            plugins = json.load(open(reg)).get('plugins', {})
        except (OSError, ValueError):
            plugins = {}
        for key, installs in plugins.items():
            plugin = key.split('@')[0]
            for inst in installs or []:
                base = inst.get('installPath')
                if not base:
                    continue
                for p in sorted(glob.glob(os.path.join(base, 'skills', '**', 'SKILL.md'), recursive=True)):
                    add(skill(p, f'plugin {plugin}', prefix=f'{plugin}:', agents=('claude',)))
    builtin = next((c for c in (os.path.join(os.path.dirname(os.path.abspath(__file__)), 'builtin-skills.json'),
                                 os.path.join(ROOT, 'scripts', 'builtin-skills.json')) if os.path.isfile(c)), '')
    if os.path.isfile(builtin):
        for s in json.load(open(builtin)):
            s.setdefault('source', 'built-in')
            s.setdefault('agents', ['claude'])  # funzioni interne di Claude Code: nessun altro agente le ha
            add(s)
    return sorted(out.values(), key=lambda s: s['name'].lower())


def main():
    data = json.dumps(collect(), ensure_ascii=False, indent=1)
    if '--print' in sys.argv:
        print(data); return
    r = subprocess.run(['docker', 'exec', '-i', '-u', os.environ.get('GRIGLIA_USER', 'www-data'), CONTAINER, 'php', 'artisan', 'griglia:skills-import'], input=data, text=True)
    sys.exit(r.returncode)


if __name__ == '__main__':
    main()
