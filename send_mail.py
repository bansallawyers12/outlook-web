import smtplib
import sys
import socket
from typing import List, Tuple, Optional
from email.mime.text import MIMEText


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


def _try_send(
    host_or_ip: str,
    port: int,
    security: str,
    email_user: str,
    token: str,
    to_addr: str,
    msg: MIMEText,
    sni_hostname: Optional[str] = None,
) -> None:
    # First, verify DNS for the hostname to surface gaierror early and allow fallback
    if security in ("starttls", "ssl"):
        try:
            socket.getaddrinfo(host_or_ip, port)
        except socket.gaierror as e:
            raise

    if security == "ssl":
        server = smtplib.SMTP_SSL(host_or_ip, port, timeout=30)
    else:
        server = smtplib.SMTP(host_or_ip, port, timeout=30)
    try:
        if security in ("starttls", "starttls_ip"):
            server.ehlo()
            if security == "starttls_ip" and sni_hostname:
                # Use SNI with the Zoho hostname while connecting to an IP address
                server.starttls(server_hostname=sni_hostname)
            else:
                server.starttls()
            server.ehlo()
        server.login(email_user, token)
        server.sendmail(email_user, [to_addr], msg.as_string())
    finally:
        try:
            server.quit()
        except Exception:
            pass


def main() -> None:
    if len(sys.argv) < 7:
        print("Usage: send_mail.py <provider> <email_user> <token> <to> <subject> <body>")
        sys.exit(2)

    provider, email_user, token, to_addr, subject, body = sys.argv[1:7]

    if provider != "zoho":
        print(f"Unsupported provider: {provider}")
        sys.exit(3)

    msg = MIMEText(body)
    msg["From"] = email_user
    msg["To"] = to_addr
    msg["Subject"] = subject

    last_error: Exception | None = None
    for host_or_ip, port, security, sni_hostname in _zoho_smtp_endpoints():
        try:
            _try_send(host_or_ip, port, security, email_user, token, to_addr, msg, sni_hostname)
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
    elif last_error is not None:
        print(f"Failed to send email: {last_error}")
    else:
        print("Failed to send email: Unknown error")
    sys.exit(5)


if __name__ == "__main__":
    main()


