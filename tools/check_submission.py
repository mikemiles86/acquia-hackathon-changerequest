#!/usr/bin/env python3
"""
Basic validator for Permission to Run submissions.

Usage:
    python3 tools/check_submission.py /path/to/submission
"""

from __future__ import annotations

import argparse
import pathlib
import re
import sys
from typing import Iterable

REQUIRED_README_SECTIONS = [
    "what we built",
    "what the agent did",
    "what the human did",
    "drupal-in-the-loop",
    "ax artifact",
    "how to run",
]

RUN_LOG_HINTS = [
    "agent run log",
    "run log",
]

AX_REPORT_HINTS = [
    "agent experience report",
    "experience report",
]


def normalize(text: str) -> str:
    return re.sub(r"\s+", " ", text.lower()).strip()


def read_text(path: pathlib.Path) -> str:
    try:
        return path.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        return ""


def find_files(root: pathlib.Path) -> list[pathlib.Path]:
    return [p for p in root.rglob("*") if p.is_file()]


def find_first_by_name(files: Iterable[pathlib.Path], names: Iterable[str]) -> pathlib.Path | None:
    targets = {n.lower() for n in names}
    for file in files:
        if file.name.lower() in targets:
            return file
    return None


def find_by_content(files: Iterable[pathlib.Path], hints: Iterable[str]) -> pathlib.Path | None:
    hints_norm = [normalize(h) for h in hints]
    for file in files:
        if file.suffix.lower() not in {".md", ".txt", ".rst"}:
            continue
        text = normalize(read_text(file))
        if any(h in text for h in hints_norm):
            return file
    return None


def report(ok: bool, message: str) -> None:
    status = "PASS" if ok else "FAIL"
    print(f"[{status}] {message}")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("path", help="Path to submission folder")
    args = parser.parse_args()

    root = pathlib.Path(args.path).expanduser().resolve()
    if not root.exists():
        print(f"Path does not exist: {root}", file=sys.stderr)
        return 2
    if not root.is_dir():
        print(f"Path is not a directory: {root}", file=sys.stderr)
        return 2

    files = find_files(root)

    readme = find_first_by_name(files, ["README.md", "readme.md"]) or find_by_content(files, ["what we built", "drupal-in-the-loop"])
    run_log = find_by_content(files, RUN_LOG_HINTS)
    ax_report = find_by_content(files, AX_REPORT_HINTS)

    agents_md = find_first_by_name(files, ["AGENTS.md", "agents.md"])
    has_skill_or_runbook = any(("skill" in f.name.lower() or "runbook" in f.name.lower()) for f in files)
    has_validator = any(("validator" in f.name.lower() or "check_" in f.name.lower()) for f in files)
    has_benchmark = any("benchmark" in f.name.lower() for f in files)

    has_any_ax_artifact = bool(agents_md or has_skill_or_runbook or has_validator or has_benchmark)

    okay = True

    report(readme is not None, "README present")
    if readme is None:
        okay = False
    else:
        text = normalize(read_text(readme))
        missing = [section for section in REQUIRED_README_SECTIONS if section not in text]
        report(not missing, f"README contains expected sections ({', '.join(REQUIRED_README_SECTIONS)})")
        if missing:
            okay = False
            print("  Missing sections:", ", ".join(missing))

    report(run_log is not None, "Agent Run Log present")
    if run_log is None:
        okay = False

    report(ax_report is not None, "Agent Experience Report present")
    if ax_report is None:
        okay = False

    report(has_any_ax_artifact, "At least one AX artifact present (AGENTS.md, skill/runbook, validator, or benchmark)")
    if not has_any_ax_artifact:
        okay = False

    work_hints = ["what the agent did", "agent did", "commands", "tool calls", "generated", "drafted", "proposed"]
    work_found = False
    for file in files:
        if file.suffix.lower() not in {".md", ".txt", ".rst"}:
            continue
        text = normalize(read_text(file))
        if any(h in text for h in work_hints):
            work_found = True
            break

    report(work_found, "Evidence of a real agent-work moment")
    if not work_found:
        okay = False

    print()
    if okay:
        print("Submission looks structurally complete.")
        return 0

    print("Submission is missing one or more required elements.")
    return 1


if __name__ == "__main__":
    raise SystemExit(main())
