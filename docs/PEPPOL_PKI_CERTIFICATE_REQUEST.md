# Peppol PKI Certificate Request (CSR) — Template & Guide

OpenPeppol issues AP certificates from their own CA. You must submit a
Certificate Signing Request (CSR) via the Member Portal.

---

## 1. Subject Fields Required by OpenPeppol

| Field | Value | Example |
|-------|-------|---------|
| `C`  | ISO 3166-1 alpha-2 country code | `GB` |
| `O`  | Registered company name (exact, as in OpenPeppol membership) | `Yii3-i Ltd` |
| `OU` | Always literal `PEPPOL` | `PEPPOL` |
| `CN` | `PNO<country>:<OpenPeppol member ID>` | `PNOGB:0088:1234567890123` |

The `CN` format is defined in the OpenPeppol PKI Policy v2.x:
`PNO<ISO-country-code>:<participant-id-value>`.

---

## 2. Generate the CSR

```bash
# Step 1 — Generate a 4096-bit RSA private key (OpenPeppol requires RSA for now)
openssl genrsa -out /etc/as4/certificates/signing-key.pem 4096

# Step 2 — Create a CSR with the correct subject
openssl req -new \
  -key /etc/as4/certificates/signing-key.pem \
  -out /etc/as4/certificates/signing.csr \
  -subj "/C=GB/O=Yii3-i Ltd/OU=PEPPOL/CN=PNOGB:0088:1234567890123"

# Step 3 — Verify the CSR
openssl req -in /etc/as4/certificates/signing.csr -text -noout
```

Replace:
- `GB` — your country
- `Yii3-i Ltd` — your exact company name from OpenPeppol membership
- `0088:1234567890123` — your Peppol participant ID (GLN or other scheme)

---

## 3. Submit the CSR

1. Log in to https://openpeppol.atlassian.net
2. Navigate to **Certificates → Request Certificate**
3. Choose **Test** or **Production** environment
4. Paste the full content of `/etc/as4/certificates/signing.csr`
5. Submit and wait for approval (typically 1–5 business days)

---

## 4. Install the Issued Certificate

```bash
# Save the certificate from the portal to:
/etc/as4/certificates/signing-cert.pem

# Verify it matches your private key (fingerprints must match)
openssl x509 -noout -modulus -in /etc/as4/certificates/signing-cert.pem | openssl md5
openssl rsa  -noout -modulus -in /etc/as4/certificates/signing-key.pem  | openssl md5

# Verify subject
openssl x509 -in /etc/as4/certificates/signing-cert.pem -noout -subject

# Check validity period
openssl x509 -in /etc/as4/certificates/signing-cert.pem -noout -dates
```

---

## 5. Monitor Expiry

OpenPeppol AP certificates are valid for **12 months**. Set up the monitor:

```bash
# Check now
php yii as4/monitor \
  --signing-cert=/etc/as4/certificates/signing-cert.pem \
  --warn-days=30

# Add to crontab (hourly)
0 * * * * php /var/www/invoice/yii as4/monitor \
  --signing-cert=/etc/as4/certificates/signing-cert.pem \
  --warn-days=30 \
  || mail -s "Peppol cert expiry warning" ops@example.com
```

---

## 6. File Permissions (Security)

```bash
# Private key: owner-read only
chmod 400 /etc/as4/certificates/signing-key.pem
chown www-data:www-data /etc/as4/certificates/signing-key.pem

# Certificate and CSR: owner-readable
chmod 440 /etc/as4/certificates/signing-cert.pem
chmod 440 /etc/as4/certificates/signing.csr
chown www-data:www-data /etc/as4/certificates/signing-cert.pem

# Directory: restricted
chmod 700 /etc/as4/certificates/
chown www-data:www-data /etc/as4/certificates/
```

---

## 7. Renewal Checklist (Annual)

- [ ] Generate a new private key and CSR (do NOT reuse the old key)
- [ ] Submit CSR to OpenPeppol Member Portal before current cert expires
- [ ] Install new cert and verify key match
- [ ] Restart PHP-FPM / web server so the new cert is loaded
- [ ] Confirm `php yii as4/monitor` shows new expiry date
- [ ] Update Peppol Directory (SMP) if your certificate fingerprint changed
