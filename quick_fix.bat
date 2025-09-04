@echo off
echo Windows Network Quick Fix for Email Sending Issues
echo ==================================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% == 0 (
    echo Running as Administrator - Good!
) else (
    echo WARNING: Not running as Administrator
    echo For best results, right-click this file and select "Run as administrator"
    echo.
    pause
)

echo.
echo Applying network fixes...
echo.

echo 1. Flushing DNS cache...
ipconfig /flushdns
if %errorLevel% == 0 (
    echo    ✓ DNS cache flushed successfully
) else (
    echo    ✗ Failed to flush DNS cache
)

echo.
echo 2. Resetting Winsock catalog...
netsh winsock reset
if %errorLevel% == 0 (
    echo    ✓ Winsock reset successfully
) else (
    echo    ✗ Failed to reset Winsock
)

echo.
echo 3. Resetting TCP/IP stack...
netsh int ip reset
if %errorLevel% == 0 (
    echo    ✓ TCP/IP reset successfully
) else (
    echo    ✗ Failed to reset TCP/IP
)

echo.
echo 4. Testing network connectivity...
ping -n 1 8.8.8.8 >nul 2>&1
if %errorLevel% == 0 (
    echo    ✓ Internet connectivity: OK
) else (
    echo    ✗ Internet connectivity: FAILED
)

echo.
echo 5. Testing DNS resolution...
nslookup smtp.zoho.com >nul 2>&1
if %errorLevel% == 0 (
    echo    ✓ DNS resolution: OK
) else (
    echo    ✗ DNS resolution: FAILED
)

echo.
echo ==================================================
echo IMPORTANT: You need to restart your computer for
echo the network changes to take effect.
echo ==================================================
echo.
echo After restarting, try sending emails again.
echo.
pause
