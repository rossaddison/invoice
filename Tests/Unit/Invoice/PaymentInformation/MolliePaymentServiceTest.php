<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\MolliePaymentService;
use App\Invoice\Setting\SettingRepository;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * MolliePaymentService requires the concrete `final` SettingRepository class,
 * not an interface. Tests/bootstrap.php enables DG\BypassFinals, which strips
 * `final` at runtime, so a plain createStub() works here; getSetting()/decode()
 * are stubbed directly (decode() as identity) rather than exercised for real,
 * so no encryption/DB setup is needed. createStub() (not createMock()) since
 * no call-count/argument expectations are being verified — just canned
 * returns — matching the existing BacsPaymentServiceTest::makeRepo() pattern.
 */
final class MolliePaymentServiceTest extends TestCase
{
    // Must match Mollie\Api\Http\Auth\TokenValidator::API_KEY_PATTERN: (live|test)_\w{30,}
    private const string VALID_TEST_KEY = 'test_123456789012345678901234567890';

    /** @param array<string, string> $settings */
    private function makeSettingsRepo(array $settings = []): SettingRepository&Stub
    {
        $repo = $this->createStub(SettingRepository::class);
        $repo->method('getSetting')->willReturnCallback(
            static fn(string $key): string => $settings[$key] ?? '',
        );
        $repo->method('decode')->willReturnArgument(0);

        return $repo;
    }

    /** @param array<string, string> $settings */
    private function makeService(array $settings = []): MolliePaymentService
    {
        return new MolliePaymentService($this->makeSettingsRepo($settings), new NullLogger());
    }

    public function testGetDriverKeyReturnsMollie(): void
    {
        self::assertSame('mollie', $this->makeService()->getDriverKey());
    }

    public function testIsConfiguredFalseWhenApiKeyNotSet(): void
    {
        self::assertFalse($this->makeService()->isConfigured());
    }

    public function testIsConfiguredTrueWhenApiKeySet(): void
    {
        $service = $this->makeService([
            'gateway_mollie_testOrLiveApiKey' => self::VALID_TEST_KEY,
        ]);
        self::assertTrue($service->isConfigured());
    }
}
