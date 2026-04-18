#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/opt/leadochat"
BRANCH="main"
COMPOSE_FILE="docker-compose.prod.yml"

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

echo "==> building containers"
docker compose -f "$COMPOSE_FILE" build app reverb

echo "==> starting postgres first"
docker compose -f "$COMPOSE_FILE" up -d postgres

echo "==> waiting for postgres"
for i in {1..30}; do
  if docker compose -f "$COMPOSE_FILE" exec -T postgres sh -lc 'pg_isready -U "$POSTGRES_USER" -d "$POSTGRES_DB"' >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

echo "==> starting app, reverb, nginx"
docker compose -f "$COMPOSE_FILE" up -d app reverb nginx

echo "==> installing php dependencies"
docker compose -f "$COMPOSE_FILE" exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader

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
