#!/usr/bin/env python3
"""
Conta i token REALI spesi da Claude Code in questa sessione a partire da un istante
(tipicamente il `working_since` di un task della lista sviluppo), leggendo il transcript
JSONL che Claude Code scrive in ~/.claude/projects/<progetto>/<session>.jsonl
(ogni messaggio dell'assistente porta il blocco `usage` dell'API).

Uso (dal host, dove vive il transcript):
  scripts/claude-tokens.py --since 2026-08-19T08:12:00+02:00          # in=… out=…
  scripts/claude-tokens.py --todo 180 --args                          # legge working_since dal DB via artisan
      → stampa "--tokens-in=N --tokens-out=N", da incollare in `griglia:check --done=… --tokens-in… --tokens-out…`
  scripts/claude-tokens.py --context                                  # quanto pesa ORA il contesto della sessione

Il contesto viene riletto a ogni turno: quando diventa pesante ogni singolo passo costa di più. Con
--warn-at=N (migliaia di token, default 400; 0 = mai) lo script stampa su STDERR un promemoria da girare
all'utente — /clear lo può lanciare solo lui.

Token "in" = input_tokens + cache_creation_input_tokens + cache_read_input_tokens (tutto ciò che il modello
ha letto); "out" = output_tokens. I record duplicati dello stesso messaggio (stesso `message.id`) contano una volta.
"""
import argparse, glob, json, os, subprocess, sys
from datetime import datetime, timezone


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


REPO = project_root()
# Claude Code stores transcripts under ~/.claude/projects/<repo path with / replaced by ->/
PROJECT_DIR = os.path.expanduser('~/.claude/projects/' + os.environ.get('CLAUDE_PROJECT_SLUG', '-' + REPO.strip('/').replace('/', '-')))


def transcript_path(a) -> str:
    """Il transcript da leggere: quello indicato con --session, altrimenti il più recente del progetto."""
    if a.agent == 'codex':
        files = sorted(glob.glob(os.path.expanduser('~/.codex/sessions/**/rollout-*.jsonl'), recursive=True), key=os.path.getmtime)
    else:
        files = [os.path.join(PROJECT_DIR, a.session + '.jsonl')] if a.session else sorted(glob.glob(os.path.join(PROJECT_DIR, '*.jsonl')), key=os.path.getmtime)
    if not files or not os.path.isfile(files[-1]):
        sys.exit(f'no transcript found in {PROJECT_DIR}')
    return files[-1]


def parse_ts(s: str) -> datetime:
    s = s.strip().replace('Z', '+00:00')
    dt = datetime.fromisoformat(s)
    return dt if dt.tzinfo else dt.replace(tzinfo=timezone.utc)


def working_since_of(todo_id: int) -> str:
    out = subprocess.check_output(['docker', 'exec', 'laravel-dev-app', 'php', 'artisan', 'griglia:check', '--json', '--all'], text=True)
    for t in json.loads(out):
        if int(t['id']) == todo_id:
            if not t.get('working_since'):
                sys.exit(f'todo {todo_id} is not working (working_since is empty): use --since')
            return t['working_since']
    sys.exit(f'todo {todo_id} not found in the agent list')


def codex_usage(since: datetime):
    """Best effort for Codex CLI: rollouts in ~/.codex/sessions/**/rollout-*.jsonl carry `token_count` events
    (payload.info.total_token_usage / last_token_usage). Sums the per-turn `last_token_usage` after `since`."""
    base = os.path.expanduser('~/.codex/sessions')
    files = sorted(glob.glob(os.path.join(base, '**', 'rollout-*.jsonl'), recursive=True), key=os.path.getmtime)
    if not files:
        return 0, 0, 0, None
    path = files[-1]
    tin = tout = n = 0
    with open(path, encoding='utf-8') as fh:
        for line in fh:
            try:
                o = json.loads(line)
            except json.JSONDecodeError:
                continue
            p = o.get('payload') or {}
            if o.get('type') != 'event_msg' or p.get('type') != 'token_count':
                continue
            ts = o.get('timestamp')
            if ts and parse_ts(ts) < since:
                continue
            u = ((p.get('info') or {}).get('last_token_usage')) or {}
            if not u:
                continue
            n += 1
            tin += int(u.get('input_tokens', 0)) + int(u.get('cached_input_tokens', 0))
            tout += int(u.get('output_tokens', 0)) + int(u.get('reasoning_output_tokens', 0))
    return tin, tout, n, path


