<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Peppol;

use App\Invoice\Peppol\PeppolInboundGuard;
use Testo\Assert;
use Testo\Test;

/**
 * The decision behind /peppol/inbound/delivery: no secret configured refuses everything (so a blank
 * configuration cannot be matched by a blank header), and otherwise only the exact secret passes.
 */
#[Test]
final class PeppolInboundGuardTest
{
    public function noConfiguredSecretRefusesEvenABlankOne(): void
    {
        Assert::same(PeppolInboundGuard::refusal('', ''), PeppolInboundGuard::SECRET_NOT_SET);
        Assert::same(PeppolInboundGuard::refusal('', 'anything'), PeppolInboundGuard::SECRET_NOT_SET);
    }

    public function aMissingOrWrongSecretIsRefused(): void
    {
        Assert::same(PeppolInboundGuard::refusal('s3cret-value', ''), PeppolInboundGuard::SECRET_MISMATCH);
        Assert::same(PeppolInboundGuard::refusal('s3cret-value', 'wrong'), PeppolInboundGuard::SECRET_MISMATCH);
        Assert::same(PeppolInboundGuard::refusal('s3cret-value', 's3cret'), PeppolInboundGuard::SECRET_MISMATCH);
        Assert::same(PeppolInboundGuard::refusal('s3cret-value', 'S3CRET-VALUE'), PeppolInboundGuard::SECRET_MISMATCH);
    }

    public function theExactSecretPasses(): void
    {
        Assert::null(PeppolInboundGuard::refusal('s3cret-value', 's3cret-value'));
    }
}
