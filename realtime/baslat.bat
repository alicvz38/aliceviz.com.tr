@echo off
chcp 65001 >nul
rem Canlı bildirim (WebSocket) sunucusunu pencere açmadan arka planda başlatır.
start "" "C:\xampp\php\php-win.exe" "%~dp0server.php"
echo.
echo  Canli sunucu baslatildi (ws://127.0.0.1:8090).
echo  Paneli yenileyin: Mesajlar sayfasinda "Canli" yazisi gorunur.
echo  Durdurmak icin: durdur.bat
echo.
timeout /t 4 >nul
