<?php
declare(strict_types=1);

Namespace App\Invoice\Libraries;

/*
 * InvoicePlane
 *
 * @author		InvoicePlane Developers & Contributors
 * @copyright	Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 */

/**
 * Class Crypt
 */
class Crypt
{
    private const DECRYPT_KEY = 'base64:3iqxXZEG5aR0NPvmE4qubcE/sn6nuzXKLrZVRMP3/Ak=';
    private string $decrypt_key = self::DECRYPT_KEY;
    
    public function salt(): string
    {
        return substr(sha1((string)mt_rand()), 0, 22);
    }

    /**
     * @param string $password
     * @param string $salt
     * @return string
     */
    public function generate_password($password, $salt)
    {
        return crypt($password, '$2a$10$' . $salt);
    }

    /**
     * @param string $hash
     * @param string $password
     * @return bool
     */
    public function check_password($hash, $password)
    {
        $new_hash = crypt($password, $hash);

        return ($hash == $new_hash);
    }

    /**
     * @param string $data
     * @return mixed $encrypted
     */
    public function encode(string $data) : mixed
    {
        $key = '';
        if (preg_match("/^base64:(.*)$/", $this->decrypt_key, $matches)) {
            $key = base64_decode($matches[1]);
        }

        /** @var mixed $encrypted */
        $encrypted = Cryptor::Encrypt($data, $key);
        return $encrypted;

    }

    /**
     * @param string $data
     * @return mixed $decrypted
     */
    public function decode(string $data) : mixed
    {
        $key = '';
        if (empty($data)) {
            return '';
        }

        if (preg_match("/^base64:(.*)$/", $this->decrypt_key, $matches)) {
            $key = base64_decode($matches[1]);
        }

        /** @var mixed $decrypted */
        $decrypted = Cryptor::Decrypt($data, $key);
        return $decrypted;

    }
}
