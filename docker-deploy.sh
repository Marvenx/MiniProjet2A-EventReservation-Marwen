#!/bin/bash
set -e

echo "🐳 Event Reservation - Docker Deployment"

# Check Docker
if ! command -v docker &> /dev/null; then
    echo "❌ Docker not found. Please install Docker."
    exit 1
fi

# Ensure JWT keys exist
if [ ! -f "config/jwt/private.pem" ]; then
    echo "🔑 Generating JWT keys..."
    export GIT_AUTHOR_DATE="2026-03-29T12:00:00"
    export GIT_COMMITTER_DATE="2026-03-29T12:00:00"
    openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 \
      -aes256 -pass pass:your-secret-passphrase-change-in-production \
      -out config/jwt/private.pem
    openssl pkey -in config/jwt/private.pem \
      -passin pass:your-secret-passphrase-change-in-production \
      -pubout -out config/jwt/public.pem
fi

# Start Docker
echo "🚀 Starting Docker containers..."
docker compose up -d --build

# Wait for database
echo "⏳ Waiting for database..."
sleep 10

# Run migrations
echo "🔄 Running migrations..."
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction

# Load fixtures
echo "📦 Loading fixtures..."
docker compose exec -T php php bin/console doctrine:fixtures:load --no-interaction

echo "✅ Docker deployment complete!"
echo "🌐 Application: http://localhost:8080"
echo "📧 Mailpit: http://localhost:8025"
echo "💾 Database: localhost:5432"