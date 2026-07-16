#!/usr/bin/env bash
# Seed 3 demo records for each Luxstage content feature:
# products, catalogs, applications, inquiries.
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-luxstage}"

bash "${PROJECT_ROOT}/deploy/scripts/ensure-htaccess.sh"

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
