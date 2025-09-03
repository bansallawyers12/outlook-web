import smtplib
import sys
from email.mime.text import MIMEText


def main() -> None:
    if len(sys.argv) < 7:
        print("Usage: send_mail.py <provider> <email_user> <token> <to> <subject> <body>")
        sys.exit(2)

    provider, email_user, token, to_addr, subject, body = sys.argv[1:7]

    if provider == "zoho":
        smtp_host, smtp_port = "smtp.zoho.com", 587
    else:
        print(f"Unsupported provider: {provider}")
        sys.exit(3)

    msg = MIMEText(body)
    msg["From"] = email_user
    msg["To"] = to_addr
    msg["Subject"] = subject

    server = smtplib.SMTP(smtp_host, smtp_port, timeout=30)
    try:
        server.starttls()
        server.login(email_user, token)
        server.sendmail(email_user, [to_addr], msg.as_string())
        print("OK")
    finally:
        try:
            server.quit()
        except Exception:
            pass


if __name__ == "__main__":
    main()


