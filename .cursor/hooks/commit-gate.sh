#!/usr/bin/env bash
# Fast local gate before git commit on the Cloud Agent VM.
set -euo pipefail

input="$(cat)"
command="$(printf '%s' "${input}" | jq -r '.command // .tool_input.command // empty' 2>/dev/null || true)"

case "${command}" in
  "git commit"*|*" git commit"*)
    cd "${CURSOR_PROJECT_DIR:-${CLAUDE_PROJECT_DIR:-.}}"
    npm ci
    npm test
    ;;
esac

exit 0
