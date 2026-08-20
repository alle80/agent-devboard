# Persistent workers

An interactive terminal or chat does not stay alive forever. A **persistent worker** runs under the host's
service manager, polls Griglia and starts a fresh non-interactive agent session whenever work is assigned.
Closing the terminal, browser or original agent session does not stop it.

Griglia ships the worker and a systemd user-service template with its other host scripts:

```bash
php artisan vendor:publish --tag=griglia-scripts
```

The worker is vendor-neutral around the board contract: every instance uses its own agent key with
`griglia:check --agent=<key>`, its own lock and the same task states. Built-in launch drivers are available
for **Codex CLI** and **Claude Code**; a JSON argv template connects another CLI without shell evaluation.

## Install the systemd user service

Copy the example and replace `/absolute/path/to/project` in both lines with the real absolute project path:

```bash
mkdir -p ~/.config/systemd/user
cp scripts/systemd/griglia-agent-worker@.service.example \
  ~/.config/systemd/user/griglia-agent-worker@.service
sed -i 's#/absolute/path/to/project#/srv/my-project#g' \
  ~/.config/systemd/user/griglia-agent-worker@.service
systemctl --user daemon-reload
```

Enable one instance per configured agent. The instance name is the Griglia agent key:

```bash
systemctl --user enable --now griglia-agent-worker@codex.service
systemctl --user enable --now griglia-agent-worker@claude.service
```

`codex` invokes `codex exec --approve-for-me`; `claude` invokes
`claude -p --permission-mode acceptEdits`. The unit adds `%h/.local/bin` to `PATH`, the usual location for
user-installed launchers. If `command -v codex` or `command -v claude` reports another directory, put a
complete `PATH=...` line in `~/.config/griglia-worker/<agent-key>.env`.

Inspect the service and follow its output:

```bash
systemctl --user status griglia-agent-worker@codex.service
journalctl --user -u griglia-agent-worker@codex.service -f
```

To keep user services running after logout and start them during boot, enable lingering once:

```bash
loginctl enable-linger "$USER"
loginctl show-user "$USER" -p Linger   # expected: Linger=yes
```

## Configuration

Each instance optionally reads `~/.config/griglia-worker/<agent-key>.env`:

```dotenv
GRIGLIA_WORKER_DRIVER=codex
GRIGLIA_WORKER_INTERVAL=10
GRIGLIA_WORKER_RETRY_DELAY=30
GRIGLIA_WORKER_CONTAINER=laravel-dev-app
GRIGLIA_WORKER_REPO=/srv/my-project
```

The driver defaults to the agent key, so keys named `codex` and `claude` need no env file. If the key is
different, set the matching driver explicitly.

For Gemini CLI, Aider or another agent, use the custom driver. The JSON array is executed directly (never
through a shell); `{prompt}`, `{repo}` and `{agent}` are replaced in individual arguments:

```dotenv
GRIGLIA_WORKER_DRIVER=custom
GRIGLIA_WORKER_COMMAND_JSON=["agent-cli","--cwd","{repo}","--prompt","{prompt}"]
```

The service account must be able to run Docker (when the Laravel app is containerized) and the selected agent
CLI non-interactively. Do not use unrestricted sandbox/approval bypass flags: grant only the project permissions
the workflow needs.

## Behaviour and testing

The worker polls the current board state, so it also finds work that was already open before a restart. It
prefers an already-working task, otherwise the first open task in board order. One `flock` per agent prevents
duplicate sessions. While an agent runs, the worker keeps polling; a board Stop terminates that child process.
After the agent exits, the service returns to polling and systemd restarts the worker after failures.

Check configuration without launching an agent:

```bash
scripts/griglia-agent-worker.py --agent=codex --driver=codex --once --dry-run
```

For an end-to-end smoke test, enable the service, create a harmless task assigned to that agent and mark it
open to work. The journal should show `dispatching task <id> to <agent>` and the board should move from open,
to working, to done. Closing the terminal that started the test does not affect the systemd service.

Disable an instance with:

```bash
systemctl --user disable --now griglia-agent-worker@codex.service
```

## See also

- [The agent side](index.md) — commands, states and multi-agent scoping.
- [Host scripts](scripts.md) — all helpers published by `griglia-scripts`.
- [Artisan commands](../reference/commands.md) — generated command reference.
