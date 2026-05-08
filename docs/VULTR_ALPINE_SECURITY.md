# Vultr + Alpine Linux — Reliability & Security Guide

> How to assess and maintain the security of an Alpine Linux instance hosted on Vultr.

---

## Vultr as an Infrastructure Provider

### Trust Signals
- Well-established cloud provider (founded 2014) used by thousands of businesses
- **SOC 2 Type II certified** — independently audited security controls
- **DDoS protection** included on all instances
- 25+ global data centre regions with redundant networking
- 99.99% uptime SLA on compute instances

### Your Responsibilities on Vultr
- Enable the **Vultr Firewall** (cloud firewall) to restrict ports — do not rely solely on the OS firewall
- Use **SSH keys only** — disable password authentication
- Enable **automated backups** and snapshots via the Vultr dashboard
- Monitor for unusual bandwidth or CPU spikes in the Vultr dashboard

---

## Alpine Linux Security

### Why Alpine is Considered Secure
- Minimal attack surface — ships with almost nothing by default
- Uses **musl libc** instead of glibc — avoids a large class of glibc vulnerabilities
- All binaries compiled with **stack smashing protection (SSP)** and **PIE** by default
- `apk` package manager verifies package signatures on install
- Tiny footprint means fewer packages = fewer CVEs to patch

### Keeping Alpine Updated
```bash
# Update package index and upgrade all packages
apk update && apk upgrade

# Check for packages with available upgrades
apk version -l '<'
```

### Security Advisories
Monitor Alpine's official security tracker for CVEs affecting your installed packages:
```
https://security.alpinelinux.org
```

---

## Verifying Your Instance is Healthy

### Check for Unpatched Packages
```bash
apk version -l '<'
```

### Check Login History
```bash
last
```

### Check Open Ports
```bash
ss -tulnp
```

### Check Running Services
```bash
rc-status
```

### Check for Suspicious Processes
```bash
top
ps aux
```

---

## Key Trust Boundaries

| Layer | Responsible Party |
|-------|------------------|
| Physical hardware | Vultr |
| Network & DDoS protection | Vultr |
| Hypervisor security | Vultr |
| OS patching | You |
| Firewall rules (cloud + OS) | You |
| Application security | You |

---

## Hardening Checklist

| Task | Command / Action |
|------|-----------------|
| Disable root SSH login | Set `PermitRootLogin no` in `/etc/ssh/sshd_config` |
| Use SSH keys only | Set `PasswordAuthentication no` in `/etc/ssh/sshd_config` |
| Enable Vultr cloud firewall | Restrict to necessary ports only (80, 443, 22) |
| Enable OS firewall | `apk add iptables` and configure rules |
| Regular OS updates | `apk update && apk upgrade` |
| Monitor open ports | `ss -tulnp` — close anything unexpected |
| Enable automated backups | Configure in Vultr dashboard |
| Remove unused packages | `apk del <package>` for anything not needed |

---

## SSH Hardening Example

```bash
# Edit SSH config
nano /etc/ssh/sshd_config

# Recommended settings
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
Port 2222   # Change default port to reduce noise

# Restart SSH
rc-service sshd restart
```

---

## Basic iptables Firewall for Alpine

```bash
# Install iptables
apk add iptables

# Allow established connections
iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT

# Allow SSH (adjust port if changed)
iptables -A INPUT -p tcp --dport 22 -j ACCEPT

# Allow HTTP and HTTPS
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# Drop everything else
iptables -A INPUT -j DROP

# Save rules
/etc/init.d/iptables save
rc-update add iptables
```

---

## Apache on Alpine

Your stack uses **Apache2** (not Nginx) as the web server, configured with SSL via Let's Encrypt for `yii3i.online`.

### Install Apache
```bash
apk update
apk add apache2 apache2-ssl
```

### Start and Enable Apache
```bash
# Start apache
rc-service apache2 start

# Enable apache to start on boot
rc-update add apache2 default

# Check status
rc-service apache2 status
```

### Key File Locations on Alpine

| File/Folder | Path |
|---|---|
| Main config | `/etc/apache2/httpd.conf` |
| Site configs | `/etc/apache2/conf.d/` |
| SSL config | `/etc/apache2/conf.d/ssl.conf` |
| Web root | `/var/www/invoice/public` |
| Error log | `/var/log/apache2/error.log` |
| Access log | `/var/log/apache2/access.log` |

### Common Commands

```bash
# Test config syntax
httpd -t

# Restart apache
rc-service apache2 restart

# Reload config (no downtime)
rc-service apache2 reload

# Stop apache
rc-service apache2 stop

# View error log
tail -f /var/log/apache2/error.log

# View access log
tail -f /var/log/apache2/access.log
```

### Key Difference from Ubuntu

On Ubuntu you would use `systemctl` — on Alpine you use `rc-service`:

| Ubuntu (systemd) | Alpine (OpenRC) |
|---|---|
| `systemctl start apache2` | `rc-service apache2 start` |
| `systemctl enable apache2` | `rc-update add apache2 default` |
| `systemctl status apache2` | `rc-service apache2 status` |
| `systemctl reload apache2` | `rc-service apache2 reload` |

---

## Bottom Line

Vultr + Alpine is a solid, widely trusted combination. Alpine's minimalism keeps the attack surface small, and Vultr's infrastructure is mature and well-audited. The weak points are almost always at the **application layer** (such as an exposed phpMyAdmin endpoint) or **misconfiguration** — not the underlying platform itself.

---

*See also: `phpmyadmin-vulnerabilities.md` for risks associated with exposing phpMyAdmin on this stack.*

---

*Authored by Claude (Sonnet 4.6), an AI assistant made by Anthropic — March 2026.*
