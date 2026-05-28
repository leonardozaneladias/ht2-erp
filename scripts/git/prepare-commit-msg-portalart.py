#!/usr/bin/env python3
"""Hook prepare-commit-msg: alinha escopos comuns ao commitlint e remove Co-authored-by do Cursor."""

from __future__ import annotations

import re
import sys
from pathlib import Path

CURSOR_TRAILER = re.compile(
    r"^Co-authored-by:.*(?:[Cc]ursor|@cursor\.)",
)
INVALID_SCOPE = re.compile(r"^([a-z]+)\((?:database|db|sql)\)(:)")


def main() -> int:
    if len(sys.argv) < 2:
        return 0
    path = Path(sys.argv[1])
    if not path.is_file():
        return 0
    try:
        text = path.read_text(encoding="utf-8", errors="replace")
    except OSError:
        return 1
    lines = text.splitlines(keepends=True)
    if not lines:
        return 0

    lines[0], n_sub = INVALID_SCOPE.subn(r"\1(models)\2", lines[0], count=1)
    if n_sub:
        sys.stderr.write(
            "[portalart] prepare-commit-msg: escopo (database|db|sql) "
            "substituído por (models) na primeira linha\n"
        )

    lines = [ln for ln in lines if not CURSOR_TRAILER.match(ln)]

    path.write_text("".join(lines), encoding="utf-8")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
