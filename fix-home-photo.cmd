@echo off
setlocal
cd /d "%~dp0"
chcp 65001 >nul
where node >nul 2>nul
if errorlevel 1 (
    echo Node.js is not installed. Install it from https://nodejs.org/
    pause
    exit /b 1
)
:repair
if exist "repair-home-photo.mjs" (
    node --use-env-proxy repair-home-photo.mjs
) else (
    node --use-env-proxy scripts\repair-home-photo.mjs
)
if errorlevel 2 (
    echo.
    echo Close the previous start-local window, then press any key here.
    pause
    goto repair
)
if errorlevel 1 (
    pause
    exit /b 1
)
set "PORT=9400"
call start-local.cmd
endlocal
