#!/bin/bash
set -e

MODE="${1:-start}"
if [ "$MODE" != "init" ] && [ "$MODE" != "start" ]; then
  echo "Usage: ./docker-deploy.sh [init|start]"
  exit 1
fi

echo "Event Reservation - Docker Deployment ($MODE mode)"

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker not found. Please install Docker."
    exit 1
fi

if [ ! -f "config/jwt/private.pem" ]; then
    echo "Generating JWT keys..."
    openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 \
      -aes256 -pass pass:your-secret-passphrase-change-in-production \
      -out config/jwt/private.pem
    openssl pkey -in config/jwt/private.pem \
      -passin pass:your-secret-passphrase-change-in-production \
      -pubout -out config/jwt/public.pem
fi

echo "Starting Docker containers..."
if [ "$MODE" = "init" ]; then
  docker compose --env-file .env.docker up -d --build
else
  docker compose --env-file .env.docker up -d
fi

if [ "$MODE" = "init" ]; then
  echo "Waiting for database..."
  sleep 10

  echo "Running migrations..."
  docker compose --env-file .env.docker exec -T php php bin/console doctrine:migrations:migrate --no-interaction

  echo "Loading fixtures..."
  docker compose --env-file .env.docker exec -T php php bin/console doctrine:fixtures:load --no-interaction

  echo "Initialization complete."
else
  echo "Startup complete (no migrations or fixtures)."
fi

echo "Application: http://localhost:8080"
echo "Mailpit UI: http://localhost:8026"
echo "PostgreSQL host access: localhost:5435"
echo "Use './docker-deploy.sh init' on a fresh machine."
