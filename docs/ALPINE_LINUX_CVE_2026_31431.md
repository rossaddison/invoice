# Alpine Linux CVE-2026-31431 Remediation Notes

## Summary
This system was identified as potentially vulnerable to CVE-2026-31431 ("Copy Fail"), a Linux kernel local privilege escalation vulnerability.

The issue affects vulnerable Linux kernel versions and is particularly important in shared-host and containerized environments.

---

# Initial Detection

Initial running kernel:

```bash
uname -r
6.12.49-0-lts
```

The 6.12 LTS branch is reported fixed starting at:

```text
6.12.85+
```

The running kernel was therefore considered vulnerable.

---

# Immediate Mitigation Applied

The vulnerable kernel interface was disabled temporarily using:

```bash
echo 'install algif_aead /bin/false' > /etc/modprobe.d/disable-algif.conf
rmmod algif_aead 2>/dev/null || true
```

Verification:

```bash
lsmod | grep algif_aead
```

Expected result:

- No output.

---

# Alpine Package and Kernel Updates

System packages updated:

```bash
apk update && apk upgrade --available
```

Kernel package upgraded:

```bash
apk add --upgrade linux-lts
```

Available kernel versions observed:

```text
6.12.87-r0
6.18.29-r0
```

---

# Reboot and Verification

System rebooted:

```bash
reboot
```

Post-reboot verification:

```bash
uname -r
6.18.29-0-lts
```

This confirms the system is no longer running the vulnerable 6.12.49 kernel.

---

# Apache Restart Notes (Alpine Linux)

Alpine Linux typically uses OpenRC instead of systemd.

Instead of:

```bash
systemctl restart apache2
```

Use:

```bash
rc-service apache2 restart
```

Or:

```bash
service apache2 restart
```

Check service status:

```bash
rc-service apache2 status
```

Enable Apache at boot:

```bash
rc-update add apache2 default
```

---

# Current Status

- Vulnerable kernel removed from active runtime
- System successfully rebooted into patched kernel
- Temporary mitigation can remain in place as defense-in-depth
- No further urgent remediation required for CVE-2026-31431

---

# Useful Commands Reference

## Check Alpine version

```bash
cat /etc/alpine-release
```

## Check installed kernels

```bash
apk info | grep linux
```

## Verify running kernel

```bash
uname -r
```

## Refresh package repositories

```bash
apk update
```

## Upgrade all packages

```bash
apk upgrade --available
```

## Clean package cache

```bash
apk cache clean
```

