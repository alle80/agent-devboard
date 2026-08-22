#!/usr/bin/env python3
"""Persistent host worker that dispatches Griglia tasks to a CLI coding agent.

The worker polls ``griglia:check --agent=<key> --worker-json --all`` and starts one non-interactive agent
session per eligible task: exactly one in the board's ``ordered`` mode, up to ``--max-parallel`` in
``multitasking``. It keeps itself current without interrupting anybody (task 507):

* when this file changes on disk it re-executes itself in place — same PID, same lock, and the running
  sessions are handed over with ``--adopt`` — so a new release of the script is live within one interval;
* ``SIGHUP`` drains it: no new session starts and, once the running ones have ended, the worker exits so
  the service manager restarts it with the current environment (``Restart=always`` in the systemd unit).
"""

from __future__ import annotations

import argparse
import fcntl
import hashlib
import json
import os
from pathlib import Path
import signal
import subprocess
import sys
import time


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--agent", required=True, help="Griglia agent key")
    parser.add_argument("--driver", choices=("codex", "claude", "custom"), default=os.getenv("GRIGLIA_WORKER_DRIVER"))
    parser.add_argument(
        "--transport",
        choices=("docker", "local"),
        default=os.getenv("GRIGLIA_WORKER_TRANSPORT", os.getenv("GRIGLIA_TRANSPORT", "docker")),
        help="How to invoke Artisan (default: docker); GRIGLIA_TRANSPORT is the shared fallback",
    )
    parser.add_argument("--container", default=os.getenv("GRIGLIA_WORKER_CONTAINER", os.getenv("GRIGLIA_CONTAINER", "laravel-dev-app")))
    parser.add_argument("--php", default=os.getenv("GRIGLIA_WORKER_PHP", os.getenv("GRIGLIA_PHP", "php")), help="PHP executable for local transport")
    parser.add_argument("--model", default=os.getenv("GRIGLIA_WORKER_MODEL"), help="Model for the agent CLI (alias or full name, e.g. fable or claude-fable-5)")
    parser.add_argument("--effort", default=os.getenv("GRIGLIA_WORKER_EFFORT"), help="Reasoning effort for the agent CLI (low, medium, high, xhigh, max)")
    parser.add_argument("--interval", type=int, default=int(os.getenv("GRIGLIA_WORKER_INTERVAL", "10")))
    parser.add_argument("--retry-delay", type=int, default=int(os.getenv("GRIGLIA_WORKER_RETRY_DELAY", "30")))
    parser.add_argument("--max-parallel", type=int, default=int(os.getenv("GRIGLIA_WORKER_MAX_PARALLEL", "2")), help="Concurrent sessions in board multitasking mode (default: 2)")
    parser.add_argument("--repo", type=Path, default=Path(os.getenv("GRIGLIA_WORKER_REPO", Path.cwd())))
    parser.add_argument("--once", action="store_true", help="Run at most one agent session")
    parser.add_argument("--dry-run", action="store_true", help="Print the selected task and command")
    # Internal, written by a hot reload: the sessions ("task:pid,...") and the lock fd of the previous incarnation
    parser.add_argument("--adopt", default="", help=argparse.SUPPRESS)
    parser.add_argument("--lock-fd", type=int, default=None, help=argparse.SUPPRESS)
    return parser.parse_args()


def board_command(args: argparse.Namespace, all_items: bool = False) -> list[str]:
    artisan = ["artisan", "griglia:check", f"--agent={args.agent}", "--worker-json"]
    if args.transport == "docker":
        command = ["docker", "exec", args.container, "php", *artisan]
    else:
        command = [args.php, *artisan]
    if all_items:
        command.append("--all")
    return command


def parse_board(output: str) -> dict:
    """Read the JSON object at the start of the output and ignore whatever follows it.

    The board must print nothing else in --worker-json, but one stray warning after the document (seen with a
    stalled plan, task 507) must not blind the worker for hours.
    """
    text = output.lstrip()
    start = text.find("{")
    if start < 0:
        raise RuntimeError(f"griglia:check did not return JSON: {text[:200]!r}")
    try:
        state, _ = json.JSONDecoder().raw_decode(text, start)
    except json.JSONDecodeError as exc:
        raise RuntimeError(f"griglia:check returned invalid JSON: {exc}") from exc
    if not isinstance(state, dict):
        raise RuntimeError("griglia:check --worker-json must return an object")
    return state


def board(args: argparse.Namespace, all_items: bool = False) -> dict:
    command = board_command(args, all_items)
    result = subprocess.run(command, cwd=args.repo, text=True, capture_output=True, check=False)
    if result.returncode:
        raise RuntimeError(result.stderr.strip() or result.stdout.strip() or "griglia:check failed")
    return parse_board(result.stdout)


