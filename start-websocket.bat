@echo off
setlocal
cd /d "%~dp0"

if exist "%~dp0..\..\php\php.exe" (
    "%~dp0..\..\php\php.exe" "%~dp0websocket-server.php"
) else (
    php "%~dp0websocket-server.php"
)

if errorlevel 1 pause
