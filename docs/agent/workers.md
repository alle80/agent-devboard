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

### Multiple applications on the same computer

Run **one worker per application and agent**: each worker polls one board and starts the agent in that
project's directory. The lock automatically includes both repository and agent key, so two applications can
both use `codex` without blocking each other.

For each application, copy the template under a unique unit prefix and replace its project path. For example:

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

Shared agent configuration remains in `~/.config/griglia-worker/codex.env`. Project-specific settings can
override it in `~/.config/griglia-worker/griglia-agent-worker-app-one-codex.env` (the unit's `%p-%i.env`
pattern). In particular, each Docker application needs a distinct `GRIGLIA_WORKER_CONTAINER`; for local
transport set `GRIGLIA_WORKER_REPO` and, when needed, `GRIGLIA_WORKER_PHP` instead.

`codex` invokes `codex exec --approve-for-me`; `claude` invokes
`claude -p --permission-mode bypassPermissions`. The unit adds `%h/.local/bin` to `PATH`, the usual location for
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
GRIGLIA_WORKER_MAX_PARALLEL=2
GRIGLIA_WORKER_TRANSPORT=docker
GRIGLIA_WORKER_CONTAINER=laravel-dev-app
GRIGLIA_WORKER_REPO=/srv/my-project
```

The Docker transport is the default and runs `docker exec <container> php artisan`. If Laravel runs directly
on the worker host, use the local transport instead; Artisan runs with the repository as working directory, so
no Docker is involved anywhere in the loop:

```dotenv
GRIGLIA_WORKER_TRANSPORT=local
GRIGLIA_WORKER_PHP=/usr/bin/php8.4
GRIGLIA_WORKER_REPO=/srv/my-project
```

The `GRIGLIA_WORKER_*` names configure one instance. When they are absent the worker falls back to the
variables the other [host scripts](scripts.md) read — `GRIGLIA_TRANSPORT`, `GRIGLIA_PHP`, `GRIGLIA_CONTAINER` —
so a single choice, exported once for the machine, covers the worker and the helpers the agent itself runs
(token counting, context and skill synchronization). Every setting also has a flag, useful for a one-off run:

| Flag | Env variable | Default |
| --- | --- | --- |
| `--transport docker\|local` | `GRIGLIA_WORKER_TRANSPORT`, `GRIGLIA_TRANSPORT` | `docker` |
| `--container` | `GRIGLIA_WORKER_CONTAINER`, `GRIGLIA_CONTAINER` | `laravel-dev-app` |
| `--php` | `GRIGLIA_WORKER_PHP`, `GRIGLIA_PHP` | `php` |
| `--repo` | `GRIGLIA_WORKER_REPO` | current directory |
| `--driver codex\|claude\|custom` | `GRIGLIA_WORKER_DRIVER` | the agent key |
| `--interval`, `--retry-delay` | `GRIGLIA_WORKER_INTERVAL`, `GRIGLIA_WORKER_RETRY_DELAY` | `10`, `30` |
| `--max-parallel` | `GRIGLIA_WORKER_MAX_PARALLEL` | `2` |
| `--model` | `GRIGLIA_WORKER_MODEL` | the agent CLI's own default |
| `--effort` | `GRIGLIA_WORKER_EFFORT` | the agent CLI's own default |

The driver defaults to the agent key, so keys named `codex` and `claude` need no env file. If the key is
different, set the matching driver explicitly.

### Model and reasoning effort

Without further configuration each session uses the model the agent CLI is configured with. `GRIGLIA_WORKER_MODEL`
and `GRIGLIA_WORKER_EFFORT` choose them per worker instead, so the board agent can run on a different model than
the interactive sessions of the same CLI:

```dotenv
GRIGLIA_WORKER_MODEL=fable
GRIGLIA_WORKER_EFFORT=max
```

The `claude` driver passes them as `--model` and `--effort` (`low`, `medium`, `high`, `xhigh`, `max`); the
`codex` driver as `--model` and `-c model_reasoning_effort="<effort>"`. The custom driver receives them as the
`{model}` and `{effort}` placeholders (empty strings when unset), so an argv template decides where they go.
Values are not validated by the worker: an unknown model or effort level fails inside the agent CLI.

The variables are read when the worker starts, so a change takes effect at the next
`systemctl --user restart griglia-agent-worker@<agent-key>.service` — which also terminates a session that is
running at that moment.

For Gemini CLI, Aider or another agent, use the custom driver. The JSON array is executed directly (never
through a shell); `{prompt}`, `{repo}`, `{agent}`, `{model}` and `{effort}` are replaced in individual arguments:

```dotenv
GRIGLIA_WORKER_DRIVER=custom
GRIGLIA_WORKER_COMMAND_JSON=["agent-cli","--cwd","{repo}","--prompt","{prompt}"]
```

Transport and driver are independent, so Codex, Claude and custom drivers work in both modes. The service
account must be able to run Docker or the configured local PHP executable, and the selected agent
CLI non-interactively. Do not use unrestricted sandbox/approval bypass flags: grant only the project permissions
the workflow needs.

## Behaviour and testing

The worker polls the current board state, so it also finds work that was already open before a restart. In
`ordered` mode it runs exactly one session. In `multitasking` mode it runs up to `--max-parallel` sessions
(default 2), one per eligible task; reduce the limit when tasks can touch the same files. One `flock` per
repository/agent pair prevents duplicate worker processes, while the worker tracks every child by task id.
A board Stop terminates only that task process. After a child exits, its slot becomes available.

Check configuration without launching an agent:

```bash
scripts/griglia-agent-worker.py --agent=codex --driver=codex --once --dry-run
scripts/griglia-agent-worker.py --agent=codex --transport=local --php=/usr/bin/php8.4 \
  --repo=/srv/my-project --once --dry-run
```

The command reads the board through the selected transport and prints the argv it would execute, so a failure
here is a transport or permission problem, not an agent one.

For an end-to-end smoke test, enable the service, create a harmless task assigned to that agent and mark it
open to work. The journal should show `dispatching task <id> to <agent>` and the board should move from open,
to working, to done. Closing the terminal that started the test does not affect the systemd service.

Disable an instance with:

```bash
systemctl --user disable --now griglia-agent-worker@codex.service
```

## See also

- [The agent side](index.md) — commands, states and multi-agent scoping.
- [Two agents at once](concurrency.md) — what two workers share, and how they avoid each other.
- [Host scripts](scripts.md) — all helpers published by `griglia-scripts`.
- [Artisan commands](../reference/commands.md) — generated command reference.
