# Why You Have Apache and What It Does

> Context: PHP (Yii3) invoice application handling payment data, hosted on a public Vultr/Alpine server at yii3i.online.

---

## Your Actual Stack

Your server runs **Apache2** (not Nginx) as the web server, with:
- SSL/HTTPS via Let's Encrypt
- PHP 8.3 via `mod_php` (`php83-module.conf`)
- `.htaccess` support enabled via `AllowOverride All`
- Domain: `yii3i.online`

---

## What Apache Does for You

### 1. Reverse Proxy Between Internet and PHP
Apache sits between the internet and your PHP app, handling all incoming requests before they reach Yii3.

```
Internet → Apache2 → PHP 8.3 → Yii3 App
```

---

### 2. SSL/HTTPS Termination
Apache handles your Let's Encrypt TLS certificate, encrypting all traffic between users and your server. This means:

- Payment data (Stripe, Braintree, Amazon Pay) is never transmitted in plaintext
- Session cookies and login credentials are protected in transit
- Configured in `/etc/apache2/conf.d/ssl.conf`

```apache
SSLEngine on
SSLCertificateFile /etc/letsencrypt/live/yii3i.online/fullchain.pem
SSLCertificateKeyFile /etc/letsencrypt/live/yii3i.online/privkey.pem
```

---

### 3. `.htaccess` Support
Unlike Nginx, Apache supports `.htaccess` files per directory. Yii3 ships with a `public/.htaccess` that handles URL rewriting — because `AllowOverride All` is set in your config, this works automatically.

```apache
# Yii3 public/.htaccess — works because AllowOverride All is set
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php
```

---

### 4. Access Control — Blocking Sensitive Files
Apache blocks sensitive files via `<FilesMatch>` directives in `ssl.conf`:

```apache
# Block sensitive PHP files
<FilesMatch "^(phpinfo|install|install_writable|requirements|configuration|autoload)\.php$">
    Require all denied
</FilesMatch>

# Block dot files (.env, .gitignore etc.)
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

# Block config and lock files
<FilesMatch "\.(env|lock|json|yaml|yml|phar|bat|sh|xml)$">
    Require all denied
</FilesMatch>
```

---

### 5. Security Headers
Apache adds HTTP security headers to every response via `mod_headers`:

```apache
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

---

### 6. HTTP to HTTPS Redirect
Apache redirects all plain HTTP traffic to HTTPS automatically:

```apache
<VirtualHost *:80>
    ServerName yii3i.online
    ServerAlias www.yii3i.online
    Redirect permanent / https://yii3i.online/
</VirtualHost>
```

---

### 7. Directory Listing Prevention
`Options -Indexes` in your config prevents Apache from listing directory contents if no index file is found — stops attackers from browsing your file structure.

---

## Why This Matters for Your Invoice App Specifically

| Data Type | Protection Apache Provides |
|-----------|--------------------------|
| Payment data (Stripe, Braintree, Amazon Pay) | HTTPS encryption via Let's Encrypt |
| Client invoices and financial records | Access control via `<FilesMatch>` |
| User login credentials | Brute force reducible via `mod_evasive` |
| Database credentials in `.env` | Blocked by `<FilesMatch>` |
| PHP configuration via `phpinfo.php` | Blocked by `<FilesMatch>` |

---

## Apache Rate Limiting (mod_evasive)

Apache does not rate limit by default. To add brute force protection install `mod_evasive`:

```bash
apk add apache2-mod-evasive
```

Add to `/etc/apache2/conf.d/evasive.conf`:

```apache
LoadModule evasive20_module modules/mod_evasive.so

<IfModule mod_evasive20.c>
    DOSHashTableSize    3097
    DOSPageCount        5
    DOSSiteCount        50
    DOSPageInterval     1
    DOSSiteInterval     1
    DOSBlockingPeriod   60
</IfModule>
```

---

## Key Config Files

| File | Purpose |
|------|---------|
| `/etc/apache2/httpd.conf` | Main Apache config |
| `/etc/apache2/conf.d/ssl.conf` | Your site — HTTPS, document root, security |
| `/etc/apache2/conf.d/php83-module.conf` | PHP 8.3 module |
| `/etc/apache2/conf.d/yii3i.eu.org.conf` | Old unused config — safe to delete |
| `/var/www/invoice/public/.htaccess` | Yii3 URL rewriting |

---

## Safe to Delete

The old `yii3i.eu.org.conf` is no longer needed and can be removed:

```bash
rm /etc/apache2/conf.d/yii3i.eu.org.conf
httpd -t && rc-service apache2 restart
```

---

*See also: `vultr-alpine-security.md` for Apache installation and commands on Alpine.*  
*See also: `invoice-repo-security-evaluation.md` for specific Apache blocking rules for this repository.*

---

*Authored by Claude (Sonnet 4.6), an AI assistant made by Anthropic — March 2026.*
