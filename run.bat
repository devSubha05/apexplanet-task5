@echo off
title LearnLoop Launcher
cd /d "%~dp0"

echo ========================================================
echo Starting LearnLoop (Database + Web Server)...
echo ========================================================

netstat -ano | findstr :3306 | findstr LISTENING >nul 2>&1
if %errorlevel% neq 0 (
    echo Starting MariaDB Server on port 3306...
    start "MariaDB Server" cmd /k "cd /d \"C:\Program Files\MariaDB 13.0\" && \"C:\Program Files\MariaDB 13.0\bin\mysqld.exe\" --defaults-file=\"C:\Program Files\MariaDB 13.0\data\my.ini\" --console"
    timeout /t 3 /nobreak >nul
) else (
    echo [OK] MariaDB is already running on port 3306.
)

for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8000 ^| findstr LISTENING') do (
    echo Clearing previous process on port 8000 (PID %%a)...
    taskkill /F /PID %%a >nul 2>&1
)

echo.
echo ========================================================
echo Web Server starting at: http://127.0.0.1:8000/
echo Opening website in your default browser...
echo Keep this window open while using the website!
echo Press Ctrl+C to stop.
echo ========================================================
echo.

start "" http://127.0.0.1:8000/

php -S 127.0.0.1:8000
pause
