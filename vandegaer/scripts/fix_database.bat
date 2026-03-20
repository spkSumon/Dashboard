@echo off
REM Fix database by running missing migrations
REM This will fix the 500 errors on the posts page

echo ========================================
echo   SocialBit - Database Migration Fixer
echo ========================================
echo.

cd /d "%~dp0"

REM Try to find PHP in XAMPP
set PHP_PATH=C:\xampp3\php\php.exe

if not exist "%PHP_PATH%" (
    set PHP_PATH=C:\xampp\php\php.exe
)

if not exist "%PHP_PATH%" (
    echo ERROR: Could not find PHP.exe
    echo Please update PHP_PATH in this script
    pause
    exit /b 1
)

echo Using PHP: %PHP_PATH%
echo.
echo Running migrations...
echo.

"%PHP_PATH%" execute_missing_migrations.php

echo.
echo ========================================
echo   Done! Press any key to close...
echo ========================================
pause > nul
