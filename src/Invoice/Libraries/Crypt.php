<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

/**
 * final class Crypt
 *
 * Security improvements:
 * - Use cryptographically secure random values (random_bytes / random_int) for salts when legacy bcrypt salts are required.
 * - Prefer PHP's password_hash / password_verify for new password hashing (safe salt generation + algorithm agility).
 * - Maintain a fallback to legacy crypt-based verification to allow rolling migration of existing hashes.
 */
final class Crypt
{
    private const string DECRYPT_KEY = 'base64:3iqxXZEG5aR0NPvmE4qubcE/sn6nuzXKLrZVRMP3/Ak=';
    private string $decrypt_key = self::DECRYPT_KEY;

    public function __construct(private readonly bool $legacyExternalSaltRequired = false)
    {
    }

    /**
     * Generate a cryptographically secure salt suitable for legacy bcrypt-style crypt() usage.
     *
     * Notes:
     * - bcrypt salts require 22 characters from the alphabet "./A-Za-z0-9".
     * - For new password storage use password_hash() which generates its own secure salt automatically.
     *
     * @return string|null 22-character bcrypt-compatible salt
     */
    public function salt(): ?string
    {
        if ($this->legacyExternalSaltRequired) {
            // Use 16 bytes (128 bits) of entropy, base64-encode and adapt to bcrypt alphabet.
            $raw = random_bytes(16);
            // base64 encode then translate '+' -> '.' and remove '=' padding
            $b64 = str_replace('=', '', strtr(base64_encode($raw), '+', '.'));
            return substr($b64, 0, 22);
        }
        return null;
    }

    /**
     * Generate a password hash.
     *
     * - If $salt is provided we will produce a legacy bcrypt-style hash using crypt() to preserve backwards compatibility.
     * - If $salt is omitted (recommended) we'll use password_hash() which produces a secure, salted hash automatically.
     *
     * @param string $password
     * @param string $salt optional legacy bcrypt salt (22 chars) to keep compatibility with older hashes
     * @return string hash suitable for storage
     */
    public function generate_password(string $password, string $salt = ''): string
    {
        if (strlen($salt) > 0 && $this->legacyExternalSaltRequired) {
            // Legacy path: produce bcrypt hash with provided salt (preserve existing behaviour if callers pass a salt)
            // Use $2y$ to be compatible with PHP's bcrypt safe identifier.
            $prefix = '$2y$10$';
            return crypt($password, $prefix . $salt);
        }

        // Recommended path: use PHP's password_hash which handles salt generation securely.
        // PASSWORD_DEFAULT provides algorithm agility.
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a password against a stored hash.
     *
     * - First try password_verify() (covers password_hash-generated hashes).
     * - If that fails, fall back to legacy crypt() verification to support existing stored bcrypt/crypt hashes.
     *
     * When migrating, after verifying a legacy hash you should re-hash the password with
     * password_hash() and store the new value so users gradually move to the stronger default.
     *
     * @param string $hash stored hash
     * @param string $password plaintext password to verify
     * @return bool
     */
    public function check_password(string $hash, string $password): bool
    {
        // Preferred method: password_verify (covers password_hash-produced hashes)
        if (password_verify($password, $hash)) {
            return true;
        }

        // Fallback: legacy crypt() check (keeps compatibility with older bcrypt-style hashes)
        $new_hash = crypt($password, $hash);

        // Use hash_equals for timing-attack-safe comparison
        return hash_equals($hash, $new_hash);
    }

    /**
     * @param string $data
     * @return mixed $encrypted
     */
    public function encode(string $data): mixed
    {
        $key = '';
        if (preg_match('/^base64:(.*)$/', $this->decrypt_key, $matches)) {
            $key = base64_decode($matches[1]);
        }

        /** @var mixed $encrypted */
        return Cryptor::Encrypt($data, $key);
    }

    /**
     * @param string $data
     * @return mixed $decrypted
     */
    public function decode(string $data): mixed
    {
        $key = '';
        if (empty($data)) {
            return '';
        }

        if (preg_match('/^base64:(.*)$/', $this->decrypt_key, $matches)) {
            $key = base64_decode($matches[1]);
        }

        /** @var mixed $decrypted */
        return Cryptor::Decrypt($data, $key);
    }
}
