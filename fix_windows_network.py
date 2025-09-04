#!/usr/bin/env python3
"""
Windows Network Troubleshooting Script for Email Sending Issues

This script helps diagnose and fix common Windows networking issues
that cause WinError 10106 (service provider initialization failed).
"""

import subprocess
import sys
import socket
import ssl
import os
import platform


def run_command(cmd, description):
    """Run a command and return the result."""
    print(f"\n{description}...")
    try:
        result = subprocess.run(cmd, shell=True, capture_output=True, text=True, timeout=30)
        if result.returncode == 0:
            print(f"✓ {description}: SUCCESS")
            if result.stdout.strip():
                print(f"  Output: {result.stdout.strip()}")
        else:
            print(f"✗ {description}: FAILED")
            if result.stderr.strip():
                print(f"  Error: {result.stderr.strip()}")
        return result.returncode == 0
    except subprocess.TimeoutExpired:
        print(f"✗ {description}: TIMEOUT")
        return False
    except Exception as e:
        print(f"✗ {description}: ERROR - {e}")
        return False


def test_basic_connectivity():
    """Test basic network connectivity."""
    print("\n=== Testing Basic Connectivity ===")
    
    # Test internet connectivity
    try:
        socket.create_connection(("8.8.8.8", 53), timeout=5)
        print("✓ Internet connectivity: OK")
    except Exception as e:
        print(f"✗ Internet connectivity: FAILED ({e})")
        return False
    
    # Test DNS resolution
    try:
        socket.gethostbyname("smtp.zoho.com")
        print("✓ DNS resolution for smtp.zoho.com: OK")
    except Exception as e:
        print(f"✗ DNS resolution for smtp.zoho.com: FAILED ({e})")
        return False
    
    return True


def test_ssl_context():
    """Test SSL context creation."""
    print("\n=== Testing SSL Context ===")
    
    try:
        context = ssl.create_default_context()
        context.check_hostname = False
        context.verify_mode = ssl.CERT_NONE
        context.minimum_version = ssl.TLSVersion.TLSv1_2
        print("✓ SSL context creation: OK")
        return True
    except Exception as e:
        print(f"✗ SSL context creation: FAILED ({e})")
        return False


def diagnose_windows_services():
    """Diagnose Windows networking services."""
    print("\n=== Diagnosing Windows Services ===")
    
    services_to_check = [
        "Dnscache",
        "Tcpip",
        "Netman",
        "LanmanServer",
        "LanmanWorkstation"
    ]
    
    all_ok = True
    for service in services_to_check:
        success = run_command(f'sc query "{service}"', f"Checking {service} service")
        if not success:
            all_ok = False
    
    return all_ok


def suggest_fixes():
    """Suggest fixes for common Windows networking issues."""
    print("\n=== Suggested Fixes ===")
    print("If you're experiencing WinError 10106, try these solutions in order:")
    print()
    print("1. Reset Winsock Catalog (Run as Administrator):")
    print("   netsh winsock reset")
    print("   Then restart your computer")
    print()
    print("2. Reset TCP/IP stack (Run as Administrator):")
    print("   netsh int ip reset")
    print("   Then restart your computer")
    print()
    print("3. Flush DNS cache (Run as Administrator):")
    print("   ipconfig /flushdns")
    print()
    print("4. Reset network adapters (Run as Administrator):")
    print("   netsh int ip reset all")
    print("   netsh winsock reset")
    print("   netsh advfirewall reset")
    print("   Then restart your computer")
    print()
    print("5. Check Windows Firewall and Antivirus:")
    print("   - Temporarily disable Windows Firewall")
    print("   - Temporarily disable antivirus real-time protection")
    print("   - Test if email sending works")
    print()
    print("6. Update network drivers:")
    print("   - Go to Device Manager")
    print("   - Find Network Adapters")
    print("   - Right-click and select 'Update driver'")
    print()
    print("7. Try using a VPN or different network:")
    print("   - Connect to a different WiFi network")
    print("   - Use a VPN service")
    print("   - Use mobile hotspot")


def main():
    """Main troubleshooting function."""
    print("Windows Network Troubleshooting for Email Sending Issues")
    print("=" * 60)
    print(f"Platform: {platform.platform()}")
    print(f"Python: {sys.version}")
    
    # Run diagnostics
    connectivity_ok = test_basic_connectivity()
    ssl_ok = test_ssl_context()
    services_ok = diagnose_windows_services()
    
    print("\n=== Summary ===")
    print(f"Basic Connectivity: {'✓ OK' if connectivity_ok else '✗ FAILED'}")
    print(f"SSL Context: {'✓ OK' if ssl_ok else '✗ FAILED'}")
    print(f"Windows Services: {'✓ OK' if services_ok else '✗ FAILED'}")
    
    if not (connectivity_ok and ssl_ok):
        suggest_fixes()
    else:
        print("\n✓ All basic tests passed. The issue might be specific to SMTP connections.")
        print("Try the email sending again, or check your email credentials.")


if __name__ == "__main__":
    main()
