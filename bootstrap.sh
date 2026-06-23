#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_NAME="$(basename "${ROOT_DIR}" | tr '[:upper:]' '[:lower:]' | tr -cs 'a-z0-9' '-')"
WPCLI_DOCKER=(docker run --rm --network "${PROJECT_NAME}_default" -v "${ROOT_DIR}/src:/var/www/html" wordpress:cli)
WP_URL="${WP_URL:-http://localhost:8080}"
WP_TITLE="${WP_TITLE:-Luxstage}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-ChangeMeStrong123!}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@luxstage.local}"

if [[ ! -f "${ROOT_DIR}/.env" ]]; then
  cp "${ROOT_DIR}/.env.example" "${ROOT_DIR}/.env"
fi

docker compose up -d
sleep 12

if ! "${WPCLI_DOCKER[@]}" core is-installed --path=/var/www/html; then
  "${WPCLI_DOCKER[@]}" core install \
    --path=/var/www/html \
    --url="${WP_URL}" \
    --title="${WP_TITLE}" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --skip-email
fi

"${WPCLI_DOCKER[@]}" plugin install advanced-custom-fields --activate --path=/var/www/html
"${WPCLI_DOCKER[@]}" plugin install fluentform --activate --path=/var/www/html || "${WPCLI_DOCKER[@]}" plugin install contact-form-7 --activate --path=/var/www/html
"${WPCLI_DOCKER[@]}" plugin install elementor --activate --path=/var/www/html
"${WPCLI_DOCKER[@]}" plugin install seo-by-rank-math --activate --path=/var/www/html
"${WPCLI_DOCKER[@]}" plugin install webp-converter-for-media --activate --path=/var/www/html

"${WPCLI_DOCKER[@]}" theme activate luxstage --path=/var/www/html
"${WPCLI_DOCKER[@]}" rewrite structure '/%postname%/' --hard --path=/var/www/html
"${WPCLI_DOCKER[@]}" rewrite flush --hard --path=/var/www/html

echo "Bootstrap completed."
echo "Open: http://localhost:8080"
