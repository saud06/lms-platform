param(
  [switch]$InstallOnly
)

Write-Host "=== LMS Platform: Local Setup (Laravel + React) ===" -ForegroundColor Cyan

# --- Prereq checks ---
function Assert-Cmd($name) {
  $exists = Get-Command $name -ErrorAction SilentlyContinue
  if (-not $exists) {
    Write-Error "Required command '$name' not found in PATH."; exit 1
  }
}

Assert-Cmd php
Assert-Cmd composer
Assert-Cmd node
Assert-Cmd npm

# --- Backend ---
Push-Location backend
try {
  if (-not (Test-Path vendor)) {
    Write-Host "[Backend] Installing composer dependencies..." -ForegroundColor Yellow
    composer install
  } else {
    Write-Host "[Backend] Dependencies already installed." -ForegroundColor DarkGray
  }

  if (-not (Test-Path .env)) {
    Write-Host "[Backend] Creating .env and generating key..." -ForegroundColor Yellow
    Copy-Item .env.example .env -Force
    php artisan key:generate
  }
}
finally { Pop-Location }

if ($InstallOnly) { Write-Host "InstallOnly set. Exiting."; exit 0 }

# --- Start servers ---
$backend = Start-Process powershell -ArgumentList "-NoExit","-Command","cd backend; php artisan serve --host=127.0.0.1 --port=8000" -PassThru
Start-Sleep -Seconds 2

$frontend = Start-Process powershell -ArgumentList "-NoExit","-Command","cd frontend; if (Test-Path node_modules) { npm run dev } else { npm install; npm run dev }" -PassThru

Write-Host "Backend PID: $($backend.Id) | Frontend PID: $($frontend.Id)" -ForegroundColor Green
Write-Host "Laravel: http://127.0.0.1:8000  |  React (Vite): http://127.0.0.1:3000" -ForegroundColor Green
