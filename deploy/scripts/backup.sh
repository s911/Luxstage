#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-/opt/luxstage}"
BACKUP_DIR="${BACKUP_DIR:-/opt/luxstage-backups}"
COMPOSE_FILE="${COMPOSE_FILE:-${PROJECT_ROOT}/docker-compose.prod.yml}"
STAMP="$(date +%Y%m%d-%H%M%S)"

mkdir -p "${BACKUP_DIR}/${STAMP}"

echo "Backing up code snapshot..."
tar --exclude='.git' -czf "${BACKUP_DIR}/${STAMP}/code.tar.gz" -C "${PROJECT_ROOT}" .

echo "Backing up database..."
DB_NAME="$(grep -E '^DB_NAME=' "${PROJECT_ROOT}/.env" | cut -d'=' -f2-)"
DB_USER="$(grep -E '^DB_USER=' "${PROJECT_ROOT}/.env" | cut -d'=' -f2-)"
DB_PASSWORD="$(grep -E '^DB_PASSWORD=' "${PROJECT_ROOT}/.env" | cut -d'=' -f2-)"

docker compose --env-file "${PROJECT_ROOT}/.env" -f "${COMPOSE_FILE}" exec -T db \
  mysqldump -u"${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" > "${BACKUP_DIR}/${STAMP}/db.sql"

echo "${STAMP}" > "${BACKUP_DIR}/latest.txt"
echo "Backup created at ${BACKUP_DIR}/${STAMP}"
