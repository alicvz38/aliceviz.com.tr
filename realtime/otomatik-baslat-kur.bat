@echo off
chcp 65001 >nul
rem Windows açıldığında canlı sunucunun kendiliğinden başlaması için Başlangıç klasörüne kısayol ekler.
set "STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"
(
  echo @echo off
  echo start "" "C:\xampp\php\php-win.exe" "%~dp0server.php"
) > "%STARTUP%\websitem-canli-sunucu.bat"
echo.
echo  TAMAM: Bilgisayar her acildiginda canli sunucu kendiliginden baslayacak.
echo  (Not: XAMPP'te MySQL'in de acik olmasi gerekir.)
echo  Kaldirmak icin: otomatik-baslat-kaldir.bat
echo.
start "" "C:\xampp\php\php-win.exe" "%~dp0server.php"
echo  Sunucu simdi de baslatildi.
echo.
pause
