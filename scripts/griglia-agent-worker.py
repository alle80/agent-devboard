#!/usr/bin/env python3
"""Persistent host worker that dispatches Griglia tasks to a CLI coding agent."""

from __future__ import annotations

import argparse
import fcntl
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
    parser.add_argument("--interval", type=int, default=int(os.getenv("GRIGLIA_WORKER_INTERVAL", "10")))
    parser.add_argument("--retry-delay", type=int, default=int(os.getenv("GRIGLIA_WORKER_RETRY_DELAY", "30")))
    parser.add_argument("--repo", type=Path, default=Path(os.getenv("GRIGLIA_WORKER_REPO", Path.cwd())))
    parser.add_argument("--once", action="store_true", help="Run at most one agent session")
    parser.add_argument("--dry-run", action="store_true", help="Print the selected task and command")
    return parser.parse_args()


def board_command(args: argparse.Namespace, all_items: bool = False) -> list[str]:
    artisan = ["artisan", "griglia:check", f"--agent={args.agent}", "--json"]
    if args.transport == "docker":
        command = ["docker", "exec", args.container, "php", *artisan]
    else:
        command = [args.php, *artisan]
    if all_items:
        command.append("--all")
    return command


def board(args: argparse.Namespace, all_items: bool = False) -> list[dict]:
    command = board_command(args, all_items)
    result = subprocess.run(command, cwd=args.repo, text=True, capture_output=True, check=False)
    if result.returncode:
        raise RuntimeError(result.stderr.strip() or result.stdout.strip() or "griglia:check failed")
    return json.loads(result.stdout)


def selected_task(items: list[dict]) -> dict | None:
    eligible = [item for item in items if not item.get("completed") and not item.get("question") and (item.get("working") or item.get("open_to_work"))]
    return next((item for item in eligible if item.get("working")), eligible[0] if eligible else None)


def prompt(agent: str, task: dict) -> str:
    return (
        f"Work on Griglia as agent {agent}. Read AGENTS.md first and obey it. "
        f"Task id {task['id']} ({task['title']!r}) is the task selected by the persistent worker. "
        f"Your first board action must be `griglia:check --agent={agent} --take={task['id']}` unless it is already working. "
        "Complete the task, including required tests, documentation, progress, git workflow, token statistics and board closure. "
        "Stop immediately if the board reports a stop request. Do not work on a different task."
    )


def driver_command(args: argparse.Namespace, message: str) -> list[str]:
    driver = args.driver or args.agent
    if driver == "codex":
        return ["codex", "exec", "--approve-for-me", "-C", str(args.repo), message]
    if driver == "claude":
        return ["claude", "-p", "--permission-mode", "bypassPermissions", message]
    if driver == "custom":
        raw = os.getenv("GRIGLIA_WORKER_COMMAND_JSON")
        if not raw:
            raise RuntimeError("GRIGLIA_WORKER_COMMAND_JSON is required for the custom driver")
        return [str(part).format(prompt=message, repo=args.repo, agent=args.agent) for part in json.loads(raw)]
    raise RuntimeError(f"No driver for agent {args.agent!r}; set GRIGLIA_WORKER_DRIVER=custom")


def stop_requested(args: argparse.Namespace, task_id: int) -> bool:
    item = next((item for item in board(args, all_items=True) if item.get("id") == task_id), None)
    return item is None or bool(item.get("stopped_at"))


def run_agent(args: argparse.Namespace, task: dict) -> int:
    command = driver_command(args, prompt(args.agent, task))
    print(f"dispatching task {task['id']} to {args.driver or args.agent}", flush=True)
    if args.dry_run:
        print(json.dumps(command, ensure_ascii=False))
        return 0
    process = subprocess.Popen(command, cwd=args.repo)
    while process.poll() is None:
        time.sleep(max(2, args.interval))
        try:
            if stop_requested(args, int(task["id"])):
                print(f"stop requested for task {task['id']}; terminating agent", flush=True)
                process.terminate()
                try:
                    return process.wait(timeout=15)
                except subprocess.TimeoutExpired:
                    process.kill()
                    return process.wait()
        except Exception as exc:
            print(f"board poll failed while agent runs: {exc}", file=sys.stderr, flush=True)
    return int(process.returncode or 0)


def main() -> int:
    args = parse_args()
    args.repo = args.repo.resolve()
    lock_path = Path("/tmp") / f"griglia-agent-worker-{args.agent}.lock"
    with lock_path.open("w") as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            print(f"worker for {args.agent} is already running", file=sys.stderr)
            return 2
        while True:
            try:
                task = selected_task(board(args))
                if task:
                    status = run_agent(args, task)
                    if args.once:
                        return status
                    if status:
                        time.sleep(max(2, args.retry_delay))
                elif args.once:
                    return 0
                else:
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
