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
     * Test that Crypt can be instantiated without parameters
     */
    public function testConstructorWithoutParameters(): void
    {
        $crypt = new Crypt(null);
        $this->assertInstanceOf(Crypt::class, $crypt);
    }

    /**
     * Test that getSalt returns a non-empty string
     */
    public function testGetSaltReturnsNonEmptyString(): void
    {
        $crypt = new Crypt(null);
        $salt = $crypt->getSalt();
        
        $this->assertIsString($salt);
        $this->assertNotEmpty($salt);
        $this->assertEquals(22, strlen($salt));
    }

    /**
     * Test that constructor accepts and uses custom salt
     */
    public function testConstructorWithCustomSalt(): void
    {
        $customSalt = 'custom1234567890123456';
        $crypt = new Crypt($customSalt);
        
        $this->assertEquals($customSalt, $crypt->getSalt());
    }

    /**
     * Test that salt() method returns same value as getSalt() for backward compatibility
     */
    public function testSaltMethodBackwardCompatibility(): void
    {
        $crypt = new Crypt(null);
        
        $this->assertEquals($crypt->getSalt(), $crypt->salt());
    }

    /**
     * Test that auto-generated salts are consistent within same instance
     */
    public function testSaltConsistencyWithinInstance(): void
    {
        $crypt = new Crypt(null);
        $salt1 = $crypt->getSalt();
        $salt2 = $crypt->getSalt();
        
        $this->assertEquals($salt1, $salt2);
    }

    /**
     * Test that different instances generate different salts
     */
    public function testDifferentInstancesGenerateDifferentSalts(): void
    {
        $crypt1 = new Crypt(null);
        $crypt2 = new Crypt(null);
        
        $this->assertNotEquals($crypt1->getSalt(), $crypt2->getSalt());
    }

    /**
     * Test encode and decode functionality
     */
    public function testEncodeAndDecode(): void
    {
        $crypt = new Crypt(null);
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
        $crypt = new Crypt(null);
        $password = 'testpassword';
        $salt = $crypt->getSalt();
        
        $hashedPassword = $crypt->generate_password($password, $salt);
        
        $this->assertIsString($hashedPassword);
        $this->assertNotEmpty($hashedPassword);
        $this->assertNotEquals($password, $hashedPassword);
    }

    /**
     * Test check_password method
     */
    public function testCheckPassword(): void
    {
        $crypt = new Crypt(null);
        $password = 'testpassword';
        $salt = $crypt->getSalt();
        
        $hashedPassword = $crypt->generate_password($password, $salt);
        
        $this->assertTrue($crypt->check_password($hashedPassword, $password));
        $this->assertFalse($crypt->check_password($hashedPassword, 'wrongpassword'));
    }
}
