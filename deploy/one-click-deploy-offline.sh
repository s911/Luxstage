#!/usr/bin/env bash
# Luxstage B2B offline one-click deployment (air-gapped server).
# Prerequisites: Docker + Compose pre-installed, offline bundle prepared on a connected PC.
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.prod.yml"
ENV_FILE="${PROJECT_ROOT}/.env"
CREDENTIALS_FILE="${PROJECT_ROOT}/deploy-credentials.txt"
OFFLINE_DIR="${PROJECT_ROOT}/deploy/offline"
PACKAGES_DIR="${OFFLINE_DIR}/packages"
IMAGES_DIR="${OFFLINE_DIR}/images"

DOMAIN=""
EMAIL=""
WP_ADMIN_USER="luxstage_admin"
WP_ADMIN_EMAIL=""
WP_TITLE="Luxstage B2B"
ENABLE_NGINX=0
INSTALL_SAMPLE_DATA=0
SKIP_IMAGE_LOAD=0

usage() {
  cat <<'EOF'
Luxstage offline one-click deployment

Usage:
  sudo bash deploy/one-click-deploy-offline.sh --domain example.com --email admin@example.com [options]

Options:
  --domain DOMAIN        Site domain or server IP used in WP_URL
  --email EMAIL          WordPress admin email
  --admin-user USER      WordPress admin username (default: luxstage_admin)
  --title TITLE          Site title (default: Luxstage B2B)
  --with-nginx           Configure local Nginx reverse proxy on port 80 (HTTP only)
  --seed-demo-data       Import demo products/catalogs/applications/inquiries
  --skip-image-load      Skip docker load when images are already imported
  -h, --help             Show help

Before running on an offline server:
  1) On a PC with internet, run: powershell -File deploy/offline/prepare-bundle.ps1
     (downloads WordPress + plugin zips only; Docker images prepared separately on server)
  2) Copy the whole project folder to the server
  3) Manually install Docker Engine + Compose plugin on the server
  4) See deploy/offline/ARTIFACTS.md for the full artifact checklist
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --domain)
      DOMAIN="${2:-}"
      shift 2
      ;;
    --email)
      EMAIL="${2:-}"
      shift 2
      ;;
    --admin-user)
      WP_ADMIN_USER="${2:-}"
      shift 2
      ;;
    --title)
      WP_TITLE="${2:-}"
      shift 2
      ;;
    --with-nginx)
      ENABLE_NGINX=1
      shift
      ;;
    --seed-demo-data)
      INSTALL_SAMPLE_DATA=1
      shift
      ;;
    --skip-image-load)
      SKIP_IMAGE_LOAD=1
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown argument: $1"
      usage
      exit 1
      ;;
  esac
done

if [[ $EUID -ne 0 ]]; then
  echo "Please run with sudo/root."
  exit 1
fi

if [[ -z "${DOMAIN}" || -z "${EMAIL}" ]]; then
  echo "Both --domain and --email are required."
  usage
  exit 1
fi

WP_ADMIN_EMAIL="${EMAIL}"
WP_URL="http://${DOMAIN}"

log() {
  echo
  echo "==> $*"
}

random_secret() {
  if command -v openssl >/dev/null 2>&1; then
    openssl rand -base64 32 | tr -d '\n'
  else
    date +%s%N | sha256sum | awk '{print $1}'
  fi
}

shell_quote() {
  local value="${1:-}"
  printf "'%s'" "${value//\'/\'\\\'\'}"
}

check_prerequisites() {
  log "Checking host prerequisites"
  local missing=0

  for cmd in docker unzip; do
    if ! command -v "${cmd}" >/dev/null 2>&1; then
      echo "ERROR: missing command: ${cmd}"
      missing=1
    fi
  done

  if ! docker compose version >/dev/null 2>&1; then
    echo "ERROR: docker compose plugin is required."
    missing=1
  fi

  if [[ "${ENABLE_NGINX}" -eq 1 ]] && ! command -v nginx >/dev/null 2>&1; then
    echo "ERROR: nginx is required when --with-nginx is set. Install nginx RPM/DEB manually."
    missing=1
  fi

  if [[ "${missing}" -ne 0 ]]; then
    echo "Install missing host packages manually. See deploy/offline/ARTIFACTS.md"
    exit 1
  fi
}

