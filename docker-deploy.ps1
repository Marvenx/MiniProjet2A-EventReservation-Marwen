param(
    [ValidateSet("init", "start")]
    [string]$Mode = "start"
)

$ErrorActionPreference = "Stop"

Write-Host "Event Reservation - Docker Deployment ($Mode mode)" -ForegroundColor Cyan

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "Docker not found. Please install Docker Desktop." -ForegroundColor Red
    exit 1
}

if (-not (Test-Path "config/jwt/private.pem")) {
    Write-Host "Generating JWT keys..." -ForegroundColor Yellow
    openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -aes256 -pass pass:your-secret-passphrase-change-in-production -out config/jwt/private.pem
    openssl pkey -in config/jwt/private.pem -passin pass:your-secret-passphrase-change-in-production -pubout -out config/jwt/public.pem
}

Write-Host "Starting Docker containers..." -ForegroundColor Yellow
if ($Mode -eq "init") {
    docker compose --env-file .env.docker up -d --build
} else {
    docker compose --env-file .env.docker up -d
}

if ($LASTEXITCODE -ne 0) {
    Write-Host "Docker startup failed." -ForegroundColor Red
    exit 1
}

if ($Mode -eq "init") {
    Write-Host "Waiting for database..." -ForegroundColor Yellow
    Start-Sleep -Seconds 10

    Write-Host "Running migrations..." -ForegroundColor Yellow
    docker compose --env-file .env.docker exec -T php php bin/console doctrine:migrations:migrate --no-interaction

    Write-Host "Loading fixtures..." -ForegroundColor Yellow
    docker compose --env-file .env.docker exec -T php php bin/console doctrine:fixtures:load --no-interaction

    Write-Host "Initialization complete." -ForegroundColor Green
} else {
    Write-Host "Startup complete (no migrations or fixtures)." -ForegroundColor Green
}

Write-Host "Application: http://localhost:8080" -ForegroundColor Cyan
Write-Host "Mailpit UI: http://localhost:8026" -ForegroundColor Cyan
Write-Host "PostgreSQL host access: localhost:5435" -ForegroundColor Cyan
Write-Host "Use '.\\docker-deploy.ps1 init' on a fresh machine." -ForegroundColor Yellow
