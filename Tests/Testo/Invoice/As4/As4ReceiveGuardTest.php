<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\As4;

use App\Invoice\As4\As4ReceiveGuard;
use Testo\Assert;
use Testo\Test;

/**
 * The interim decision behind /as4/receive: no secret configured refuses everything (a blank presented
 * secret cannot match a blank configuration), otherwise only the exact secret passes.
 */
#[Test]
final class As4ReceiveGuardTest
{
    public function noConfiguredSecretRefusesEvenABlankOne(): void
    {
        Assert::same(As4ReceiveGuard::refusal('', ''), As4ReceiveGuard::SECRET_NOT_SET);
        Assert::same(As4ReceiveGuard::refusal('', 'anything'), As4ReceiveGuard::SECRET_NOT_SET);
    }

    public function aMissingOrWrongSecretIsRefused(): void
    {
        Assert::same(As4ReceiveGuard::refusal('s3cret-value', ''), As4ReceiveGuard::SECRET_MISMATCH);
        Assert::same(As4ReceiveGuard::refusal('s3cret-value', 'wrong'), As4ReceiveGuard::SECRET_MISMATCH);
        Assert::same(As4ReceiveGuard::refusal('s3cret-value', 's3cret'), As4ReceiveGuard::SECRET_MISMATCH);
    }

    public function theExactSecretPasses(): void
    {
        Assert::null(As4ReceiveGuard::refusal('s3cret-value', 's3cret-value'));
    }
}
