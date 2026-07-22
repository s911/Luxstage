#!/usr/bin/env bash
# Install WordPress plugins from local zip packages (offline).
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PACKAGES_DIR="${PROJECT_ROOT}/deploy/offline/packages"
COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-luxstage}"
ENV_FILE="${PROJECT_ROOT}/.env"

if [[ -f "${ENV_FILE}" ]]; then
  set -a
  # shellcheck disable=SC1090
  source "${ENV_FILE}"
  set +a
fi

wp_cli() {
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
    wp --allow-root "$@" --path=/var/www/html
}

install_zip() {
  local zip_name="$1"
  local zip_path="${PACKAGES_DIR}/${zip_name}"

  if [[ ! -f "${zip_path}" ]]; then
    echo "SKIP: ${zip_name} not found in ${PACKAGES_DIR}"
    return 0
  fi

  echo "Installing plugin from ${zip_name}..."
  wp_cli plugin install "/work/deploy/offline/packages/${zip_name}" --activate --force
}

# Required for Luxstage B2B baseline.
install_zip "advanced-custom-fields.zip"
install_zip "contact-form-7.zip"
install_zip "polylang.zip"
install_zip "seo-by-rank-math.zip"

# Optional licensed or extended plugins.
install_zip "fluentform.zip"
install_zip "elementor.zip"
install_zip "elementor-pro.zip"
install_zip "webp-converter-for-media.zip"

echo "Offline plugin installation finished."
