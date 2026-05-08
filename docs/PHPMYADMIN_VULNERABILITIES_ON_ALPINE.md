# phpMyAdmin Open Endpoint Vulnerabilities

> Summary of security risks for a phpMyAdmin endpoint exposed with an IP restriction and URL alias on Alpine Linux.

---

## 1. Authentication Weaknesses

- **Brute force** — No built-in rate limiting; unlimited password attempts possible
- **Default/weak credentials** — Many deployments leave weak MySQL root passwords
- **Credential stuffing** — Leaked credentials from other breaches can be reused
- **No MFA** — phpMyAdmin has no native multi-factor authentication

---

## 2. IP Restriction Bypasses

- **X-Forwarded-For / X-Real-IP spoofing** — If the server trusts proxy headers, IP restrictions can be forged
- **SSRF pivoting** — An attacker with access to another internal service can route requests through it
- **IPv6 gaps** — IP allowlist may only cover IPv4 while the server also listens on IPv6

---

## 3. Alias / Security Through Obscurity

- **Directory enumeration** — Tools like `gobuster` or `ffuf` discover non-standard paths via wordlists
- **Log/referrer leakage** — The alias path appears in server logs, browser history, and HTTP Referer headers
- **Search engine indexing** — Without `robots.txt` or `X-Robots-Tag`, the path can be indexed

---

## 4. phpMyAdmin-Specific CVEs

| CVE | Type | Description |
|-----|------|-------------|
| CVE-2019-12616 | CSRF | Cross-site request forgery on older versions |
| CVE-2018-12613 | LFI → RCE | Local file inclusion leading to remote code execution |
| Various | SQL Injection | Certain UI input fields have had injection vulnerabilities |

- **File read/write** — `LOAD DATA INFILE` / `INTO OUTFILE` can read server files or write webshells if MySQL has file privileges

---

## 5. Alpine Linux / Container-Specific Risks

- **Outdated base image** — Alpine packages may lag behind upstream security patches
- **Running as root** — Default phpMyAdmin Docker images often run as root inside the container
- **Shared kernel** — Container escape vulnerabilities (e.g. `runc` CVEs) can expose the host
- **No seccomp/AppArmor** — Default Docker deployments often lack syscall filtering

---

## 6. No Built-in Rate Limiting

phpMyAdmin has **no true rate limiting**. Its only protection is cookie-based failed login tracking, which is trivially bypassed by:

- Clearing cookies between attempts
- Using different sessions or browsers
- Sending direct POST requests without cookie state

Rate limiting must be enforced externally via Nginx, fail2ban, or a firewall.

---

## 7. Transport & Configuration Issues

- **HTTP instead of HTTPS** — Credentials transmitted in plaintext; session cookies exposed
- **Weak TLS** — Outdated ciphers if HTTPS is manually configured
- **Weak/missing blowfish secret** — `$cfg['blowfish_secret']` must be strong; if missing, cookie encryption is compromised
- **`AllowNoPassword` enabled** — Allows passwordless login if misconfigured

---

## 8. Session & Cookie Attacks

- **Session hijacking** — Without HTTPS and `Secure`/`HttpOnly` flags, session tokens are stealable
- **Session fixation** — Attacker sets a known session ID before the victim logs in
- **Predictable session IDs** — Weak entropy in session generation

---

## Recommended Mitigations

| Control | Implementation |
|---------|---------------|
| **Remove phpMyAdmin** | Use MySQL CLI over SSH instead — eliminates the attack surface entirely |
| **MFA** | Place an auth proxy (Authelia, OAuth2 Proxy) in front of phpMyAdmin |
| **Rate limiting** | Nginx `limit_req`, fail2ban, or iptables |
| **HTTPS** | Enforce TLS with HSTS |
| **Network isolation** | VPN-only access, internal VLAN |
| **Least privilege** | phpMyAdmin MySQL user should have minimal permissions |
| **Keep updated** | Pin to a recent image and rebuild regularly |

---

## Safer Alternatives to phpMyAdmin

| Tool | Method | Why Safer |
|------|--------|-----------|
| MySQL CLI | `mysql -u root -p` via SSH | No web exposure |
| TablePlus / DBeaver | SSH tunnel to port 3306 | GUI, no HTTP surface |
| Adminer | Single file, spin up/down on demand | No persistent exposure |
| VS Code MySQL extension | Over SSH | Already in your workflow |

---

## Uninstall Commands

### Alpine

```bash
# 1. Remove the package
apk del phpmyadmin

# 2. Remove any leftover files
rm -rf /etc/phpmyadmin
rm -rf /usr/share/webapps/phpmyadmin
rm -rf /var/www/phpmyadmin

# 3. Verify gone
apk info | grep phpmyadmin   # should return nothing
```

### Ubuntu

```bash
# 1. Remove the package (keeps config files)
apt remove phpmyadmin

# 2. Full purge including config files
apt purge phpmyadmin

# 3. Clean up dependencies
apt autoremove

# 4. Remove any leftover files
rm -rf /etc/phpmyadmin
rm -rf /usr/share/phpmyadmin
rm -rf /var/lib/phpmyadmin

# 5. Verify gone
dpkg -l | grep phpmyadmin   # should return nothing
```

### Both — Disable the Nginx endpoint

```bash
# Remove the phpmyadmin nginx config block
nano /etc/nginx/conf.d/phpmyadmin.conf   # delete the file or remove the block

# Test and reload nginx
nginx -t && nginx -s reload

# Verify the endpoint is dead
curl -I http://localhost/your-alias   # should return 404
```

### Both — Drop the MySQL user

```sql
DROP USER 'phpmyadmin'@'localhost';
FLUSH PRIVILEGES;
```

> **Note:** `apt purge` on Ubuntu wipes configs automatically, while Alpine's `apk del` leaves config files behind — so the manual `rm -rf` steps matter more on Alpine.

---

*The combination of IP restriction and a URL alias reduces visibility but does not eliminate the attack surface. The safest option is removal.*
