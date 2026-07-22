#!/usr/bin/env bash
# Restore WordPress core files into src/ without overwriting wp-content or wp-config.php.
# Git only tracks src/wp-content; core files are required at runtime.
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SRC="${PROJECT_ROOT}/src"

if [[ -f "${SRC}/wp-blog-header.php" && -d "${SRC}/wp-includes" ]]; then
  echo "OK: WordPress core already present in ${SRC}"
  exit 0
fi

bash "${PROJECT_ROOT}/deploy/scripts/extract-wordpress-core.sh"

COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-luxstage}"
if [[ -f "${PROJECT_ROOT}/.env" ]]; then
  set -a
  # shellcheck disable=SC1090
  source "${PROJECT_ROOT}/.env"
  set +a
fi

docker run --rm \
  --user 0:0 \
  --network "${COMPOSE_PROJECT_NAME}_default" \
  -v "${SRC}:/var/www/html" \
  wordpress:cli \
  wp --allow-root rewrite flush --hard --path=/var/www/html 2>/dev/null || true

echo "OK: WordPress core restored."
