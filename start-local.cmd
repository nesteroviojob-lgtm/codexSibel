@echo off
setlocal
cd /d "%~dp0"
chcp 65001 >nul
where node >nul 2>nul
if errorlevel 1 (
    echo Node.js is not installed.
    echo Install Node.js 24 LTS or newer from https://nodejs.org/
    echo Then open this file again.
    pause
    exit /b 1
)
node -e "const [v,m]=process.versions.node.split('.').map(Number); if(v<24 || (v===24 && m<18)){console.error('Please install Node.js 24.18 or newer from https://nodejs.org/');process.exit(1)}"
if errorlevel 1 (
    pause
    exit /b 1
)
node --use-env-proxy scripts\start-local.mjs
if errorlevel 1 (
    echo.
    echo Startup failed. See the message above.
    pause
    exit /b 1
)
endlocal
