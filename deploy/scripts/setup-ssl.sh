#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${1:-luxstage.com}"
EMAIL="${2:-admin@luxstage.com}"

if [[ $EUID -ne 0 ]]; then
  echo "Please run as root: sudo bash deploy/scripts/setup-ssl.sh <domain> <email>"
  exit 1
fi

if ! command -v certbot >/dev/null 2>&1; then
  dnf -y install certbot python3-certbot-nginx
fi

certbot --nginx \
  --non-interactive \
  --agree-tos \
  -m "${EMAIL}" \
  -d "${DOMAIN}" \
  -d "www.${DOMAIN}" \
  --redirect

systemctl enable certbot-renew.timer || true
systemctl start certbot-renew.timer || true

echo "SSL setup complete for ${DOMAIN}."
