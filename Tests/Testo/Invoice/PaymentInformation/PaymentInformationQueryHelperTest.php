<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\PaymentInformationQueryHelper;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Api\Types\RefundStatus;
use Testo\Assert;
use Testo\Test;

/**
 * Mollie\Api\Resources\Payment::$status and Refund::$status are typed
 * `PaymentStatus|string` / `RefundStatus|string|null` — the SDK's own
 * transition to PHP backed enums, kept union-typed with the historical plain
 * string for backward compatibility. Neither concatenates or casts to string
 * directly (see PaymentInformationQueryHelper::mollieStatusToString()'s own
 * docblock), so this exercises all three shapes the union actually allows.
 */
#[Test]
final class PaymentInformationQueryHelperTest
{
    public function mollieStatusToStringReadsTheEnumsValue(): void
    {
        Assert::same('paid', PaymentInformationQueryHelper::mollieStatusToString(PaymentStatus::Paid));
        Assert::same('refunded', PaymentInformationQueryHelper::mollieStatusToString(RefundStatus::Refunded));
    }

    public function mollieStatusToStringPassesAPlainStringThrough(): void
    {
        Assert::same('paid', PaymentInformationQueryHelper::mollieStatusToString('paid'));
    }

    public function mollieStatusToStringReturnsEmptyStringForNull(): void
    {
        Assert::same('', PaymentInformationQueryHelper::mollieStatusToString(null));
    }
}
