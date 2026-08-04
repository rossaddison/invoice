<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\YookassaWebhookIpVerifier;
use Testo\Assert;
use Testo\Test;

/**
 * Covers YookassaWebhookIpVerifier against the exact CIDR/IP ranges
 * ground-truthed from YooKassa's own official `yoomoney/yookassa-sdk-php`
 * SDK's `SecurityHelper` class — confirmed identical against both the
 * stale GitHub mirror and YooMoney's current actively-maintained source
 * (v3.14.0 as of June 2026) — pure logic, no network I/O.
 */
#[Test]
final class YookassaWebhookIpVerifierTest
{
    private function verifier(): YookassaWebhookIpVerifier
    {
        return new YookassaWebhookIpVerifier();
    }

    public function trustsAnAddressInsideAnIpv4CidrRange(): void
    {
        // 185.71.76.0/27 covers .0-.31.
        Assert::true($this->verifier()->isTrusted('185.71.76.15'));
    }

    public function rejectsAnAddressJustOutsideAnIpv4CidrRange(): void
    {
        // 185.71.76.0/27 covers .0-.31 — .32 is the next block.
        Assert::false($this->verifier()->isTrusted('185.71.76.32'));
    }

    public function trustsAnExactBareIpv4Address(): void
    {
        Assert::true($this->verifier()->isTrusted('77.75.156.11'));
        Assert::true($this->verifier()->isTrusted('77.75.156.35'));
    }

    public function rejectsAnUntrustedIpv4Address(): void
    {
        Assert::false($this->verifier()->isTrusted('8.8.8.8'));
    }

    public function trustsAnAddressInsideAnIpv6Range(): void
    {
        Assert::true($this->verifier()->isTrusted('2a02:5180:0000:1509:0000:0000:0000:0001'));
    }

    public function rejectsAnAddressOutsideEveryIpv6Range(): void
    {
        Assert::false($this->verifier()->isTrusted('2a02:5180:0000:9999:0000:0000:0000:0001'));
    }

    public function rejectsAMalformedIpAddress(): void
    {
        Assert::false($this->verifier()->isTrusted('not-an-ip'));
    }
}
