# PHP 8.4 Alpine Linux Setup Guide

## Overview

This document covers the complete setup of PHP 8.4 on Alpine Linux with Apache2,
including all required extensions for a Yii3 application. It also covers the migration
from PHP 8.3 and common pitfalls encountered during the process.

---

## Prerequisites

```bash
apk add unzip nano
```

- `unzip` — required by Composer to extract packages
- `nano` — recommended editor (avoid `vi` unless experienced)

---

## Install PHP 8.4 and Extensions

```bash
apk add \
  php84 \
  php84-apache2 \
  php84-common \
  php84-fpm \
  php84-bcmath \
  php84-ctype \
  php84-curl \
  php84-dom \
  php84-fileinfo \
  php84-gd \
  php84-iconv \
  php84-intl \
  php84-mbstring \
  php84-mysqlnd \
  php84-opcache \
  php84-openssl \
  php84-pdo \
  php84-pdo_mysql \
  php84-pecl-apcu \
  php84-pecl-imagick \
  php84-phar \
  php84-session \
  php84-simplexml \
  php84-sodium \
  php84-tokenizer \
  php84-xml \
  php84-xmlreader \
  php84-xmlwriter \
  php84-zip
```

---

## Apache2 Configuration

### Disable PHP 8.3 module (if migrating)

```bash
mv /etc/apache2/conf.d/php83-module.conf \
   /etc/apache2/conf.d/php83-module.conf.disabled
```

### Find the PHP 8.4 module path

```bash
find / -name "mod_php84.so" 2>/dev/null
```

Expected result: `/usr/lib/apache2/mod_php84.so`

### Create PHP 8.4 Apache config

```bash
nano /etc/apache2/conf.d/php84-module.conf
```

Add:

```apache
LoadModule php_module /usr/lib/apache2/mod_php84.so
```

### Restart Apache

```bash
rc-service apache2 restart
```

### Verify PHP version in browser

```php
<?php echo PHP_VERSION;
```

Should return `8.4.x`.

---

## Setting Nano as Default Editor

Avoid vi — set nano as the default system editor permanently:

```bash
echo "export EDITOR=nano" >> ~/.profile
echo "export VISUAL=nano" >> ~/.profile
source ~/.profile
```

---

## Composer

If Composer fails with missing `unzip`:

```bash
apk add unzip
```

If vendor directory becomes corrupt after a failed update:

```bash
rm -rf /var/www/invoice/vendor
composer install
```

---

## PHP 8.4 Key Changes

### New without parentheses (RFC)
PHP 8.4 natively supports method calls directly on `new` expressions:

```php
// PHP 8.3 and below — required wrapping
(new DateTimeImmutable())->format('Y');

// PHP 8.4 — works natively
new DateTimeImmutable()->format('Y');
```

Both forms remain valid in 8.4. The wrapped form is recommended if you need
to maintain backward compatibility with PHP 8.3.

### Other notable PHP 8.4 changes
- Property hooks
- Asymmetric visibility (`public` get / `protected` set)
- `#[\Deprecated]` attribute
- New array functions: `array_find()`, `array_find_key()`, `array_any()`, `array_all()`
- `new` in initializers now stable

---

## Alpine-Specific Notes

### OpenRC vs systemd
Alpine uses **OpenRC** — not systemd. Use `rc-service` not `systemctl`:

```bash
# Start
rc-service apache2 start

# Stop
rc-service apache2 stop

# Restart
rc-service apache2 restart

# Check all running services
rc-status

# Enable service on boot
rc-update add apache2
rc-update add php-fpm84
```

### Check installed PHP packages

```bash
apk info | grep php84
```

### Check Apache loaded modules

```bash
apache2 -M | grep php
```

---

## Troubleshooting

### HTTP 500 after PHP upgrade
Check Apache error log first:

```bash
tail -50 /var/log/apache2/error.log
```

Then check application log:

```bash
tail -50 /var/www/invoice/runtime/logs/app.log
```

### Runtime permissions error
After composer reinstall, reset permissions:

```bash
chmod -R 775 /var/www/invoice/runtime
chmod -R 775 /var/www/invoice/public/assets
```

### Apache fails to start — module not found
```
Cannot load modules/mod_php84.so: No such file or directory
```

The module path in your config is wrong. Find the correct path:

```bash
find / -name "mod_php84.so" 2>/dev/null
```

Update `/etc/apache2/conf.d/php84-module.conf` with the correct absolute path.

### CLI vs web server PHP version mismatch
The terminal and web server can run different PHP versions:

```bash
# CLI version
php --version

# Web version — check via browser
<?php echo PHP_VERSION;
```

If they differ, the Apache module config is still pointing to the old version.

### Composer vendor corruption
```
DirectoryNotFoundException: The "/var/www/invoice/vendor/composer/XXXXXXXX" directory does not exist
```

Clean and reinstall:

```bash
rm -rf /var/www/invoice/vendor
composer install
```

---

## References

- Alpine Linux packages: `https://pkgs.alpinelinux.org`
- PHP 8.4 migration guide: `https://www.php.net/migration84`
- PHP 8.4 new without parentheses RFC: `https://wiki.php.net/rfc/new_without_parentheses`
- Composer documentation: `https://getcomposer.org/doc/`
