# Windows Network Fix Script for Email Sending Issues
# This script fixes common Windows networking problems that cause WinError 10106

param(
    [switch]$ResetWinsock,
    [switch]$ResetTCPIP,
    [switch]$FlushDNS,
    [switch]$ResetFirewall,
    [switch]$All,
    [switch]$TestOnly
)

# Check if running as administrator
function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

# Test network connectivity
function Test-NetworkConnectivity {
    Write-Host "Testing network connectivity..." -ForegroundColor Yellow
    
    try {
        $ping = Test-Connection -ComputerName "8.8.8.8" -Count 1 -Quiet
        if ($ping) {
            Write-Host "✓ Internet connectivity: OK" -ForegroundColor Green
        } else {
            Write-Host "✗ Internet connectivity: FAILED" -ForegroundColor Red
            return $false
        }
    } catch {
        Write-Host "✗ Internet connectivity: FAILED - $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
    
    try {
        $dns = [System.Net.Dns]::GetHostAddresses("smtp.zoho.com")
        if ($dns) {
            Write-Host "✓ DNS resolution for smtp.zoho.com: OK" -ForegroundColor Green
        } else {
            Write-Host "✗ DNS resolution for smtp.zoho.com: FAILED" -ForegroundColor Red
            return $false
        }
    } catch {
        Write-Host "✗ DNS resolution for smtp.zoho.com: FAILED - $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
    
    return $true
}

# Reset Winsock catalog
function Reset-Winsock {
    Write-Host "Resetting Winsock catalog..." -ForegroundColor Yellow
    try {
        $result = netsh winsock reset
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ Winsock reset: SUCCESS" -ForegroundColor Green
            Write-Host "  Note: A restart is required for changes to take effect" -ForegroundColor Cyan
        } else {
            Write-Host "✗ Winsock reset: FAILED" -ForegroundColor Red
        }
    } catch {
        Write-Host "✗ Winsock reset: ERROR - $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Reset TCP/IP stack
function Reset-TCPIP {
    Write-Host "Resetting TCP/IP stack..." -ForegroundColor Yellow
    try {
        $result = netsh int ip reset
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ TCP/IP reset: SUCCESS" -ForegroundColor Green
            Write-Host "  Note: A restart is required for changes to take effect" -ForegroundColor Cyan
        } else {
            Write-Host "✗ TCP/IP reset: FAILED" -ForegroundColor Red
        }
    } catch {
        Write-Host "✗ TCP/IP reset: ERROR - $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Flush DNS cache
function Flush-DNSCache {
    Write-Host "Flushing DNS cache..." -ForegroundColor Yellow
    try {
        $result = ipconfig /flushdns
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ DNS cache flush: SUCCESS" -ForegroundColor Green
        } else {
            Write-Host "✗ DNS cache flush: FAILED" -ForegroundColor Red
        }
    } catch {
        Write-Host "✗ DNS cache flush: ERROR - $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Reset Windows Firewall
function Reset-Firewall {
    Write-Host "Resetting Windows Firewall..." -ForegroundColor Yellow
    try {
        $result = netsh advfirewall reset
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ Firewall reset: SUCCESS" -ForegroundColor Green
        } else {
            Write-Host "✗ Firewall reset: FAILED" -ForegroundColor Red
        }
    } catch {
        Write-Host "✗ Firewall reset: ERROR - $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Check Windows services
function Test-WindowsServices {
    Write-Host "Checking Windows networking services..." -ForegroundColor Yellow
    
    $services = @("Dnscache", "Tcpip", "Netman", "LanmanServer", "LanmanWorkstation")
    $allOk = $true
    
    foreach ($service in $services) {
        try {
            $svc = Get-Service -Name $service -ErrorAction Stop
            if ($svc.Status -eq "Running") {
                Write-Host "✓ $service service: Running" -ForegroundColor Green
            } else {
                Write-Host "✗ $service service: $($svc.Status)" -ForegroundColor Red
                $allOk = $false
            }
        } catch {
            Write-Host "✗ $service service: Not found" -ForegroundColor Red
            $allOk = $false
        }
    }
    
    return $allOk
}

# Main script
Write-Host "Windows Network Fix Script for Email Sending Issues" -ForegroundColor Cyan
Write-Host "=" * 60 -ForegroundColor Cyan

# Check if running as administrator
if (-not (Test-Administrator)) {
    Write-Host "⚠️  This script should be run as Administrator for best results" -ForegroundColor Yellow
    Write-Host "   Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    Write-Host ""
}

# Test network connectivity first
$connectivityOk = Test-NetworkConnectivity

# Check Windows services
$servicesOk = Test-WindowsServices

# If test-only mode, just run diagnostics
if ($TestOnly) {
    Write-Host "`n=== Test Results ===" -ForegroundColor Cyan
    Write-Host "Connectivity: $(if ($connectivityOk) {'✓ OK'} else {'✗ FAILED'})" -ForegroundColor $(if ($connectivityOk) {'Green'} else {'Red'})
    Write-Host "Services: $(if ($servicesOk) {'✓ OK'} else {'✗ FAILED'})" -ForegroundColor $(if ($servicesOk) {'Green'} else {'Red'})
    exit
}

# Apply fixes based on parameters
if ($All -or $ResetWinsock) {
    Reset-Winsock
}

if ($All -or $ResetTCPIP) {
    Reset-TCPIP
}

if ($All -or $FlushDNS) {
    Flush-DNSCache
}

if ($All -or $ResetFirewall) {
    Reset-Firewall
}

# If no specific parameters, suggest what to do
if (-not ($ResetWinsock -or $ResetTCPIP -or $FlushDNS -or $ResetFirewall -or $All)) {
    Write-Host "`n=== Recommendations ===" -ForegroundColor Cyan
    
    if (-not $connectivityOk) {
        Write-Host "Network connectivity issues detected. Try:" -ForegroundColor Yellow
        Write-Host "  .\fix_windows_network.ps1 -All" -ForegroundColor White
        Write-Host "  Then restart your computer" -ForegroundColor White
    } elseif (-not $servicesOk) {
        Write-Host "Some Windows services are not running properly. Try:" -ForegroundColor Yellow
        Write-Host "  .\fix_windows_network.ps1 -ResetWinsock -ResetTCPIP" -ForegroundColor White
        Write-Host "  Then restart your computer" -ForegroundColor White
    } else {
        Write-Host "Basic network tests passed. The issue might be specific to SMTP connections." -ForegroundColor Green
        Write-Host "Try the email sending again, or check your email credentials." -ForegroundColor Green
    }
    
    Write-Host "`nAvailable options:" -ForegroundColor Cyan
    Write-Host "  -ResetWinsock    Reset Winsock catalog" -ForegroundColor White
    Write-Host "  -ResetTCPIP      Reset TCP/IP stack" -ForegroundColor White
    Write-Host "  -FlushDNS        Flush DNS cache" -ForegroundColor White
    Write-Host "  -ResetFirewall   Reset Windows Firewall" -ForegroundColor White
    Write-Host "  -All             Apply all fixes" -ForegroundColor White
    Write-Host "  -TestOnly        Run diagnostics only" -ForegroundColor White
}

Write-Host "`nScript completed." -ForegroundColor Cyan
