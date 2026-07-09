#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${1:-/opt/luxstage}"
COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.prod.yml"
ENV_FILE="${PROJECT_ROOT}/.env"

if [[ $EUID -ne 0 ]]; then
  echo "Please run as root: sudo bash deploy/scripts/install-systemd.sh /opt/luxstage"
  exit 1
fi

install -D -m 644 deploy/systemd/luxstage-docker.service /etc/systemd/system/luxstage-docker.service
sed -i "s|^WorkingDirectory=.*|WorkingDirectory=${PROJECT_ROOT}|g" /etc/systemd/system/luxstage-docker.service
sed -i "s|^ExecStart=.*|ExecStart=/usr/bin/docker compose --env-file ${ENV_FILE} -f ${COMPOSE_FILE} up -d|g" /etc/systemd/system/luxstage-docker.service
sed -i "s|^ExecStop=.*|ExecStop=/usr/bin/docker compose --env-file ${ENV_FILE} -f ${COMPOSE_FILE} down|g" /etc/systemd/system/luxstage-docker.service

systemctl daemon-reload
systemctl enable luxstage-docker.service
systemctl restart luxstage-docker.service

echo "Systemd service installed and started."
