# Apache2 vs Nginx — Command Comparison & .htaccess Conversion Guide

> Reference guide for migrating or comparing Apache2 and Nginx on Alpine Linux using OpenRC.

---

## Service Management Commands

| Action | Apache2 (Alpine) | Nginx (Alpine) |
|--------|-----------------|----------------|
| Start | `rc-service apache2 start` | `rc-service nginx start` |
| Stop | `rc-service apache2 stop` | `rc-service nginx stop` |
| Restart | `rc-service apache2 restart` | `rc-service nginx restart` |
| Reload (no downtime) | `rc-service apache2 reload` | `rc-service nginx reload` |
| Status | `rc-service apache2 status` | `rc-service nginx status` |
| Enable on boot | `rc-update add apache2 default` | `rc-update add nginx default` |
| Disable on boot | `rc-update del apache2 default` | `rc-update del nginx default` |
| Test config syntax | `httpd -t` | `nginx -t` |

---

## Installation

| Action | Apache2 | Nginx |
|--------|---------|-------|
| Install | `apk add apache2 apache2-ssl` | `apk add nginx` |
| Install PHP module | `apk add php83-apache2` | `apk add php83-fpm` |
| Install headers module | `apk add apache2` (built-in) | Built-in |

---

## Key File Locations

| File/Folder | Apache2 | Nginx |
|-------------|---------|-------|
| Main config | `/etc/apache2/httpd.conf` | `/etc/nginx/nginx.conf` |
| Site configs | `/etc/apache2/conf.d/` | `/etc/nginx/conf.d/` |
| SSL config | `/etc/apache2/conf.d/ssl.conf` | `/etc/nginx/conf.d/ssl.conf` |
| Web root | `/var/www/invoice/public` | `/var/www/invoice/public` |
| Error log | `/var/log/apache2/error.log` | `/var/log/nginx/error.log` |
| Access log | `/var/log/apache2/access.log` | `/var/log/nginx/access.log` |
| Per-dir config | `.htaccess` (per directory) | Not supported — central config only |

---

## Ubuntu vs Alpine Commands

| Action | Ubuntu (systemd) | Alpine Apache2 | Alpine Nginx |
|--------|-----------------|----------------|--------------|
| Start | `systemctl start apache2` | `rc-service apache2 start` | `rc-service nginx start` |
| Stop | `systemctl stop apache2` | `rc-service apache2 stop` | `rc-service nginx stop` |
| Restart | `systemctl restart apache2` | `rc-service apache2 restart` | `rc-service nginx restart` |
| Enable | `systemctl enable apache2` | `rc-update add apache2 default` | `rc-update add nginx default` |
| Status | `systemctl status apache2` | `rc-service apache2 status` | `rc-service nginx status` |

---

## .htaccess — The Key Difference

**.htaccess works in Apache only.** Nginx has no concept of `.htaccess` and ignores these files entirely. Every rule in `.htaccess` must be manually translated into the central Nginx config.

---

## .htaccess Conversion Reference

### URL Rewriting (Yii3 / most PHP frameworks)

**.htaccess (Apache):**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php
```

**Nginx equivalent:**
```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

---

### Deny Access to a File

**.htaccess (Apache):**
```apache
<FilesMatch "^\.env$">
    Require all denied
</FilesMatch>
```

**Nginx equivalent:**
```nginx
location ~* ^/\.env$ {
    deny all;
    return 404;
}
```

---

### Deny Access to Dot Files

**.htaccess (Apache):**
```apache
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

**Nginx equivalent:**
```nginx
location ~ /\. {
    deny all;
    return 404;
}
```

---

### Block Sensitive File Extensions

**.htaccess (Apache):**
```apache
<FilesMatch "\.(env|lock|json|yaml|yml|phar|bat|sh|xml)$">
    Require all denied
</FilesMatch>
```

**Nginx equivalent:**
```nginx
location ~* \.(env|lock|json|yaml|yml|phar|bat|sh|xml)$ {
    deny all;
    return 404;
}
```

---

### Block Specific PHP Files by Name

**.htaccess (Apache):**
```apache
<FilesMatch "^(phpinfo|install|install_writable|requirements)\.php$">
    Require all denied
