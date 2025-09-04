import smtplib
import sys
import socket
import ssl
import os
import json
from typing import List, Tuple, Optional
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from email.mime.base import MIMEBase
from email import encoders


def _zoho_smtp_endpoints() -> List[Tuple[str, int, str, Optional[str]]]:
    """Return candidate Zoho SMTP endpoints.

    Each tuple is (host_or_ip, port, security, sni_hostname), where security is one of:
    - "starttls" for explicit TLS on port 587
    - "ssl" for implicit TLS on port 465
    - "starttls_ip" for explicit TLS over direct IP with SNI set to sni_hostname
    """
    return [
        ("smtp.zoho.com", 587, "starttls", None),
        ("smtp.zoho.com", 465, "ssl", None),
        ("smtp.zoho.in", 587, "starttls", None),
        ("smtp.zoho.in", 465, "ssl", None),
        ("smtp.zoho.eu", 587, "starttls", None),
        ("smtp.zoho.eu", 465, "ssl", None),
        # DNS-bypass fallback using observed Zoho SMTP IP; SNI must be smtp.zoho.com
        ("204.141.32.56", 587, "starttls_ip", "smtp.zoho.com"),
    ]


def _create_ssl_context() -> ssl.SSLContext:
    """Create a robust SSL context for Windows compatibility."""
    context = ssl.create_default_context()
    
    # For Windows compatibility, be more permissive with SSL
    context.check_hostname = False
    context.verify_mode = ssl.CERT_NONE
    
    # Set minimum TLS version
    context.minimum_version = ssl.TLSVersion.TLSv1_2
    
    # Add Windows-specific SSL options
    if hasattr(ssl, 'OP_NO_SSLv2'):
        context.options |= ssl.OP_NO_SSLv2
    if hasattr(ssl, 'OP_NO_SSLv3'):
        context.options |= ssl.OP_NO_SSLv3
    
    return context


def _try_send(
    host_or_ip: str,
    port: int,
    security: str,
    email_user: str,
    token: str,
    to_addr: str,
    msg: MIMEMultipart,
    cc: str = "",
    bcc: str = "",
    sni_hostname: Optional[str] = None,
) -> None:
    # First, verify DNS for the hostname to surface gaierror early and allow fallback
    if security in ("starttls", "ssl"):
        try:
            socket.getaddrinfo(host_or_ip, port)
        except socket.gaierror as e:
            raise

    # Create SSL context for better Windows compatibility
    ssl_context = _create_ssl_context()
    
    if security == "ssl":
        server = smtplib.SMTP_SSL(host_or_ip, port, timeout=30, context=ssl_context)
    else:
        server = smtplib.SMTP(host_or_ip, port, timeout=30)
    
    try:
        if security in ("starttls", "starttls_ip"):
            server.ehlo()
            if security == "starttls_ip" and sni_hostname:
                # Use SNI with the Zoho hostname while connecting to an IP address
                server.starttls(context=ssl_context, server_hostname=sni_hostname)
            else:
                server.starttls(context=ssl_context)
            server.ehlo()
        server.login(email_user, token)
        
        # Prepare recipient list
        recipients = [to_addr]
        if cc:
            recipients.extend([addr.strip() for addr in cc.split(',') if addr.strip()])
        if bcc:
            recipients.extend([addr.strip() for addr in bcc.split(',') if addr.strip()])
        
        server.sendmail(email_user, recipients, msg.as_string())
    finally:
        try:
            server.quit()
        except Exception:
            pass


def _diagnose_network() -> None:
    """Run basic network diagnostics for troubleshooting."""
    print("Running network diagnostics...")
    
    # Test basic connectivity
    try:
        socket.create_connection(("8.8.8.8", 53), timeout=5)
        print("✓ Basic internet connectivity: OK")
    except Exception as e:
        print(f"✗ Basic internet connectivity: FAILED ({e})")
    
    # Test DNS resolution
    try:
        socket.gethostbyname("smtp.zoho.com")
        print("✓ DNS resolution for smtp.zoho.com: OK")
    except Exception as e:
        print(f"✗ DNS resolution for smtp.zoho.com: FAILED ({e})")
    
    # Test SSL context creation
    try:
        context = _create_ssl_context()
        print("✓ SSL context creation: OK")
    except Exception as e:
        print(f"✗ SSL context creation: FAILED ({e})")


