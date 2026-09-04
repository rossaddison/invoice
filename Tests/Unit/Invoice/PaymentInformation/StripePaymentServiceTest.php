<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\StripePaymentService;
use App\Invoice\Setting\SettingRepository;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * StripePaymentService requires the concrete `final` SettingRepository class,
 * not an interface. Tests/bootstrap.php enables DG\BypassFinals, which strips
 * `final` at runtime, so a plain createStub() works here; getSetting()/decode()
 * are stubbed directly (decode() as identity) rather than exercised for real,
 * so no encryption/DB setup is needed. createStub() (not createMock()) since
 * no call-count/argument expectations are being verified — just canned
 * returns — matching the existing BacsPaymentServiceTest::makeRepo() pattern.
 */
final class StripePaymentServiceTest extends TestCase
{
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
    private function makeService(array $settings = []): StripePaymentService
    {
        return new StripePaymentService($this->makeSettingsRepo($settings), new NullLogger());
    }

    public function testGetDriverKeyReturnsStripe(): void
    {
        self::assertSame('stripe', $this->makeService()->getDriverKey());
    }

    public function testIsConfiguredFalseWhenNoSettings(): void
    {
        self::assertFalse($this->makeService()->isConfigured());
    }

    public function testIsConfiguredFalseWhenOnlySecretKeySet(): void
    {
        $service = $this->makeService(['gateway_stripe_secretKey' => 'sk_test_123']);
        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredFalseWhenOnlyPublishableKeySet(): void
    {
        $service = $this->makeService(['gateway_stripe_publishableKey' => 'pk_test_123']);
        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredTrueWhenBothSet(): void
    {
        $service = $this->makeService([
            'gateway_stripe_secretKey' => 'sk_test_123',
            'gateway_stripe_publishableKey' => 'pk_test_123',
        ]);
        self::assertTrue($service->isConfigured());
    }

    public function testGetPublishableKeyReturnsEmptyWhenNotSet(): void
    {
        self::assertSame('', $this->makeService()->getPublishableKey());
    }

    public function testGetPublishableKeyReturnsDecodedValue(): void
    {
        $service = $this->makeService(['gateway_stripe_publishableKey' => 'pk_test_abc']);
        self::assertSame('pk_test_abc', $service->getPublishableKey());
    }

    public function testVerifyWebhookSignatureReturnsNullWhenSecretNotConfigured(): void
    {
        self::assertNull($this->makeService()->verifyWebhookSignature('{}', 't=1,v1=abc'));
    }

    public function testVerifyWebhookSignatureReturnsEventForValidSignature(): void
    {
        $secret = 'whsec_test_secret';
        $payload = '{"id":"evt_1","type":"payment_intent.succeeded"}';
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        $header = 't=' . $timestamp . ',v1=' . $signature;

        $service = $this->makeService(['gateway_stripe_webhookSecret' => $secret]);
        $event = $service->verifyWebhookSignature($payload, $header);

        self::assertNotNull($event);
        self::assertSame('evt_1', $event->id);
        self::assertSame('payment_intent.succeeded', $event->type);
    }

    public function testVerifyWebhookSignatureReturnsNullForTamperedPayload(): void
    {
        $secret = 'whsec_test_secret';
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . '{"id":"evt_1"}', $secret);
        $header = 't=' . $timestamp . ',v1=' . $signature;

        $service = $this->makeService(['gateway_stripe_webhookSecret' => $secret]);
        self::assertNull($service->verifyWebhookSignature('{"id":"evt_2"}', $header));
    }

    public function testVerifyWebhookSignatureReturnsNullForWrongSecret(): void
    {
        $payload = '{"id":"evt_1"}';
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, 'whsec_correct');
        $header = 't=' . $timestamp . ',v1=' . $signature;

        $service = $this->makeService(['gateway_stripe_webhookSecret' => 'whsec_wrong']);
        self::assertNull($service->verifyWebhookSignature($payload, $header));
    }
}
