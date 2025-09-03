#!/usr/bin/env python3
"""
Network diagnostic script for email sync troubleshooting
Run this script to test network connectivity to email providers
"""

import socket
import ssl
import sys
import json
import urllib.request
from datetime import datetime

def test_internet_connectivity():
    """Test basic internet connectivity"""
    try:
        print("Testing basic internet connectivity...")
        urllib.request.urlopen('http://www.google.com', timeout=10)
        print("[OK] Internet connectivity successful")
        return {"success": True}
    except Exception as e:
        print(f"[FAIL] Internet connectivity failed: {e}")
        print("  This suggests your local machine may not have internet access.")
        print("  Consider using ngrok or checking your network configuration.")
        return {"success": False, "error": str(e)}

def test_dns_resolution(hostname):
    """Test DNS resolution for a hostname"""
    try:
        print(f"Testing DNS resolution for {hostname}...")
        ip_addresses = socket.getaddrinfo(hostname, None, socket.AF_UNSPEC, socket.SOCK_STREAM)
        resolved_ips = [ip[4][0] for ip in ip_addresses]
        print(f"[OK] DNS resolution successful: {resolved_ips}")
        return {"success": True, "ips": resolved_ips}
    except socket.gaierror as e:
        print(f"[FAIL] DNS resolution failed: {e}")
        print(f"  Error details: {e.errno} - {e.strerror}")
        if e.errno == 11001:  # WSAHOST_NOT_FOUND
            print(f"  This usually means the hostname cannot be resolved.")
            print(f"  Check your internet connection and DNS settings.")
        elif e.errno == 11003:  # WSANO_DATA
            print(f"  This usually means no data record of the requested type exists.")
        return {"success": False, "error": str(e), "errno": e.errno}

def test_socket_connection(hostname, port, timeout=10):
    """Test basic socket connection"""
    try:
        print(f"Testing socket connection to {hostname}:{port}...")
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(timeout)
        result = sock.connect_ex((hostname, port))
        sock.close()
        
        if result == 0:
            print(f"[OK] Socket connection successful")
            return {"success": True}
        else:
            print(f"[FAIL] Socket connection failed (error code: {result})")
            return {"success": False, "error_code": result}
    except Exception as e:
        print(f"[FAIL] Socket connection error: {e}")
        return {"success": False, "error": str(e)}

def test_ssl_connection(hostname, port, timeout=10):
    """Test SSL connection"""
    try:
        print(f"Testing SSL connection to {hostname}:{port}...")
        context = ssl.create_default_context()
        with socket.create_connection((hostname, port), timeout=timeout) as sock:
            with context.wrap_socket(sock, server_hostname=hostname) as ssock:
                cert = ssock.getpeercert()
                print(f"[OK] SSL connection successful")
                print(f"  Certificate subject: {cert.get('subject', 'N/A')}")
                print(f"  Certificate issuer: {cert.get('issuer', 'N/A')}")
                return {"success": True, "certificate": cert}
    except Exception as e:
        print(f"[FAIL] SSL connection failed: {e}")
        return {"success": False, "error": str(e)}

def test_imap_connection(hostname, port=993):
    """Test IMAP connection"""
    try:
        print(f"Testing IMAP connection to {hostname}:{port}...")
        import imaplib
        mail = imaplib.IMAP4_SSL(hostname, port)
        mail.logout()
        print(f"[OK] IMAP connection successful")
        return {"success": True}
    except Exception as e:
        print(f"[FAIL] IMAP connection failed: {e}")
        return {"success": False, "error": str(e)}

def run_network_diagnostics(hostname, port=993):
    """Run comprehensive network diagnostics"""
    print(f"\n{'='*60}")
    print(f"Network Diagnostics for {hostname}:{port}")
    print(f"Timestamp: {datetime.now().isoformat()}")
    print(f"{'='*60}")
    
    results = {
        "hostname": hostname,
        "port": port,
        "timestamp": datetime.now().isoformat(),
        "tests": {}
    }
    
    # Test basic internet connectivity first
    results["tests"]["internet"] = test_internet_connectivity()
    
    # Test DNS resolution
    results["tests"]["dns"] = test_dns_resolution(hostname)
    
    # Test socket connection
    results["tests"]["socket"] = test_socket_connection(hostname, port)
    
    # Test SSL connection
    results["tests"]["ssl"] = test_ssl_connection(hostname, port)
    
    # Test IMAP connection
    results["tests"]["imap"] = test_imap_connection(hostname, port)
    
    # Summary
    print(f"\n{'='*60}")
    print("SUMMARY:")
    print(f"{'='*60}")
    
    all_passed = True
    for test_name, result in results["tests"].items():
        status = "[OK] PASS" if result["success"] else "[FAIL] FAIL"
        print(f"{test_name.upper()}: {status}")
        if not result["success"]:
            all_passed = False
    
    print(f"\nOverall Status: {'[OK] ALL TESTS PASSED' if all_passed else '[FAIL] SOME TESTS FAILED'}")
    
    return results

def main():
    """Main function"""
    if len(sys.argv) < 2:
        print("Usage: python test_network.py <hostname> [port]")
        print("Example: python test_network.py imap.zoho.com 993")
        sys.exit(1)
    
    hostname = sys.argv[1]
    port = int(sys.argv[2]) if len(sys.argv) > 2 else 993
    
    results = run_network_diagnostics(hostname, port)
    
    # Save results to file
    output_file = f"network_test_{hostname}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
    with open(output_file, 'w') as f:
        json.dump(results, f, indent=2)
    
    print(f"\nDetailed results saved to: {output_file}")
    
    # Output simplified results for Laravel to parse
    simplified_results = {
        "internet": results["tests"]["internet"]["success"],
        "dns": results["tests"]["dns"]["success"],
        "socket": results["tests"]["socket"]["success"],
        "ssl": results["tests"]["ssl"]["success"],
        "imap": results["tests"]["imap"]["success"]
    }
    
    # Print JSON results to stdout for Laravel to capture
    print(json.dumps(simplified_results))

if __name__ == "__main__":
    main()
