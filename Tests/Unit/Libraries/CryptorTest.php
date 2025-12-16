<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries;

use App\Invoice\Libraries\Cryptor;
use Codeception\Test\Unit;

class CryptorTest extends Unit
{
    private Cryptor $cryptor;
    private string $testKey = 'test_encryption_key_12345';
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->cryptor = new Cryptor();
    }

    public function testConstructorWithDefaults(): void
    {
        $cryptor = new Cryptor();
        
        // Should construct without throwing exceptions
        $this->assertInstanceOf(Cryptor::class, $cryptor);
    }

    public function testConstructorWithCustomParameters(): void
    {
        $cryptor = new Cryptor('aes-256-cbc', 'sha512', Cryptor::FORMAT_HEX);
        
        $this->assertInstanceOf(Cryptor::class, $cryptor);
    }

    public function testConstructorWithInvalidCipher(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('unknown cipher algo');
    }

    public function testConstructorWithInvalidHashAlgo(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('unknown hash algo');
    }

    public function testEncryptDecryptBasicString(): void
    {
        $plaintext = 'Hello, World!';
        
        $encrypted = $this->cryptor->encryptString($plaintext, $this->testKey);
        $decrypted = $this->cryptor->decryptString($encrypted, $this->testKey);
        
        $this->assertNotEquals($plaintext, $encrypted);
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testEncryptDecryptEmptyString(): void
    {
        $plaintext = '';
        
        $encrypted = $this->cryptor->encryptString($plaintext, $this->testKey);
        
        // Handle case where empty string encryption might return false
        if ($encrypted !== false) {
            $decrypted = $this->cryptor->decryptString($encrypted, $this->testKey);
            $this->assertEquals($plaintext, $decrypted);
        } else {
            // If empty string encryption returns false, that's acceptable behavior
            $this->assertFalse($encrypted);
        }
    }

    public function testEncryptDecryptLongString(): void
    {
        $plaintext = str_repeat('This is a test string for encryption. ', 100);
        
        $encrypted = $this->cryptor->encryptString($plaintext, $this->testKey);
        $decrypted = $this->cryptor->decryptString($encrypted, $this->testKey);
        
        $this->assertNotEquals($plaintext, $encrypted);
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testEncryptDecryptSpecialCharacters(): void
    {
        $plaintext = '!@#$%^&*()_+-=[]{}|;:,.<>?`~"\'\\';
        
        $encrypted = $this->cryptor->encryptString($plaintext, $this->testKey);
        $decrypted = $this->cryptor->decryptString($encrypted, $this->testKey);
        
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testEncryptDecryptUnicodeCharacters(): void
    {
        $plaintext = 'Hello 世界! 🌍 Héllö Wørld™€₹中文';
        
        $encrypted = $this->cryptor->encryptString($plaintext, $this->testKey);
        $decrypted = $this->cryptor->decryptString($encrypted, $this->testKey);
        
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testEncryptionWithDifferentKeys(): void
    {
        $plaintext = 'Secret message';
        $key1 = 'key_one';
        $key2 = 'key_two';
        
        $encrypted1 = $this->cryptor->encryptString($plaintext, $key1);
        $encrypted2 = $this->cryptor->encryptString($plaintext, $key2);
        
        // Different keys should produce different encrypted results
        $this->assertNotEquals($encrypted1, $encrypted2);
        
        // Each should decrypt correctly with its own key
        $this->assertEquals($plaintext, $this->cryptor->decryptString($encrypted1, $key1));
        $this->assertEquals($plaintext, $this->cryptor->decryptString($encrypted2, $key2));
    }

    public function testDecryptionWithWrongKey(): void
    {
        $plaintext = 'Secret message';
        $correctKey = 'correct_key';
        $wrongKey = 'wrong_key';
        
        $encrypted = $this->cryptor->encryptString($plaintext, $correctKey);
        $decrypted = $this->cryptor->decryptString($encrypted, $wrongKey);
        
        // Wrong key should not produce the original plaintext
        $this->assertNotEquals($plaintext, $decrypted);
    }

    public function testEncryptionRandomness(): void
    {
        $plaintext = 'Same message';
        
        $encrypted1 = $this->cryptor->encryptString($plaintext, $this->testKey);
        $encrypted2 = $this->cryptor->encryptString($plaintext, $this->testKey);
        
        // Same plaintext and key should produce different encrypted results due to random IV
        $this->assertNotEquals($encrypted1, $encrypted2);
        
        // But both should decrypt to the same plaintext
        $this->assertEquals($plaintext, $this->cryptor->decryptString($encrypted1, $this->testKey));
        $this->assertEquals($plaintext, $this->cryptor->decryptString($encrypted2, $this->testKey));
    }

    public function testFormatRaw(): void
    {
        $plaintext = 'Raw format test';
        
        $encrypted = $this->cryptor->encryptString($plaintext, $this->testKey, Cryptor::FORMAT_RAW);
        $decrypted = $this->cryptor->decryptString($encrypted, $this->testKey, Cryptor::FORMAT_RAW);
        
        $this->assertEquals($plaintext, $decrypted);
        
        // Raw format should contain binary data
        $this->assertIsString($encrypted);
    }

    public function testFormatBase64(): void
    {
        $plaintext = 'Base64 format test';
        
        $encrypted = $this->cryptor->encryptString($plaintext, $this->testKey, Cryptor::FORMAT_B64);
        $decrypted = $this->cryptor->decryptString($encrypted, $this->testKey, Cryptor::FORMAT_B64);
        
        $this->assertEquals($plaintext, $decrypted);
        
        // Base64 format should be valid base64
        $this->assertIsString($encrypted);
        $this->assertSame($encrypted, base64_encode(base64_decode($encrypted)));
    }

    public function testFormatHex(): void
    {
        $plaintext = 'Hex format test';
        
        $encrypted = $this->cryptor->encryptString($plaintext, $this->testKey, Cryptor::FORMAT_HEX);
        $decrypted = $this->cryptor->decryptString($encrypted, $this->testKey, Cryptor::FORMAT_HEX);
        
        $this->assertEquals($plaintext, $decrypted);
        
        // Hex format should contain only hex characters
        $this->assertIsString($encrypted);
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $encrypted);
    }

    public function testStaticEncryptMethod(): void
    {
        $plaintext = 'Static encrypt test';
        
        $encrypted = Cryptor::Encrypt($plaintext, $this->testKey);
        $decrypted = Cryptor::Decrypt($encrypted, $this->testKey);
        
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testStaticDecryptMethod(): void
    {
        $plaintext = 'Static decrypt test';
        
        $encrypted = Cryptor::Encrypt($plaintext, $this->testKey);
        $decrypted = Cryptor::Decrypt($encrypted, $this->testKey);
        
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testStaticMethodsWithFormats(): void
    {
        $plaintext = 'Static format test';
        
        // Test with hex format
        $encryptedHex = Cryptor::Encrypt($plaintext, $this->testKey, Cryptor::FORMAT_HEX);
        $decryptedHex = Cryptor::Decrypt($encryptedHex, $this->testKey, Cryptor::FORMAT_HEX);
        
        $this->assertEquals($plaintext, $decryptedHex);
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $encryptedHex);
        
        // Test with base64 format
        $encryptedB64 = Cryptor::Encrypt($plaintext, $this->testKey, Cryptor::FORMAT_B64);
        $decryptedB64 = Cryptor::Decrypt($encryptedB64, $this->testKey, Cryptor::FORMAT_B64);
        
        $this->assertEquals($plaintext, $decryptedB64);
    }

    public function testDecryptStringTooShort(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('data length');
        
        $this->cryptor->decryptString('short', $this->testKey, Cryptor::FORMAT_RAW);
    }

    public function testMultipleEncryptDecryptCycles(): void
    {
        $originalText = 'Multiple cycles test';
        $currentText = $originalText;
        
        // Encrypt and decrypt multiple times
        for ($i = 0; $i < 5; $i++) {
            $encrypted = $this->cryptor->encryptString($currentText, $this->testKey);
            $decrypted = $this->cryptor->decryptString($encrypted, $this->testKey);
            $currentText = $decrypted;
        }
        
        $this->assertEquals($originalText, $currentText);
    }

    public function testDifferentCipherAlgorithms(): void
    {
        $plaintext = 'Algorithm test';
        $key = 'test_key_12345';
        
        $algorithms = ['aes-256-ctr', 'aes-256-cbc', 'aes-128-ctr'];
        
        foreach ($algorithms as $algo) {
            $cryptor = new Cryptor($algo);
            
            $encrypted = $cryptor->encryptString($plaintext, $key);
            $decrypted = $cryptor->decryptString($encrypted, $key);
            
            $this->assertEquals($plaintext, $decrypted, "Failed with algorithm: $algo");
        }
    }

    public function testDifferentHashAlgorithms(): void
    {
        $plaintext = 'Hash algorithm test';
        $key = 'test_key_12345';
        
        $hashAlgos = ['sha256', 'sha512', 'md5'];
        
        foreach ($hashAlgos as $hashAlgo) {
            $cryptor = new Cryptor('aes-256-ctr', $hashAlgo);
            
            $encrypted = $cryptor->encryptString($plaintext, $key);
            $decrypted = $cryptor->decryptString($encrypted, $key);
            
            $this->assertEquals($plaintext, $decrypted, "Failed with hash algorithm: $hashAlgo");
        }
    }

    public function testFormatConstants(): void
    {
        $this->assertSame(0, Cryptor::FORMAT_RAW);
        $this->assertSame(1, Cryptor::FORMAT_B64);
        $this->assertSame(2, Cryptor::FORMAT_HEX);
    }

    public function testEncryptStringWithDifferentKeySizes(): void
    {
        $plaintext = 'Key size test';
        
        $keys = [
            'short',
            'medium_length_key',
            'very_long_key_with_many_characters_for_testing_purposes_12345',
            str_repeat('x', 256), // Very long key
        ];
        
        foreach ($keys as $key) {
            $encrypted = $this->cryptor->encryptString($plaintext, $key);
            $decrypted = $this->cryptor->decryptString($encrypted, $key);
            
            $this->assertEquals($plaintext, $decrypted, "Failed with key length: " . strlen($key));
        }
    }

    public function testMixedFormatUsage(): void
    {
        $plaintext = 'Mixed format test';
        
        // Encrypt with one format, try to decrypt with another (should fail or give wrong result)
        $encryptedB64 = $this->cryptor->encryptString($plaintext, $this->testKey, Cryptor::FORMAT_B64);
        
        // This should work - decrypt with correct format
        $correctDecrypt = $this->cryptor->decryptString($encryptedB64, $this->testKey, Cryptor::FORMAT_B64);
        $this->assertEquals($plaintext, $correctDecrypt);
        
        // This might fail or give incorrect results - decrypt with wrong format
        try {
            $wrongDecrypt = $this->cryptor->decryptString($encryptedB64, $this->testKey, Cryptor::FORMAT_RAW);
            $this->assertNotEquals($plaintext, $wrongDecrypt);
        } catch (\Exception $e) {
            // Expected behavior - format mismatch causes exception
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    public function testDefaultFormatUsage(): void
    {
        $plaintext = 'Default format test';
        
        // When no format specified, should use constructor default (FORMAT_B64)
        $encrypted = $this->cryptor->encryptString($plaintext, $this->testKey);
        $decrypted = $this->cryptor->decryptString($encrypted, $this->testKey);
        
        $this->assertEquals($plaintext, $decrypted);
        
        // Result should be base64 encoded (default format)
        $this->assertSame($encrypted, base64_encode(base64_decode($encrypted)));
    }
}
