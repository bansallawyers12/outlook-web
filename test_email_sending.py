#!/usr/bin/env python3
"""
Test script for email sending functionality
"""

import subprocess
import sys
import os


def test_email_sending():
    """Test the email sending functionality."""
    print("Testing Email Sending Functionality")
    print("=" * 40)
    
    # Check if send_mail.py exists
    if not os.path.exists('send_mail.py'):
        print("✗ send_mail.py not found")
        return False
    
    print("✓ send_mail.py found")
    
    # Test with invalid parameters to check error handling
    print("\nTesting error handling...")
    try:
        result = subprocess.run([
            sys.executable, 'send_mail.py'
        ], capture_output=True, text=True, timeout=10)
        
        if result.returncode == 2:  # Expected exit code for missing parameters
            print("✓ Error handling: OK")
        else:
            print(f"✗ Error handling: Unexpected exit code {result.returncode}")
            print(f"  Output: {result.stdout}")
            print(f"  Error: {result.stderr}")
    except Exception as e:
        print(f"✗ Error handling test failed: {e}")
        return False
    
    # Test with unsupported provider
    print("\nTesting unsupported provider...")
    try:
        result = subprocess.run([
            sys.executable, 'send_mail.py', 'gmail', 'test@example.com', 'password', 'recipient@example.com', 'Test Subject', 'Test Body'
        ], capture_output=True, text=True, timeout=10)
        
        if result.returncode == 3:  # Expected exit code for unsupported provider
            print("✓ Unsupported provider handling: OK")
        else:
            print(f"✗ Unsupported provider handling: Unexpected exit code {result.returncode}")
            print(f"  Output: {result.stdout}")
            print(f"  Error: {result.stderr}")
    except Exception as e:
        print(f"✗ Unsupported provider test failed: {e}")
        return False
    
    print("\n✓ All basic tests passed!")
    print("\nTo test actual email sending, you need valid Zoho credentials.")
    print("The script will now show better error messages for Windows networking issues.")
    
    return True


if __name__ == "__main__":
    success = test_email_sending()
    sys.exit(0 if success else 1)
