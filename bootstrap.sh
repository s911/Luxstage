#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ ! -f "${ROOT_DIR}/.env" ]]; then
  cp "${ROOT_DIR}/.env.example" "${ROOT_DIR}/.env"
fi

docker compose up -d --build

docker compose exec web bash -lc "bash /usr/local/bin/install-plugins.sh"

docker compose exec web wp theme activate fabricwarm-b2b --path=/var/www/html
docker compose exec web wp rewrite structure '/%postname%/' --hard --path=/var/www/html
docker compose exec web wp rewrite flush --hard --path=/var/www/html

echo "Bootstrap completed."
echo "Open: http://localhost:8080"
