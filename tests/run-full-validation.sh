#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_URL="${BASE_URL:-http://localhost:8080}"
COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-luxstage}"

cd "${PROJECT_ROOT}"

echo "[1/3] Starting docker services..."
docker compose up -d

echo "[2/3] Waiting for web endpoint..."
for i in {1..40}; do
  if curl -fsS "${BASE_URL}/wp-login.php" >/dev/null 2>&1; then
    break
  fi
  sleep 3
done

echo "[3/3] Running unified functional tests..."
python3 tests/run-functional-tests.py \
  --base-url "${BASE_URL}" \
  --project-name "${COMPOSE_PROJECT_NAME}"

echo "Done. Reports:"
echo " - tests/reports/functional-test-report.csv"
echo " - tests/reports/functional-test-report.json"
