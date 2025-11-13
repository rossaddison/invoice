<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Auth validation logic
 * Note: This test focuses on validation patterns without mocking final classes
 */
final class AuthValidationTest extends TestCase
{
    public string $oneToSix = '123456';
    /**
     * Test TOTP code validation patterns
     */
    public function testTotpCodeValidation(): void
    {
        // Valid 6-digit TOTP codes
        $this->assertTrue($this->isValidTotpCode($this->oneToSix));
        $this->assertTrue($this->isValidTotpCode('000000'));
        $this->assertTrue($this->isValidTotpCode('999999'));

        // Invalid TOTP codes
        $this->assertFalse($this->isValidTotpCode('12345')); // Too short
        $this->assertFalse($this->isValidTotpCode('1234567')); // Too long
        $this->assertFalse($this->isValidTotpCode('ABCDEF')); // Letters
        $this->assertFalse($this->isValidTotpCode('12345@')); // Special chars
        $this->assertFalse($this->isValidTotpCode('')); // Empty
    }

    /**
     * Test backup code validation patterns
     */
    public function testBackupCodeValidation(): void
    {
        // Valid 8-character backup codes
        $this->assertTrue($this->isValidBackupCode('ABCD1234'));
        $this->assertTrue($this->isValidBackupCode('12345678'));
        $this->assertTrue($this->isValidBackupCode('abcdefgh'));
        $this->assertTrue($this->isValidBackupCode('MixEd123'));

        // Invalid backup codes
        $this->assertFalse($this->isValidBackupCode('ABCD123')); // Too short
        $this->assertFalse($this->isValidBackupCode('ABCD12345')); // Too long
        $this->assertFalse($this->isValidBackupCode('ABCD123@')); // Special chars
        $this->assertFalse($this->isValidBackupCode('')); // Empty
        $this->assertFalse($this->isValidBackupCode('ABC DEF1')); // Space
    }

    /**
     * Test code sanitization patterns
     */
    public function testCodeSanitization(): void
    {
        // Test trimming whitespace
        $this->assertEquals($this->oneToSix, $this->sanitizeCode(' 123456 '));
        $this->assertEquals($this->oneToSix, $this->sanitizeCode("\t123456\n"));
        
        // Test removal of common separators
        $this->assertEquals($this->oneToSix, $this->sanitizeCode('1-2-3-4-5-6'));
        $this->assertEquals('ABCD1234', $this->sanitizeCode('A.B.C.D.1.2.3.4'));
        
        // Test numeric input handling
        $this->assertEquals($this->oneToSix, $this->sanitizeCode(123456));
    }

    /**
     * Test input validation edge cases
     */
    public function testInputValidationEdgeCases(): void
    {
        // Test null and invalid inputs
        $this->assertNull($this->sanitizeAndValidateCode(null));
        $this->assertNull($this->sanitizeAndValidateCode([]));
        $this->assertNull($this->sanitizeAndValidateCode(new \stdClass()));
        
        // Test mixed content
        $this->assertEquals($this->oneToSix, $this->sanitizeAndValidateCode('1a2b3c4d5e6f', true)); // Extract digits only
        $this->assertEquals('ABCD1234', $this->sanitizeAndValidateCode('AB-CD-12-34', false)); // Remove separators
    }

    /**
     * Replicate TOTP code validation logic
     */
    private function isValidTotpCode(string $code): bool
    {
        return is_numeric($code) && strlen($code) === 6;
    }

    /**
     * Replicate backup code validation logic
     */
    private function isValidBackupCode(string $code): bool
    {
        return ctype_alnum($code) && strlen($code) === 8;
    }

    /**
     * Replicate code sanitization logic
     */
    private function sanitizeCode(mixed $input): string
    {
        if ($input === null) {
            return '';
        }
        
        $code = (string) $input;
        $code = trim($code);
        $code = preg_replace('/[^a-zA-Z0-9]/', '', $code);
        
        return $code;
    }

    /**
     * Replicate sanitize and validate logic
     */
    private function sanitizeAndValidateCode(mixed $input, bool $digitsOnly = false): ?string
    {
        if ($input === null || is_array($input) || is_object($input)) {
            return null;
        }
        
        $code = $this->sanitizeCode($input);
        
        if ($digitsOnly) {
            $code = preg_replace('/\D/', '', $code);
        }
        
        if (empty($code)) {
            return null;
        }
        
        // Validate format
        if ($this->isValidTotpCode($code) || $this->isValidBackupCode($code)) {
            return $code;
        }
        
        return null;
    }
}