def prompt(agent: str, task: dict) -> str:
    return (
        f"Work on Griglia as agent {agent}. Read AGENTS.md first and obey it. "
        f"Task id {task['id']} ({task['title']!r}) is the task selected by the persistent worker. "
        f"Your first board action must be `griglia:check --agent={agent} --take={task['id']}` unless it is already working. "
        "Complete the task, including required tests, documentation, progress, git workflow, token statistics and board closure. "
        "Stop immediately if the board reports a stop request. Do not work on a different task."
    )


def driver_command(args: argparse.Namespace, message: str) -> list[str]:
    """Build the argv of one agent session, adding model and effort when configured."""
    driver = args.driver or args.agent
    if driver == "codex":
        command = ["codex", "exec", "--approve-for-me", "-C", str(args.repo)]
        if args.model:
            command += ["--model", args.model]
        if args.effort:
            command += ["-c", f'model_reasoning_effort="{args.effort}"']
        return [*command, message]
    if driver == "claude":
        command = ["claude", "-p", "--permission-mode", "bypassPermissions"]
        if args.model:
            command += ["--model", args.model]
        if args.effort:
            command += ["--effort", args.effort]
        return [*command, message]
    if driver == "custom":
        raw = os.getenv("GRIGLIA_WORKER_COMMAND_JSON")
        if not raw:
            raise RuntimeError("GRIGLIA_WORKER_COMMAND_JSON is required for the custom driver")
        placeholders = {"prompt": message, "repo": args.repo, "agent": args.agent, "model": args.model or "", "effort": args.effort or ""}
        return [str(part).format(**placeholders) for part in json.loads(raw)]
    raise RuntimeError(f"No driver for agent {args.agent!r}; set GRIGLIA_WORKER_DRIVER=custom")


def lock_path(repo: Path, agent: str) -> Path:
    """Keep one worker per agent and repository, without cross-project collisions."""
    repo_key = hashlib.sha256(str(repo).encode()).hexdigest()[:12]
    return Path("/tmp") / f"griglia-agent-worker-{repo_key}-{agent}.lock"


def acquire_lock(args: argparse.Namespace):
    """Take the per-agent lock, or keep the one a hot reload handed over (same open file, flock still held)."""
    if args.lock_fd is not None:
        return os.fdopen(args.lock_fd, "w")
    lock = lock_path(args.repo, args.agent).open("w")
    try:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
    except BlockingIOError:
        lock.close()
        return None
    return lock


def alive(pid: int) -> bool:
    try:
        os.kill(pid, 0)
    except ProcessLookupError:
        return False
    except PermissionError:
        return True
    return True


class Session:
    """One agent session: a child started by this worker, or one adopted across a hot reload.

    A reload keeps the PID, so adopted sessions are still our children and ``waitpid`` reaps them.
    """

    def __init__(self, task_id: int, pid: int, process: subprocess.Popen | None = None):
        self.task_id = task_id
        self.pid = pid
        self.process = process
        self.returncode: int | None = None

    def poll(self) -> int | None:
        if self.returncode is not None:
            return self.returncode
        if self.process is not None:
            self.returncode = self.process.poll()
            return self.returncode
        try:
            pid, status = os.waitpid(self.pid, os.WNOHANG)
        except ChildProcessError:
            # Not our child (a hand-written --adopt): it counts as running for as long as the PID exists
            if alive(self.pid):
                return None
            self.returncode = 0
            return self.returncode
        if pid == 0:
            return None
        self.returncode = os.waitstatus_to_exitcode(status)
        return self.returncode

    def send(self, signum: int) -> None:
        try:
            os.kill(self.pid, signum)
        except ProcessLookupError:
            pass

    def wait(self, timeout: float) -> int | None:
        deadline = time.monotonic() + timeout
        while self.poll() is None and time.monotonic() < deadline:
            time.sleep(0.2)
        return self.poll()


def adopt(spec: str) -> dict[int, Session]:
    """The sessions the previous incarnation of this worker handed over ("task:pid,...")."""
    running: dict[int, Session] = {}
    for pair in filter(None, spec.split(",")):
        task_id, pid = (int(part) for part in pair.split(":", 1))
        session = Session(task_id, pid)
        if session.poll() is None:
            running[task_id] = session
    return running


