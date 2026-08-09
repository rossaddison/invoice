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
    public function webhookEmojiIsHighVoltage(): void
    {
        Assert::same('⚡', PaymentRecordChannel::Webhook->emoji());
    }

    public function webhookEmojiIsBmpSafe(): void
    {
        // Regression guard for the MySQL charset incident — every codepoint
        // must be <= U+FFFF (Basic Multilingual Plane), i.e. at most 3 bytes
        // in UTF-8, so it can never repeat the crash documented in
        // docs/MYSQL_CONNECTION_CHARSET_BUG_AUGUST_2026.md regardless of
        // what the live connection's negotiated charset turns out to be.
        $emoji = PaymentRecordChannel::Webhook->emoji();
        $codepoints = mb_str_split($emoji, 1, 'UTF-8');
        foreach ($codepoints as $char) {
            Assert::true(mb_ord($char, 'UTF-8') <= 0xFFFF);
        }
    }

    public function redirectEmojiIsTheCurvingArrow(): void
    {
        Assert::same('↩️', PaymentRecordChannel::Redirect->emoji());
    }

    public function redirectEmojiIsBmpSafe(): void
    {
        $emoji = PaymentRecordChannel::Redirect->emoji();
        $codepoints = mb_str_split($emoji, 1, 'UTF-8');
        foreach ($codepoints as $char) {
            Assert::true(mb_ord($char, 'UTF-8') <= 0xFFFF);
        }
    }

    public function theTwoChannelsHaveDifferentEmoji(): void
    {
        Assert::notSame(
            PaymentRecordChannel::Webhook->emoji(),
            PaymentRecordChannel::Redirect->emoji(),
        );
    }
}
