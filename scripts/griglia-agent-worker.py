#!/usr/bin/env python3
"""Persistent host worker that dispatches Griglia tasks to a CLI coding agent."""

from __future__ import annotations

import argparse
import fcntl
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys
import time


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
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


def board(args: argparse.Namespace, all_items: bool = False) -> dict:
    command = board_command(args, all_items)
    result = subprocess.run(command, cwd=args.repo, text=True, capture_output=True, check=False)
    if result.returncode:
        raise RuntimeError(result.stderr.strip() or result.stdout.strip() or "griglia:check failed")
    return json.loads(result.stdout)


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


def start_agent(args: argparse.Namespace, task: dict) -> subprocess.Popen | None:
    command = driver_command(args, prompt(args.agent, task))
    print(f"dispatching task {task['id']} to {args.driver or args.agent}", flush=True)
    if args.dry_run:
        print(json.dumps(command, ensure_ascii=False))
        return None
    return subprocess.Popen(command, cwd=args.repo)


def terminate(process: subprocess.Popen, task_id: int) -> int:
    print(f"stop requested for task {task_id}; terminating agent", flush=True)
    process.terminate()
    try:
        return process.wait(timeout=15)
    except subprocess.TimeoutExpired:
        process.kill()
        return process.wait()


def main() -> int:
    args = parse_args()
    args.repo = args.repo.resolve()
    with lock_path(args.repo, args.agent).open("w") as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            print(f"worker for {args.agent} in {args.repo} is already running", file=sys.stderr)
            return 2
        running: dict[int, subprocess.Popen] = {}
        while True:
            try:
                state = board(args, all_items=True)
                items = state["items"]
                for task_id, process in list(running.items()):
                    if process.poll() is not None:
                        status = int(process.returncode or 0)
                        del running[task_id]
                        if status:
                            time.sleep(max(2, args.retry_delay))
                    elif not any(item.get("id") == task_id and not item.get("stopped_at") for item in items):
                        terminate(process, task_id)
                        del running[task_id]

                limit = max(1, args.max_parallel) if state.get("task_mode") == "multitasking" else 1
                eligible = [item for item in items if item.get("id") not in running and not item.get("completed") and not item.get("question") and (item.get("working") or item.get("open_to_work"))]
                for task in eligible[:max(0, limit - len(running))]:
                    process = start_agent(args, task)
                    if process is not None:
                        running[int(task["id"])] = process
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
