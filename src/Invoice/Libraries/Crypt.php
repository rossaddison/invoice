<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

/**
 * Related logic: see https://github.com/InvoicePlane/InvoicePlane/blob/development/application/libraries/Crypt.php
 *
 * final class Crypt
 */
final class Crypt
{
    private const string DECRYPT_KEY = 'base64:3iqxXZEG5aR0NPvmE4qubcE/sn6nuzXKLrZVRMP3/Ak=';
    private string $decrypt_key = self::DECRYPT_KEY;

    /**
     * Password hashing and verification is now handled via PHP's password_hash and password_verify,
     * which internally use secure, random salts and recommended algorithms like bcrypt or Argon2.
     */

    /**
     * Hash a password using a strong algorithm with automatic secure salt.
     *
     * @param string $password
     * @return string
     */
    public function generate_password(string $password): string
    {
        // Use PASSWORD_DEFAULT (currently bcrypt) or PASSWORD_ARGON2ID for strongest security.
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a password against a hash.
     *
     * @param string $hash
     * @param string $password
     * @return bool
     */
    public function check_password(string $hash, string $password): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Password salts are now handled internally; this always returns null.
     * @deprecated Password salt is handled automatically. Do not use.
     */
    public function getSalt(): ?string
    {
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