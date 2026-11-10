@echo off
chcp 65001 >nul
schtasks /Delete /F /TN "WebSitem Gmail Senkronizasyonu"
echo.
echo  Gorev kaldirildi. Senkronizasyon artik yalnizca panel acikken calisir.
echo.
pause
