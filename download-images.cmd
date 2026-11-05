@echo off
setlocal
cd /d "%~dp0"
chcp 65001 >nul
where node >nul 2>nul
if errorlevel 1 (
    echo Install Node.js 24 or newer from https://nodejs.org/
    pause
    exit /b 1
)
if not exist "node_modules\fflate\package.json" (
    echo First launch start-local.cmd in this folder to install the project.
    pause
    exit /b 1
)
node --use-env-proxy scripts\download-assets.mjs
echo.
pause
endlocal
