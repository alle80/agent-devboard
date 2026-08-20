#!/usr/bin/env python3
"""
Raccoglie piano e finestre di utilizzo degli agenti CLI sul server e le manda alla board (/agents) con
`griglia:agent-status-import`. Le credenziali restano qui: alla board arrivano solo percentuali e orari di reset.

Agenti:
  - Claude Code: ~/.claude/.credentials.json (claudeAiOauth) → GET https://api.anthropic.com/api/oauth/usage
    (five_hour / seven_day: utilization %, resets_at; extra_usage). Piano da subscriptionType/rateLimitTier.
  - Codex CLI: nessuna API di utilizzo nota → riga «non configurato» se ~/.codex esiste.

Uso:  scripts/agent-status.py            # raccoglie e importa nel container
      scripts/agent-status.py --print    # stampa solo il JSON
Cron: */5 * * * * /path/to/laravel-dev/scripts/agent-status.py -q
"""
import json, os, subprocess, sys, urllib.request
from datetime import datetime, timezone

HOME = os.path.expanduser('~')
CONTAINER = os.environ.get('GRIGLIA_CONTAINER', 'laravel-dev-app')
# Trasporto di Artisan: 'docker' (default, `docker exec <container>`) oppure 'local' (PHP sull'host, niente Docker)
TRANSPORT = os.environ.get('GRIGLIA_TRANSPORT', 'docker')


def project_root():
    """La root del progetto che usa la board: $GRIGLIA_PROJECT_ROOT, altrimenti la cartella che contiene questi
    script (<progetto>/scripts) oppure — lanciando lo script da vendor/alle80/griglia/scripts — quella che
    contiene `vendor`. Serve come working directory quando Artisan gira in locale."""
    env = os.environ.get('GRIGLIA_PROJECT_ROOT')
    if env:
        return os.path.abspath(os.path.expanduser(env))
    here = os.path.dirname(os.path.abspath(__file__))
    parts = here.split(os.sep)
    if 'vendor' in parts:
        return os.sep.join(parts[:parts.index('vendor')]) or os.sep
    return os.path.dirname(here)


def artisan_command(*args):
    """`php artisan …` via `docker exec` oppure, con GRIGLIA_TRANSPORT=local, con GRIGLIA_PHP sull'host."""
    if TRANSPORT == 'local':
        return [os.environ.get('GRIGLIA_PHP', 'php'), 'artisan', *args]
    return ['docker', 'exec', '-i', '-u', os.environ.get('GRIGLIA_USER', 'www-data'), CONTAINER, 'php', 'artisan', *args]
PLAN_LABELS = {'max': 'Max', 'pro': 'Pro', 'team': 'Team', 'enterprise': 'Enterprise', 'free': 'Free'}


def claude():
    agent = {'key': 'claude', 'name': 'Claude Code', 'plan': None, 'plan_kind': None, 'windows': [], 'extra_usage': None, 'error': None}
    path = os.path.join(HOME, '.claude', '.credentials.json')
    if not os.path.isfile(path):
        agent['error'] = 'credentials not found'
        return agent
    try:
        o = json.load(open(path)).get('claudeAiOauth') or {}
    except (OSError, ValueError) as e:
        agent['error'] = f'cannot read credentials: {e}'
        return agent
    sub = (o.get('subscriptionType') or '').lower()
    tier = o.get('rateLimitTier') or ''
    mult = ''
    for part in tier.split('_'):
        if part.endswith('x') and part[:-1].isdigit():
            mult = ' ' + part
    agent['plan'] = (PLAN_LABELS.get(sub, sub.capitalize()) + mult).strip() or None
    agent['plan_kind'] = 'flat' if sub in ('max', 'pro', 'team', 'enterprise') else (sub or None)
    tok = o.get('accessToken')
    if not tok:
        agent['error'] = 'no access token'
        return agent
    req = urllib.request.Request('https://api.anthropic.com/api/oauth/usage', headers={
        'Authorization': 'Bearer ' + tok, 'anthropic-beta': 'oauth-2025-04-20', 'Accept': 'application/json', 'User-Agent': 'griglia/1.0'})
    try:
        with urllib.request.urlopen(req, timeout=20) as r:
            body = json.loads(r.read())
    except Exception as e:  # noqa: BLE001
        agent['error'] = f'usage endpoint: {e}'
        return agent
    for key, label in (('five_hour', '5 ore'), ('seven_day', '7 giorni'), ('seven_day_opus', '7 giorni · Opus'), ('seven_day_sonnet', '7 giorni · Sonnet')):
        w = body.get(key)
        if not isinstance(w, dict):
            continue
        agent['windows'].append({'key': key, 'label': label, 'utilization': w.get('utilization'), 'resets_at': w.get('resets_at'),
                                 'limit_dollars': w.get('limit_dollars'), 'used_dollars': w.get('used_dollars')})
    extra = body.get('extra_usage')
    if isinstance(extra, dict):
        agent['extra_usage'] = {k: extra.get(k) for k in ('is_enabled', 'monthly_limit', 'used_credits', 'utilization')}
    return agent


def codex():
    if not os.path.isdir(os.path.join(HOME, '.codex')):
        return None
    return {'key': 'codex', 'name': 'Codex CLI', 'plan': None, 'plan_kind': None, 'windows': [], 'extra_usage': None, 'error': None}


def main():
    agents = [a for a in (claude(), codex()) if a]
    data = {'updated_at': datetime.now(timezone.utc).isoformat(), 'agents': agents}
    payload = json.dumps(data, ensure_ascii=False, indent=1)
    if '--print' in sys.argv:
        print(payload); return
    r = subprocess.run(artisan_command('griglia:agent-status-import'), input=payload, text=True, capture_output=True, cwd=project_root() if TRANSPORT == 'local' else None)
    if '-q' not in sys.argv:
        print((r.stdout or r.stderr).strip())
    sys.exit(r.returncode)


if __name__ == '__main__':
    main()
