@echo off
title RMS Lightweight Dev Server (Low-Spec Mode)
echo ============================================================================
echo   RMS Enterprise - Low-Spec ("Potato PC") Development Launcher
echo   Starting PHP Artisan Server + Vite Dev Bundler (No Idle Queue/Log Polling)
echo ============================================================================
echo.

set PHP_CLI_SERVER_WORKERS=2

if "%1"=="--with-queue" (
    echo [INFO] Starting with idle-friendly Queue Worker (--sleep=3)...
    npx concurrently -c "#93c5fd,#fdba74,#c4b5fd" "php artisan serve" "npm run dev" "php artisan queue:work --sleep=3 --max-time=3600 --max-jobs=1000" --names=server,vite,queue --kill-others
) else (
    echo [INFO] Tip: Pass --with-queue if you need background queue jobs processed.
    npx concurrently -c "#93c5fd,#fdba74" "php artisan serve" "npm run dev" --names=server,vite --kill-others
)

