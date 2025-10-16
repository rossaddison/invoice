<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use App\Auth\Controller\AuthController;
use App\Auth\AuthService;
use App\User\RecoveryCodeService;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use App\Service\WebControllerService;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Yiisoft\Rbac\Manager;
use Yiisoft\Session\SessionInterface;
use App\Invoice\Setting\SettingRepository;
use App\Auth\Client\DeveloperSandboxHmrc;
use App\Auth\Client\GovUk;
use Yiisoft\Yii\AuthClient\Client\Facebook;
use Yiisoft\Yii\AuthClient\Client\GitHub;
use Yiisoft\Yii\AuthClient\Client\Google;
use Yiisoft\Yii\AuthClient\Client\LinkedIn;
use Yiisoft\Yii\AuthClient\Client\MicrosoftOnline;
use Yiisoft\Yii\AuthClient\Client\VKontakte;
use Yiisoft\Yii\AuthClient\Client\X;
use Yiisoft\Yii\AuthClient\Client\Yandex;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Psr\Log\LoggerInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Yii\RateLimiter\CounterInterface;

/**
 * Unit tests for AuthController security improvements
 */
final class AuthControllerSecurityTest extends TestCase
{
    private AuthController $authController;
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        // Create mock dependencies
        $authService = $this->createMock(AuthService::class);
        $recoveryCodeService = $this->createMock(RecoveryCodeService::class);
        $factory = $this->createMock(DataResponseFactoryInterface::class);
        $webService = $this->createMock(WebControllerService::class);
        $viewRenderer = $this->createMock(ViewRenderer::class);
        $manager = $this->createMock(Manager::class);
        $session = $this->createMock(SessionInterface::class);
        $settingRepository = $this->createMock(SettingRepository::class);
        $developerSandboxHmrc = $this->createMock(DeveloperSandboxHmrc::class);
        $facebook = $this->createMock(Facebook::class);
        $github = $this->createMock(GitHub::class);
        $google = $this->createMock(Google::class);
        $govUk = $this->createMock(GovUk::class);
        $linkedIn = $this->createMock(LinkedIn::class);
        $microsoftOnline = $this->createMock(MicrosoftOnline::class);
        $vkontakte = $this->createMock(VKontakte::class);
        $x = $this->createMock(X::class);
        $yandex = $this->createMock(Yandex::class);
        $urlGenerator = $this->createMock(UrlGenerator::class);
        $logger = $this->createMock(LoggerInterface::class);
        $flash = $this->createMock(Flash::class);
        $rateLimiter = $this->createMock(CounterInterface::class);

        // Mock ViewRenderer to return itself for method chaining
        $viewRenderer->method('withControllerName')->willReturn($viewRenderer);

        // Mock rate limiter to always allow requests in tests
        $rateLimiter->method('hit')->willReturn(true);

        $this->authController = new AuthController(
            $authService,
            $recoveryCodeService,
            $factory,
            $webService,
            $viewRenderer,
            $manager,
            $session,
            $settingRepository,
            $developerSandboxHmrc,
            $facebook,
            $github,
            $google,
            $govUk,
            $linkedIn,
            $microsoftOnline,
            $vkontakte,
            $x,
            $yandex,
            $urlGenerator,
            $logger,
            $flash,
            $rateLimiter,
        );

