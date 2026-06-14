@echo off
echo ===================================================
echo   Memulai Live Test Server untuk Zakat App
echo ===================================================
echo.
echo Menjalankan Backend (PHP Artisan Serve)...
start cmd /k "php artisan serve"

echo Menjalankan Frontend (Vite HMR)...
start cmd /k "npm run dev"

echo.
echo Server telah berjalan di latar belakang!
echo Silakan buka browser Anda di: http://127.0.0.1:8000
echo ===================================================
pause
