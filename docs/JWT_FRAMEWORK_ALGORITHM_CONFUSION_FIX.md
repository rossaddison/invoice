# JWT Framework — JWE Algorithm Confusion Fix

**June 2026** — Pull request: [web-token/jwt-framework #658](https://github.com/web-token/jwt-framework/pull/658)

## Background

A security advisory (GitHub issue [#114](https://github.com/web-token/jwt-framework/issues/114))
reported that `JWSVerifier::getAlgorithm()` and `JWEDecrypter` both read the
`alg` parameter from a merged protected + unprotected header, allowing an
attacker to override the integrity-protected algorithm value via the unprotected
header — a classic TOCTOU (Time-of-Check / Time-of-Use) split.

The `web-token/jwt-framework` package enters this project as a transitive
dependency via `rossaddison/yii-auth-client`. The project itself uses
`phpseclib3` directly in `src/Auth/Client/GovUk.php` for all JWT/RSA
operations and never calls `JWSVerifier` or `JWEDecrypter`, so there is no
direct attack surface. The fix was contributed upstream as a public good.

## Analysis

### JWSVerifier — already fixed

Inspection of both the installed version (`4.1.7`) and the upstream `4.2.x`
branch confirmed that `getAlgorithm()` already reads `alg` exclusively from
`getProtectedHeader()`:

```php
// JWSVerifier.php — current state (safe)
private function getAlgorithm(Signature $signature): Algorithm
{
    $protectedHeader = $signature->getProtectedHeader();
    if (! isset($protectedHeader['alg'])) {
        throw new InvalidArgumentException('No "alg" parameter set in the protected header.');
    }
    // ...
}
```

No change needed here.

### JWEDecrypter — vulnerable in both 4.1.7 and 4.2.x

`decryptRecipientKey()` builds a merged header and passes it to both algorithm
getters:

```php
// JWEDecrypter.php — before fix (vulnerable)
$completeHeader = array_merge(
    $jwe->getSharedProtectedHeader(),   // protected
    $jwe->getSharedHeader(),            // unprotected — overwrites protected
    $recipient->getHeader()             // unprotected — overwrites both
);
$key_encryption_algorithm     = $this->getKeyEncryptionAlgorithm($completeHeader);
$content_encryption_algorithm = $this->getContentEncryptionAlgorithm($completeHeader);
```

Because `array_merge()` is last-wins for duplicate string keys, an attacker
placing a different `alg` or `enc` in an unprotected header field overrides the
integrity-protected value. `HeaderCheckerManager` validates the protected header
value; the decrypter then uses the attacker-supplied value — a TOCTOU split.

## Fix (PR #658)

`alg` and `enc` are read exclusively from `getSharedProtectedHeader()`. The
merged `$completeHeader` is preserved for `decryptCEK()` because ECDH-ES
parameters (`epk`, `apu`, `apv`) may legitimately reside in per-recipient
unprotected headers per RFC 7516 §4.6.

`is_string()` guards are added in both getter methods so a malformed non-string
header value cannot reach the `AlgorithmManager`.

```php
// JWEDecrypter.php — after fix
$sharedProtectedHeader = $jwe->getSharedProtectedHeader();
$key_encryption_algorithm     = $this->getKeyEncryptionAlgorithm($sharedProtectedHeader);
$content_encryption_algorithm = $this->getContentEncryptionAlgorithm($sharedProtectedHeader);

// $completeHeader still passed to decryptCEK() for ECDH epk/apu/apv

private function getKeyEncryptionAlgorithm(array $protectedHeader): KeyEncryptionAlgorithm
{
    $alg = $protectedHeader['alg'] ?? null;
    if (! is_string($alg) || $alg === '') {
        throw new InvalidArgumentException(
            'The "alg" parameter must be a non-empty string in the protected header (RFC 7516 §4.1.1).'
        );
    }
    // ...
}

private function getContentEncryptionAlgorithm(array $protectedHeader): ContentEncryptionAlgorithm
{
    $enc = $protectedHeader['enc'] ?? null;
    if (! is_string($enc) || $enc === '') {
        throw new InvalidArgumentException(
            'The "enc" parameter must be a non-empty string in the protected header (RFC 7516 §4.1.2).'
        );
    }
    // ...
}
```

## Change summary

| Component | Before | After |
|-----------|--------|-------|
| `JWSVerifier::getAlgorithm()` | Already reads from protected header only | Unchanged |
| `JWEDecrypter::getKeyEncryptionAlgorithm()` | Received merged header — unprotected wins | Receives `getSharedProtectedHeader()` only |
| `JWEDecrypter::getContentEncryptionAlgorithm()` | Received merged header — unprotected wins | Receives `getSharedProtectedHeader()` only |
| `decryptCEK()` | Received merged header | Unchanged — ECDH parameters legitimately in unprotected headers |
| Type safety | No string check on `alg`/`enc` | `is_string()` guard before `AlgorithmManager::get()` |

## RFC references

- RFC 7516 §4.1.1 — `alg` **MUST** be integrity-protected
- RFC 7516 §4.1.2 — `enc` **MUST** be integrity-protected

## Local record

The advisory is logged in `snyk-resolved.db` (ID 13) and seeded in
`seedVulnDb()` in `m.php` under key `GH-114-web-token-jwt-framework`.
Once Snyk assigns a `SNYK-PHP-xxx` ID for this advisory, add a
corresponding ignore entry in `.snyk` and update the `snyk_id` field in
the seed.
