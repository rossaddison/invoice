# How ssl.conf Protects the rossaddison/invoice Repository

> File: `/etc/apache2/conf.d/ssl.conf`  
> Server: Apache2 on Alpine Linux, Vultr  
> Domain: yii3i.online  
> Repository: github.com/rossaddison/invoice  

---

## Overview

The `ssl.conf` file is the primary Apache configuration file controlling how the invoice application is served to the public. It acts as the first line of defence between the internet and the Yii3 PHP application, handling encryption, access control, and security headers before any request reaches application code.

---

## Section 1 — HTTP to HTTPS Redirect

```apache
<VirtualHost *:80>
    ServerName yii3i.online
    ServerAlias www.yii3i.online
    Redirect permanent / https://yii3i.online/
</VirtualHost>
```

### What it does
Any visitor arriving on plain HTTP (port 80) is immediately and permanently redirected to HTTPS (port 443). The `permanent` keyword sends a `301` status code, which browsers and search engines cache — meaning future visits go directly to HTTPS without even touching port 80.

### Why it matters for this repository
The invoice app handles payment data via Stripe, Braintree, and Amazon Pay. Without this redirect, a user could accidentally visit `http://yii3i.online` and transmit login credentials or session cookies in plaintext over the network.

---

## Section 2 — HTTPS Virtual Host

```apache
<VirtualHost *:443>
    ServerName yii3i.online
    ServerAlias www.yii3i.online
    DocumentRoot /var/www/invoice/public
```

### What it does
Defines the main HTTPS virtual host, binding to port 443. The `DocumentRoot` points to `/var/www/invoice/public` — the Yii3 entry point — meaning only files inside `public/` are directly web-accessible. Everything else in the repository (config, source code, composer files) lives outside this directory and is not served by Apache.

### Why it matters for this repository
The repository root contains sensitive files like `.env`, `composer.json`, and `configuration.php`. By setting the document root to `public/` only, these files are never served even without explicit blocking rules.

---

## Section 3 — SSL Certificate

```apache
SSLEngine on
SSLCertificateFile /etc/letsencrypt/live/yii3i.online/fullchain.pem
SSLCertificateKeyFile /etc/letsencrypt/live/yii3i.online/privkey.pem
```

### What it does
Enables SSL/TLS encryption using a Let's Encrypt certificate. All traffic between the user's browser and the server is encrypted. The `fullchain.pem` includes the full certificate chain so browsers fully trust the certificate without warnings.

### Why it matters for this repository
Invoice data, client details, VAT records, and payment gateway interactions are all encrypted in transit. Without this, any network observer (e.g. on a shared network) could intercept session tokens and hijack authenticated sessions.

---

## Section 4 — Modern TLS Only

```apache
SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384
SSLHonorCipherOrder off
```

### What it does
- **`SSLProtocol`** — Disables old, broken protocols (SSLv3, TLS 1.0, TLS 1.1). Only TLS 1.2 and TLS 1.3 are accepted.
- **`SSLCipherSuite`** — Only allows modern, strong cipher suites using ECDHE (Elliptic Curve Diffie-Hellman Ephemeral) key exchange and AES-GCM encryption.
- **`SSLHonorCipherOrder off`** — Lets the client choose the preferred cipher, which is the modern recommended approach.

### Why it matters for this repository
Old TLS versions (1.0, 1.1) have known vulnerabilities (POODLE, BEAST, CRIME). A financial invoicing application must use only modern encryption to protect payment data and comply with PCI DSS standards relevant to Stripe/Braintree integrations.

---

## Section 5 — Security Headers

```apache
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
```

### What each header does

| Header | Protection | Relevance to Invoice App |
|--------|-----------|--------------------------|
| `X-Frame-Options: SAMEORIGIN` | Prevents the app being embedded in an iframe on another site — blocks clickjacking attacks | Protects invoice payment flows from being overlaid with a fake UI |
| `X-Content-Type-Options: nosniff` | Prevents browsers guessing file types — stops MIME-type confusion attacks | Protects uploaded invoice attachments from being executed as scripts |
| `X-XSS-Protection: 1; mode=block` | Instructs older browsers to block reflected XSS attacks | Protects invoice search and filter fields from script injection |
| `Strict-Transport-Security` | Forces HTTPS for 1 year (`max-age=31536000`) on all subdomains — prevents SSL stripping attacks | Ensures `www.yii3i.online` is also always HTTPS |
| `Referrer-Policy` | Only sends the origin (not full URL) as a referrer to external sites | Prevents invoice IDs or client data leaking in referrer headers to third-party services |
| `Permissions-Policy` | Disables browser APIs — geolocation, microphone, camera | Prevents malicious scripts from accessing device hardware |

---

## Section 6 — Directory Configuration

```apache
<Directory /var/www/invoice/public>
    AllowOverride All
    Require all granted
    Options -Indexes
</Directory>
```

