#!/usr/bin/env bash
# Cursor stop hook wrapper: drain stdin JSON, then run remote sidecar validation.
# Raw `chunk validate` can hang waiting for hook stdin; this script avoids that.
set -euo pipefail

export PATH="${HOME}/.local/bin:${PATH}"

# Drain hook stdin so chunk does not block on JSON input.
cat >/dev/null

cd "${CURSOR_PROJECT_DIR:-${CLAUDE_PROJECT_DIR:-.}}"

SSH_KEY="${HOME}/.ssh/chunk_ai"
if [[ ! -f "${SSH_KEY}" ]]; then
  bash .cursor/setup-chunk.sh
fi

IDENTITY_ARGS=()
if [[ -f "${SSH_KEY}" ]]; then
  IDENTITY_ARGS=(--identity-file "${SSH_KEY}")
fi

if ! chunk validate --remote "${IDENTITY_ARGS[@]}"; then
  exit 2
fi

exit 0
