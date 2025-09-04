# Windows Network Fix for Email Sending Issues

## Problem
You're experiencing the error: `Failed to send email: [WinError 10106] The requested service provider could not be loaded or initialized`

This is a common Windows networking issue that affects SSL/TLS connections, particularly when sending emails through SMTP.

## Quick Fix (Recommended)

### Option 1: Run the Quick Fix Batch File
1. **Right-click** on `quick_fix.bat` and select **"Run as administrator"**
2. Follow the prompts
3. **Restart your computer** when prompted
4. Try sending emails again

### Option 2: Use PowerShell Script
1. **Right-click** on PowerShell and select **"Run as administrator"**
2. Navigate to your project directory
3. Run: `.\fix_windows_network.ps1 -All`
4. **Restart your computer**
5. Try sending emails again

## Manual Fix (If automated scripts don't work)

### Step 1: Reset Network Stack (Run as Administrator)
```cmd
netsh winsock reset
netsh int ip reset
ipconfig /flushdns
```

### Step 2: Restart Computer
**Important**: You must restart your computer for these changes to take effect.

### Step 3: Test Email Sending
Try sending an email again through your application.

## Advanced Troubleshooting

### Run Network Diagnostics
```cmd
py fix_windows_network.py
```

This will test:
- Internet connectivity
- DNS resolution
- SSL context creation
- Windows networking services

### Check Windows Services
Make sure these services are running:
- Dnscache (DNS Client)
- Tcpip (TCP/IP Protocol Driver)
- Netman (Network Manager)
- LanmanServer (Server)
- LanmanWorkstation (Workstation)

### Firewall and Antivirus
1. Temporarily disable Windows Firewall
2. Temporarily disable antivirus real-time protection
3. Test email sending
4. Re-enable protection if it works

### Network Drivers
1. Open Device Manager
2. Expand "Network Adapters"
3. Right-click your network adapter
4. Select "Update driver"
5. Restart if prompted

## What We Fixed

### 1. Enhanced Python Script (`send_mail.py`)
- Added robust SSL context creation for Windows compatibility
- Improved error handling with specific Windows error messages
- Added network diagnostics
- Better fallback mechanisms

### 2. Improved PHP Controller (`EmailController.php`)
- Added comprehensive environment variables for Windows
- Better process management
- Enhanced error reporting

### 3. Created Diagnostic Tools
- `fix_windows_network.py` - Python diagnostic script
- `fix_windows_network.ps1` - PowerShell fix script
- `quick_fix.bat` - Simple batch file for quick fixes
- `test_email_sending.py` - Test script for email functionality

## Common Causes of WinError 10106

1. **Corrupted Winsock catalog** - Fixed by `netsh winsock reset`
2. **TCP/IP stack corruption** - Fixed by `netsh int ip reset`
3. **DNS cache issues** - Fixed by `ipconfig /flushdns`
4. **Firewall blocking connections** - Temporarily disable to test
5. **Antivirus interference** - Temporarily disable to test
6. **Outdated network drivers** - Update through Device Manager
7. **Corporate network restrictions** - Try VPN or different network

## Testing Your Fix

After applying the fixes and restarting:

1. Run the test script:
   ```cmd
   py test_email_sending.py
   ```

2. Try sending an email through your application

3. If it still fails, run diagnostics:
   ```cmd
   py fix_windows_network.py
   ```

## Prevention

To prevent this issue in the future:
1. Keep Windows updated
2. Keep network drivers updated
3. Avoid using multiple VPNs simultaneously
4. Don't modify network settings unless necessary
5. Use reputable antivirus software

## Still Having Issues?

If the problem persists after trying all solutions:

1. **Try a different network** (mobile hotspot, different WiFi)
2. **Use a VPN** to bypass network restrictions
3. **Check with your IT department** if on a corporate network
4. **Contact your ISP** if the issue is network-wide
5. **Consider using a different email provider** temporarily

## Files Created/Modified

- ✅ `send_mail.py` - Enhanced with Windows compatibility
- ✅ `app/Http/Controllers/EmailController.php` - Improved environment handling
- ✅ `fix_windows_network.py` - Network diagnostics script
- ✅ `fix_windows_network.ps1` - PowerShell fix script
- ✅ `quick_fix.bat` - Quick fix batch file
- ✅ `test_email_sending.py` - Email functionality test
- ✅ `WINDOWS_NETWORK_FIX_README.md` - This documentation

## Support

If you continue to experience issues, please provide:
1. Output from `py fix_windows_network.py`
2. Output from `py test_email_sending.py`
3. Your Windows version
4. Whether you're on a corporate network
5. Any antivirus/firewall software you're using
