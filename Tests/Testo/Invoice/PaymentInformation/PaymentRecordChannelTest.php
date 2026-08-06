<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\PaymentRecordChannel;
use Testo\Assert;
use Testo\Test;

/**
 * Covers PaymentRecordChannel's emoji() mapping — the visual marker
 * OnlinePaymentRecorderService::recordSuccess() prepends to a Payment's
 * note so an admin can tell at a glance whether a payment was confirmed by
 * an async webhook or a synchronous redirect/form-POST. See the enum's own
 * docblock for which gateways use which channel.
 */
#[Test]
final class PaymentRecordChannelTest
{
    public function webhookEmojiIsTheHook(): void
    {
        Assert::same('🪝', PaymentRecordChannel::Webhook->emoji());
    }

    public function redirectEmojiIsTheCurvingArrow(): void
    {
        Assert::same('↩️', PaymentRecordChannel::Redirect->emoji());
    }

    public function theTwoChannelsHaveDifferentEmoji(): void
    {
        Assert::notSame(
            PaymentRecordChannel::Webhook->emoji(),
            PaymentRecordChannel::Redirect->emoji(),
        );
    }
}
