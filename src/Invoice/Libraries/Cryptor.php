<?php

/*******************
 * Cryptor Class
 *******************
 * Source: https://github.com/ioncube/php-openssl-cryptor/
 * Simple example of using the openssl encrypt/decrypt functions that
 * are inadequately documented in the PHP manual.
 *
 * Available under the MIT License
 *
 * The MIT License (MIT)
 * Copyright (c) 2016 ionCube Ltd.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of
 * the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
 * OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT
 * OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

declare(strict_types=1);

namespace App\Invoice\Libraries;

class Cryptor
{
    private string $cipher_algo;
    private string $hash_algo;
    private int $iv_num_bytes = 0;
    private int $format;

    public const int FORMAT_RAW = 0;
    public const int FORMAT_B64 = 1;
    public const int FORMAT_HEX = 2;

    public function __construct(string $cipher_algo = 'aes-256-ctr', string $hash_algo = 'sha256', int $fmt = Cryptor::FORMAT_B64)
    {
        $this->cipher_algo = $cipher_algo;
        $this->hash_algo = $hash_algo;
        $this->format = $fmt;

        if (!in_array($cipher_algo, openssl_get_cipher_methods(true))) {
            throw new \Exception("Cryptor:: - unknown cipher algo {$cipher_algo}");
        }

        if (!in_array($hash_algo, openssl_get_md_methods(true))) {
            throw new \Exception("Cryptor:: - unknown hash algo {$hash_algo}");
        }

        $this->iv_num_bytes = openssl_cipher_iv_length($cipher_algo);
    }

    /**
     *
     * @param string $in
     * @param string $key
     * @param int|null $fmt
     * @return mixed
     * @throws \Exception
     */
    public function encryptString(string $in, string $key, int|null $fmt = null): mixed
    {
        if ($fmt === null) {
            $fmt = $this->format;
        }

        // Build an initialisation vector
        $iv = openssl_random_pseudo_bytes($this->iv_num_bytes, $isStrongCrypto);
        if (!$isStrongCrypto) {
            throw new \Exception("Cryptor::encryptString() - Not a strong key");
        }

        // Hash the key
        $keyhash = openssl_digest($key, $this->hash_algo, true);

        // and encrypt
        $opts =  OPENSSL_RAW_DATA;
        $encrypted = openssl_encrypt($in, $this->cipher_algo, $keyhash, $opts, $iv);

        if ($encrypted === false) {
            throw new \Exception('Cryptor::encryptString() - Encryption failed: ' . openssl_error_string());
        }

        // The result comprises the IV and encrypted data
        $res = $iv . $encrypted;

        // and format the result if required.
        if ($fmt == Cryptor::FORMAT_B64) {
            $res = base64_encode($res);
        } elseif ($fmt == Cryptor::FORMAT_HEX) {
            /** @var array|false|string $res */
            $res = unpack('H*', $res)[1];
        }

        return $res;
    }

    /**
     * Decrypt a string.
     * @param  string $in  String to decrypt.
     * @param  string $key Decryption key.
     * @param  int $fmt Optional override for the input encoding. One of FORMAT_RAW, FORMAT_B64 or FORMAT_HEX.
     * @throws \Exception
     * @return mixed
     */
    public function decryptString($in, $key, $fmt = null): mixed
    {
        if ($fmt === null) {
            $fmt = $this->format;
        }

        $raw = $in;

        // Restore the encrypted data if encoded
        if ($fmt == Cryptor::FORMAT_B64) {
            $raw = base64_decode($in);
        } elseif ($fmt == Cryptor::FORMAT_HEX) {
            $raw = pack('H*', $in);
        }

        // and do an integrity check on the size.
        if (strlen($raw) < $this->iv_num_bytes) {
            throw new \Exception('Cryptor::decryptString() - ' .
                'data length ' . strlen($raw) . " is less than iv length {$this->iv_num_bytes}");
        }

        // Extract the initialisation vector and encrypted data
        $iv = substr($raw, 0, $this->iv_num_bytes ?: 0);
        $raw = substr($raw, $this->iv_num_bytes ?: 0);

        // Hash the key
        $keyhash = openssl_digest($key, $this->hash_algo, true);

        // and decrypt.
        $opts = OPENSSL_RAW_DATA;
        $res = openssl_decrypt($raw, $this->cipher_algo, $keyhash, $opts, $iv);

        if ($res === false) {
            throw new \Exception('Cryptor::decryptString - decryption failed: ' . openssl_error_string());
        }

        return $res;
    }

    /**
     * Static convenience method for encrypting.
     * @param  string $in  String to encrypt.
     * @param  string $key Encryption key.
     * @param  int $fmt Optional override for the output encoding. One of FORMAT_RAW, FORMAT_B64 or FORMAT_HEX.
     * @return mixed
     */
    public static function Encrypt($in, $key, $fmt = null): mixed
    {
        $c = new Cryptor();
        return $c->encryptString($in, $key, $fmt);
    }

    /**
     * Static convenience method for decrypting.
     * @param  string $in  String to decrypt.
     * @param  string $key Decryption key.
     * @param  int $fmt Optional override for the input encoding. One of FORMAT_RAW, FORMAT_B64 or FORMAT_HEX.
     * @return mixed
     */
    public static function Decrypt($in, $key, $fmt = null): mixed
    {
        $c = new Cryptor();
        return $c->decryptString($in, $key, $fmt);
    }
}
