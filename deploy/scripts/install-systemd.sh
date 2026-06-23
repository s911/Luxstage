#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${1:-/opt/luxstage}"

if [[ $EUID -ne 0 ]]; then
  echo "Please run as root: sudo bash deploy/scripts/install-systemd.sh /opt/luxstage"
  exit 1
fi

install -D -m 644 deploy/systemd/luxstage-docker.service /etc/systemd/system/luxstage-docker.service
sed -i "s|^WorkingDirectory=.*|WorkingDirectory=${PROJECT_ROOT}|g" /etc/systemd/system/luxstage-docker.service

systemctl daemon-reload
systemctl enable luxstage-docker.service
systemctl restart luxstage-docker.service

echo "Systemd service installed and started."
