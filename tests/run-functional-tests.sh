#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_URL="${BASE_URL:-http://localhost:8080}"
COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-luxstage}"

cd "${PROJECT_ROOT}"

python3 tests/run-functional-tests.py \
  --base-url "${BASE_URL}" \
  --project-name "${COMPOSE_PROJECT_NAME}"
