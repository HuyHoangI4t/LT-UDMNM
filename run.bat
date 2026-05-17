@echo off
chcp 65001 >nul

echo ================================
echo     RUN LTUDMNM PROJECT
echo ================================

echo.
echo Installing frontend...
cd /d "%~dp0frontend"
call npm install

echo.
echo Installing backend...
cd /d "%~dp0backend"
call composer install

echo.
echo Setup Laravel...
if not exist ".env" copy ".env.example" ".env"
call php artisan key:generate
call php artisan migrate

echo.
echo Starting backend...
start cmd /k "cd /d %~dp0backend && php artisan serve"

echo.
echo Starting frontend...
start cmd /k "cd /d %~dp0frontend && npm start"

echo.
echo ================================
echo Frontend: http://localhost:3000
echo Backend:  http://127.0.0.1:8000
echo ================================

pause