def main() -> None:
    if len(sys.argv) < 7:
        print("Usage: send_mail.py <provider> <email_user> <token> <to> <subject> <body> [cc] [bcc] [attachments_json]")
        sys.exit(2)

    provider, email_user, token, to_addr, subject, body = sys.argv[1:7]
    cc = sys.argv[7] if len(sys.argv) > 7 else ""
    bcc = sys.argv[8] if len(sys.argv) > 8 else ""
    attachments_json = sys.argv[9] if len(sys.argv) > 9 else "[]"

    print(f"Starting email send process...")
    print(f"Provider: {provider}")
    print(f"From: {email_user}")
    print(f"To: {to_addr}")
    print(f"Subject: {subject}")
    print(f"CC: {cc}")
    print(f"BCC: {bcc}")
    print(f"Attachments JSON: {attachments_json}")

    if provider != "zoho":
        print(f"Unsupported provider: {provider}")
        sys.exit(3)

    # Create multipart message
    msg = MIMEMultipart()
    msg["From"] = email_user
    msg["To"] = to_addr
    msg["Subject"] = subject
    
    if cc:
        msg["Cc"] = cc
    if bcc:
        msg["Bcc"] = bcc

    # Add body
    msg.attach(MIMEText(body, "plain"))

    # Add attachments
    try:
        attachments = json.loads(attachments_json)
        print(f"Processing {len(attachments)} attachments...")
        for i, attachment in enumerate(attachments):
            print(f"Processing attachment {i+1}: {attachment.get('filename', 'unknown')}")
            if os.path.exists(attachment["path"]):
                file_size = os.path.getsize(attachment["path"])
                print(f"  File size: {file_size} bytes")
                with open(attachment["path"], "rb") as f:
                    part = MIMEBase("application", "octet-stream")
                    part.set_payload(f.read())
                    encoders.encode_base64(part)
                    part.add_header(
                        "Content-Disposition",
                        f'attachment; filename= "{attachment["filename"]}"'
                    )
                    msg.attach(part)
                print(f"  Successfully attached: {attachment['filename']}")
            else:
                print(f"  Warning: File not found: {attachment['path']}")
    except (json.JSONDecodeError, KeyError, OSError) as e:
        print(f"Warning: Could not process attachments: {e}")
        print(f"Attachment JSON: {attachments_json}")
        # Continue without attachments

    last_error: Exception | None = None
    for host_or_ip, port, security, sni_hostname in _zoho_smtp_endpoints():
        try:
            _try_send(host_or_ip, port, security, email_user, token, to_addr, msg, cc, bcc, sni_hostname)
            print("OK")
            return
        except socket.gaierror as e:
            # DNS resolution failed for this hostname; try next candidate
            last_error = e
            continue
        except smtplib.SMTPAuthenticationError as e:
            print(f"Auth failed: {e.smtp_error.decode() if isinstance(e.smtp_error, bytes) else e.smtp_error}")
            sys.exit(4)
        except Exception as e:
            # Connection or protocol error; try next candidate
            last_error = e
            continue

    # If we reach here, all candidates failed
    if isinstance(last_error, socket.gaierror) and getattr(last_error, "errno", None) == 11003:
        print("DNS resolution failed for Zoho SMTP hosts (WSANO_DATA 11003).")
        print("Suggestions: 1) Check internet/DNS, 2) Try VPN, 3) Reset Winsock: 'netsh winsock reset' and reboot.")
    elif isinstance(last_error, OSError) and getattr(last_error, "winerror", None) == 10106:
        print("Windows service provider initialization failed (WinError 10106).")
        print("This is a Windows networking issue. Try these solutions:")
        print("1. Run as Administrator: 'netsh winsock reset' then reboot")
        print("2. Reset network stack: 'netsh int ip reset' then reboot")
        print("3. Check Windows Firewall and antivirus settings")
        print("4. Update network drivers")
        print("5. Try using a VPN or different network")
        print("\nRunning diagnostics...")
        _diagnose_network()
    elif isinstance(last_error, ssl.SSLError):
        print(f"SSL/TLS connection failed: {last_error}")
        print("This might be due to SSL certificate issues or network restrictions.")
        print("Try: 1) Check system date/time, 2) Update certificates, 3) Try VPN")
    elif isinstance(last_error, smtplib.SMTPAuthenticationError):
        print(f"SMTP Authentication failed: {last_error}")
        print("Please check your email credentials (username/password or app password).")
        print("For Zoho, you may need to use an App Password instead of your regular password.")
    elif isinstance(last_error, smtplib.SMTPRecipientsRefused):
        print(f"SMTP Recipients refused: {last_error}")
        print("The recipient email address may be invalid or the server rejected it.")
    elif isinstance(last_error, smtplib.SMTPDataError):
        print(f"SMTP Data error: {last_error}")
        print("The email content or attachments may be too large or contain invalid data.")
    elif last_error is not None:
        print(f"Failed to send email: {last_error}")
        print(f"Error type: {type(last_error).__name__}")
        print(f"Error details: {str(last_error)}")
    else:
        print("Failed to send email: Unknown error - all SMTP endpoints failed without specific error details")
        print("This could be due to network connectivity issues, firewall restrictions, or server problems.")
    sys.exit(5)


if __name__ == "__main__":
    main()


