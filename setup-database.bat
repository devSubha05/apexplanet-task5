@echo off
title Import Database - LearnLoop
cd /d "%~dp0"

echo ========================================================
echo Importing database.sql into MariaDB/MySQL...
echo ========================================================
echo.

"C:\Program Files\MariaDB 13.0\bin\mysql.exe" -u root -p < database.sql
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Import failed. Make sure MariaDB is running on port 3306.
    echo If prompted for a password and root has none, simply press Enter.
    echo.
) else (
    echo.
    echo [OK] Database 'elearn' imported successfully!
)

pause

