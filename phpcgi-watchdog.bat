@echo off
title PHP-CGI Watchdog
echo [PHP-CGI Watchdog] Started. Monitoring port 9001...
:loop
netstat -ano | findstr ":9001" | findstr "LISTENING" >nul 2>&1
if %errorlevel% neq 0 (
    echo [%time%] PHP-CGI not running, restarting...
    start "" "D:\phpstudy_pro\Extensions\php\php7.4.3nts\php-cgi.exe" -b 127.0.0.1:9001 -c "D:\phpstudy_pro\Extensions\php\php7.4.3nts\php.ini"
    timeout /t 2 /nobreak >nul
    echo [%time%] PHP-CGI restarted.
)
timeout /t 5 /nobreak >nul
goto loop