verify_offline_artifacts() {
  log "Verifying offline packages"
  local missing=0

  for required in \
    "${PACKAGES_DIR}/advanced-custom-fields.zip" \
    "${PACKAGES_DIR}/contact-form-7.zip" \
    "${PACKAGES_DIR}/polylang.zip" \
    "${PACKAGES_DIR}/seo-by-rank-math.zip" \
    ; do
    if [[ ! -f "${required}" ]]; then
      echo "MISSING: ${required}"
      missing=1
    fi
  done

  if [[ ! -f "${PACKAGES_DIR}/wordpress-6.6.2.zip" && ! -f "${PACKAGES_DIR}/wordpress-6.8.zip" ]]; then
    echo "MISSING: WordPress core zip (wordpress-6.6.2.zip or wordpress-6.8.zip)"
    missing=1
  fi

  if [[ "${missing}" -ne 0 ]]; then
    echo
    echo "Offline packages incomplete. On a connected PC run:"
    echo "  powershell -File deploy/offline/prepare-bundle.ps1"
    echo "  # or: bash deploy/offline/prepare-bundle.sh"
    echo "Then copy deploy/offline/packages to this server."
    echo "Details: deploy/offline/ARTIFACTS.md"
    exit 1
  fi
}

docker_image_present() {
  docker image inspect "$1" >/dev/null 2>&1
}

