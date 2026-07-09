#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-/opt/luxstage}"
BACKUP_DIR="${BACKUP_DIR:-/opt/luxstage-backups}"
COMPOSE_FILE="${COMPOSE_FILE:-${PROJECT_ROOT}/docker-compose.prod.yml}"
TARGET_STAMP="${1:-}"

if [[ -z "${TARGET_STAMP}" ]]; then
  if [[ ! -f "${BACKUP_DIR}/latest.txt" ]]; then
    echo "No backup stamp provided and latest.txt not found."
    exit 1
  fi
  TARGET_STAMP="$(cat "${BACKUP_DIR}/latest.txt")"
fi

SNAPSHOT_DIR="${BACKUP_DIR}/${TARGET_STAMP}"
CODE_TAR="${SNAPSHOT_DIR}/code.tar.gz"
DB_SQL="${SNAPSHOT_DIR}/db.sql"

if [[ ! -f "${CODE_TAR}" || ! -f "${DB_SQL}" ]]; then
  echo "Backup snapshot incomplete: ${SNAPSHOT_DIR}"
  exit 1
fi

echo "Stopping stack..."
docker compose --env-file "${PROJECT_ROOT}/.env" -f "${COMPOSE_FILE}" down

echo "Restoring code..."
find "${PROJECT_ROOT}" -mindepth 1 -maxdepth 1 \
  ! -name '.git' \
  ! -name 'luxstage-backups' \
  -exec rm -rf {} +
tar -xzf "${CODE_TAR}" -C "${PROJECT_ROOT}"

echo "Starting DB for restore..."
docker compose --env-file "${PROJECT_ROOT}/.env" -f "${COMPOSE_FILE}" up -d db
sleep 12

DB_NAME="$(grep -E '^DB_NAME=' "${PROJECT_ROOT}/.env" | cut -d'=' -f2-)"
DB_USER="$(grep -E '^DB_USER=' "${PROJECT_ROOT}/.env" | cut -d'=' -f2-)"
DB_PASSWORD="$(grep -E '^DB_PASSWORD=' "${PROJECT_ROOT}/.env" | cut -d'=' -f2-)"

echo "Restoring database..."
docker compose --env-file "${PROJECT_ROOT}/.env" -f "${COMPOSE_FILE}" exec -T db \
  mysql -u"${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" < "${DB_SQL}"

echo "Starting full stack..."
docker compose --env-file "${PROJECT_ROOT}/.env" -f "${COMPOSE_FILE}" up -d

echo "Rollback complete: ${TARGET_STAMP}"
