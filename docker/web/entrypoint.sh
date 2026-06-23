#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="/var/www/html"

mkdir -p /run/php-fpm /run/nginx /var/log/php-fpm
chown -R nginx:nginx /run/php-fpm /run/nginx /var/log/php-fpm "${APP_ROOT}"

if [[ -f "${APP_ROOT}/wp-config.php" ]]; then
  sed -i "s/database_name_here/${DB_NAME:-wordpress}/g" "${APP_ROOT}/wp-config.php"
  sed -i "s/username_here/${DB_USER:-wordpress}/g" "${APP_ROOT}/wp-config.php"
  sed -i "s/password_here/${DB_PASSWORD:-wordpress}/g" "${APP_ROOT}/wp-config.php"
  sed -i "s/localhost/${DB_HOST:-db:3306}/g" "${APP_ROOT}/wp-config.php"
fi

exec "$@"
