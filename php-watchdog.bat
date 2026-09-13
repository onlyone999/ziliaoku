@echo off
title [Ziliaoku] PHP 7.4 Watchdog
echo ============================================================
echo   Ziliaoku PHP-FPM Watchdog - Auto Restart on Crash
echo   Port: 9001 ^| PHP: 7.4.3nts
echo   Press Ctrl+C to stop
echo ============================================================
echo.

:loop
netstat -ano | findstr ":9001.*LISTENING" >NUL 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [%time:~0,8%] PHP 7.4 down! Restarting...
    start /B "" "D:\phpstudy_pro\Extensions\php\php7.4.3nts\php-cgi.exe" -b 127.0.0.1:9001 -c "D:\phpstudy_pro\Extensions\php\php7.4.3nts\php.ini"
    timeout /t 3 >NUL
    netstat -ano | findstr ":9001.*LISTENING" >NUL 2>&1
    if %ERRORLEVEL% EQU 0 (
        echo [%time:~0,8%] PHP 7.4 restarted OK
    ) else (
        echo [%time:~0,8%] Restart failed! Retrying in 10s...
        timeout /t 10 >NUL
    )
)
timeout /t 3 >NUL
goto loop
