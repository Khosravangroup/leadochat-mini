#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/opt/leadochat"
BRANCH="${DEPLOY_BRANCH:-main}"
COMPOSE_FILE="docker-compose.prod.yml"
MIN_NODE_MAJOR=20

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "ERROR: required command '$1' is not installed on the server."
    exit 1
  fi
}

check_node_version() {
  require_command node
  require_command npm

  local node_version
  local node_major

  node_version="$(node -v | sed 's/^v//')"
  node_major="$(printf '%s' "$node_version" | cut -d. -f1)"

  if [ "${node_major}" -lt "${MIN_NODE_MAJOR}" ]; then
    echo "ERROR: Node.js ${MIN_NODE_MAJOR}+ is required. Current version: v${node_version}"
    exit 1
  fi

  echo "==> node version: v${node_version}"
  echo "==> npm version: $(npm -v)"
}

cd "$APP_DIR"

echo "==> fetching latest code"
git fetch --all
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"

if [ ! -f src/.env ]; then
  echo "ERROR: src/.env not found"
  echo "Create /opt/leadochat/src/.env on the server first, then run deploy again."
  exit 1
fi

ln -sfn src/.env .env

echo "==> checking frontend build requirements"
check_node_version

echo "==> installing frontend dependencies"
cd "$APP_DIR/src"
if [ -f package-lock.json ]; then
  npm ci
else
  npm install
fi

echo "==> building frontend assets"
npm run build

if [ ! -f "$APP_DIR/src/public/build/manifest.json" ]; then
  echo "ERROR: Vite build did not produce public/build/manifest.json"
  exit 1
fi

cd "$APP_DIR"

echo "==> building containers"
docker compose -f "$COMPOSE_FILE" build app reverb queue

echo "==> starting postgres first"
docker compose -f "$COMPOSE_FILE" up -d postgres

echo "==> waiting for postgres"
for i in {1..30}; do
  if docker compose -f "$COMPOSE_FILE" exec -T postgres sh -lc 'pg_isready -U "$POSTGRES_USER" -d "$POSTGRES_DB"' >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

echo "==> starting app, reverb, queue, nginx"
docker compose -f "$COMPOSE_FILE" up -d app reverb queue nginx

echo "==> installing php dependencies"
docker compose -f "$COMPOSE_FILE" exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader

echo "==> fixing laravel permissions"
docker compose -f "$COMPOSE_FILE" exec -T app sh -lc '
  chown -R www-data:www-data storage bootstrap/cache &&
  chmod -R 775 storage bootstrap/cache &&
  touch storage/logs/laravel.log &&
  chown www-data:www-data storage/logs/laravel.log &&
  chmod 664 storage/logs/laravel.log
'

echo "==> generating app key if missing"
if ! grep -q '^APP_KEY=base64:' src/.env; then
  docker compose -f "$COMPOSE_FILE" exec -T app php artisan key:generate --force
fi

echo "==> running database migrations"
docker compose -f "$COMPOSE_FILE" exec -T app php artisan migrate --force

echo "==> clearing and caching laravel"
docker compose -f "$COMPOSE_FILE" exec -T app php artisan optimize:clear
docker compose -f "$COMPOSE_FILE" exec -T app php artisan config:cache
docker compose -f "$COMPOSE_FILE" exec -T app php artisan route:cache
docker compose -f "$COMPOSE_FILE" exec -T app php artisan view:cache

echo "==> creating storage symlink if needed"
docker compose -f "$COMPOSE_FILE" exec -T app php artisan storage:link || true

echo "==> done"
docker compose -f "$COMPOSE_FILE" ps
