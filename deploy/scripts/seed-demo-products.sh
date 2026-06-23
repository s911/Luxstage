#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-luxstage}"

docker run --rm \
  --user 0:0 \
  --network "${COMPOSE_PROJECT_NAME}_default" \
  -v "${PROJECT_ROOT}:/work" \
  -v "${PROJECT_ROOT}/src:/var/www/html" \
  wordpress:cli \
  wp --allow-root eval-file /work/deploy/scripts/seed-demo-products.php --path=/var/www/html

docker run --rm \
  --user 0:0 \
  --network "${COMPOSE_PROJECT_NAME}_default" \
  -v "${PROJECT_ROOT}/src:/var/www/html" \
  wordpress:cli \
  wp --allow-root rewrite flush --hard --path=/var/www/html
