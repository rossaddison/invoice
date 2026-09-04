<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\Company\CompanyRepository;
use App\Invoice\PaymentInformation\Service\TrueLayerPaymentService;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;

/**
 * Covers TrueLayerPaymentService.
 *
 * createPayment()'s network-calling path (once past the currency/IBAN
 * guards) and verifyPayment() are intentionally not exercised here: the
 * `truelayer/client` SDK builds its own fluent object graph internally
 * (Client::configure()->...->create()) requiring a genuine EC private key
 * for its JWS signer to construct at all, with no seam to substitute a
 * mock HTTP response without a real, validly-formatted key — a "no
 * injection seam without real crypto material" limitation. Verified
 * instead via a real TrueLayer sandbox account once Console credentials
 * are available — see
 * gateways.json's own truelayer row.
 */
#[Test]
final class TrueLayerPaymentServiceTest
{
    /** @param array<string, string> $settings */
    private function makeSettingsRepo(array $settings = []): SettingRepository&m\MockInterface
    {
        /** @var SettingRepository&m\MockInterface $repo */
        $repo = m::mock(SettingRepository::class);
        $e = $repo->shouldReceive('getSetting');
        $e->andReturnUsing(static fn (string $key): string => $settings[$key] ?? '');
        $e2 = $repo->shouldReceive('decode');
        $e2->andReturnUsing(static fn (string $value): string => $value);

        return $repo;
    }

    private function makeCompanyRepo(): CompanyRepository&m\MockInterface
    {
        /** @var CompanyRepository&m\MockInterface $repo */
        $repo = m::mock(CompanyRepository::class);
        return $repo;
    }

    /** @param array<string, string> $settings */
    private function makeService(array $settings = [], ?CompanyRepository $compR = null): TrueLayerPaymentService
    {
        /** @var LoggerInterface $logger */
        $logger = m::spy(LoggerInterface::class);

        return new TrueLayerPaymentService(
            $this->makeSettingsRepo($settings),
            $compR ?? $this->makeCompanyRepo(),
            $logger,
        );
    }

    public function getDriverKeyReturnsTrueLayer(): void
    {
        Assert::same('truelayer', $this->makeService()->getDriverKey());
    }

    public function isConfiguredFalseWhenNoSettings(): void
    {
        Assert::false($this->makeService()->isConfigured());
    }

    public function isConfiguredFalseWhenPrivateKeyMissing(): void
    {
        $service = $this->makeService([
            'gateway_truelayer_clientId' => 'id',
            'gateway_truelayer_clientSecret' => 'secret',
            'gateway_truelayer_signingKid' => 'kid',
        ]);
        Assert::false($service->isConfigured());
    }

    public function isConfiguredTrueWhenAllSet(): void
    {
        $service = $this->makeService([
            'gateway_truelayer_clientId' => 'id',
            'gateway_truelayer_clientSecret' => 'secret',
            'gateway_truelayer_signingKid' => 'kid',
            'gateway_truelayer_privateKey' => 'pem',
        ]);
        Assert::true($service->isConfigured());
    }

    public function isSandboxReturnsFalseByDefault(): void
    {
        Assert::false($this->makeService()->isSandbox());
    }

    public function isSandboxReturnsTrueWhenFlagSet(): void
    {
        $service = $this->makeService(['gateway_truelayer_sandbox' => '1']);
        Assert::true($service->isSandbox());
    }

    /**
     * The currency guard runs before any company/network work, so this is
     * genuinely exercised without needing to mock CompanyRepository at all.
     */
    public function createPaymentReturnsNullForUnsupportedCurrency(): void
    {
        $service = $this->makeService();

        $result = $service->createPayment(10.0, 'USD', 'url-key', 'Jane Doe', 'jane@example.com');

        Assert::null($result);
    }

    /**
     * The returnUrl guard runs before any company/network work too — see
     * TrueLayerCredentials::returnUrl()'s own docblock for why this is
     * a fixed Setting rather than a parameter.
     */
    public function createPaymentReturnsNullWhenNoReturnUrlConfigured(): void
    {
        $service = $this->makeService();

        $result = $service->createPayment(10.0, 'GBP', 'url-key', 'Jane Doe', 'jane@example.com');

        Assert::null($result);
    }

    public function createPaymentReturnsNullWhenNoActiveCompany(): void
    {
        $compR = $this->makeCompanyRepo();
        $e = $compR->shouldReceive('repoCompanyActivequery');
        $e->once()->andReturn(null);

        $service = $this->makeService(['gateway_truelayer_returnUrl' => 'https://example.com/return'], $compR);

        $result = $service->createPayment(10.0, 'GBP', 'url-key', 'Jane Doe', 'jane@example.com');

        Assert::null($result);
    }

    /**
     * Refunds are only supported for settled merchant-account payments
     * (confirmed directly in the truelayer/client SDK's own README) —
     * this integration always pays into an external_account beneficiary,
     * so refund() must always report unsupported without ever attempting
     * a network call, which this test asserts by never stubbing
     * SettingRepository/CompanyRepository with anything a network call
     * would need.
     */
    public function refundAlwaysReturnsUnsupported(): void
    {
        $service = $this->makeService();

        $result = $service->refund('payment-id-123', 10.0);

        Assert::false($result->refunded);
        Assert::same('payment-id-123', $result->providerReference);
        Assert::true(str_contains($result->message, 'only supports refunds for settled merchant-account payments'));
    }
}
