#!/usr/bin/env bash
set -euo pipefail

# Install the Chunk CLI (Linux x86_64/arm64) and prepare SSH identity for sidecar access.
# Idempotent: safe to run multiple times in Cloud Agent install or manually.

CHUNK_VERSION="${CHUNK_VERSION:-}"
INSTALL_DIR="${HOME}/.local/bin"
SSH_KEY="${HOME}/.ssh/chunk_ai"

mkdir -p "${INSTALL_DIR}" "${HOME}/.ssh"
chmod 700 "${HOME}/.ssh"

arch="$(uname -m)"
case "${arch}" in
  x86_64) platform="Linux_x86_64" ;;
  aarch64|arm64) platform="Linux_arm64" ;;
  *)
    echo "Unsupported architecture: ${arch}" >&2
    exit 1
    ;;
esac

if ! command -v chunk >/dev/null 2>&1; then
  if [[ -z "${CHUNK_VERSION}" ]]; then
    CHUNK_VERSION="$(
      curl -fsSL https://api.github.com/repos/CircleCI-Public/chunk-cli/releases/latest \
        | grep -o '"tag_name": "[^"]*"' \
        | head -1 \
        | cut -d'"' -f4
    )"
  fi

  tmpdir="$(mktemp -d)"
  trap 'rm -rf "${tmpdir}"' EXIT

  tarball="chunk-cli_${platform}.tar.gz"
  url="https://github.com/CircleCI-Public/chunk-cli/releases/download/${CHUNK_VERSION}/${tarball}"

  echo "Installing chunk ${CHUNK_VERSION} (${platform})..."
  curl -fsSL "${url}" -o "${tmpdir}/${tarball}"
  tar -xzf "${tmpdir}/${tarball}" -C "${tmpdir}"
  install -m 0755 "${tmpdir}/chunk" "${INSTALL_DIR}/chunk"
fi

export PATH="${INSTALL_DIR}:${PATH}"

if [[ ! -f "${SSH_KEY}" ]]; then
  ssh-keygen -t ed25519 -f "${SSH_KEY}" -N "" -C "chunk-sidecar" >/dev/null
  chmod 600 "${SSH_KEY}"
fi

token="${CIRCLECI_TOKEN:-${CIRCLE_TOKEN:-}}"
if [[ -n "${token}" ]]; then
  chunk auth set circleci --token "${token}" >/dev/null 2>&1 || true
fi

if [[ -f ".chunk/config.json" ]]; then
  org_id="$(grep -o '"orgID"[[:space:]]*:[[:space:]]*"[^"]*"' .chunk/config.json | head -1 | cut -d'"' -f4 || true)"
  if [[ -n "${org_id}" ]]; then
    chunk config set orgID "${org_id}" >/dev/null 2>&1 || true
  fi
fi

echo "chunk $(chunk --version 2>/dev/null || echo unknown)"
test -f "${SSH_KEY}" && echo "ssh key ok: ${SSH_KEY}"