def start_agent(args: argparse.Namespace, task: dict) -> Session | None:
    command = driver_command(args, prompt(args.agent, task))
    print(f"dispatching task {task['id']} to {args.driver or args.agent}", flush=True)
    if args.dry_run:
        print(json.dumps(command, ensure_ascii=False))
        return None
    process = subprocess.Popen(command, cwd=args.repo)
    return Session(int(task["id"]), process.pid, process)


def terminate(session: Session) -> int | None:
    print(f"stop requested for task {session.task_id}; terminating agent", flush=True)
    session.send(signal.SIGTERM)
    if session.wait(15) is None:
        session.send(signal.SIGKILL)
        session.wait(15)
    return session.returncode


def fingerprint() -> str:
    """Hash of this script as it is on disk (the symlink the service runs is followed)."""
    return hashlib.sha256(Path(__file__).read_bytes()).hexdigest()


def reload_worker(lock, running: dict[int, Session]) -> None:
    """Re-execute the script on disk in place: same PID, same lock, every running session handed over with
    --adopt, so an update never interrupts anybody. Returns only when the new file does not even compile —
    then the old code keeps running until the file changes again."""
    source = Path(__file__)
    try:
        compile(source.read_bytes(), str(source), "exec")
    except SyntaxError as exc:
        print(f"not reloading: {source} does not compile ({exc})", file=sys.stderr, flush=True)
        return
    argv = [arg for arg in sys.argv[1:] if not arg.startswith(("--adopt", "--lock-fd"))]
    if running:
        argv.append("--adopt=" + ",".join(f"{task_id}:{session.pid}" for task_id, session in running.items()))
    os.set_inheritable(lock.fileno(), True)
    argv.append(f"--lock-fd={lock.fileno()}")
    print(f"reloading worker from {source} ({len(running)} running session(s) carried over)", flush=True)
    sys.stdout.flush()
    sys.stderr.flush()
    os.execv(sys.executable, [sys.executable, str(source), *argv])


class DrainRequest:
    """SIGHUP: finish the running sessions, start no new one, then exit so the service manager restarts the
    worker with the current configuration (environment file, script)."""

    def __init__(self) -> None:
        self.requested = False

    def install(self) -> None:
        signal.signal(signal.SIGHUP, self.handle)

    def handle(self, signum, frame) -> None:
        self.requested = True
        print("SIGHUP received: draining — no new session starts, exiting when the running ones end", flush=True)


def main() -> int:
    args = parse_args()
    args.repo = args.repo.resolve()
    lock = acquire_lock(args)
    if lock is None:
        print(f"worker for {args.agent} in {args.repo} is already running", file=sys.stderr)
        return 2
    running = adopt(args.adopt)
    if running:
        print("carried over after reload: " + ", ".join(f"task {task_id} (pid {session.pid})" for task_id, session in running.items()), flush=True)
    drain = DrainRequest()
    drain.install()
    known = seen = fingerprint()
    while True:
        try:
            # 1. Reap finished sessions first, whatever the board says (a board error must not leave zombies)
            for task_id, session in list(running.items()):
                if session.poll() is not None:
                    status = int(session.returncode or 0)
                    print(f"task {task_id}: agent session ended with status {status}", flush=True)
                    del running[task_id]
                    if status:
                        time.sleep(max(2, args.retry_delay))
            if drain.requested and not running:
                print("drained: exiting so the service manager restarts the worker", flush=True)
                return 0

            # 2. A new version of this script on disk: re-exec in place once it has been stable for an interval
            if not args.once and not args.dry_run:
                current = fingerprint()
                if current != known and current == seen:
                    reload_worker(lock, running)  # only returns when the new file does not compile
                    known = current
                seen = current

            # 3. The board: stop what the user stopped (or moved away), then fill the free slots
            state = board(args, all_items=True)
            items = state["items"]
            for task_id, session in list(running.items()):
                if not any(item.get("id") == task_id and not item.get("stopped_at") for item in items):
                    terminate(session)
                    del running[task_id]

            limit = max(1, args.max_parallel) if state.get("task_mode") == "multitasking" else 1
            eligible = [] if drain.requested else [item for item in items if item.get("id") not in running and not item.get("completed") and not item.get("question") and (item.get("working") or item.get("open_to_work"))]
            for task in eligible[:max(0, limit - len(running))]:
                session = start_agent(args, task)
                if session is not None:
                    running[session.task_id] = session
                if args.once:
                    return 0

            if args.once and not running:
                return 0
            time.sleep(max(2, args.interval))
        except KeyboardInterrupt:
            return 130
        except Exception as exc:
            print(f"worker error: {exc}", file=sys.stderr, flush=True)
            if args.once:
                return 1
            time.sleep(max(2, args.retry_delay))


if __name__ == "__main__":
    raise SystemExit(main())
