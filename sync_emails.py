import imaplib
import email
import sys
import json
import socket
import ssl
import traceback
import os
from datetime import datetime, timedelta
from email.utils import parsedate_to_datetime
from pathlib import Path


def resolve_hostname_with_fallback(hostname, port=993):
    """Try multiple methods to resolve hostname"""
    methods = [
        # Method 1: Standard getaddrinfo
        lambda: socket.getaddrinfo(hostname, port, socket.AF_UNSPEC, socket.SOCK_STREAM),
        # Method 2: IPv4 only
        lambda: socket.getaddrinfo(hostname, port, socket.AF_INET, socket.SOCK_STREAM),
        # Method 3: gethostbyname
        lambda: [(socket.AF_INET, socket.SOCK_STREAM, 0, '', (socket.gethostbyname(hostname), port))],
    ]
    
    for i, method in enumerate(methods, 1):
        try:
            print(f"DEBUG: Trying DNS resolution method {i} for {hostname}", file=sys.stderr)
            result = method()
            ip_addresses = [ip[4][0] for ip in result]
            print(f"DEBUG: Method {i} succeeded: {ip_addresses}", file=sys.stderr)
            return ip_addresses
        except Exception as e:
            print(f"DEBUG: Method {i} failed: {e}", file=sys.stderr)
            continue
    
    raise socket.gaierror(f"All DNS resolution methods failed for {hostname}")


def create_imap_connection_with_fallback(hostname, port=993):
    """Create IMAP connection with DNS fallback"""
    try:
        # Try hostname first
        print(f"DEBUG: Attempting IMAP connection to {hostname}:{port}", file=sys.stderr)
        mail = imaplib.IMAP4_SSL(hostname, port)
        print(f"DEBUG: IMAP connection established using hostname", file=sys.stderr)
        return mail
    except socket.gaierror as e:
        print(f"DEBUG: Hostname connection failed: {e}", file=sys.stderr)
        # Try with resolved IP address
        try:
            ip_addresses = resolve_hostname_with_fallback(hostname, port)
            if ip_addresses:
                ip_address = ip_addresses[0]
                print(f"DEBUG: Attempting IMAP connection using IP address: {ip_address}", file=sys.stderr)
                mail = imaplib.IMAP4_SSL(ip_address, port)
                print(f"DEBUG: IMAP connection established using IP address", file=sys.stderr)
                return mail
        except Exception as ip_e:
            print(f"DEBUG: IP address connection also failed: {ip_e}", file=sys.stderr)
        
        # Re-raise the original error if all methods fail
        raise e


def test_network_connectivity(hostname, port=993):
    """Test network connectivity and DNS resolution"""
    debug_info = {
        "hostname": hostname,
        "port": port,
        "dns_resolution": None,
        "socket_connection": None,
        "ssl_connection": None,
        "error_details": []
    }
    
    try:
        # Test DNS resolution with fallback methods
        print(f"DEBUG: Testing DNS resolution for {hostname}", file=sys.stderr)
        debug_info["dns_resolution"] = resolve_hostname_with_fallback(hostname, port)
        print(f"DEBUG: DNS resolved to: {debug_info['dns_resolution']}", file=sys.stderr)
        
        # Test socket connection
        print(f"DEBUG: Testing socket connection to {hostname}:{port}", file=sys.stderr)
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(10)  # 10 second timeout
        result = sock.connect_ex((hostname, port))
        sock.close()
        
        if result == 0:
            debug_info["socket_connection"] = "SUCCESS"
            print(f"DEBUG: Socket connection successful", file=sys.stderr)
        else:
            debug_info["socket_connection"] = f"FAILED (error code: {result})"
            debug_info["error_details"].append(f"Socket connection failed with error code: {result}")
            print(f"DEBUG: Socket connection failed with error code: {result}", file=sys.stderr)
            
        # Test SSL connection
        print(f"DEBUG: Testing SSL connection to {hostname}:{port}", file=sys.stderr)
        context = ssl.create_default_context()
        with socket.create_connection((hostname, port), timeout=10) as sock:
            with context.wrap_socket(sock, server_hostname=hostname) as ssock:
                debug_info["ssl_connection"] = "SUCCESS"
                print(f"DEBUG: SSL connection successful", file=sys.stderr)
                
    except socket.gaierror as e:
        debug_info["error_details"].append(f"DNS resolution failed: {e}")
        print(f"DEBUG: DNS resolution failed: {e}", file=sys.stderr)
    except socket.timeout as e:
        debug_info["error_details"].append(f"Connection timeout: {e}")
        print(f"DEBUG: Connection timeout: {e}", file=sys.stderr)
    except ConnectionRefusedError as e:
        debug_info["error_details"].append(f"Connection refused: {e}")
        print(f"DEBUG: Connection refused: {e}", file=sys.stderr)
    except Exception as e:
        debug_info["error_details"].append(f"Network test error: {e}")
        print(f"DEBUG: Network test error: {e}", file=sys.stderr)
    
    return debug_info


