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
        $this->crypt = new Crypt();
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

    public function testGeneratePasswordWithoutSalt(): void
    {
        $password = 'test_password_123';
        $hash = $this->crypt->generate_password($password);
        
        // Should generate a password_hash compatible hash
        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
        $this->assertNotEquals($password, $hash);
        
        // Should start with $2y$ (PHP password_hash default) or similar
        $this->assertMatchesRegularExpression('/^\$\d[a-z]*\$/', $hash);
    }

    public function testGeneratePasswordWithSalt(): void
    {
        $password = 'test_password_123';
        $salt = $this->crypt->salt();
        $hash = $this->crypt->generate_password($password, $salt);
        
        // Should generate a bcrypt-style hash with provided salt
        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
        $this->assertNotEquals($password, $hash);
        
        // Should start with $2y$10$ (legacy bcrypt format)
        $this->assertStringStartsWith('$2y$10$', $hash);
        
        // Hash should contain the salt in encoded form (first part after $2y$10$)
        $hashParts = explode('$', $hash);
        $this->assertCount(4, $hashParts);
        $this->assertSame('2y', $hashParts[1]);
        $this->assertSame('10', $hashParts[2]);
    }

    public function testGeneratePasswordConsistencyWithSalt(): void
    {
        $password = 'test_password_123';
        $salt = $this->crypt->salt();
        
        $hash1 = $this->crypt->generate_password($password, $salt);
        $hash2 = $this->crypt->generate_password($password, $salt);
        
        // Same password and salt should produce identical hashes
        $this->assertSame($hash1, $hash2);
    }

    public function testGeneratePasswordRandomnessWithoutSalt(): void
    {
        $password = 'test_password_123';
        
        $hash1 = $this->crypt->generate_password($password);
        $hash2 = $this->crypt->generate_password($password);
        
        // Same password without salt should produce different hashes (random salt)
        $this->assertNotEquals($hash1, $hash2);
    }

    public function testCheckPasswordWithModernHash(): void
    {
        $password = 'test_password_123';
        $hash = $this->crypt->generate_password($password);
        
        // Correct password should verify
        $this->assertTrue($this->crypt->check_password($hash, $password));
        
        // Wrong password should not verify
        $this->assertFalse($this->crypt->check_password($hash, 'wrong_password'));
    }

    public function testCheckPasswordWithLegacyHash(): void
    {
        $password = 'test_password_123';
        $salt = $this->crypt->salt();
        $hash = $this->crypt->generate_password($password, $salt);
        
        // Correct password should verify with legacy hash
        $this->assertTrue($this->crypt->check_password($hash, $password));
        
        // Wrong password should not verify
        $this->assertFalse($this->crypt->check_password($hash, 'wrong_password'));
    }

    public function testCheckPasswordWithEmptyPassword(): void
    {
        $password = '';
        $hash = $this->crypt->generate_password($password);
        
        // Empty password should verify against its own hash
        $this->assertTrue($this->crypt->check_password($hash, $password));
        
        // Non-empty password should not verify against empty password hash
        $this->assertFalse($this->crypt->check_password($hash, 'not_empty'));
    }

    public function testCheckPasswordWithSpecialCharacters(): void
    {
        $password = 'p@ssw0rd!#$%^&*()_+-=[]{}|;:,.<>?';
        $hash = $this->crypt->generate_password($password);
        
        $this->assertTrue($this->crypt->check_password($hash, $password));
        $this->assertFalse($this->crypt->check_password($hash, 'different_password'));
    }

    public function testCheckPasswordWithUnicodeCharacters(): void
    {
        $password = 'pāssωörd™€₹中文';
        $hash = $this->crypt->generate_password($password);
        
        $this->assertTrue($this->crypt->check_password($hash, $password));
        $this->assertFalse($this->crypt->check_password($hash, 'different_password'));
    }

    public function testCheckPasswordWithLongPassword(): void
    {
        $password = str_repeat('long_password_test_', 20); // 360 characters
        $hash = $this->crypt->generate_password($password);
        
        $this->assertTrue($this->crypt->check_password($hash, $password));
        $this->assertFalse($this->crypt->check_password($hash, 'different_password'));
    }

    public function testPasswordHashingWorkflow(): void
    {
        $password = 'user_password_123';
        
        // Step 1: Generate hash
        $hash = $this->crypt->generate_password($password);
        
        // Step 2: Store hash (simulated)
        $storedHash = $hash;
        
        // Step 3: Verify password during login
        $this->assertTrue($this->crypt->check_password($storedHash, $password));
        
        // Step 4: Reject wrong password
        $this->assertFalse($this->crypt->check_password($storedHash, 'wrong_password'));
    }

    public function testLegacyToModernMigrationPath(): void
    {
        $password = 'migration_test_password';
        
        // Step 1: Create legacy hash with salt
        $salt = $this->crypt->salt();
        $legacyHash = $this->crypt->generate_password($password, $salt);
        
        // Step 2: Verify legacy hash works
        $this->assertTrue($this->crypt->check_password($legacyHash, $password));
        
        // Step 3: Create modern hash for migration
        $modernHash = $this->crypt->generate_password($password);
        
        // Step 4: Both hashes should verify the same password
        $this->assertTrue($this->crypt->check_password($legacyHash, $password));
        $this->assertTrue($this->crypt->check_password($modernHash, $password));
        
        // Step 5: Hashes should be different formats
        $this->assertNotEquals($legacyHash, $modernHash);
    }

    public function testMultiplePasswordsWithDifferentSalts(): void
    {
        $passwords = ['password1', 'password2', 'password3'];
        $hashes = [];
        
        foreach ($passwords as $password) {
            $salt = $this->crypt->salt();
            $hashes[] = $this->crypt->generate_password($password, $salt);
        }
        
        // All hashes should be different
        $this->assertSame(count($hashes), count(array_unique($hashes)));
        
        // Each password should verify against its corresponding hash
        foreach ($passwords as $index => $password) {
            $this->assertTrue($this->crypt->check_password($hashes[$index], $password));
        }
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

    public function testPasswordStrengthVariations(): void
    {
        $testPasswords = [
            'weak',
            'StrongPassword123!',
            '12345',
            'a',
            str_repeat('x', 50), // Well under bcrypt max length
        ];
        
        foreach ($testPasswords as $password) {
            $hash = $this->crypt->generate_password($password);
            
            $this->assertTrue($this->crypt->check_password($hash, $password));
            $this->assertFalse($this->crypt->check_password($hash, $password . '_modified'));
        }
    }

    public function testCaseSensitivity(): void
    {
        $password = 'CaseSensitivePassword';
        $hash = $this->crypt->generate_password($password);
        
        $this->assertTrue($this->crypt->check_password($hash, $password));
        $this->assertFalse($this->crypt->check_password($hash, strtolower($password)));
        $this->assertFalse($this->crypt->check_password($hash, strtoupper($password)));
    }
}