<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries;

use App\Invoice\Libraries\Crypt;
use Codeception\Test\Unit;

class CryptTest extends Unit
{
    private Crypt $crypt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crypt = new Crypt(false);
    }

    public function testSaltGeneration(): void
    {
        $salt1 = $this->crypt->salt();
        $salt2 = $this->crypt->salt();
        
        // Each salt should be exactly 22 characters
        $this->assertSame(22, strlen($salt1));
        $this->assertSame(22, strlen($salt2));
        
        // Each salt should be different (cryptographically random)
        $this->assertNotEquals($salt1, $salt2);
    }

    public function testSaltCharacterSet(): void
    {
        $salt = $this->crypt->salt();
        
        // bcrypt salt should only contain valid characters: ./A-Za-z0-9
        $this->assertMatchesRegularExpression('/^[\.\/A-Za-z0-9]{22}$/', $salt);
    }

    public function testMultipleSaltsAreUnique(): void
    {
        $salts = [];
        
        // Generate 10 salts and ensure they're all unique
        for ($i = 0; $i < 10; $i++) {
            $salts[] = $this->crypt->salt();
        }
        
        $this->assertSame(count($salts), count(array_unique($salts)));
    }

    public function testSaltFormatConsistency(): void
    {
        // Generate multiple salts and verify they all meet bcrypt requirements
        for ($i = 0; $i < 5; $i++) {
            $salt = $this->crypt->salt();
            
            // Length check
            $this->assertSame(22, strlen($salt));
            
            // Character set check (bcrypt alphabet)
            $this->assertMatchesRegularExpression('/^[\.\/A-Za-z0-9]+$/', $salt);
            
            // Should not contain invalid characters
            $this->assertStringNotContainsString('+', $salt);
            $this->assertStringNotContainsString('=', $salt);
        }
    }
}
