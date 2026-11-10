@echo off
chcp 65001 >nul
rem Çalışan canlı sunucuya "dur" işareti bırakır; sunucu 1-2 saniye içinde kapanır.
type nul > "%~dp0..\storage\realtime.stop"
echo.
echo  Canli sunucu durduruluyor... Panel otomatik olarak 30 saniyelik kontrole doner.
echo.
timeout /t 3 >nul
