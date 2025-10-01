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
     * Snyk: What is Salting?
     * A salt is a random string that gets attached to a plaintext password before it gets hashed.
     * A hash cannot be reversed but it can be compared with existing generated hash outputs.
     * If a user is using a weak password, that password may have been hashed and stored somewhere for
     * potential hackers to compare against. By adding a salt to the password, the hash output is no
     * longer predictable. This is because it is increasing the uniqueness of the password, thus,
     * the uniqueness of the hash itself.
     */

    /**
     * A salt now must be added to a hash to prevent hash table lookups used by attackers
     * Related logic: see https://cwe.mitre.org/data/definitions/916.html
     * @return string
     */
    public function salt(): string
    {
        /**
         * Previously: return substr(sha1((string)mt_rand()), 0, 22);
         * Use of Password Hash With Insufficient Computational Effort
         * Related logic: see https://www.php.net/manual/en/function.hash-algos.php
         */
        $random = (string) mt_rand();
        $hash = hash('sha256', $random);
        return substr($hash, 0, 22);
    }

    /**
     * @param string $password
     * @param string $salt
     * @return string
     */
    public function generate_password($password, $salt): string
    {
        return crypt($password, '$2a$10$' . $salt);
    }

    /**
     * @param string $hash
     * @param string $password
     * @return bool
     */
    public function check_password($hash, $password): bool
    {
        $new_hash = crypt($password, $hash);

        return $hash == $new_hash;
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
