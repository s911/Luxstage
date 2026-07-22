#!/usr/bin/env bash
# Prepare offline deployment bundle on a PC WITH internet access.
# Downloads WordPress core + plugin zips into deploy/offline/packages/.
# Docker images are skipped by default; use --include-docker on a machine with Docker.
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
OFFLINE_DIR="${PROJECT_ROOT}/deploy/offline"
PACKAGES_DIR="${OFFLINE_DIR}/packages"
IMAGES_DIR="${OFFLINE_DIR}/images"
WP_CORE_VERSION="${WP_CORE_VERSION:-6.6.2}"
WORDPRESS_IMAGE="${WORDPRESS_IMAGE:-wordpress:6.6.2-php8.3-apache}"
INCLUDE_DOCKER=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --include-docker)
      INCLUDE_DOCKER=1
      shift
      ;;
    -h|--help)
      echo "Usage: bash deploy/offline/prepare-bundle.sh [--include-docker]"
      echo "  --include-docker  Also pull/save Docker images (requires Docker on this PC)"
      exit 0
      ;;
    *)
      echo "Unknown argument: $1"
      exit 1
      ;;
  esac
done

mkdir -p "${PACKAGES_DIR}" "${IMAGES_DIR}"

log() {
  echo
  echo "==> $*"
}

require_cmd() {
  local cmd="$1"
  if ! command -v "${cmd}" >/dev/null 2>&1; then
    echo "ERROR: ${cmd} is required."
    exit 1
  fi
}

download_plugin_zip() {
  local slug="$1"
  local version="${2:-}"
  local target="${PACKAGES_DIR}/${slug}.zip"

  if [[ -f "${target}" ]]; then
    echo "SKIP: ${target} already exists"
    return 0
  fi

  local url="https://downloads.wordpress.org/plugin/${slug}"
  if [[ -n "${version}" ]]; then
    url="${url}.${version}.zip"
  else
    url="${url}.latest-stable.zip"
  fi

  echo "Downloading ${url}"
  curl -fL "${url}" -o "${target}"
}

log "Checking prerequisites"
require_cmd curl
require_cmd unzip

if [[ "${INCLUDE_DOCKER}" -eq 1 ]]; then
  require_cmd docker
  log "Pulling and saving Docker images"
  docker pull "${WORDPRESS_IMAGE}"
  docker pull mysql:8.0
  docker pull wordpress:cli

  docker save -o "${IMAGES_DIR}/wordpress.tar" "${WORDPRESS_IMAGE}"
  docker save -o "${IMAGES_DIR}/mysql-8.0.tar" mysql:8.0
  docker save -o "${IMAGES_DIR}/wordpress-cli.tar" wordpress:cli
else
  log "Skipping Docker image download (default)"
  echo "Prepare images on the deployment server instead. See deploy/offline/ARTIFACTS.md"
fi

log "Downloading WordPress core"
if [[ ! -f "${PACKAGES_DIR}/wordpress-${WP_CORE_VERSION}.zip" ]]; then
  curl -fL "https://wordpress.org/wordpress-${WP_CORE_VERSION}.zip" \
    -o "${PACKAGES_DIR}/wordpress-${WP_CORE_VERSION}.zip"
fi

log "Downloading required plugins"
download_plugin_zip "advanced-custom-fields"
download_plugin_zip "contact-form-7" "6.1.6"
download_plugin_zip "polylang"
download_plugin_zip "seo-by-rank-math"

log "Downloading optional plugins"
download_plugin_zip "fluentform" || true
download_plugin_zip "elementor" || true
download_plugin_zip "webp-converter-for-media" || true

if [[ -f "${PROJECT_ROOT}/contact-form-7.6.1.6.zip" && ! -f "${PACKAGES_DIR}/contact-form-7.zip" ]]; then
  cp "${PROJECT_ROOT}/contact-form-7.6.1.6.zip" "${PACKAGES_DIR}/contact-form-7.zip"
fi

cat > "${OFFLINE_DIR}/bundle-manifest.txt" <<EOF
Generated: $(date -u +"%Y-%m-%dT%H:%M:%SZ")
WordPress core: wordpress-${WP_CORE_VERSION}.zip
Docker images included: ${INCLUDE_DOCKER}
Images:
$(ls -1 "${IMAGES_DIR}" 2>/dev/null || echo "(skipped - prepare on server)")
Packages:
$(ls -1 "${PACKAGES_DIR}")
EOF

log "Bundle ready in ${OFFLINE_DIR}"
echo "Next steps:"
echo "1) Copy entire Luxstage project folder (including deploy/offline/images and packages) to offline server"
echo "2) On offline server install Docker Engine + Compose plugin manually"
echo "3) Run: sudo bash deploy/one-click-deploy-offline.sh --domain YOUR_DOMAIN --email admin@example.com --seed-demo-data"
