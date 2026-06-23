#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${1:-/opt/luxstage}"
CRON_EXPR="${2:-30 2 * * *}"

if [[ $EUID -ne 0 ]]; then
  echo "Please run as root: sudo bash deploy/scripts/install-backup-cron.sh /opt/luxstage '30 2 * * *'"
  exit 1
fi

CRON_FILE="/etc/cron.d/luxstage-backup"
echo "${CRON_EXPR} root cd ${PROJECT_ROOT} && PROJECT_ROOT=${PROJECT_ROOT} bash deploy/scripts/backup.sh >> /var/log/luxstage-backup.log 2>&1" > "${CRON_FILE}"
chmod 644 "${CRON_FILE}"

echo "Backup cron installed: ${CRON_EXPR}"
