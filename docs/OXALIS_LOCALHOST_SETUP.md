# Oxalis Access Point — Localhost Setup

**Date:** June 2026

## Two phases

| Phase | What | Certificate needed? |
|-------|------|-------------------|
| A — Mock | WireMock stub in `docker-compose.yml` — fakes Oxalis on port 8181 | No |
| B — Real | Actual Oxalis AS4 container — joins the Peppol acceptance network | Yes |

---

## Phase A — Mock (works today)

`docker-compose.yml` already includes a WireMock stub that listens on port 8181
and returns a canned success response to `POST /outbound/send`.

```powershell
docker-compose up oxalis-mock
```

Set in `.env`:

```dotenv
OXALIS_BASE_URL=http://localhost:8181
```

Click **Peppol Send** on any invoice — the message is persisted as `SENT`
immediately with `messageId: mock-msg-00000001`.  No real network, no certificate.

The stub mapping lives in `oxalis-mock/mappings/outbound-send.json`.
Add further mappings there if you need to simulate error responses.

---

## Phase B — Real Oxalis (acceptance environment)

### Prerequisite — obtain a Peppol test certificate

You cannot join the Peppol network without a certificate issued by a
Peppol-accredited access point provider.  Ask any provider for an
**acceptance-environment** certificate (`.p12` / keystore file):

- Storecove — https://www.storecove.com
- Tickstar — https://www.tickstar.com
- Pagero — https://www.pagero.com

They will issue a `.p12` file and an associated password.

### Add a real Oxalis service to `docker-compose.yml`

Replace (or add alongside) the `oxalis-mock` block:

```yaml
oxalis:
  image: oxalis/oxalis-standalone:latest
  ports:
    - "8080:8080"
  volumes:
    - ./oxalis-config:/oxalis
  environment:
    - JAVA_OPTS=-Doxalis.conf=/oxalis/oxalis.conf
```

### Create `oxalis-config/oxalis.conf`

```hocon
oxalis.keystore {
  path     = /oxalis/your-certificate.p12
  password = "your-keystore-password"
  key.alias    = "your-key-alias"
  key.password = "your-key-password"
}

# Inbound callback — Oxalis calls this after successful delivery
oxalis.as4.inbound.callback.url = "http://host.docker.internal/peppol/inbound/delivery"
```

`host.docker.internal` resolves to the host machine from inside Docker (Windows/Mac).
On Linux use the host's Docker bridge IP (typically `172.17.0.1`).

### Switch `.env`

```dotenv
OXALIS_BASE_URL=http://localhost:8080
PEPPOL_SENDER_ID=0088:1234567890123
PEPPOL_SML_ZONE=acc.edelivery.tech.ec.europa.eu
PEPPOL_SMP_BASE_URL=
```

### Start

```powershell
docker-compose up oxalis
```

### Verify

```powershell
curl http://localhost:8080/outbound/health
```

---

## Retry failed sends

If Oxalis was down when a send was attempted:

```bash
php yii peppol/retry-failed
```

Source: `src/Invoice/Peppol/Console/RetryFailedCommand.php`

---

## Acceptance vs Production

| | Acceptance | Production |
|---|---|---|
| `PEPPOL_SML_ZONE` | `acc.edelivery.tech.ec.europa.eu` | `edelivery.tech.ec.europa.eu` |
| Certificate | Test cert from AP provider | Production cert from AP provider |
| Participant IDs | Test IDs | Live registered IDs |

Always validate fully in acceptance before pointing at production.

---

## Related

- `src/Invoice/Peppol/README.md` — architecture, file roles, 8-step connection guide
- `oxalis-mock/mappings/outbound-send.json` — WireMock stub mapping
- `.env.example` — all four Peppol env vars documented
- `config/common/di/peppol.php` — DI wiring for `PeppolSendService` and `SmpResolver`
