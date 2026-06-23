#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_NAME_RAW="$(basename "${ROOT_DIR}" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//')"
export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-${PROJECT_NAME_RAW}}"
WPCLI_VOLUME="${ROOT_DIR}/src:/var/www/html"
if command -v getenforce >/dev/null 2>&1; then
  SELINUX_STATE="$(getenforce || true)"
  if [[ "${SELINUX_STATE}" == "Enforcing" || "${SELINUX_STATE}" == "Permissive" ]]; then
    WPCLI_VOLUME="${ROOT_DIR}/src:/var/www/html:Z"
  fi
fi
WPCLI_DOCKER=(docker run --rm --user 0:0 --network "${COMPOSE_PROJECT_NAME}_default" -v "${WPCLI_VOLUME}" wordpress:cli wp --allow-root)
WP_URL="${WP_URL:-http://localhost:8080}"
WP_TITLE="${WP_TITLE:-Luxstage}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-ChangeMeStrong123!}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@luxstage.local}"

if [[ ! -f "${ROOT_DIR}/.env" ]]; then
  cp "${ROOT_DIR}/.env.example" "${ROOT_DIR}/.env"
fi

# shellcheck disable=SC1091
set -a
source "${ROOT_DIR}/.env"
set +a

DB_NAME="${DB_NAME:-wp_stage_lighting}"
DB_USER="${DB_USER:-wp_user}"
DB_PASSWORD="${DB_PASSWORD:-wp_pass}"
DB_HOST="${DB_HOST:-db}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-wp_root_pass}"
WP_CORE_VERSION="${WP_CORE_VERSION:-latest}"
LOCAL_WP_TARBALL="${LOCAL_WP_TARBALL:-}"

if [[ "${DB_HOST}" == "mysql" ]]; then
  DB_HOST="db"
fi

if [[ -z "${LOCAL_WP_TARBALL}" ]]; then
  if [[ -f "${ROOT_DIR}/../wordpress-${WP_CORE_VERSION}.tar.gz" ]]; then
    LOCAL_WP_TARBALL="${ROOT_DIR}/../wordpress-${WP_CORE_VERSION}.tar.gz"
  elif [[ -f "${ROOT_DIR}/../wordpress-6.6.2.tar.gz" ]]; then
    LOCAL_WP_TARBALL="${ROOT_DIR}/../wordpress-6.6.2.tar.gz"
  fi
fi

docker compose up -d
sleep 12

if [[ ! -f "${ROOT_DIR}/src/wp-settings.php" ]]; then
  if [[ -n "${LOCAL_WP_TARBALL}" && -f "${LOCAL_WP_TARBALL}" ]]; then
    tar -xzf "${LOCAL_WP_TARBALL}" -C "${ROOT_DIR}/src" --strip-components=1
  elif ! docker compose exec -T web sh -lc "cp -an /usr/src/wordpress/. /var/www/html/"; then
      "${WPCLI_DOCKER[@]}" core download \
        --path=/var/www/html \
        --version="${WP_CORE_VERSION}" \
        --force
  fi
fi

if [[ ! -f "${ROOT_DIR}/src/wp-config.php" ]]; then
  "${WPCLI_DOCKER[@]}" config create \
    --path=/var/www/html \
    --dbname="${DB_NAME}" \
    --dbuser="${DB_USER}" \
    --dbpass="${DB_PASSWORD}" \
    --dbhost="${DB_HOST}" \
    --skip-check \
    --force
fi

"${WPCLI_DOCKER[@]}" config set DB_NAME "${DB_NAME}" --type=constant --raw --path=/var/www/html
"${WPCLI_DOCKER[@]}" config set DB_USER "${DB_USER}" --type=constant --raw --path=/var/www/html
"${WPCLI_DOCKER[@]}" config set DB_PASSWORD "${DB_PASSWORD}" --type=constant --raw --path=/var/www/html
"${WPCLI_DOCKER[@]}" config set DB_HOST "${DB_HOST}" --type=constant --raw --path=/var/www/html

for i in {1..60}; do
  if docker compose exec -T db mysqladmin ping -uroot "-p${DB_ROOT_PASSWORD}" --silent >/dev/null 2>&1; then
    break
  fi
  if docker compose exec -T db mysqladmin ping -uroot --silent >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

MYSQL_ROOT_CMD=(docker compose exec -T db mysql -uroot "-p${DB_ROOT_PASSWORD}")
if ! "${MYSQL_ROOT_CMD[@]}" -e "SELECT 1;" >/dev/null 2>&1; then
  MYSQL_ROOT_CMD=(docker compose exec -T db mysql -uroot)
  if "${MYSQL_ROOT_CMD[@]}" -e "SELECT 1;" >/dev/null 2>&1; then
    "${MYSQL_ROOT_CMD[@]}" -e \
      "ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_ROOT_PASSWORD}'; FLUSH PRIVILEGES;"
    MYSQL_ROOT_CMD=(docker compose exec -T db mysql -uroot "-p${DB_ROOT_PASSWORD}")
  else
    echo "Unable to log in to MySQL as root after waiting."
    echo "Check DB_ROOT_PASSWORD in .env and ensure db container uses same value."
    echo "Quick reset command: docker compose down -v"
    exit 1
  fi
fi

"${MYSQL_ROOT_CMD[@]}" -e \
  "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
   CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASSWORD}'; \
   ALTER USER '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASSWORD}'; \
   GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'%'; \
   FLUSH PRIVILEGES;"

for i in {1..30}; do
  if "${WPCLI_DOCKER[@]}" db check --path=/var/www/html >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

if ! "${WPCLI_DOCKER[@]}" db check --path=/var/www/html >/dev/null 2>&1; then
  echo "Database is still unreachable after waiting."
  echo "Current DB_HOST=${DB_HOST}, DB_NAME=${DB_NAME}, DB_USER=${DB_USER}"
  exit 1
fi

if ! "${WPCLI_DOCKER[@]}" core is-installed --path=/var/www/html >/dev/null 2>&1; then
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
