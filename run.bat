@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

REM ===========================
REM   LTUDMNM - Full Run Script
REM ===========================

echo.
echo ====================================
echo     LTUDMNM - Development Setup
echo ====================================
echo.

REM Check if Node.js is installed
where node >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Node.js is not installed or not in PATH
    echo Please install Node.js from https://nodejs.org/
    pause
    exit /b 1
)

REM Check if PHP is installed
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] PHP is not installed or not in PATH
    echo Please install PHP or add it to your PATH
    pause
    exit /b 1
)

echo [✓] Node.js found: $(node --version)
echo [✓] PHP found: $(php --version)
echo.

REM Install frontend dependencies
echo ====================================
echo Installing Frontend Dependencies...
echo ====================================
cd /d "%~dp0frontend"
call npm install
if %errorlevel% neq 0 (
    echo [ERROR] Failed to install frontend dependencies
    pause
    exit /b 1
)
echo [✓] Frontend dependencies installed
echo.

REM Install backend dependencies
echo ====================================
echo Installing Backend Dependencies...
echo ====================================
cd /d "%~dp0backend"
call composer install
if %errorlevel% neq 0 (
    echo [ERROR] Failed to install backend dependencies
    pause
    exit /b 1
)
echo [✓] Backend dependencies installed
echo.

REM Create .env file if not exists
if not exist "%~dp0backend\.env" (
    echo Creating .env file from .env.example...
    copy "%~dp0backend\.env.example" "%~dp0backend\.env"
    echo [✓] .env file created
)
echo.

REM Generate application key
echo ====================================
echo Generating Laravel Application Key...
echo ====================================
call php artisan key:generate
echo [✓] Application key generated
echo.

REM Migrate database
echo ====================================
echo Running Database Migrations...
echo ====================================
call php artisan migrate --force
echo [✓] Database migrated
echo.

REM Start backend server
echo ====================================
echo Starting Backend Server...
echo ====================================
echo Backend will run on: http://127.0.0.1:8000
start cmd /k "cd /d "%~dp0backend" && php artisan serve"
timeout /t 3 /nobreak
echo [✓] Backend started
echo.

REM Start frontend server
echo ====================================
echo Starting Frontend Server...
echo ====================================
echo Frontend will run on: http://localhost:3000
cd /d "%~dp0frontend"
start cmd /k "npm start"
echo [✓] Frontend started
echo.

echo ====================================
echo     Development Servers Running
echo ====================================
echo.
echo Frontend:  http://localhost:3000
echo Backend:   http://127.0.0.1:8000
echo API:       http://127.0.0.1:8000/api
echo.
echo Press Ctrl+C in either terminal to stop the respective server
echo.
pause
