<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Libraries;

use PHPUnit\Framework\TestCase;
use App\Invoice\Libraries\Crypt;
use Yiisoft\Security\Crypt as YiisoftCrypt;
use Yiisoft\Security\AuthenticationException;

/**
 * Unit tests for Crypt class
 */
final class CryptTest extends TestCase
{
    private string $testKey = 'base64:TESTTESTTESTTESTTESTTESTTESTTESTTESTTESTTEST==';

    public function testConstructorAndDefaultKey(): void
    {
        $crypt = new Crypt();
        $this->assertInstanceOf(Crypt::class, $crypt);

        $yiiCrypt = new YiisoftCrypt();
        $cryptWithDep = new Crypt($yiiCrypt, $this->testKey);
        $this->assertInstanceOf(Crypt::class, $cryptWithDep);

        // Test getter/setter for defaultKey
        $crypt->setDefaultKey($this->testKey);
        $this->assertEquals($this->testKey, $crypt->getDefaultKey());
    }

    public function testEncodeAndDecode(): void
    {
        $crypt = new Crypt();
        $originalDefaultKey = $crypt->getDefaultKey();

        $testData = 'test data to encrypt';
        // Optionally set a test key for predictable results
        $crypt->setDefaultKey($this->testKey);

        $encoded = $crypt->encode($testData);
        $this->assertIsString($encoded);
        $this->assertNotEquals($testData, $encoded);

        $decoded = $crypt->decode($encoded);
        $this->assertEquals($testData, $decoded);

        // Restore the original default key
        $crypt->setDefaultKey($originalDefaultKey);
    }

    public function testDecodeWithWrongKeyThrows(): void
    {
        $crypt = new Crypt();
        $testData = 'sensitive info';
        $crypt->setDefaultKey($this->testKey);
        $encoded = $crypt->encode($testData);

        $crypt->setDefaultKey('base64:WRONGKEYWRONGKEYWRONGKEYWRONGKEY==');
        $this->expectException(AuthenticationException::class);
        $crypt->decode($encoded);
    }

    public function testEnvVarIsUsedIfPresent(): void
    {
        // Set the environment variable temporarily
        putenv('APP_CRYPT_KEY=' . $this->testKey);

        $crypt = new Crypt();
        $this->assertEquals($this->testKey, $crypt->getDefaultKey());

        // Clean up: Unset env var after test
        putenv('APP_CRYPT_KEY');
    }
}