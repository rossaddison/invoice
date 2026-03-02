# Email Setup for yii3i.online — No Mail Server Required

## Overview

This document summarises how a fully functional company email address (**info@yii3i.online**) was set up without installing or maintaining a mail server on the Vultr hosting server. The solution combines three free services — **ImprovMX**, **Gmail**, and **Gmail SMTP** — to handle both incoming and outgoing email cleanly and securely.

---

## Why No Mail Server on Vultr?

Running a self-hosted mail server on Vultr, while possible, comes with significant overhead:

- Vultr blocks outbound port 25 by default on new accounts, requiring a support ticket to unblock
- Mail servers require ongoing maintenance, security patching, and monitoring
- IP reputation management is complex — a new or shared IP may already be blacklisted
- Deliverability (avoiding spam folders) requires careful configuration of SPF, DKIM, and DMARC records

For most applications — particularly those only sending transactional emails such as signup confirmations — a self-hosted mail server is unnecessary. The setup described here achieves the same result with none of the overhead.

---

## The Solution: ImprovMX + Gmail

### What is ImprovMX?

[ImprovMX](https://improvmx.com) is a free email forwarding service that allows you to create email aliases for your own domain and forward any emails received to an existing inbox. The free plan includes:

- Unlimited email aliases for your domain (e.g. info@yii3i.online)
- Forwarding to any existing email address (e.g. a Gmail inbox)
- No mail server setup required
- Simple DNS-based configuration

### How it Works

When someone sends an email to **info@yii3i.online**:

1. The email arrives at **ImprovMX's servers** (directed there by your domain's MX records)
2. ImprovMX **forwards the email** to your Gmail inbox
3. You read and reply from Gmail as normal

---

## DNS Configuration

### Vultr DNS Manager

Since the domain's nameservers point to Vultr (ns1.vultr.com and ns2.vultr.com), the MX records were added in Vultr's DNS manager — **not** in Namecheap, even though the domain is registered there.

The following MX records were added:

| Type | Name | Data | Priority | TTL |
|------|------|------|----------|-----|
| MX | *(blank/root)* | mx1.improvmx.com | 10 | 300 |
| MX | *(blank/root)* | mx2.improvmx.com | 20 | 300 |

> **Note:** In Vultr's DNS manager, the Name field was left **blank** (rather than using @) to represent the root domain. Vultr interprets a blank name field as the root domain automatically.

---

## Sending Email from Gmail as info@yii3i.online

Gmail was configured to allow sending emails **from** info@yii3i.online while routing them through Gmail's SMTP servers. This means recipients see info@yii3i.online as the sender address.

### Steps Taken

1. A **Google App Password** was generated at myaccount.google.com/apppasswords (requires 2-Step Verification to be enabled)
2. In Gmail Settings → Accounts and Import → Send mail as, **info@yii3i.online** was added as a sender address using the following SMTP settings:

| Setting | Value |
|---------|-------|
| SMTP Host | smtp.gmail.com |
| Port | 587 |
| Username | your.gmail@gmail.com |
| Password | 16-character App Password |
| Encryption | TLS |

3. A verification email was sent to info@yii3i.online, which ImprovMX forwarded to Gmail, and the verification link was clicked to confirm.

---

## Sending Transactional Emails from Yii3 Application

The Yii3 application (hosted on Vultr) was configured to send emails such as signup confirmations through Gmail's SMTP using the **smtps** scheme on port 465.

### params.php Configuration

```php
'yiisoft/mailer-symfony' => [
    'esmtpTransport' => [
        'enabled' => true,
        'useSendMail' => false,
        'scheme' => 'smtps',
        'host' => 'smtp.gmail.com',
        'port' => 465,
        'username' => $_ENV['SYMFONY_MAILER_USERNAME'] ?? '',
        'password' => $_ENV['SYMFONY_MAILER_PASSWORD'] ?? '',
    ],
],
'senderEmail' => 'info@yii3i.online',
'senderName' => 'Your Company Name',
```

### .env File (on Vultr server)

```
SYMFONY_MAILER_USERNAME=your.gmail@gmail.com
SYMFONY_MAILER_PASSWORD=your16characterapppassword
```

> **Important:** Use `$_ENV` to read environment variables in Yii3, not `filter_input(INPUT_ENV, ...)`. The latter only reads system-level environment variables and will not pick up values loaded from a .env file by the application.

---

## Summary

| Component | Service Used | Cost |
|-----------|-------------|------|
| Email aliases & forwarding | ImprovMX (free plan) | Free |
| Inbox for receiving email | Gmail | Free |
| Sending email as info@yii3i.online from Gmail | Gmail SMTP + App Password | Free |
| Sending transactional emails from Yii3 app | Gmail SMTP via Symfony Mailer | Free |
| Mail server on Vultr | **Not required** | N/A |

This setup is reliable, secure, free, and requires no ongoing server maintenance for email.
