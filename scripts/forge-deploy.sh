#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="${ROOT_DIR}/docker-compose.prod.yml"
STATE_DIR="${ROOT_DIR}/.deploy"
CURRENT_FILE="${STATE_DIR}/current.env"
PREVIOUS_FILE="${STATE_DIR}/previous.env"
ACTION="${1:-deploy}"

mkdir -p "${STATE_DIR}"

require_var() {
  local name="$1"

  if [[ -z "${!name:-}" ]]; then
    echo "Missing required environment variable: ${name}" >&2
    exit 1
  fi
}

compose() {
  docker compose -f "${COMPOSE_FILE}" "$@"
}

write_state() {
  local file="$1"
  cat >"${file}" <<EOF
GHCR_OWNER=${GHCR_OWNER}
API_IMAGE_TAG=${API_IMAGE_TAG}
FRONTEND_IMAGE_TAG=${FRONTEND_IMAGE_TAG}
APP_DOMAIN=${APP_DOMAIN}
EOF
}

load_state() {
  local file="$1"

  if [[ ! -f "${file}" ]]; then
    echo "State file not found: ${file}" >&2
    exit 1
  fi

  # shellcheck disable=SC1090
  source "${file}"
  export GHCR_OWNER API_IMAGE_TAG FRONTEND_IMAGE_TAG APP_DOMAIN
}

run_smoke() {
  compose exec -T api php artisan smoke:test
}

deploy() {
  require_var GHCR_OWNER
  require_var API_IMAGE_TAG
  require_var FRONTEND_IMAGE_TAG
  require_var APP_DOMAIN
  require_var GHCR_TOKEN

  if [[ -f "${CURRENT_FILE}" ]]; then
    cp "${CURRENT_FILE}" "${PREVIOUS_FILE}"
  fi

  write_state "${CURRENT_FILE}"

  git fetch --all --prune
  git pull --ff-only origin "${GITHUB_REF_NAME:-main}"

  echo "${GHCR_TOKEN}" | docker login ghcr.io -u "${GHCR_OWNER}" --password-stdin

  compose pull api queue-worker scheduler nginx
  compose up -d postgres redis
  compose up -d --scale api=2 api
  compose run --rm api composer install --no-dev --optimize-autoloader
  compose run --rm api php artisan migrate --force
  (
    cd "${ROOT_DIR}/frontend"
    corepack enable
    corepack prepare pnpm@10 --activate
    pnpm install --frozen-lockfile
    pnpm build
  )
  compose run --rm api php artisan config:cache
  compose run --rm api php artisan route:cache
  compose run --rm api php artisan view:cache
  compose up -d queue-worker scheduler nginx
  compose exec -T api php artisan queue:restart
  run_smoke
  curl -fsS "https://${APP_DOMAIN}/api/v1/health" >/dev/null
  compose up -d --scale api=1 api
}

rollback() {
  load_state "${PREVIOUS_FILE}"
  require_var GHCR_TOKEN
  echo "${GHCR_TOKEN}" | docker login ghcr.io -u "${GHCR_OWNER}" --password-stdin
  write_state "${CURRENT_FILE}"
  compose pull api queue-worker scheduler nginx
  compose up -d api queue-worker scheduler nginx
  run_smoke
}

case "${ACTION}" in
  deploy)
    if ! deploy; then
      echo "Deploy failed, attempting rollback..." >&2
      rollback
      exit 1
    fi
    ;;
  rollback)
    rollback
    ;;
  *)
    echo "Usage: $0 [deploy|rollback]" >&2
    exit 1
    ;;
esac
