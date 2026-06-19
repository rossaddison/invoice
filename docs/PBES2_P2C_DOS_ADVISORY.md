# PBES2 p2c Unbounded Iteration Count — CPU-Amplification DoS

**June 2026** — Advisory: [GHSA-3prj-6hqw-cm82](https://github.com/advisories/GHSA-3prj-6hqw-cm82)

## Summary

When a JWE uses a password-based key-encryption algorithm
(`PBES2-HS256+A128KW`, `PBES2-HS384+A192KW`, `PBES2-HS512+A256KW`),
`PBES2AESKW::unwrapKey()` reads the `p2c` (PBKDF2 iteration count) parameter
directly from the attacker-controlled JOSE header and passes it to
`hash_pbkdf2()` with no upper bound. The only validation previously performed
was `is_int($p2c) && $p2c > 0`.

An unauthenticated attacker can craft a single JWE with `p2c` set to a very
large value (e.g. `100_000_000` ≈ 87 s CPU, or `PHP_INT_MAX`), forcing a
worker to burn CPU inside PBKDF2 before the key-unwrap can even fail. The
decrypter swallows the eventual exception, so the attacker pays almost nothing
while the server is consumed. JSON General serialization (multiple recipients)
and multi-key JWKSets multiply the cost.

**CWE-400** — Uncontrolled Resource Consumption.

## Status in this project

### Not directly exposed

`web-token/jwt-framework` enters as a **transitive dependency** via
`rossaddison/yii-auth-client`. The project's own JWT work (`GovUk.php`) uses
`phpseclib3` and never registers or invokes any PBES2 algorithm. There is no
call path from application code to `PBES2AESKW::unwrapKey()`.

### Already fixed in installed version

The advisory affects `<= 4.1.6`. The **installed version is `4.1.7`**, which
already contains the fix in both the vendor copy and the `4.2.x` upstream
branch:

```php
// PBES2AESKW.php — current state in 4.1.7 and 4.2.x (safe)
abstract readonly class PBES2AESKW implements KeyWrapping
{
    public const DEFAULT_MAX_COUNT = 1_000_000;

    public function __construct(
        private readonly int $salt_size = 64,
        private readonly int $nb_count  = 4096,
        private readonly int $max_count = self::DEFAULT_MAX_COUNT   // configurable cap
    ) { ... }

    protected function checkHeaderAdditionalParameters(array $header): void
    {
        // ...
        if (! is_int($header['p2c']) || $header['p2c'] <= 0) {
            throw new InvalidArgumentException('The header parameter "p2c" is not valid.');
        }
        if ($header['p2c'] > $this->max_count) {          // hard cap enforced here
            throw new InvalidArgumentException(sprintf(
                'The header parameter "p2c" is too large. The maximum allowed value is %d.',
                $this->max_count
            ));
        }
    }
}
```

`DEFAULT_MAX_COUNT = 1_000_000` is well above legitimate PBKDF2 counts (which
are typically in the range of 600 000 for OWASP-recommended settings) while
completely blocking the `PHP_INT_MAX` / `100_000_000` attack payloads. The cap
is exposed via the constructor so operators can tune it.

## Why no PR was raised

- `4.1.7` (installed) already contains the fix — diff between the installed
  vendor copy and the fork `4.2.x` branch is empty.
- Unlike [GH-114 / JWEDecrypter](JWT_FRAMEWORK_ALGORITHM_CONFUSION_FIX.md),
  there is no residual gap in the upstream branch.

## RFC reference

- RFC 7518 §4.8 — PBES2 key encryption

## Local record

Logged in `snyk-resolved.db` (ID 14) and seeded in `seedVulnDb()` in `m.php`
under key `GHSA-3prj-6hqw-cm82`. Category: **Resolved — Fixed in installed
version 4.1.7** (not a false positive — the vulnerability was real in
`<= 4.1.6`).
