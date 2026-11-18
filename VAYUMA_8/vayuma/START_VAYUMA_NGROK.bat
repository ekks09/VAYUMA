@echo off
setlocal

set "XAMPP=C:\xampp"
set "NGROK=C:\ngrok\ngrok.exe"
set "DOMAIN=https://intersystematic-yolonda-gymnogenous.ngrok-free.dev"
set "APP_URL=http://localhost/VAYUMA_8/vayuma/"
set "PUBLIC_URL=%DOMAIN%/VAYUMA_8/vayuma/"

echo.
echo  VAYUMA local server and Paystack callback tunnel
echo  -----------------------------------------------

if not exist "%XAMPP%\apache_start.bat" (
    echo ERROR: Apache startup file not found: "%XAMPP%\apache_start.bat"
    goto failed
)
if not exist "%XAMPP%\mysql_start.bat" (
    echo ERROR: MySQL startup file not found: "%XAMPP%\mysql_start.bat"
    goto failed
)

tasklist /FI "IMAGENAME eq mysqld.exe" /NH | find /I "mysqld.exe" >nul
if errorlevel 1 (
    echo Starting MySQL...
    start "VAYUMA - MySQL" /min "%XAMPP%\mysql_start.bat"
) else (
    echo MySQL is already running.
)

tasklist /FI "IMAGENAME eq httpd.exe" /NH | find /I "httpd.exe" >nul
if errorlevel 1 (
    echo Starting Apache...
    start "VAYUMA - Apache" /min "%XAMPP%\apache_start.bat"
) else (
    echo Apache is already running.
)

echo Waiting for Apache and MySQL...
powershell -NoProfile -Command "$ready=$false; for($i=0;$i -lt 30;$i++){ $ready=$true; foreach($port in @(80,3306)){ $c=New-Object Net.Sockets.TcpClient; try{$c.Connect('127.0.0.1',$port)}catch{$ready=$false}finally{$c.Dispose()} }; if($ready){break}; Start-Sleep -Seconds 1 }; if($ready){exit 0}else{exit 1}"
if errorlevel 1 (
    echo ERROR: Apache or MySQL did not respond on port 80 or 3306 within 30 seconds.
    echo Check the XAMPP windows for startup errors.
    goto failed
)

echo Opening VAYUMA: %APP_URL%
start "" "%APP_URL%"

if not exist "%NGROK%" (
    echo ERROR: ngrok was not found at "%NGROK%".
    echo Install ngrok there, or update the NGROK path in this file.
    goto failed
)

tasklist /FI "IMAGENAME eq ngrok.exe" /NH | find /I "ngrok.exe" >nul
if not errorlevel 1 (
    echo Checking the existing ngrok tunnel...
    powershell -NoProfile -Command "$desired='%DOMAIN%'; try{$t=Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 3; foreach($x in $t.tunnels){if($x.public_url -eq $desired -and $x.config.addr -match ':80/?$'){exit 0}}; exit 1}catch{exit 1}"
    if not errorlevel 1 goto tunnel_ready
    echo ERROR: ngrok is running, but its local status API does not show the VAYUMA tunnel.
    echo Stop the existing ngrok process with Ctrl+C, then run this launcher again.
    echo The expected tunnel is %DOMAIN% forwarding to localhost port 80.
    goto failed
)

echo Starting ngrok for %DOMAIN% ...
start "VAYUMA - ngrok" "%NGROK%" http 80 --url "%DOMAIN%"
echo Waiting for ngrok to establish the tunnel...
powershell -NoProfile -Command "$desired='%DOMAIN%'; $ready=$false; for($i=0;$i -lt 30;$i++){try{$t=Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 2; foreach($x in $t.tunnels){if($x.public_url -eq $desired -and $x.config.addr -match ':80/?$'){$ready=$true;break}}}catch{}; if($ready){break}; Start-Sleep -Seconds 1}; if($ready){exit 0}else{exit 1}"
if errorlevel 1 (
    echo ERROR: ngrok did not establish the expected tunnel within 30 seconds.
    echo Check the ngrok window for authentication, domain, or connection errors.
    goto failed
)

:tunnel_ready
echo ngrok tunnel is active and forwarding to localhost port 80.
echo Public VAYUMA URL: %PUBLIC_URL%
start "" "%PUBLIC_URL%"
goto finished

:failed
echo.
echo Startup did not complete. Resolve the message above, then run this file again.
pause
exit /b 1

:finished
echo.
echo Local VAYUMA URL: %APP_URL%
echo Public VAYUMA URL: %PUBLIC_URL%
echo Keep Apache, MySQL, and the active ngrok tunnel running during testing.
echo.
pause
endlocal
