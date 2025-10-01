<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

use Yiisoft\Security\Crypt as YiisoftCrypt;

/**
 * Crypt library that uses Yiisoft's Crypt internally for encryption and decryption,
 * and supports a default key for backwards compatibility. The default key can be set
 * via constructor, environment variable (APP_CRYPT_KEY), or fallback to a hardcoded default.
 */
final class Crypt
{
    private YiisoftCrypt $crypt;
    /**
     * Default key used for all encode/decode operations.
     * NOTE: This is a transitional solution. For strong security, use explicit key management long-term.
     */
    private string $defaultKey = 'base64:3iqxXZEG5aR0NPvmE4qubcE/sn6nuzXKLrZVRMP3/Ak=';

    /**
     * Optionally allows passing a configured Yiisoft Crypt instance and/or overriding the default key.
     * The key is resolved in this order: constructor argument > APP_CRYPT_KEY env > hardcoded default.
     */
    public function __construct(?YiisoftCrypt $crypt = null, ?string $defaultKey = null)
    {
        $this->crypt = $crypt ?? new YiisoftCrypt();
        $envKey = getenv('APP_CRYPT_KEY');
        $this->defaultKey = $defaultKey ?? ($envKey !== false ? $envKey : $this->defaultKey);
    }

    /**
     * Getter for the default key.
     *
     * @return string
     */
    public function getDefaultKey(): string
    {
        return $this->defaultKey;
    }

    /**
     * Setter for the default key.
     *
     * @param string $key
     * @return void
     */
    public function setDefaultKey(string $key): void
    {
        $this->defaultKey = $key;
    }

    /**
     * Encrypt data using Yiisoft's Crypt and the default key.
     *
     * @param string $data
     * @return string Base64-encoded encrypted string
     * @throws \Exception
     */
    public function encode(string $data): string
    {
        return base64_encode($this->crypt->encryptByPassword($data, $this->defaultKey));
    }

    /**
     * Decrypt data using Yiisoft's Crypt and the default key.
     *
     * @param string $data Base64-encoded encrypted string
     * @return string
     * @throws \Exception
     */
    public function decode(string $data): string
    {
        return $this->crypt->decryptByPassword(base64_decode($data), $this->defaultKey);
    }
}