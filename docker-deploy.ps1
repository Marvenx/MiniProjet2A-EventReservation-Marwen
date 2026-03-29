$ErrorActionPreference = "Stop"

Write-Host "🐳 Event Reservation - Docker Deployment" -ForegroundColor Cyan

# Check Docker
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Docker not found. Please install Docker Desktop." -ForegroundColor Red
    exit 1
}

# Ensure JWT keys exist
if (-not (Test-Path "config/jwt/private.pem")) {
    Write-Host "🔑 Generating JWT keys..." -ForegroundColor Yellow
    $env:GIT_AUTHOR_DATE = "2026-03-29T12:00:00"
    $env:GIT_COMMITTER_DATE = "2026-03-29T12:00:00"
    openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -aes256 -pass pass:your-secret-passphrase-change-in-production -out config/jwt/private.pem
    openssl pkey -in config/jwt/private.pem -passin pass:your-secret-passphrase-change-in-production -pubout -out config/jwt/public.pem
}

# Start Docker
Write-Host "🚀 Starting Docker containers..." -ForegroundColor Yellow
docker compose up -d --build

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Docker startup failed" -ForegroundColor Red
    exit 1
}

# Wait for database
Write-Host "⏳ Waiting for database..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Run migrations
Write-Host "🔄 Running migrations..." -ForegroundColor Yellow
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction

# Load fixtures
Write-Host "📦 Loading fixtures..." -ForegroundColor Yellow
docker compose exec -T php php bin/console doctrine:fixtures:load --no-interaction

Write-Host "✅ Docker deployment complete!" -ForegroundColor Green
Write-Host "🌐 Application: http://localhost:8080" -ForegroundColor Cyan
Write-Host "📧 Mailpit: http://localhost:8025" -ForegroundColor Cyan
Write-Host "💾 Database: localhost:5432" -ForegroundColor Cyan