</FilesMatch>
```

**Nginx equivalent:**
```nginx
location ~* ^/(phpinfo|install|install_writable|requirements)\.php$ {
    deny all;
    return 404;
}
```

---

### Force HTTPS Redirect

**.htaccess (Apache):**
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**Nginx equivalent:**
```nginx
server {
    listen 80;
    server_name yii3i.online www.yii3i.online;
    return 301 https://yii3i.online$request_uri;
}
```

---

### Disable Directory Listing

**.htaccess (Apache):**
```apache
Options -Indexes
```

**Nginx equivalent:**
```nginx
autoindex off;
```

---

### Custom Error Pages

**.htaccess (Apache):**
```apache
ErrorDocument 403 /errors/403.html
ErrorDocument 404 /errors/404.html
```

**Nginx equivalent:**
```nginx
error_page 403 /errors/403.html;
error_page 404 /errors/404.html;
```

---

### Password Protection (Basic Auth)

**.htaccess (Apache):**
```apache
AuthType Basic
AuthName "Restricted"
AuthUserFile /etc/apache2/.htpasswd
Require valid-user
```

**Nginx equivalent:**
```nginx
location /restricted/ {
    auth_basic "Restricted";
    auth_basic_user_file /etc/nginx/.htpasswd;
}
```

---

### Security Headers

**.htaccess (Apache):**
```apache
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set Strict-Transport-Security "max-age=31536000"
```

**Nginx equivalent:**
```nginx
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header Strict-Transport-Security "max-age=31536000";
```

---

### Rate Limiting

**.htaccess / Apache (`mod_evasive`):**
```apache
# Requires mod_evasive — configured in httpd.conf not .htaccess
DOSPageCount        5
DOSSiteCount        50
DOSBlockingPeriod   60
```

**Nginx equivalent (built-in):**
```nginx
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

location /login {
    limit_req zone=login burst=3 nodelay;
    limit_req_status 429;
}
```

> **Note:** Nginx has built-in rate limiting. Apache requires the separate `mod_evasive` module.

---

### PHP Settings

**.htaccess (Apache):**
```apache
php_value upload_max_filesize 20M
php_value post_max_size 20M
php_value max_execution_time 360
```

**Nginx equivalent:**
```nginx
# PHP settings cannot be set in Nginx config
# Must be set in php.ini or a PHP-FPM pool config
# /etc/php83/php.ini
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 360
```

> **Note:** Nginx cannot set PHP values — they must go in `php.ini` directly.

---

## Feature Comparison Summary

| Feature | Apache2 | Nginx |
|---------|---------|-------|
| `.htaccess` support | ✅ Yes | ❌ No |
| Per-directory config | ✅ Yes | ❌ No — central config only |
| Built-in rate limiting | ❌ Needs `mod_evasive` | ✅ Built-in `limit_req` |
| PHP execution | Direct via `mod_php` | Via PHP-FPM only |
| PHP settings in config | ✅ `php_value` in `.htaccess` | ❌ Must use `php.ini` |
| Static file performance | Good | Excellent |
| Memory usage | Higher | Lower |
| Config reload (no downtime) | ✅ `reload` | ✅ `reload` |
| Alpine fit | ✅ Good | ✅ Ideal |
| SSL/TLS | ✅ `mod_ssl` | ✅ Built-in |
| URL rewriting | `mod_rewrite` | `rewrite` / `try_files` |

---

## For This Repository (yii3i.online)

Your stack currently uses **Apache2** with `.htaccess` enabled (`AllowOverride All`). Yii3's `public/.htaccess` handles URL rewriting automatically. If you ever migrate to Nginx:

1. Disable `.htaccess` — it will be silently ignored
2. Add `try_files $uri $uri/ /index.php?$args;` to your Nginx server block
3. Move all `<FilesMatch>` blocks from `ssl.conf` to Nginx `location` blocks
4. Move security headers from `Header always set` to `add_header`
5. Replace `mod_evasive` with Nginx `limit_req`
6. Move PHP settings from `.htaccess` `php_value` directives to `php.ini`

---

*See also: `ssl-conf-explained.md` for a full breakdown of the current Apache ssl.conf.*  
*See also: `why-apache.md` for the role Apache plays in this stack.*

---

*Authored by Claude (Sonnet 4.6), an AI assistant made by Anthropic — March 2026.*
