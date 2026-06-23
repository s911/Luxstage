#!/usr/bin/env bash
set -euo pipefail

WP_PATH="${WP_PATH:-/var/www/html}"
WP_URL="${WP_URL:-http://localhost:8080}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-ChangeMeStrong123!}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@luxstage.local}"
WP_TITLE="${WP_TITLE:-Luxstage B2B}"

if ! command -v wp >/dev/null 2>&1; then
  echo "WP-CLI is required but not found in PATH."
  exit 1
fi

echo "Using WordPress path: ${WP_PATH}"

if [[ ! -f "${WP_PATH}/wp-settings.php" ]]; then
  echo "WordPress core not found. Installing core ${WP_CORE_VERSION:-latest}..."
  wp core download --path="${WP_PATH}" --version="${WP_CORE_VERSION:-latest}" --force
fi

if [[ ! -f "${WP_PATH}/wp-config.php" ]]; then
  echo "Creating wp-config.php..."
  wp config create \
    --path="${WP_PATH}" \
    --dbname="${DB_NAME:-luxstage_b2b}" \
    --dbuser="${DB_USER:-luxstage_user}" \
    --dbpass="${DB_PASSWORD:-ChangeMeStrongPassword}" \
    --dbhost="${DB_HOST:-db:3306}" \
    --skip-check \
    --force
fi

if ! wp core is-installed --path="${WP_PATH}" >/dev/null 2>&1; then
  echo "Running first-time WordPress install..."
  wp core install \
    --path="${WP_PATH}" \
    --url="${WP_URL}" \
    --title="${WP_TITLE}" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --skip-email
fi

echo "Installing required B2B plugins..."
wp plugin install advanced-custom-fields --activate --path="${WP_PATH}"
wp plugin install fluentform --activate --path="${WP_PATH}" || wp plugin install contact-form-7 --activate --path="${WP_PATH}"
wp plugin install elementor --activate --path="${WP_PATH}"
wp plugin install seo-by-rank-math --activate --path="${WP_PATH}"
wp plugin install webp-converter-for-media --activate --path="${WP_PATH}"

echo "Elementor Pro note:"
echo "- Place your licensed package at ${WP_PATH}/wp-content/plugins/elementor-pro.zip"
echo "- Then run: wp plugin install ${WP_PATH}/wp-content/plugins/elementor-pro.zip --activate --path=${WP_PATH}"

echo "All requested plugins are installed (or documented for licensed distribution)."