### What it does
- **`AllowOverride All`** — Enables `.htaccess` files inside `public/`. Yii3 ships with a `public/.htaccess` that rewrites all URLs through `index.php`. Without this, clean URLs like `/invoice/view/1` would return 404.
- **`Require all granted`** — Allows public access to the `public/` directory (required for the app to be accessible).
- **`Options -Indexes`** — Disables directory listing. If no `index.php` is found in a directory, Apache returns 403 instead of listing all files.

### Why it matters for this repository
Without `Options -Indexes`, a visitor could browse `/public/assets/` and see every CSS, JS, and image file listed — useful reconnaissance for an attacker mapping the application structure.

---

## Section 7 — Blocking Sensitive PHP Files

```apache
<FilesMatch "^(phpinfo|install|install_writable|requirements|configuration|autoload)\.php$">
    Require all denied
</FilesMatch>
```

### What it does
Explicitly denies HTTP access to specific PHP files that exist in the repository root but should never be publicly accessible.

### Files blocked and why

| File | Risk if Accessible |
|------|--------------------|
| `phpinfo.php` | Exposes full PHP config, server paths, and environment variables |
| `install.php` | Could allow re-running the installer, overwriting the database |
| `install_writable.php` | Same risk as `install.php` |
| `requirements.php` | Exposes server software versions — useful to attackers |
| `configuration.php` | May expose database credentials or app secrets |
| `autoload.php` | Exposes Composer autoloader — not intended for direct access |

---

## Section 8 — Blocking Dot Files

```apache
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

### What it does
Blocks any file beginning with a dot (`.`) from being served. This covers a wide range of sensitive files in the repository.

### Files protected

| File | Contains |
|------|---------|
| `.env` | Database credentials, API keys, app secrets |
| `.gitignore` | Reveals project structure to attackers |
| `.snyk` | Security tool configuration |
| `.php-cs-fixer.php` | Code style config |
| `.phpunit.result.cache` | Test result data |
| `.browserslistrc` | Browser targeting config |

---

## Section 9 — Blocking Config and Build Files

```apache
<FilesMatch "\.(env|lock|json|yaml|yml|phar|bat|sh|xml)$">
    Require all denied
</FilesMatch>
```

### What it does
Blocks any file with these extensions regardless of name. This is a broad safety net catching config, dependency, and build files.

### Why it matters for this repository

| Extension | Files in Repo | Risk |
|-----------|--------------|------|
| `.env` | `.env` | Database credentials, secrets |
| `.lock` | `composer.lock` | Reveals exact dependency versions — useful for CVE targeting |
| `.json` | `composer.json`, `package.json` | Reveals all dependencies and versions |
| `.yaml` / `.yml` | `codeception.yml`, `.phpunit-watcher.yml` | Test and build configuration |
| `.phar` | `psalm.phar` | Executable PHP archive |
| `.bat` | `install.bat`, `yii.bat` | Windows batch scripts |
| `.sh` | `sync-check.sh` | Shell scripts |
| `.xml` | `psalm.xml`, `phpunit.xml.dist` | Static analysis and test config |

---

## Section 10 — Logging

```apache
ErrorLog /var/log/apache2/yii3i_error.log
CustomLog /var/log/apache2/yii3i_access.log combined
```

### What it does
Writes all errors and access requests to dedicated log files for `yii3i.online`, separate from other Apache logs.

### Why it matters
Logs are essential for detecting attacks. Repeated 403 responses to blocked files indicate active scanning. Monitor with:

```bash
# Watch for blocked file access attempts
tail -f /var/log/apache2/yii3i_access.log | grep " 403 "

# Watch for errors
tail -f /var/log/apache2/yii3i_error.log
```

---

## How All Sections Work Together

```
Request arrives at yii3i.online
        │
        ▼
Port 80? ──► Redirect 301 to https://yii3i.online (Section 1)
        │
        ▼
Port 443 — TLS 1.2/1.3 handshake (Sections 3 & 4)
        │
        ▼
Security headers added to response (Section 5)
        │
        ▼
Is it a dot file? ──► 403 Denied (Section 8)
Is it a sensitive PHP file? ──► 403 Denied (Section 7)
Is it a config/build file? ──► 403 Denied (Section 9)
        │
        ▼
Is it inside /public/? ──► Serve via Yii3 (Section 6)
        │
        ▼
Log the request (Section 10)
```

---

## Deploying Changes to ssl.conf

```bash
# Test config before applying
httpd -t

# If test passes, restart Apache
rc-service apache2 restart

# Verify the site is still up
curl -I https://yii3i.online
```

---

*See also: `invoice-repo-security-evaluation.md` for the full repository security assessment.*  
*See also: `why-apache.md` for a broader explanation of Apache's role in this stack.*

---

*Authored by Claude (Sonnet 4.6), an AI assistant made by Anthropic — March 2026.*