def context_size(path: str, agent: str) -> int:
    """Quanto pesa ora il contesto: l'input dell'ultimo turno (prompt + cache riletta)."""
    last = 0
    with open(path, encoding='utf-8') as fh:
        for line in fh:
            try:
                o = json.loads(line)
            except json.JSONDecodeError:
                continue
            if agent == 'codex':
                p = o.get('payload') or {}
                if o.get('type') != 'event_msg' or p.get('type') != 'token_count':
                    continue
                u = ((p.get('info') or {}).get('last_token_usage')) or {}
                if u:
                    last = int(u.get('input_tokens', 0)) + int(u.get('cached_input_tokens', 0))
                continue
            if o.get('type') != 'assistant':
                continue
            u = (o.get('message') or {}).get('usage')
            if u:
                last = int(u.get('input_tokens', 0)) + int(u.get('cache_creation_input_tokens', 0)) + int(u.get('cache_read_input_tokens', 0))
    return last


def warn_if_heavy(path: str, agent: str, warn_at_k: int) -> None:
    if warn_at_k <= 0 or not path:
        return
    size = context_size(path, agent)
    if size >= warn_at_k * 1000:
        print(f'⚠ contesto ~{round(size / 1000)}k token (soglia {warn_at_k}k): dì all\'utente di lanciare /clear '
              f'prima del prossimo task — il contesto si rilegge a ogni turno.', file=sys.stderr)


def main() -> None:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument('--since', help='ISO timestamp: count assistant messages from here on')
    ap.add_argument('--todo', type=int, help='id of the todo: use its working_since (via artisan --json)')
    ap.add_argument('--session', help='session id (default: the transcript most recently modified)')
    ap.add_argument('--args', action='store_true', help='print as griglia:check options (--tokens-in=N --tokens-out=N)')
    ap.add_argument('--agent', choices=['claude', 'codex'], default=os.environ.get('GRIGLIA_AGENT', 'claude'), help='which agent wrote the transcript (default claude; codex = ~/.codex/sessions rollouts)')
    ap.add_argument('--context', action='store_true', help='stampa quanto pesa ORA il contesto della sessione (ultimo turno)')
    ap.add_argument('--warn-at', type=int, default=int(os.environ.get('GRIGLIA_CLEAR_REMINDER_K', 400)), help='migliaia di token oltre le quali ricordare /clear su stderr (0 = mai)')
    a = ap.parse_args()
    if not a.since and not a.todo and not a.context:
        ap.error('--since, --todo or --context is required')

    if a.context and not a.since and not a.todo:
        path = transcript_path(a)
        size = context_size(path, a.agent)
        print(f'context={size} (~{round(size / 1000)}k) transcript={os.path.basename(path)}')
        warn_if_heavy(path, a.agent, a.warn_at)
        return

    since = parse_ts(a.since or working_since_of(a.todo))

    if a.agent == 'codex':
        tin, tout, n, path = codex_usage(since)
        if a.args:
            print(f'--tokens-in={tin} --tokens-out={tout}')
        else:
            print(f'in={tin} out={tout} events={n} since={since.isoformat()} transcript={os.path.basename(path) if path else "-"}')
        if path:
            warn_if_heavy(path, 'codex', a.warn_at)
        return

    path = transcript_path(a)

    seen, tin, tout, n = set(), 0, 0, 0
    with open(path, encoding='utf-8') as fh:
        for line in fh:
            try:
                o = json.loads(line)
            except json.JSONDecodeError:
                continue
            if o.get('type') != 'assistant':
                continue
            m = o.get('message') or {}
            u = m.get('usage')
            if not u or not o.get('timestamp'):
                continue
            if parse_ts(o['timestamp']) < since:
                continue
            key = m.get('id') or o.get('uuid')
            if key in seen:
                continue
            seen.add(key)
            n += 1
            tin += int(u.get('input_tokens', 0)) + int(u.get('cache_creation_input_tokens', 0)) + int(u.get('cache_read_input_tokens', 0))
            tout += int(u.get('output_tokens', 0))

    if a.args:
        print(f'--tokens-in={tin} --tokens-out={tout}')
    else:
        ctx = context_size(path, 'claude')
        print(f'in={tin} out={tout} messages={n} context={ctx} since={since.isoformat()} transcript={os.path.basename(path)}')

    warn_if_heavy(path, 'claude', a.warn_at)


if __name__ == '__main__':
    main()