def fetch_emails(provider: str, email_user: str, access_token: str, folder: str = "inbox", 
                 start_date: str = None, end_date: str = None, limit: int = 50):
    if provider == "zoho":
        imap_host = "imap.zoho.com"
    else:
        return {"error": "Unsupported provider"}

    # Perform network diagnostics before attempting connection
    print(f"DEBUG: Starting email sync for {email_user} using {provider}", file=sys.stderr)
    print(f"DEBUG: IMAP host: {imap_host}", file=sys.stderr)
    print(f"DEBUG: Folder: {folder}, Limit: {limit}", file=sys.stderr)
    
    network_debug = test_network_connectivity(imap_host)
    
    try:
        # Use the robust connection method with fallback
        mail = create_imap_connection_with_fallback(imap_host)
        
        print(f"DEBUG: Attempting login for {email_user}", file=sys.stderr)
        mail.login(email_user, access_token)
        print(f"DEBUG: Login successful", file=sys.stderr)
        
        print(f"DEBUG: Selecting folder: {folder}", file=sys.stderr)
        mail.select(folder)
        print(f"DEBUG: Folder selected successfully", file=sys.stderr)

        # Build search criteria
        search_criteria = "ALL"
        if start_date and end_date:
            # Convert dates to IMAP format (DD-MMM-YYYY)
            start_dt = datetime.strptime(start_date, "%Y-%m-%d")
            end_dt = datetime.strptime(end_date, "%Y-%m-%d")
            search_criteria = f'SINCE {start_dt.strftime("%d-%b-%Y")} BEFORE {end_dt.strftime("%d-%b-%Y")}'
        elif start_date:
            start_dt = datetime.strptime(start_date, "%Y-%m-%d")
            search_criteria = f'SINCE {start_dt.strftime("%d-%b-%Y")}'

        status, data = mail.search(None, search_criteria)
        if status != "OK":
            return {"error": "Failed to search mailbox"}

        mail_ids = data[0].split()
        if not mail_ids:
            return []

        # Get the most recent emails up to the limit
        mail_ids = mail_ids[-limit:] if len(mail_ids) > limit else mail_ids
        
        messages = []
        for i in mail_ids:
            status, msg_data = mail.fetch(i, "(RFC822)")
            if status != "OK" or not msg_data:
                continue
                
            msg = email.message_from_bytes(msg_data[0][1])
            
            # Extract message ID for uniqueness
            message_id = msg.get("Message-ID", f"msg_{i.decode()}")
            
            # Parse date properly
            date_str = msg.get("Date")
            parsed_date = None
            if date_str:
                try:
                    parsed_date = parsedate_to_datetime(date_str)
                except:
                    parsed_date = None
            
            # Extract body and attachments
            text_body = ""
            html_body = ""
            attachments = []
            if msg.is_multipart():
                for part in msg.walk():
                    content_type = part.get_content_type()
                    content_disposition = (part.get("Content-Disposition") or "").lower()
                    filename = part.get_filename()

                    # Body parts (exclude attachment disposition)
                    if content_type == "text/plain" and not text_body and 'attachment' not in content_disposition:
                        payload = part.get_payload(decode=True)
                        text_body = (payload.decode('utf-8', errors='ignore') if isinstance(payload, (bytes, bytearray)) else str(payload))
                    elif content_type == "text/html" and not html_body and 'attachment' not in content_disposition:
                        payload = part.get_payload(decode=True)
                        html_body = (payload.decode('utf-8', errors='ignore') if isinstance(payload, (bytes, bytearray)) else str(payload))

                    # Attachment parts
                    if filename or ('attachment' in content_disposition):
                        try:
                            payload = part.get_payload(decode=True) or b""
                            safe_msg_id = (msg.get("Message-ID") or "noid").replace('<','').replace('>','').replace(':','_').replace('/','_').replace('\\','_')
                            base_dir = Path('storage') / 'app' / 'attachments' / safe_msg_id
                            base_dir.mkdir(parents=True, exist_ok=True)
                            name = filename or f"attachment_{len(attachments)+1}"
                            ext = ''
                            if '.' in name:
                                ext = name.split('.')[-1].lower()
                            file_path = base_dir / name
                            with open(file_path, 'wb') as f:
                                f.write(payload)
                            attachments.append({
                                "filename": name,
                                "display_name": name,
                                "content_type": content_type,
                                "file_size": file_path.stat().st_size,
                                "file_path": str(file_path),
                                "content_id": part.get('Content-ID'),
                                "is_inline": 'inline' in content_disposition,
                                "headers": dict(part.items()),
                                "extension": ext,
                            })
                        except Exception:
                            # Skip attachment save errors, continue processing
                            pass
            else:
                payload = msg.get_payload(decode=True)
                text_body = (payload.decode('utf-8', errors='ignore') if isinstance(payload, (bytes, bytearray)) else str(payload))
            
            messages.append({
                "message_id": message_id,
                "from": msg.get("From"),
                "to": msg.get("To"),
                "cc": msg.get("Cc"),
                "reply_to": msg.get("Reply-To"),
                "subject": msg.get("Subject"),
                "date": date_str,
                "parsed_date": parsed_date.isoformat() if parsed_date else None,
                "body": text_body[:4000] if text_body else "",
                "text_body": text_body[:4000] if text_body else "",
                "html_body": html_body if html_body else None,
                "headers": dict(msg.items()),
                "folder": folder,
                "attachments": attachments,
                "has_attachments": len(attachments) > 0
            })

        mail.close()
        mail.logout()
        print(f"DEBUG: Successfully processed {len(messages)} emails", file=sys.stderr)
        return messages
    except imaplib.IMAP4.error as e:
        error_msg = f"IMAP error: {e}"
        print(f"DEBUG: {error_msg}", file=sys.stderr)
        return {
            "error": error_msg,
            "debug_info": {
                "network_test": network_debug,
                "error_type": "IMAP_ERROR",
                "traceback": traceback.format_exc()
            }
        }
    except socket.gaierror as e:
        error_msg = f"DNS resolution failed: {e}"
        print(f"DEBUG: {error_msg}", file=sys.stderr)
        return {
            "error": error_msg,
            "debug_info": {
                "network_test": network_debug,
                "error_type": "DNS_ERROR",
                "traceback": traceback.format_exc()
            }
        }
    except socket.timeout as e:
        error_msg = f"Connection timeout: {e}"
        print(f"DEBUG: {error_msg}", file=sys.stderr)
        return {
            "error": error_msg,
            "debug_info": {
                "network_test": network_debug,
                "error_type": "TIMEOUT_ERROR",
                "traceback": traceback.format_exc()
            }
        }
    except ConnectionRefusedError as e:
        error_msg = f"Connection refused: {e}"
        print(f"DEBUG: {error_msg}", file=sys.stderr)
        return {
            "error": error_msg,
            "debug_info": {
                "network_test": network_debug,
                "error_type": "CONNECTION_REFUSED",
                "traceback": traceback.format_exc()
            }
        }
    except Exception as e:
        error_msg = f"Unexpected error: {e}"
        print(f"DEBUG: {error_msg}", file=sys.stderr)
        print(f"DEBUG: Full traceback: {traceback.format_exc()}", file=sys.stderr)
        return {
            "error": error_msg,
            "debug_info": {
                "network_test": network_debug,
                "error_type": "UNKNOWN_ERROR",
                "traceback": traceback.format_exc()
            }
        }


if __name__ == "__main__":
    provider = sys.argv[1]
    email_user = sys.argv[2]
    token = sys.argv[3]
    folder = sys.argv[4] if len(sys.argv) > 4 else "inbox"
    limit = int(sys.argv[5]) if len(sys.argv) > 5 else 50
    start_date = sys.argv[6] if len(sys.argv) > 6 else None
    end_date = sys.argv[7] if len(sys.argv) > 7 else None
    
    emails = fetch_emails(provider, email_user, token, folder, start_date, end_date, limit)
    print(json.dumps(emails))