ensure_docker_images() {
  local wordpress_image="wordpress:6.6.2-php8.3-apache"
  local required_images=(
    "${wordpress_image}"
    "mysql:8.0"
    "wordpress:cli"
  )
  local tar_files=(
    "${IMAGES_DIR}/wordpress.tar"
    "${IMAGES_DIR}/mysql-8.0.tar"
    "${IMAGES_DIR}/wordpress-cli.tar"
  )
  local has_all_tars=1
  local tar_file

  for tar_file in "${tar_files[@]}"; do
    if [[ ! -f "${tar_file}" ]]; then
      has_all_tars=0
      break
    fi
  done

  if [[ "${SKIP_IMAGE_LOAD}" -eq 1 ]]; then
    log "Skipping docker image load (--skip-image-load)"
    local image
    for image in "${required_images[@]}"; do
      if ! docker_image_present "${image}"; then
        echo "ERROR: Docker image not found locally: ${image}"
        echo "Load images from deploy/offline/images/*.tar or remove --skip-image-load when tars are present."
        exit 1
      fi
    done
    return
  fi

  if [[ "${has_all_tars}" -eq 1 ]]; then
    log "Loading Docker images from offline archives"
    bash "${OFFLINE_DIR}/load-images.sh"
    return
  fi

  local image
  local missing_images=()
  for image in "${required_images[@]}"; do
    if ! docker_image_present "${image}"; then
      missing_images+=("${image}")
    fi
  done

  if [[ ${#missing_images[@]} -eq 0 ]]; then
    log "Docker images already present locally (no offline tar archives)"
    return
  fi

  echo "ERROR: Docker images missing and no offline tar archives found."
  echo "Missing images:"
  printf '  - %s\n' "${missing_images[@]}"
  echo
  echo "On the server (if it has Docker registry access), run:"
  echo "  docker pull ${wordpress_image}"
  echo "  docker pull mysql:8.0"
  echo "  docker pull wordpress:cli"
  echo
  echo "Or copy deploy/offline/images/*.tar from a machine that ran prepare-bundle with --include-docker."
  echo "If images are already loaded, re-run with --skip-image-load."
  exit 1
}

create_env_file() {
  if [[ -f "${ENV_FILE}" ]]; then
    log ".env already exists, keeping existing credentials"
    set -a
    # shellcheck disable=SC1090
    source "${ENV_FILE}"
    set +a
    return
  fi

  log "Creating production .env with generated secrets"
  DB_NAME="luxstage_b2b"
  DB_USER="luxstage_user"
  DB_PASSWORD="$(random_secret)"
  DB_ROOT_PASSWORD="$(random_secret)"
  WP_ADMIN_PASSWORD="$(random_secret)"

  WP_TITLE_QUOTED="$(shell_quote "${WP_TITLE}")"
  LUXSTAGE_MAIL_FROM_NAME_QUOTED="$(shell_quote "Luxstage")"

  cat > "${ENV_FILE}" <<EOF
COMPOSE_PROJECT_NAME=luxstage
WEB_PORT=8080
DOMAIN=${DOMAIN}
WP_URL=${WP_URL}
WP_TITLE=${WP_TITLE_QUOTED}
WP_ADMIN_USER=${WP_ADMIN_USER}
WP_ADMIN_PASSWORD=${WP_ADMIN_PASSWORD}
WP_ADMIN_EMAIL=${WP_ADMIN_EMAIL}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
LUXSTAGE_MAIL_MODE=smtp
LUXSTAGE_SMTP_HOST=${LUXSTAGE_SMTP_HOST:-}
LUXSTAGE_SMTP_PORT=${LUXSTAGE_SMTP_PORT:-587}
LUXSTAGE_SMTP_SECURE=${LUXSTAGE_SMTP_SECURE:-tls}
LUXSTAGE_SMTP_USER=${LUXSTAGE_SMTP_USER:-}
LUXSTAGE_SMTP_PASSWORD=${LUXSTAGE_SMTP_PASSWORD:-}
LUXSTAGE_MAIL_FROM=${LUXSTAGE_MAIL_FROM:-no-reply@${DOMAIN}}
LUXSTAGE_MAIL_FROM_NAME=${LUXSTAGE_MAIL_FROM_NAME_QUOTED}
LUXSTAGE_WHATSAPP_NUMBER=${LUXSTAGE_WHATSAPP_NUMBER:-}
EOF

  chmod 600 "${ENV_FILE}"
  cat > "${CREDENTIALS_FILE}" <<EOF
Luxstage offline deployment credentials
Generated at: $(date -u +"%Y-%m-%dT%H:%M:%SZ")

Site URL: ${WP_URL}
WordPress admin user: ${WP_ADMIN_USER}
WordPress admin password: ${WP_ADMIN_PASSWORD}
WordPress admin email: ${WP_ADMIN_EMAIL}

Database name: ${DB_NAME}
Database user: ${DB_USER}
Database password: ${DB_PASSWORD}
Database root password: ${DB_ROOT_PASSWORD}
EOF
  chmod 600 "${CREDENTIALS_FILE}"

  set -a
  # shellcheck disable=SC1090
  source "${ENV_FILE}"
  set +a
}

compose() {
  docker compose --env-file "${ENV_FILE}" -f "${COMPOSE_FILE}" "$@"
}

wp_cli() {
  docker run --rm \
    --user 0:0 \
    --network "${COMPOSE_PROJECT_NAME:-luxstage}_default" \
    --env WORDPRESS_DB_HOST="db:3306" \
    --env WORDPRESS_DB_USER="${DB_USER}" \
    --env WORDPRESS_DB_PASSWORD="${DB_PASSWORD}" \
    --env WORDPRESS_DB_NAME="${DB_NAME}" \
    --env WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL}" \
    --env LUXSTAGE_MAIL_FROM="${LUXSTAGE_MAIL_FROM:-no-reply@${DOMAIN}}" \
    --env LUXSTAGE_MAIL_FROM_NAME="${LUXSTAGE_MAIL_FROM_NAME:-Luxstage}" \
    -v "${PROJECT_ROOT}:/work" \
    -v "${PROJECT_ROOT}/src:/var/www/html" \
    wordpress:cli \
    wp --allow-root "$@" --path=/var/www/html
}

wait_for_stack() {
  log "Waiting for database health"
  for _ in $(seq 1 60); do
    if compose exec -T db mysqladmin ping -h localhost -uroot -p"${DB_ROOT_PASSWORD}" >/dev/null 2>&1; then
      return
    fi
    sleep 3
  done
  echo "Database did not become ready in time."
  compose logs db
  exit 1
}

prepare_wordpress() {
  log "Preparing WordPress core, plugins, theme, and site content"
  mkdir -p "${PROJECT_ROOT}/src/wp-content/uploads"
  chown -R 33:33 "${PROJECT_ROOT}/src/wp-content/uploads" 2>/dev/null || true

  bash "${PROJECT_ROOT}/deploy/scripts/ensure-htaccess.sh"
  bash "${PROJECT_ROOT}/deploy/scripts/extract-wordpress-core.sh"

  if [[ ! -f "${PROJECT_ROOT}/src/wp-config.php" ]]; then
    wp_cli config create \
      --dbname="${DB_NAME}" \
      --dbuser="${DB_USER}" \
      --dbpass="${DB_PASSWORD}" \
      --dbhost="db:3306" \
      --skip-check \
      --force
  fi

  if ! wp_cli core is-installed >/dev/null 2>&1; then
    wp_cli core install \
      --url="${WP_URL}" \
      --title="${WP_TITLE}" \
      --admin_user="${WP_ADMIN_USER}" \
      --admin_password="${WP_ADMIN_PASSWORD}" \
      --admin_email="${WP_ADMIN_EMAIL}" \
      --skip-email
  fi

  bash "${PROJECT_ROOT}/deploy/scripts/install-plugins-offline.sh"
  bash "${PROJECT_ROOT}/deploy/scripts/bootstrap-wordpress-site.sh"

  if [[ "${INSTALL_SAMPLE_DATA}" -eq 1 ]]; then
    bash "${PROJECT_ROOT}/deploy/scripts/seed-demo-products.sh"
  else
    wp_cli rewrite flush --hard
  fi
}

configure_nginx() {
  if [[ "${ENABLE_NGINX}" -ne 1 ]]; then
    log "Skipping Nginx configuration (site available on http://SERVER_IP:${WEB_PORT:-8080})"
    return
  fi

  log "Configuring Nginx HTTP reverse proxy"
  cat > /etc/nginx/conf.d/luxstage.conf <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};

    client_max_body_size 64m;

    location / {
        proxy_pass http://127.0.0.1:${WEB_PORT:-8080};
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 120s;
    }
}
EOF

  nginx -t
  systemctl enable nginx
  systemctl restart nginx
}

