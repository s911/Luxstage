#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.prod.yml"
ENV_FILE="${PROJECT_ROOT}/.env"
CREDENTIALS_FILE="${PROJECT_ROOT}/deploy-credentials.txt"

DOMAIN=""
EMAIL=""
WP_ADMIN_USER="luxstage_admin"
WP_ADMIN_EMAIL=""
WP_TITLE="Luxstage B2B"
ENABLE_SSL=1
INSTALL_SAMPLE_DATA=0

usage() {
  cat <<'EOF'
Usage:
  sudo bash deploy/one-click-deploy.sh --domain example.com --email admin@example.com [options]

Options:
  --domain DOMAIN          Public domain for the site, e.g. luxstage.com
  --email EMAIL            Email used for Let's Encrypt and WordPress admin
  --admin-user USER        WordPress admin username (default: luxstage_admin)
  --title TITLE            WordPress site title (default: Luxstage B2B)
  --no-ssl                 Configure HTTP reverse proxy only
  --seed-demo-data         Import demo products after installation
  -h, --help               Show this help

Environment overrides:
  LUXSTAGE_WHATSAPP_NUMBER, LUXSTAGE_SMTP_HOST, LUXSTAGE_SMTP_USER,
  LUXSTAGE_SMTP_PASSWORD, LUXSTAGE_SMTP_PORT, LUXSTAGE_SMTP_SECURE,
  LUXSTAGE_MAIL_FROM
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
    --no-ssl)
      ENABLE_SSL=0
      shift
      ;;
    --seed-demo-data)
      INSTALL_SAMPLE_DATA=1
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
  echo "Please run with sudo/root because the script installs system packages and configures Nginx."
  exit 1
fi

if [[ -z "${DOMAIN}" || -z "${EMAIL}" ]]; then
  echo "Both --domain and --email are required."
  usage
  exit 1
fi

WP_ADMIN_EMAIL="${EMAIL}"
WP_URL="http://${DOMAIN}"
if [[ "${ENABLE_SSL}" -eq 1 ]]; then
  WP_URL="https://${DOMAIN}"
fi

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

install_packages() {
  log "Installing host dependencies"
  if command -v dnf >/dev/null 2>&1; then
    dnf -y update
    dnf -y install git curl ca-certificates openssl nginx certbot python3-certbot-nginx
  elif command -v apt-get >/dev/null 2>&1; then
    apt-get update
    DEBIAN_FRONTEND=noninteractive apt-get -y install git curl ca-certificates openssl nginx certbot python3-certbot-nginx
  else
    echo "Unsupported OS: dnf or apt-get is required."
    exit 1
  fi
}

install_docker() {
  if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    log "Docker and Compose plugin already installed"
  else
    log "Installing Docker Engine and Compose plugin"
    curl -fsSL https://get.docker.com | sh
  fi

  systemctl enable docker
  systemctl start docker
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
Luxstage deployment credentials
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
  log "Preparing WordPress core, config, plugins, and theme"
  mkdir -p "${PROJECT_ROOT}/src/wp-content/uploads"
  chown -R 33:33 "${PROJECT_ROOT}/src/wp-content/uploads" || true

  if [[ ! -f "${PROJECT_ROOT}/src/wp-settings.php" ]]; then
    wp_cli core download --version=6.6.2 --force
  fi

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

  wp_cli option update home "${WP_URL}"
  wp_cli option update siteurl "${WP_URL}"
  wp_cli option update blog_public 1
  wp_cli rewrite structure '/%postname%/' --hard

  wp_cli plugin install advanced-custom-fields --activate
  wp_cli plugin install contact-form-7 --activate || true
  wp_cli plugin install polylang --activate || true
  wp_cli plugin install seo-by-rank-math --activate || true

  wp_cli theme activate luxstage
  wp_cli rewrite flush --hard

  if [[ "${INSTALL_SAMPLE_DATA}" -eq 1 && -f "${PROJECT_ROOT}/deploy/scripts/seed-demo-products.php" ]]; then
    docker run --rm \
      --user 0:0 \
      --network "${COMPOSE_PROJECT_NAME:-luxstage}_default" \
      -v "${PROJECT_ROOT}:/work" \
      -v "${PROJECT_ROOT}/src:/var/www/html" \
      wordpress:cli \
      wp --allow-root eval-file /work/deploy/scripts/seed-demo-products.php --path=/var/www/html || true
  fi
}

configure_nginx() {
  log "Configuring Nginx reverse proxy"
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

  if [[ "${ENABLE_SSL}" -eq 1 ]]; then
    log "Requesting Let's Encrypt certificate"
    certbot --nginx \
      --non-interactive \
      --agree-tos \
      -m "${EMAIL}" \
      -d "${DOMAIN}" \
      -d "www.${DOMAIN}" \
      --redirect

    systemctl enable certbot-renew.timer || true
    systemctl start certbot-renew.timer || true
  fi
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

main() {
  install_packages
  install_docker
  create_env_file

  log "Starting Docker stack"
  compose pull
  compose up -d
  wait_for_stack
  prepare_wordpress
  configure_nginx
  install_systemd_service

  log "Deployment complete"
  echo "Site: ${WP_URL}"
  echo "Admin: ${WP_URL}/wp-admin/"
  echo "Credentials saved to: ${CREDENTIALS_FILE}"
  echo "Important: keep .env and deploy-credentials.txt private; do not commit them."
}

main "$@"
