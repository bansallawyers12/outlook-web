#!/usr/bin/env python3
"""
Network diagnostic script for email sync troubleshooting
Run this script to test network connectivity to email providers
"""

import socket
import ssl
import sys
import json
from datetime import datetime

def test_dns_resolution(hostname):
    """Test DNS resolution for a hostname"""
    try:
        print(f"Testing DNS resolution for {hostname}...")
        ip_addresses = socket.getaddrinfo(hostname, None, socket.AF_UNSPEC, socket.SOCK_STREAM)
        resolved_ips = [ip[4][0] for ip in ip_addresses]
        print(f"✓ DNS resolution successful: {resolved_ips}")
        return {"success": True, "ips": resolved_ips}
    except socket.gaierror as e:
        print(f"✗ DNS resolution failed: {e}")
        return {"success": False, "error": str(e)}

def test_socket_connection(hostname, port, timeout=10):
    """Test basic socket connection"""
    try:
        print(f"Testing socket connection to {hostname}:{port}...")
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(timeout)
        result = sock.connect_ex((hostname, port))
        sock.close()
        
        if result == 0:
            print(f"✓ Socket connection successful")
            return {"success": True}
        else:
            print(f"✗ Socket connection failed (error code: {result})")
            return {"success": False, "error_code": result}
    except Exception as e:
        print(f"✗ Socket connection error: {e}")
        return {"success": False, "error": str(e)}

def test_ssl_connection(hostname, port, timeout=10):
    """Test SSL connection"""
    try:
        print(f"Testing SSL connection to {hostname}:{port}...")
        context = ssl.create_default_context()
        with socket.create_connection((hostname, port), timeout=timeout) as sock:
            with context.wrap_socket(sock, server_hostname=hostname) as ssock:
                cert = ssock.getpeercert()
                print(f"✓ SSL connection successful")
                print(f"  Certificate subject: {cert.get('subject', 'N/A')}")
                print(f"  Certificate issuer: {cert.get('issuer', 'N/A')}")
                return {"success": True, "certificate": cert}
    except Exception as e:
        print(f"✗ SSL connection failed: {e}")
        return {"success": False, "error": str(e)}

def test_imap_connection(hostname, port=993):
    """Test IMAP connection"""
    try:
        print(f"Testing IMAP connection to {hostname}:{port}...")
        import imaplib
        mail = imaplib.IMAP4_SSL(hostname, port)
        mail.logout()
        print(f"✓ IMAP connection successful")
        return {"success": True}
    except Exception as e:
        print(f"✗ IMAP connection failed: {e}")
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
        status = "✓ PASS" if result["success"] else "✗ FAIL"
        print(f"{test_name.upper()}: {status}")
        if not result["success"]:
            all_passed = False
    
    print(f"\nOverall Status: {'✓ ALL TESTS PASSED' if all_passed else '✗ SOME TESTS FAILED'}")
    
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

if __name__ == "__main__":
    main()