        $this->reflection = new ReflectionClass($this->authController);
    }

    /**
     * Test sanitizeAndValidateCode method with valid TOTP codes
     */
    public function testSanitizeAndValidateCodeValidTotp(): void
    {
        $method = $this->getPrivateMethod('sanitizeAndValidateCode');

        // Valid 6-digit TOTP codes
        $this->assertEquals('123456', $method->invoke($this->authController, '123456'));
        $this->assertEquals('000000', $method->invoke($this->authController, '000000'));
        $this->assertEquals('999999', $method->invoke($this->authController, '999999'));

        // Valid with whitespace that should be trimmed
        $this->assertEquals('123456', $method->invoke($this->authController, ' 123456 '));
        $this->assertEquals('123456', $method->invoke($this->authController, "\t123456\n"));
    }

    /**
     * Test sanitizeAndValidateCode method with valid backup codes
     */
    public function testSanitizeAndValidateCodeValidBackup(): void
    {
        $method = $this->getPrivateMethod('sanitizeAndValidateCode');

        // Valid 8-character backup codes
        $this->assertEquals('ABCD1234', $method->invoke($this->authController, 'ABCD1234'));
        $this->assertEquals('12345678', $method->invoke($this->authController, '12345678'));
        $this->assertEquals('abcdefgh', $method->invoke($this->authController, 'abcdefgh'));
        $this->assertEquals('MixEd123', $method->invoke($this->authController, 'MixEd123'));
    }

    /**
     * Test sanitizeAndValidateCode method with invalid inputs
     */
    public function testSanitizeAndValidateCodeInvalid(): void
    {
        $method = $this->getPrivateMethod('sanitizeAndValidateCode');

        // Invalid formats
        $this->assertNull($method->invoke($this->authController, ''));
        $this->assertNull($method->invoke($this->authController, '12345')); // Too short
        $this->assertNull($method->invoke($this->authController, '1234567')); // Wrong length
        $this->assertNull($method->invoke($this->authController, '123456789')); // Too long
        $this->assertNull($method->invoke($this->authController, '12345@')); // Invalid characters
        $this->assertNull($method->invoke($this->authController, 'ABCDEF!@')); // Invalid characters
        $this->assertNull($method->invoke($this->authController, null)); // Null input
        $this->assertNull($method->invoke($this->authController, [])); // Array input
        $this->assertNull($method->invoke($this->authController, new \stdClass())); // Object input
    }

    /**
     * Test isValidTotpCode method
     */
    public function testIsValidTotpCode(): void
    {
        $method = $this->getPrivateMethod('isValidTotpCode');

        // Valid TOTP codes
        $this->assertTrue($method->invoke($this->authController, '123456'));
        $this->assertTrue($method->invoke($this->authController, '000000'));
        $this->assertTrue($method->invoke($this->authController, '999999'));

        // Invalid TOTP codes
        $this->assertFalse($method->invoke($this->authController, '12345')); // Too short
        $this->assertFalse($method->invoke($this->authController, '1234567')); // Too long
        $this->assertFalse($method->invoke($this->authController, 'ABCDEF')); // Letters
        $this->assertFalse($method->invoke($this->authController, '12345@')); // Special chars
        $this->assertFalse($method->invoke($this->authController, '')); // Empty
    }

    /**
     * Test isValidBackupCode method
     */
    public function testIsValidBackupCode(): void
    {
        $method = $this->getPrivateMethod('isValidBackupCode');

        // Valid backup codes
        $this->assertTrue($method->invoke($this->authController, 'ABCD1234'));
        $this->assertTrue($method->invoke($this->authController, '12345678'));
        $this->assertTrue($method->invoke($this->authController, 'abcdefgh'));
        $this->assertTrue($method->invoke($this->authController, 'MixEd123'));

        // Invalid backup codes
        $this->assertFalse($method->invoke($this->authController, 'ABCD123')); // Too short
        $this->assertFalse($method->invoke($this->authController, 'ABCD12345')); // Too long
        $this->assertFalse($method->invoke($this->authController, 'ABCD123@')); // Special chars
        $this->assertFalse($method->invoke($this->authController, '')); // Empty
        $this->assertFalse($method->invoke($this->authController, 'ABC DEF1')); // Space
    }

    /**
     * Test validateSessionIntegrity method
     */
    public function testValidateSessionIntegrity(): void
    {
        $method = $this->getPrivateMethod('validateSessionIntegrity');

        // Mock session to return valid user ID
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')
               ->with('verified_2fa_user_id')
               ->willReturn(123);

        // Use reflection to set the session property
        $sessionProperty = $this->reflection->getProperty('session');
        $sessionProperty->setAccessible(true);
        $sessionProperty->setValue($this->authController, $session);

        // Test valid session
        $this->assertTrue($method->invoke($this->authController, 123));

        // Test invalid user ID
        $this->assertFalse($method->invoke($this->authController, 0));
        $this->assertFalse($method->invoke($this->authController, -1));

        // Test mismatched user ID
        $this->assertFalse($method->invoke($this->authController, 456));
    }

    /**
     * Test input sanitization removes special characters
     */
    public function testInputSanitizationRemovesSpecialChars(): void
    {
        $method = $this->getPrivateMethod('sanitizeAndValidateCode');

        // Test removal of special characters
        $this->assertEquals('123456', $method->invoke($this->authController, '1-2-3-4-5-6'));
        $this->assertEquals('ABCD1234', $method->invoke($this->authController, 'A.B.C.D.1.2.3.4'));
        $this->assertNull($method->invoke($this->authController, '!@#$%^')); // Only special chars
    }

    /**
     * Test numeric input handling
     */
    public function testNumericInputHandling(): void
    {
        $method = $this->getPrivateMethod('sanitizeAndValidateCode');

        // Test numeric inputs
        $this->assertEquals('123456', $method->invoke($this->authController, 123456));
        $this->assertNull($method->invoke($this->authController, 12345)); // Too short
        $this->assertNull($method->invoke($this->authController, 1234567)); // Wrong length
    }

    /**
     * Helper method to access private methods via reflection
     */
    private function getPrivateMethod(string $methodName): ReflectionMethod
    {
        $method = $this->reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }
}
