#!/usr/bin/env bash
# Extract WordPress core from offline zip packages (no internet).
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SRC="${PROJECT_ROOT}/src"
PACKAGES_DIR="${PROJECT_ROOT}/deploy/offline/packages"

if [[ -f "${SRC}/wp-blog-header.php" && -d "${SRC}/wp-includes" ]]; then
  echo "OK: WordPress core already present in ${SRC}"
  exit 0
fi

find_core_zip() {
  local candidate
  for candidate in \
    "${PACKAGES_DIR}/wordpress-6.6.2.zip" \
    "${PACKAGES_DIR}/wordpress-6.8.zip" \
    "${SRC}/wp-content/plugins/wordpress-6.8.zip" \
    ; do
    if [[ -f "${candidate}" ]]; then
      echo "${candidate}"
      return 0
    fi
  done

  shopt -s nullglob
  local matches=("${PACKAGES_DIR}"/wordpress-*.zip)
  shopt -u nullglob
  if [[ ${#matches[@]} -gt 0 ]]; then
    echo "${matches[0]}"
    return 0
  fi

  return 1
}

CORE_ZIP="$(find_core_zip || true)"
if [[ -z "${CORE_ZIP}" ]]; then
  echo "ERROR: WordPress core zip not found."
  echo "Place wordpress-6.6.2.zip in ${PACKAGES_DIR}"
  echo "See deploy/offline/ARTIFACTS.md"
  exit 1
fi

if ! command -v unzip >/dev/null 2>&1; then
  echo "ERROR: unzip is required."
  exit 1
fi

echo "Extracting WordPress core from ${CORE_ZIP}..."
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "${TMP_DIR}"' EXIT

unzip -q "${CORE_ZIP}" -d "${TMP_DIR}"
CORE_DIR="${TMP_DIR}/wordpress"
if [[ ! -d "${CORE_DIR}" ]]; then
  CORE_DIR="$(find "${TMP_DIR}" -mindepth 1 -maxdepth 1 -type d | head -n 1)"
fi

if [[ ! -f "${CORE_DIR}/wp-blog-header.php" ]]; then
  echo "ERROR: ${CORE_ZIP} does not look like a WordPress core archive."
  exit 1
fi

if command -v rsync >/dev/null 2>&1; then
  rsync -a \
    --exclude 'wp-content/' \
    --exclude 'wp-config.php' \
    --exclude 'wp-config-sample.php' \
    "${CORE_DIR}/" "${SRC}/"
else
  (
    cd "${CORE_DIR}"
    find . -mindepth 1 -maxdepth 1 ! -name wp-content -exec cp -a {} "${SRC}/" \;
  )
fi

echo "OK: WordPress core extracted to ${SRC}"
