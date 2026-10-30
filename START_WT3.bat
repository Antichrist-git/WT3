@echo off
chcp 65001 >nul
cd /d "%~dp0"
title WT3 - Gods of Olympus

echo =============================================
echo      Gods of Olympus - Laravel 12
echo =============================================
echo.
where php >nul 2>nul
if errorlevel 1 (
    echo PHP is not in PATH. Please install/configure PHP 8.3+.
    pause
    exit /b 1
)
if not exist "vendor\autoload.php" (
    echo Missing vendor\autoload.php. Run: composer install
    pause
    exit /b 1
)
if not exist ".env" (
    echo Missing .env. Copy .env.example to .env, then run: php artisan key:generate
    pause
    exit /b 1
)
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data"
if not exist "storage\logs" mkdir "storage\logs"
if not exist "public\storage" (
    echo Connecting Laravel storage...
    mklink /J "%CD%\public\storage" "%CD%\storage\app\public" >nul 2>nul
    if errorlevel 1 (
        echo Junction failed. Trying Laravel storage:link...
        php artisan storage:link
    )
    if not exist "public\storage" (
        echo Storage link could not be created. Photos may not appear.
    )
)
echo.
echo Applying pending migrations (existing records are preserved)...
php artisan migrate --force
if errorlevel 1 (
    echo.
    echo Could not run migrations. Check pdo_sqlite and SQLite file permissions.
    pause
    exit /b 1
)
echo.
echo Open: http://127.0.0.1:8000/gods
echo Press Ctrl+C to stop the server.
echo.
php artisan serve --host=127.0.0.1 --port=8000
pause
