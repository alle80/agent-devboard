#!/usr/bin/env python3
"""
Rigenera CLAUDE.md / AGENTS.md (le istruzioni che l'agente — Claude Code, Codex CLI, Gemini CLI… — legge a ogni turno) dal contesto gestito nella board
(/context: gruppi e blocchi attivi) — `php artisan griglia:context export` nel container.

Uso:  scripts/sync-context.py            # scrive CLAUDE.md solo se il contenuto è cambiato (exit 0)
      scripts/sync-context.py --check    # stampa solo se CLAUDE.md è allineato (exit 1 se no)
      scripts/sync-context.py --import   # direzione opposta: importa il CLAUDE.md attuale nella board (--replace)
      scripts/sync-context.py --restore  # rimette i file ORIGINALI (docs/context-originals/) al posto di quelli generati
      scripts/sync-context.py --backup   # salva come originali i file attuali non generati
Se in /context l'interruttore «Genera i file di istruzioni dalla board» è spento, lo script ripristina gli originali e non tocca più i file.

Pensato per girare anche da cron (ogni minuto, silenzioso): `* * * * * /path/to/laravel-dev/scripts/sync-context.py -q`.
Il file generato porta un'intestazione HTML-comment: modifica i blocchi in /context, non il file.
"""
import os, subprocess, sys


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


ROOT = project_root()
# Target files: one per agent family — CLAUDE.md (Claude Code), AGENTS.md (Codex CLI, Cursor, Jules, Amp, Zed…),
# GEMINI.md (Gemini CLI) — override with GRIGLIA_CONTEXT_TARGETS="CLAUDE.md,AGENTS.md,GEMINI.md"
TARGETS = [t.strip() for t in os.environ.get('GRIGLIA_CONTEXT_TARGETS', 'CLAUDE.md,AGENTS.md').split(',') if t.strip()]
TARGET = os.path.join(ROOT, TARGETS[0])
CONTAINER = os.environ.get('GRIGLIA_CONTAINER', 'laravel-dev-app')
HEADER = '<!-- Generato da Griglia (/context): modifica i blocchi lì, non questo file. -->\n'
# Originali (pre-board) dei file generati: salvati qui la prima volta che un file viene sovrascritto e ripristinati
# con --restore o quando in /context si spegne «Genera i file di istruzioni dalla board»
ORIGINALS = os.path.join(ROOT, 'docs', 'context-originals')


def artisan(*args, stdin=None):
    return subprocess.run(['docker', 'exec', '-i', '-u', 'www-data', CONTAINER, 'php', 'artisan', *args], input=stdin, text=True, capture_output=True)


def is_generated(path):
    try:
        with open(path, encoding='utf-8') as fh:
            return fh.read(len(HEADER)) == HEADER
    except OSError:
        return False


def backup_original(name):
    """Salva l'originale di <name> (solo se esiste, non è generato e non è già salvato)."""
    target = os.path.join(ROOT, name)
    dest = os.path.join(ORIGINALS, name)
    if os.path.isfile(target) and not is_generated(target) and not os.path.isfile(dest):
        os.makedirs(ORIGINALS, exist_ok=True)
        with open(target, encoding='utf-8') as src, open(dest, 'w', encoding='utf-8') as dst:
            dst.write(src.read())
        return True
    return False


def restore(quiet=False):
    """Rimette gli originali al posto dei file generati (i file senza originale vengono rimossi se generati)."""
    done = []
    for name in TARGETS:
        target = os.path.join(ROOT, name)
        orig = os.path.join(ORIGINALS, name)
        if os.path.isfile(orig):
            with open(orig, encoding='utf-8') as src:
                body = src.read()
            if not os.path.isfile(target) or open(target, encoding='utf-8').read() != body:
                with open(target, 'w', encoding='utf-8') as dst:
                    dst.write(body)
                done.append(name + ' (restored)')
        elif os.path.isfile(target) and is_generated(target):
            os.remove(target)
            done.append(name + ' (removed: no original)')
    if not quiet:
        print('originals restored: ' + (', '.join(done) if done else 'nothing to do'))


def sync_enabled():
    r = artisan('griglia:context', 'enabled')
    return r.returncode != 0 or r.stdout.strip() != '0'  # on error assume enabled (legacy package)


def main():
    quiet = '-q' in sys.argv
    if '--restore' in sys.argv:
        restore(quiet); return
    if '--backup' in sys.argv:
        print(', '.join(n for n in TARGETS if backup_original(n)) or 'nothing to back up'); return
    if not sync_enabled():
        # the board says «do not generate»: put the originals back (once) and leave the files alone
        restore(quiet=True); return
    if '--import' in sys.argv:
        md = open(TARGET, encoding='utf-8').read()
        if md.startswith(HEADER):
            md = md[len(HEADER):]
        r = artisan('griglia:context', 'import', '--replace', stdin=md)
        print(r.stdout.strip() or r.stderr.strip()); sys.exit(r.returncode)
    r = artisan('griglia:context', 'export')
    if r.returncode != 0:
        if not quiet: print(r.stderr.strip() or 'export failed', file=sys.stderr)
        sys.exit(r.returncode)
    body = r.stdout
    if not body.strip():
        if not quiet: print('context is empty: CLAUDE.md left untouched', file=sys.stderr)
        sys.exit(2)
    new = HEADER + body
    changed = []
    for name in TARGETS:
        backup_original(name)
        target = os.path.join(ROOT, name)
        old = open(target, encoding='utf-8').read() if os.path.isfile(target) else ''
        if old != new:
            changed.append(name)
    if '--check' in sys.argv:
        print('in sync' if not changed else 'OUT OF SYNC: ' + ', '.join(changed)); sys.exit(0 if not changed else 1)
    if not changed:
        if not quiet: print(', '.join(TARGETS) + ' already up to date')
        return
    for name in changed:
        with open(os.path.join(ROOT, name), 'w', encoding='utf-8') as fh:
            fh.write(new)
    if not quiet: print(f'{", ".join(changed)} regenerated ({len(new)} chars)')


if __name__ == '__main__':
    main()