install_systemd_service() {
  log "Installing systemd service"
  cat > /etc/systemd/system/luxstage-docker.service <<EOF
[Unit]
Description=Luxstage WordPress Docker Stack
Requires=docker.service
After=docker.service network-online.target
Wants=network-online.target

[Service]
Type=oneshot
WorkingDirectory=${PROJECT_ROOT}
RemainAfterExit=yes
ExecStart=/usr/bin/docker compose --env-file ${ENV_FILE} -f ${COMPOSE_FILE} up -d
ExecStop=/usr/bin/docker compose --env-file ${ENV_FILE} -f ${COMPOSE_FILE} down
TimeoutStartSec=0

[Install]
WantedBy=multi-user.target
EOF

  systemctl daemon-reload
  systemctl enable luxstage-docker.service
}

verify_site() {
  log "Verifying deployment"
  curl -fsI "http://127.0.0.1:${WEB_PORT:-8080}/" >/dev/null
  curl -fsI "http://127.0.0.1:${WEB_PORT:-8080}/wp-json/" >/dev/null
  curl -fsI "http://127.0.0.1:${WEB_PORT:-8080}/products/" >/dev/null
}

main() {
  check_prerequisites
  verify_offline_artifacts
  create_env_file
  ensure_docker_images

  log "Starting Docker stack (offline, no pull)"
  compose up -d --pull never
  wait_for_stack
  prepare_wordpress
  configure_nginx
  install_systemd_service
  verify_site

  log "Offline deployment complete"
  echo "Site: ${WP_URL}"
  if [[ "${ENABLE_NGINX}" -eq 1 ]]; then
    echo "Public entry: http://${DOMAIN}/"
  else
    echo "Direct entry: http://${DOMAIN}:${WEB_PORT:-8080}/"
  fi
  echo "Admin: ${WP_URL}/wp-admin/"
  echo "Credentials: ${CREDENTIALS_FILE}"
  echo "Keep .env private. Do not commit credentials."
}

main "$@"
