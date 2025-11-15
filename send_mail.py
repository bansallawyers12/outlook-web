import json
import os
import smtplib
import socket
import ssl
import sys
from email import encoders
from email.mime.base import MIMEBase
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from typing import List, Tuple


def _brevo_smtp_endpoints() -> List[Tuple[str, int, str]]:
    host = os.environ.get("BREVO_SMTP_HOST", "smtp-relay.brevo.com")
    starttls_port = int(os.environ.get("BREVO_SMTP_PORT", "587"))
    return [
        (host, starttls_port, "starttls"),
        (host, 465, "ssl"),
    ]


def _create_ssl_context() -> ssl.SSLContext:
    context = ssl.create_default_context()
    context.minimum_version = ssl.TLSVersion.TLSv1_2
    return context


def _try_send(
    host: str,
    port: int,
    security: str,
    login_user: str,
    token: str,
    from_email: str,
    to_addr: str,
    msg: MIMEMultipart,
    cc: str = "",
    bcc: str = "",
    dry_run: bool = False,
) -> None:
    ssl_context = _create_ssl_context()

    if security == "ssl":
        server = smtplib.SMTP_SSL(host, port, timeout=30, context=ssl_context)
    else:
        server = smtplib.SMTP(host, port, timeout=30)

    try:
        if security == "starttls":
            server.ehlo()
            server.starttls(context=ssl_context)
            server.ehlo()

        server.login(login_user, token)

        if dry_run:
            print("Dry run enabled — authenticated successfully, skipping send.")
            return

        recipients = [addr.strip() for addr in [to_addr] if addr]
        if cc:
            recipients.extend([addr.strip() for addr in cc.split(",") if addr.strip()])
        if bcc:
            recipients.extend([addr.strip() for addr in bcc.split(",") if addr.strip()])

        server.sendmail(from_email, recipients, msg.as_string())
    finally:
        try:
            server.quit()
        except Exception:
            pass


def _diagnose_network(host: str) -> None:
    print("Running Brevo network diagnostics...")
    try:
        socket.create_connection(("8.8.8.8", 53), timeout=5)
        print("✓ Internet connectivity: OK")
    except Exception as exc:
        print(f"✗ Internet connectivity: FAILED ({exc})")

    try:
        socket.gethostbyname(host)
        print(f"✓ DNS resolution for {host}: OK")
    except Exception as exc:
        print(f"✗ DNS resolution for {host}: FAILED ({exc})")


def main() -> None:
    if len(sys.argv) < 7:
        print(
            "Usage: send_mail.py <provider> <login_user> <token> "
            "<to> <subject> <body> [cc] [bcc] [attachments_json] [from_email] [--dry-run]"
        )
        sys.exit(2)

    provider = sys.argv[1].lower()
    login_user = sys.argv[2]
    token = sys.argv[3]
    to_addr = sys.argv[4]
    subject = sys.argv[5]
    body = sys.argv[6]
    cc = sys.argv[7] if len(sys.argv) > 7 else ""
    bcc = sys.argv[8] if len(sys.argv) > 8 else ""
    attachments_json = sys.argv[9] if len(sys.argv) > 9 else "[]"

    from_email = login_user
    dry_run = False
    if len(sys.argv) > 10:
        potential = sys.argv[10]
        if potential == "--dry-run":
            dry_run = True
        else:
            from_email = potential
    if len(sys.argv) > 11 and sys.argv[11] == "--dry-run":
        dry_run = True

    print("Starting Brevo SMTP process...")
    print(f"Provider: {provider}")
    print(f"Login user: {login_user}")
    print(f"From: {from_email}")
    print(f"To: {to_addr}")
    print(f"Subject: {subject}")
    print(f"CC: {cc}")
    print(f"BCC: {bcc}")
    print(f"Dry run: {dry_run}")

    if provider != "brevo":
        print(f"Unsupported provider: {provider}")
        sys.exit(3)

    msg = MIMEMultipart()
    msg["From"] = from_email
    msg["To"] = to_addr
    msg["Subject"] = subject
    if cc:
        msg["Cc"] = cc
    if bcc:
        msg["Bcc"] = bcc
    msg.attach(MIMEText(body, "plain"))

    try:
        attachments = json.loads(attachments_json)
        for index, attachment in enumerate(attachments, start=1):
            path = attachment.get("path")
            filename = attachment.get("filename", f"attachment-{index}")
            print(f"Processing attachment {index}: {filename}")
            if path and os.path.exists(path):
                with open(path, "rb") as handle:
                    part = MIMEBase("application", "octet-stream")
                    part.set_payload(handle.read())
                    encoders.encode_base64(part)
                    part.add_header("Content-Disposition", f'attachment; filename="{filename}"')
                    msg.attach(part)
            else:
                print(f"  Warning: attachment path not found ({path})")
    except (json.JSONDecodeError, OSError) as exc:
        print(f"Warning: unable to process attachments: {exc}")

    last_error: Exception | None = None
    endpoints = _brevo_smtp_endpoints()
    host_for_diag = endpoints[0][0]

    for host, port, security in endpoints:
        try:
            _try_send(
                host,
                port,
                security,
                login_user,
                token,
                from_email,
                to_addr,
                msg,
                cc,
                bcc,
                dry_run,
            )
            print("SMTP transaction completed.")
            return
        except smtplib.SMTPAuthenticationError as exc:
            print(f"Authentication failed: {exc}")
            sys.exit(4)
        except Exception as exc:
            last_error = exc
            continue

    if last_error is None:
        last_error = RuntimeError("Unknown SMTP failure.")

    print(f"Failed to send email via Brevo: {last_error}")
    _diagnose_network(host_for_diag)
    sys.exit(5)


if __name__ == "__main__":
    main()
