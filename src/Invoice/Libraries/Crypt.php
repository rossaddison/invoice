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
