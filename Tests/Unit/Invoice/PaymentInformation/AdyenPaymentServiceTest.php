<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use Adyen\Util\HmacSignature;
use App\Invoice\PaymentInformation\Service\AdyenPaymentService;
use App\Invoice\Setting\SettingRepository;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * AdyenPaymentService requires the concrete `final` SettingRepository class,
 * not an interface. Tests/bootstrap.php enables DG\BypassFinals, which strips
 * `final` at runtime, so a plain createStub() works here; getSetting()/decode()
 * are stubbed directly (decode() as identity) rather than exercised for real,
 * so no encryption/DB setup is needed. createStub() (not createMock()) since
 * no call-count/argument expectations are being verified — just canned
 * returns — matching the existing BacsPaymentServiceTest::makeRepo() pattern.
 */
final class AdyenPaymentServiceTest extends TestCase
{
    private const string HMAC_KEY = '44782D6D6F636B2D6B65792D666F722D74657374696E672D6F6E6C79';

    /** @param array<string, string> $settings */
    private function makeSettingsRepo(array $settings = []): SettingRepository&Stub
    {
        $repo = $this->createStub(SettingRepository::class);
        $repo->method('getSetting')->willReturnCallback(
            static fn (string $key): string => $settings[$key] ?? '',
        );
        $repo->method('decode')->willReturnArgument(0);

        return $repo;
    }

    /** @param array<string, string> $settings */
    private function makeService(array $settings = []): AdyenPaymentService
    {
        return new AdyenPaymentService($this->makeSettingsRepo($settings), new NullLogger());
    }

    /** @param array<string, mixed> $overrides */
    private function signedNotificationBody(array $overrides = [], ?string $hmacKey = null): string
    {
        $item = array_merge([
            'pspReference'        => 'psp_test_123',
            'originalReference'   => '',
            'merchantAccountCode' => 'TestMerchant',
            'merchantReference'   => 'test-invoice-url-key',
            'amount'              => ['value' => 1000, 'currency' => 'EUR'],
            'eventCode'           => 'AUTHORISATION',
            'success'             => 'true',
        ], $overrides);

        $hmac = new HmacSignature();
        $item['additionalData'] = [
            'hmacSignature' => $hmac->calculateNotificationHMAC($hmacKey ?? self::HMAC_KEY, $item),
        ];

        return json_encode(
            ['notificationItems' => [['NotificationRequestItem' => $item]]],
            JSON_THROW_ON_ERROR,
        );
    }

    public function testGetDriverKeyReturnsAdyen(): void
    {
        self::assertSame('adyen', $this->makeService()->getDriverKey());
    }

    public function testIsConfiguredFalseWhenNoSettings(): void
    {
        self::assertFalse($this->makeService()->isConfigured());
    }

    public function testIsConfiguredFalseWhenApiKeyMissing(): void
    {
        $service = $this->makeService([
            'gateway_adyen_merchantAccount' => 'Merchant',
            'gateway_adyen_clientKey' => 'client_123',
        ]);
        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredFalseWhenClientKeyMissing(): void
    {
        $service = $this->makeService([
            'gateway_adyen_merchantAccount' => 'Merchant',
            'gateway_adyen_apiKey' => 'api_123',
        ]);
        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredFalseWhenMerchantAccountMissing(): void
    {
        $service = $this->makeService([
            'gateway_adyen_apiKey' => 'api_123',
            'gateway_adyen_clientKey' => 'client_123',
        ]);
        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredTrueWhenAllSet(): void
    {
        $service = $this->makeService([
            'gateway_adyen_merchantAccount' => 'Merchant',
            'gateway_adyen_apiKey' => 'api_123',
            'gateway_adyen_clientKey' => 'client_123',
        ]);
        self::assertTrue($service->isConfigured());
    }

    public function testGetClientKeyReturnsDecodedValue(): void
    {
        $service = $this->makeService(['gateway_adyen_clientKey' => 'client_abc']);
        self::assertSame('client_abc', $service->getClientKey());
    }

    public function testIsSandboxFalseByDefault(): void
    {
        self::assertFalse($this->makeService()->isSandbox());
    }

    public function testIsSandboxTrueWhenFlagSet(): void
    {
        $service = $this->makeService(['gateway_adyen_sandbox' => '1']);
        self::assertTrue($service->isSandbox());
    }

    public function testVerifyWebhookNotificationReturnsNullWhenHmacKeyNotConfigured(): void
    {
        self::assertNull($this->makeService()->verifyWebhookNotification($this->signedNotificationBody()));
    }

    public function testVerifyWebhookNotificationReturnsNullWhenBodyHasNoNotificationItems(): void
    {
        $service = $this->makeService(['gateway_adyen_webhookHmacKey' => self::HMAC_KEY]);
        self::assertNull($service->verifyWebhookNotification('{}'));
    }

    public function testVerifyWebhookNotificationReturnsNullForInvalidSignature(): void
    {
        $service = $this->makeService(['gateway_adyen_webhookHmacKey' => self::HMAC_KEY]);
        $body = $this->signedNotificationBody(hmacKey: '00112233445566778899AABBCCDDEEFF0011223');

        self::assertNull($service->verifyWebhookNotification($body));
    }

    public function testVerifyWebhookNotificationReturnsItemForValidSignature(): void
    {
        $service = $this->makeService(['gateway_adyen_webhookHmacKey' => self::HMAC_KEY]);
        $body = $this->signedNotificationBody();

        $item = $service->verifyWebhookNotification($body);

        self::assertIsArray($item);
        self::assertSame('psp_test_123', $item['pspReference']);
    }
}
