# ============================================================
#  SmartGarden AMCS - Dev Environment Startup Script
#  Usage: .\start-dev.ps1
# ============================================================

$ErrorActionPreference = "Stop"
$RootDir = $PSScriptRoot

Write-Host ""
Write-Host "  ============================================================" -ForegroundColor Green
Write-Host "   SmartGarden AMCS - Starting Development Environment" -ForegroundColor Green
Write-Host "  ============================================================" -ForegroundColor Green
Write-Host ""

# -- Step 1: Build Frontend Assets --------------------------------
Write-Host "  [1/4] Building frontend assets (Vite)..." -ForegroundColor Cyan
Set-Location $RootDir
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Host "  FAILED: npm run build failed! Aborting." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}
Write-Host "  OK: Vite build complete." -ForegroundColor Green

# -- Step 2: Clear and Re-optimize Laravel Cache ------------------
Write-Host ""
Write-Host "  [2/4] Clearing and optimizing Laravel cache..." -ForegroundColor Cyan
php artisan optimize:clear
php artisan optimize
Write-Host "  OK: Laravel cache optimized." -ForegroundColor Green

# -- Step 3: Start Laravel Server in new window -------------------
Write-Host ""
Write-Host "  [3/4] Launching Laravel server on port 8000..." -ForegroundColor Cyan
$laravelCmd = "cd '$RootDir'; `$host.UI.RawUI.WindowTitle = 'Laravel :8000'; Write-Host ''; Write-Host '  SmartGarden Dashboard - Laravel Dev Server' -ForegroundColor Green; Write-Host '  http://localhost:8000' -ForegroundColor White; Write-Host ''; php artisan serve --port=8000"
Start-Process powershell -ArgumentList "-NoExit", "-Command", $laravelCmd

Start-Sleep -Seconds 1

# -- Step 4: Start AI Server in new window ------------------------
Write-Host "  [4/4] Launching AI Server on port 8001..." -ForegroundColor Cyan
$aiServerDir = Join-Path $RootDir "AIServer"
$aiCmd = "cd '$aiServerDir'; `$host.UI.RawUI.WindowTitle = 'AI Server :8001'; Write-Host ''; Write-Host '  SmartGarden AI Server - FastAPI + APScheduler' -ForegroundColor Magenta; Write-Host '  http://localhost:8001/docs' -ForegroundColor White; Write-Host ''; .\venv\Scripts\activate; python -m uvicorn main:app --host 0.0.0.0 --port 8001"
Start-Process powershell -ArgumentList "-NoExit", "-Command", $aiCmd

# -- Done ---------------------------------------------------------
Write-Host ""
Write-Host "  ============================================================" -ForegroundColor Green
Write-Host "   All services are starting up!" -ForegroundColor Green
Write-Host ""
Write-Host "     Dashboard  : http://localhost:8000" -ForegroundColor White
Write-Host "     AI Server  : http://localhost:8001" -ForegroundColor White
Write-Host "     AI Docs    : http://localhost:8001/docs" -ForegroundColor White
Write-Host ""
Write-Host "   NOTE: The AI Scheduler runs inside the AI Server." -ForegroundColor Yellow
Write-Host "         No extra terminal needed." -ForegroundColor Yellow
Write-Host "  ============================================================" -ForegroundColor Green
Write-Host ""
