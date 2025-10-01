<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Libraries;

use PHPUnit\Framework\TestCase;
use App\Invoice\Libraries\Crypt;

/**
 * Unit tests for Crypt class
 */
final class CryptTest extends TestCase
{
    /**
     * Test that Crypt can be instantiated
     */
    public function testConstructor(): void
    {
        $crypt = new Crypt();
        $this->assertInstanceOf(Crypt::class, $crypt);
    }

    /**
     * Test that getSalt returns null (salt is now handled internally)
     */
    public function testGetSaltReturnsNull(): void
    {
        $crypt = new Crypt();
        $salt = $crypt->getSalt();
        $this->assertNull($salt);
    }

    /**
     * Test encode and decode functionality
     */
    public function testEncodeAndDecode(): void
    {
        $crypt = new Crypt();
        $testData = 'test data to encrypt';

        $encoded = $crypt->encode($testData);
        $decoded = $crypt->decode($encoded);

        $this->assertEquals($testData, $decoded);
    }

    /**
     * Test generate_password method
     */
    public function testGeneratePassword(): void
    {
        $crypt = new Crypt();
        $password = 'testpassword';

        $hashedPassword = $crypt->generate_password($password);

        $this->assertIsString($hashedPassword);
        $this->assertNotEmpty($hashedPassword);
        $this->assertNotEquals($password, $hashedPassword);
    }

    /**
     * Test check_password method
     */
    public function testCheckPassword(): void
    {
        $crypt = new Crypt();
        $password = 'testpassword';

        $hashedPassword = $crypt->generate_password($password);

        $this->assertTrue($crypt->check_password($hashedPassword, $password));
        $this->assertFalse($crypt->check_password($hashedPassword, 'wrongpassword'));
    }
}