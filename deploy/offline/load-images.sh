#!/usr/bin/env bash
# Load Docker images from offline tar archives.
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
IMAGES_DIR="${PROJECT_ROOT}/deploy/offline/images"

if [[ ! -d "${IMAGES_DIR}" ]]; then
  echo "ERROR: ${IMAGES_DIR} does not exist."
  exit 1
fi

shopt -s nullglob
TARS=("${IMAGES_DIR}"/*.tar)
shopt -u nullglob

if [[ ${#TARS[@]} -eq 0 ]]; then
  echo "ERROR: No .tar files found in ${IMAGES_DIR}"
  echo "Run deploy/offline/prepare-bundle.sh on an online PC first."
  exit 1
fi

for tar_file in "${TARS[@]}"; do
  echo "Loading image archive: $(basename "${tar_file}")"
  docker load -i "${tar_file}"
done

echo "OK: Loaded ${#TARS[@]} Docker image archive(s)."
