<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\AdyenPaymentService;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;

/**
 * Covers AdyenPaymentService::hmacKeyKcv() — the independent Key Check
 * Value computation added after a real live incident where a
 * correctly-formatted but wrong HMAC key (copied from Adyen's Customer
 * Area but never actually saved there) silently broke every webhook,
 * despite passing every format check this app could perform on its own.
 * See docs/ADYEN_WEBHOOK_HMAC_KEY_NOT_SAVED_AUGUST_2026.md for the full
 * incident and why "well-formed" and "the right key" turned out to be two
 * separate things worth verifying independently.
 *
 * The known key/KCV pair here was computed directly via the same
 * algorithm this method implements (HMAC-SHA256("00000000",
 * key-packed-as-binary), last 3 bytes, uppercase hex) — a plain fixture,
 * not asserting against Adyen's own service.
 */
#[Test]
final class AdyenPaymentServiceTest
{

    /**
     * @return LoggerInterface&m\MockInterface
     */
    private function makeLoggerInterfaceSpy(): LoggerInterface
    {
        /** @var LoggerInterface&m\MockInterface $mock */
        $mock = m::spy(LoggerInterface::class);
        return $mock;
    }
    private const string KNOWN_KEY = '0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCD';
    private const string KNOWN_KEY_KCV = 'E3CAEE';

    private function makeService(string $storedHmacKey): AdyenPaymentService
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('gateway_adyen_webhookHmacKey')->andReturn($storedHmacKey);
        $sR->shouldReceive('decode')->with($storedHmacKey)->andReturn($storedHmacKey === '' ? '' : $storedHmacKey);

        return new AdyenPaymentService($sR, $this->makeLoggerInterfaceSpy());
    }

    public function hmacKeyKcvComputesTheCorrectValueForAKnownKey(): void
    {
        $service = $this->makeService(self::KNOWN_KEY);

        Assert::same(self::KNOWN_KEY_KCV, $service->hmacKeyKcv());
    }

    public function hmacKeyKcvReturnsNullWhenNoKeyIsConfigured(): void
    {
        $service = $this->makeService('');

        Assert::null($service->hmacKeyKcv());
    }

    /**
     * Regression guard for the exact bug class found live: a base64-shaped
     * value (contains +, /, and = padding) is well-formed as a *string*
     * but not valid hex, so it must never produce a KCV that looks
     * trustworthy — it should report null instead, the same way real
     * signature verification would fail on it.
     */
    public function hmacKeyKcvReturnsNullWhenKeyIsNotValidHex(): void
    {
        $service = $this->makeService('ZVJlvRO6xhxbhYz2W+D2BzXf842p+kRmIEihFeRv9gE=');

        Assert::null($service->hmacKeyKcv());
    }
}
