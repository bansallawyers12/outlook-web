#!/usr/bin/env python3
"""
Script to help set up ngrok tunnel for local development
"""

import subprocess
import sys
import time
import json
import requests
from datetime import datetime

def check_ngrok_status():
    """Check if ngrok is running and get tunnel info"""
    try:
        response = requests.get('http://localhost:4040/api/tunnels', timeout=5)
        if response.status_code == 200:
            tunnels = response.json()
            return tunnels.get('tunnels', [])
        return []
    except:
        return []

def start_ngrok_tunnel(port=8000):
    """Start ngrok tunnel for Laravel development server"""
    print(f"Starting ngrok tunnel for port {port}...")
    
    # Check if ngrok is already running
    existing_tunnels = check_ngrok_status()
    if existing_tunnels:
        print("ngrok is already running with the following tunnels:")
        for tunnel in existing_tunnels:
            print(f"  - {tunnel['name']}: {tunnel['public_url']} -> {tunnel['config']['addr']}")
        return existing_tunnels
    
    try:
        # Start ngrok in background
        process = subprocess.Popen([
            'ngrok', 'http', str(port), '--log=stdout'
        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        
        print("Waiting for ngrok to start...")
        time.sleep(3)
        
        # Get tunnel information
        tunnels = check_ngrok_status()
        if tunnels:
            print("\n[OK] ngrok tunnel started successfully!")
            for tunnel in tunnels:
                print(f"  Public URL: {tunnel['public_url']}")
                print(f"  Local URL: {tunnel['config']['addr']}")
                print(f"  Protocol: {tunnel['proto']}")
        else:
            print("[FAIL] Failed to start ngrok tunnel")
            return []
            
        return tunnels
        
    except FileNotFoundError:
        print("[FAIL] ngrok not found. Please install ngrok first.")
        print("  Download from: https://ngrok.com/download")
        return []
    except Exception as e:
        print(f"[FAIL] Error starting ngrok: {e}")
        return []

def stop_ngrok():
    """Stop all ngrok tunnels"""
    try:
        subprocess.run(['ngrok', 'api', 'tunnels', 'delete', '--all'], 
                      capture_output=True, text=True)
        print("[OK] All ngrok tunnels stopped")
    except Exception as e:
        print(f"[FAIL] Error stopping ngrok: {e}")

def main():
    """Main function"""
    if len(sys.argv) < 2:
        print("Usage:")
        print("  python setup_ngrok.py start [port]  - Start ngrok tunnel")
        print("  python setup_ngrok.py stop          - Stop all ngrok tunnels")
        print("  python setup_ngrok.py status        - Check ngrok status")
        print("  python setup_ngrok.py test          - Test network with ngrok")
        sys.exit(1)
    
    command = sys.argv[1].lower()
    
    if command == "start":
        port = int(sys.argv[2]) if len(sys.argv) > 2 else 8000
        tunnels = start_ngrok_tunnel(port)
        
        if tunnels:
            print(f"\n{'='*60}")
            print("NEXT STEPS:")
            print(f"{'='*60}")
            print("1. Start your Laravel development server:")
            print(f"   php artisan serve --port={port}")
            print("2. Your application will be accessible at:")
            for tunnel in tunnels:
                if tunnel['proto'] == 'https':
                    print(f"   {tunnel['public_url']}")
            print("3. Use this URL for testing email connections")
            print("4. Run 'python setup_ngrok.py stop' when done")
    
    elif command == "stop":
        stop_ngrok()
    
    elif command == "status":
        tunnels = check_ngrok_status()
        if tunnels:
            print("ngrok is running with the following tunnels:")
            for tunnel in tunnels:
                print(f"  - {tunnel['name']}: {tunnel['public_url']} -> {tunnel['config']['addr']}")
        else:
            print("ngrok is not running")
    
    elif command == "test":
        tunnels = check_ngrok_status()
        if not tunnels:
            print("[FAIL] No ngrok tunnels found. Start one first with:")
            print("  python setup_ngrok.py start")
            sys.exit(1)
        
        print("Testing network connectivity with ngrok...")
        import test_network
        for tunnel in tunnels:
            if 'zoho.com' in tunnel['public_url'] or 'imap' in tunnel['public_url']:
                # This is just an example - you'd need to set up proper routing
                print(f"Testing tunnel: {tunnel['public_url']}")
                # test_network.run_network_diagnostics(tunnel['public_url'], 443)
    
    else:
        print(f"Unknown command: {command}")
        sys.exit(1)

if __name__ == "__main__":
    main()
