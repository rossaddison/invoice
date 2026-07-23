<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\BraintreePaymentService;
use App\Invoice\Setting\SettingRepository;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * BraintreePaymentService requires the concrete `final` SettingRepository class,
 * not an interface. Tests/bootstrap.php enables DG\BypassFinals, which strips
 * `final` at runtime, so a plain createStub() works here; getSetting()/decode()
 * are stubbed directly (decode() as identity) rather than exercised for real,
 * so no encryption/DB setup is needed. createStub() (not createMock()) since
 * no call-count/argument expectations are being verified — just canned
 * returns — matching the existing BacsPaymentServiceTest::makeRepo() pattern.
 */
final class BraintreePaymentServiceTest extends TestCase
{
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
    private function makeService(array $settings = []): BraintreePaymentService
    {
        return new BraintreePaymentService($this->makeSettingsRepo($settings), new NullLogger());
    }

    public function testGetDriverKeyReturnsBraintree(): void
    {
        self::assertSame('braintree', $this->makeService()->getDriverKey());
    }

    public function testIsConfiguredFalseWhenNoSettings(): void
    {
        self::assertFalse($this->makeService()->isConfigured());
    }

    public function testIsConfiguredFalseWhenMerchantIdMissing(): void
    {
        $service = $this->makeService([
            'gateway_braintree_publicKey' => 'pub',
            'gateway_braintree_privateKey' => 'priv',
        ]);
        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredFalseWhenPublicKeyMissing(): void
    {
        $service = $this->makeService([
            'gateway_braintree_merchantId' => 'merch',
            'gateway_braintree_privateKey' => 'priv',
        ]);
        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredFalseWhenPrivateKeyMissing(): void
    {
        $service = $this->makeService([
            'gateway_braintree_merchantId' => 'merch',
            'gateway_braintree_publicKey' => 'pub',
        ]);
        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredTrueWhenAllSet(): void
    {
        $service = $this->makeService([
            'gateway_braintree_merchantId' => 'merch',
            'gateway_braintree_publicKey' => 'pub',
            'gateway_braintree_privateKey' => 'priv',
        ]);
        self::assertTrue($service->isConfigured());
    }

    public function testGetMerchantIdReturnsDecodedValue(): void
    {
        $service = $this->makeService(['gateway_braintree_merchantId' => 'merch_123']);
        self::assertSame('merch_123', $service->getMerchantId());
    }

    public function testGetEnvironmentReturnsProductionByDefault(): void
    {
        self::assertSame('production', $this->makeService()->getEnvironment());
    }

    public function testGetEnvironmentReturnsSandboxWhenFlagSet(): void
    {
        $service = $this->makeService(['gateway_braintree_sandbox' => '1']);
        self::assertSame('sandbox', $service->getEnvironment());
    }

    public function testProcessTransactionFailsFastWhenNonceEmpty(): void
    {
        $result = $this->makeService()->processTransaction(10.0, '');

        self::assertFalse($result['success']);
        self::assertNull($result['transaction_id']);
        self::assertSame('Payment method nonce is required', $result['message']);
    }

    public function testGetVersionReturnsNonEmptyString(): void
    {
        self::assertNotSame('', $this->makeService()->getVersion());
    }
}
