<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\GoCardlessPaymentService;
use App\Invoice\Setting\SettingRepository;
use GoCardlessPro\Environment;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * GoCardlessPaymentService requires the concrete `final` SettingRepository
 * class, not an interface. Tests/bootstrap.php enables DG\BypassFinals, so
 * a plain createStub() works here; getSetting()/decode() are stubbed
 * directly rather than exercised for real, matching the existing
 * BraintreePaymentServiceTest/BacsPaymentServiceTest pattern.
 *
 * createRedirectFlow()/completeRedirectFlow()/scheduleDirectDebitPayment()/
 * verifyPayment()/refund() all construct a real \GoCardlessPro\Client
 * internally and make live API calls — like the sibling Adyen/Braintree/
 * Mollie/Stripe gateways, those aren't covered here; only the pure
 * config/signature logic is.
 */
final class GoCardlessPaymentServiceTest extends TestCase
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
    private function makeService(array $settings = []): GoCardlessPaymentService
    {
        return new GoCardlessPaymentService($this->makeSettingsRepo($settings), new NullLogger());
    }

    public function testGetDriverKeyReturnsGocardless(): void
    {
        self::assertSame('gocardless', $this->makeService()->getDriverKey());
    }

    public function testIsConfiguredFalseWhenNoSettings(): void
    {
        self::assertFalse($this->makeService()->isConfigured());
    }

    public function testIsConfiguredFalseWhenAccessTokenMissing(): void
    {
        $service = $this->makeService([
            'gateway_gocardless_environment' => Environment::SANDBOX,
        ]);
        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredFalseWhenEnvironmentMissing(): void
    {
        $service = $this->makeService([
            'gateway_gocardless_accessToken' => 'sandbox_access_token',
        ]);
        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredFalseWhenEnvironmentInvalid(): void
    {
        $service = $this->makeService([
            'gateway_gocardless_accessToken' => 'sandbox_access_token',
            'gateway_gocardless_environment' => 'staging',
        ]);
        self::assertFalse($service->isConfigured());
    }

    public function testIsConfiguredTrueWhenAllSet(): void
    {
        $service = $this->makeService([
            'gateway_gocardless_accessToken' => 'sandbox_access_token',
            'gateway_gocardless_environment' => Environment::SANDBOX,
        ]);
        self::assertTrue($service->isConfigured());
    }

    public function testIsConfiguredTrueForLiveEnvironment(): void
    {
        $service = $this->makeService([
            'gateway_gocardless_accessToken' => 'live_access_token',
            'gateway_gocardless_environment' => Environment::LIVE,
        ]);
        self::assertTrue($service->isConfigured());
    }

    // ── isValidWebhookSignature ──────────────────────────────────────────────

    public function testIsValidWebhookSignatureFalseWhenSecretNotConfigured(): void
    {
        $service = $this->makeService();
        self::assertFalse($service->isValidWebhookSignature('{"events":[]}', 'anything'));
    }

    public function testIsValidWebhookSignatureTrueForCorrectlySignedBody(): void
    {
        $secret = 'whsec_test_secret';
        $body = '{"events":[{"id":"EV123"}]}';
        $signature = hash_hmac('sha256', $body, $secret);

        $service = $this->makeService(['gateway_gocardless_webhookSecret' => $secret]);

        self::assertTrue($service->isValidWebhookSignature($body, $signature));
    }

    public function testIsValidWebhookSignatureFalseForTamperedBody(): void
    {
        $secret = 'whsec_test_secret';
        $signature = hash_hmac('sha256', '{"events":[{"id":"EV123"}]}', $secret);

        $service = $this->makeService(['gateway_gocardless_webhookSecret' => $secret]);

        self::assertFalse($service->isValidWebhookSignature('{"events":[{"id":"EV999"}]}', $signature));
    }

    public function testIsValidWebhookSignatureFalseForWrongSecret(): void
    {
        $body = '{"events":[{"id":"EV123"}]}';
        $signature = hash_hmac('sha256', $body, 'the_actual_secret');

        $service = $this->makeService(['gateway_gocardless_webhookSecret' => 'a_different_secret']);

        self::assertFalse($service->isValidWebhookSignature($body, $signature));
    }
}
