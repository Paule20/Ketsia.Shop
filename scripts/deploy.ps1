$ErrorActionPreference = "Stop"

$AppDir = $env:APP_DIR
if (-not $AppDir) {
    $AppDir = "C:\var\www\ketsia-shop"
}

$ComposeFile = $env:COMPOSE_FILE
if (-not $ComposeFile) {
    $ComposeFile = "compose.yaml"
}

Write-Host "Deploying Ketsia Shop from $AppDir"
Set-Location $AppDir

git pull --ff-only
docker compose -f $ComposeFile pull
docker compose -f $ComposeFile build
docker compose -f $ComposeFile up -d --remove-orphans
docker compose -f $ComposeFile exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
docker compose -f $ComposeFile exec -T backend php bin/console cache:clear --env=prod

Write-Host "Deployment finished."
