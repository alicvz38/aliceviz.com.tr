@echo off
chcp 65001 >nul
echo.
echo  Gmail senkronizasyonu icin Windows Gorev Zamanlayici'ya gorev ekleniyor...
echo  (Her dakika arka planda, pencere acmadan calisir.)
echo.
schtasks /Create /F /SC MINUTE /MO 1 /TN "WebSitem Gmail Senkronizasyonu" /TR "\"C:\xampp\php\php-win.exe\" \"%~dp0mail-sync.php\""
if %errorlevel%==0 (
    echo.
    echo  TAMAM: Gorev kuruldu. Gmail e-postalariniz artik panel kapaliyken de her dakika aktarilir.
    echo  Kaldirmak icin: gorevi-kaldir.bat
) else (
    echo.
    echo  HATA: Gorev kurulamadi. Dosyaya sag tiklayip "Yonetici olarak calistir" deneyin.
)
echo.
pause
