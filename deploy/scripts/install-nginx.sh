#!/usr/bin/env bash
set -euo pipefail

if [[ $EUID -ne 0 ]]; then
  echo "Please run as root: sudo bash deploy/scripts/install-nginx.sh"
  exit 1
fi

dnf -y install nginx certbot python3-certbot-nginx
systemctl enable nginx
systemctl start nginx

install -D -m 644 deploy/nginx/luxstage.conf /etc/nginx/conf.d/luxstage.conf
nginx -t
systemctl reload nginx

echo "Nginx installed and site config deployed."
