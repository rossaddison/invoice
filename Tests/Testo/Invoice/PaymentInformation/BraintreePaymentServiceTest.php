<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\BraintreePaymentService;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers BraintreePaymentService.
 *
 * createGateway()/findOrCreateCustomer()/processTransaction() (success
 * path)/refund()/verifyPayment() are intentionally not exercised here: they
 * each construct a real `Braintree\Gateway` internally via
 * `new Gateway([...])` rather than accepting an injected gateway, and the
 * Braintree SDK's own HTTP layer is raw ext-curl (confirmed via its
 * composer.json — no guzzlehttp/guzzle dependency), unlike every other
 * mockable gateway in this app (Stripe, Mollie, GoCardless, Checkout.com).
 * There is no seam to substitute a mock without touching production code —
 * invoking them for real would perform a genuine network call to
 * Braintree's API. Only processTransaction()'s empty-nonce fast-fail path
 * is exercised, since it returns before createGateway() is ever called.
 * Matches the same documented limitation already accepted for
 * AmazonPayPaymentServiceTest.
 */
#[Test]
final class BraintreePaymentServiceTest
{
    /** @param array<string, string> $settings */
    private function makeSettingsRepo(array $settings = []): SettingRepository&m\MockInterface
    {
        $repo = m::mock(SettingRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->shouldReceive('getSetting');
        $e->andReturnUsing(static fn(string $key): string => $settings[$key] ?? '');
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->shouldReceive('decode');
        $e2->andReturnUsing(static fn(string $value): string => $value);

        return $repo;
    }

    /** @param array<string, string> $settings */
    private function makeService(array $settings = []): BraintreePaymentService
    {
        return new BraintreePaymentService($this->makeSettingsRepo($settings), m::spy(\Psr\Log\LoggerInterface::class));
    }

    public function getDriverKeyReturnsBraintree(): void
    {
        Assert::same('braintree', $this->makeService()->getDriverKey());
    }

    public function isConfiguredFalseWhenNoSettings(): void
    {
        Assert::false($this->makeService()->isConfigured());
    }

    public function isConfiguredFalseWhenMerchantIdMissing(): void
    {
        $service = $this->makeService([
            'gateway_braintree_publicKey' => 'pub',
            'gateway_braintree_privateKey' => 'priv',
        ]);
        Assert::false($service->isConfigured());
    }

    public function isConfiguredFalseWhenPublicKeyMissing(): void
    {
        $service = $this->makeService([
            'gateway_braintree_merchantId' => 'merch',
            'gateway_braintree_privateKey' => 'priv',
        ]);
        Assert::false($service->isConfigured());
    }

    public function isConfiguredFalseWhenPrivateKeyMissing(): void
    {
        $service = $this->makeService([
            'gateway_braintree_merchantId' => 'merch',
            'gateway_braintree_publicKey' => 'pub',
        ]);
        Assert::false($service->isConfigured());
    }

    public function isConfiguredTrueWhenAllSet(): void
    {
        $service = $this->makeService([
            'gateway_braintree_merchantId' => 'merch',
            'gateway_braintree_publicKey' => 'pub',
            'gateway_braintree_privateKey' => 'priv',
        ]);
        Assert::true($service->isConfigured());
    }

    public function getMerchantIdReturnsDecodedValue(): void
    {
        $service = $this->makeService(['gateway_braintree_merchantId' => 'merch_123']);
        Assert::same('merch_123', $service->getMerchantId());
    }

    public function getEnvironmentReturnsProductionByDefault(): void
    {
        Assert::same('production', $this->makeService()->getEnvironment());
    }

    public function getEnvironmentReturnsSandboxWhenFlagSet(): void
    {
        $service = $this->makeService(['gateway_braintree_sandbox' => '1']);
        Assert::same('sandbox', $service->getEnvironment());
    }

    public function processTransactionFailsFastWhenNonceEmpty(): void
    {
        $result = $this->makeService()->processTransaction(10.0, '');

        Assert::false($result['success']);
        Assert::null($result['transaction_id']);
        Assert::same('Payment method nonce is required', $result['message']);
    }

    public function getVersionReturnsNonEmptyString(): void
    {
        Assert::notSame('', $this->makeService()->getVersion());
    }
}
