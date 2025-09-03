import imaplib
import email
import sys
import json


def fetch_emails(provider: str, email_user: str, access_token: str):
    if provider == "zoho":
        imap_host = "imap.zoho.com"
    else:
        return {"error": "Unsupported provider"}

    try:
        mail = imaplib.IMAP4_SSL(imap_host)
        mail.login(email_user, access_token)
        mail.select("inbox")

        status, data = mail.search(None, "ALL")
        if status != "OK":
            return {"error": "Failed to search mailbox"}

        mail_ids = data[0].split()[-10:]
        messages = []
        for i in mail_ids:
            status, msg_data = mail.fetch(i, "(RFC822)")
            if status != "OK" or not msg_data:
                continue
            msg = email.message_from_bytes(msg_data[0][1])
            messages.append(
                {
                    "from": msg.get("From"),
                    "subject": msg.get("Subject"),
                    "date": msg.get("Date"),
                }
            )

        return messages
    except Exception as e:
        return {"error": str(e)}


if __name__ == "__main__":
    provider = sys.argv[1]
    email_user = sys.argv[2]
    token = sys.argv[3]
    emails = fetch_emails(provider, email_user, token)
    print(json.dumps(emails))


