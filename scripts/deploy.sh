#!/usr/bin/env sh
set -eu

APP_DIR="${APP_DIR:-/var/www/ketsia-shop}"
COMPOSE_FILE="${COMPOSE_FILE:-compose.yaml}"

echo "Deploying Ketsia Shop from ${APP_DIR}"
cd "$APP_DIR"

git pull --ff-only
docker compose -f "$COMPOSE_FILE" pull || true
docker compose -f "$COMPOSE_FILE" build
docker compose -f "$COMPOSE_FILE" up -d --remove-orphans
docker compose -f "$COMPOSE_FILE" exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
docker compose -f "$COMPOSE_FILE" exec -T backend php bin/console cache:clear --env=prod || true

echo "Deployment finished."
