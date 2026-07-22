#!/usr/bin/env bash
# Bootstrap Luxstage pages, CF7 forms, theme, and permalinks after WordPress install.
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-luxstage}"
ENV_FILE="${PROJECT_ROOT}/.env"

if [[ -f "${ENV_FILE}" ]]; then
  set -a
  # shellcheck disable=SC1090
  source "${ENV_FILE}"
  set +a
fi

docker run --rm \
  --user 0:0 \
  --network "${COMPOSE_PROJECT_NAME}_default" \
  --env WORDPRESS_DB_HOST="db:3306" \
  --env WORDPRESS_DB_USER="${DB_USER:-luxstage_user}" \
  --env WORDPRESS_DB_PASSWORD="${DB_PASSWORD:-}" \
  --env WORDPRESS_DB_NAME="${DB_NAME:-luxstage_b2b}" \
  -v "${PROJECT_ROOT}:/work" \
  -v "${PROJECT_ROOT}/src:/var/www/html" \
  wordpress:cli \
  wp --allow-root eval-file /work/deploy/scripts/bootstrap-wordpress-site.php --path=/var/www/html
