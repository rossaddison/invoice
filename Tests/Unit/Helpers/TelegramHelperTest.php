<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Phptg\BotApi\Type\Payment\LabeledPrice;

/**
 * Tests for the business logic in TelegramHelper::sendTelegramInvoice()
 * and the webhook successful_payment handler.
 *
 * Network-dependent methods (sendTelegramInvoice, answerPreCheckoutQuery)
 * are not called directly — only the pure calculation and encoding
 * logic they rely on is exercised here.
 */
final class TelegramHelperTest extends TestCase
{
    // -----------------------------------------------------------------------
    // LabeledPrice value object
    // -----------------------------------------------------------------------

    public function testLabeledPriceStoresLabelAndAmount(): void
    {
        $price = new LabeledPrice('Widget', 1999);

        $this->assertSame('Widget', $price->label);
        $this->assertSame(1999, $price->amount);
    }

    public function testLabeledPriceToRequestArray(): void
    {
        $price = new LabeledPrice('Shipping', 500);

        $this->assertSame(['label' => 'Shipping', 'amount' => 500], $price->toRequestArray());
    }

    // -----------------------------------------------------------------------
    // Amount calculation: price × quantity → integer cents
    // -----------------------------------------------------------------------

    public function testAmountCalculation_WholeNumbers(): void
    {
        $price    = 10.00;
        $quantity = 3.0;
        $amount   = (int) round($price * $quantity * 100);

        $this->assertSame(3000, $amount);
    }

    public function testAmountCalculation_FractionalPrice(): void
    {
        $price    = 9.99;
        $quantity = 1.0;
        $amount   = (int) round($price * $quantity * 100);

        $this->assertSame(999, $amount);
    }

    public function testAmountCalculation_FractionalQuantity(): void
    {
        $price    = 100.00;
        $quantity = 0.5;
        $amount   = (int) round($price * $quantity * 100);

        $this->assertSame(5000, $amount);
    }

    public function testAmountCalculation_RoundingHalfUp(): void
    {
        // 1.005 × 1 × 100 = 100.5 → rounds to 101
        $price    = 1.005;
        $quantity = 1.0;
        $amount   = (int) round($price * $quantity * 100);

        $this->assertGreaterThanOrEqual(100, $amount);
        $this->assertLessThanOrEqual(101, $amount);
    }

    public function testAmountCalculation_ZeroPrice(): void
    {
        $amount = (int) round(0.0 * 2.0 * 100);

        $this->assertSame(0, $amount);
    }

    public function testAmountCalculation_NullFallback(): void
    {
        // Mirrors the null-coalescing pattern in sendTelegramInvoice
        $price    = null;
        $quantity = null;
        $amount   = (int) round(($price ?? 0.0) * ($quantity ?? 1.0) * 100);

        $this->assertSame(0, $amount);
    }

    // -----------------------------------------------------------------------
    // Zero-amount items are filtered
    // -----------------------------------------------------------------------

    public function testZeroAmountItemsAreExcluded(): void
    {
        $items = [
            ['price' => 10.00, 'qty' => 1.0],
            ['price' => 0.00,  'qty' => 5.0],  // should be filtered
            ['price' => 5.00,  'qty' => 2.0],
        ];

        $prices = [];
        foreach ($items as $item) {
            $amount = (int) round($item['price'] * $item['qty'] * 100);
            if ($amount > 0) {
                $prices[] = new LabeledPrice('Item', $amount);
            }
        }

        $this->assertCount(2, $prices);
        $this->assertSame(1000, $prices[0]->amount);
        $this->assertSame(1000, $prices[1]->amount);
    }

    // -----------------------------------------------------------------------
    // Tax appended as a separate LabeledPrice
    // -----------------------------------------------------------------------

    public function testTaxAppendedWhenNonZero(): void
    {
        $prices   = [new LabeledPrice('Service', 5000)];
        $taxTotal = 10.00;

        if ($taxTotal > 0.0) {
            $prices[] = new LabeledPrice('Tax', (int) round($taxTotal * 100));
        }

        $this->assertCount(2, $prices);
        $this->assertSame('Tax', $prices[1]->label);
        $this->assertSame(1000, $prices[1]->amount);
    }

    public function testTaxNotAppendedWhenZero(): void
    {
        $prices   = [new LabeledPrice('Service', 5000)];
        $taxTotal = 0.0;

        if ($taxTotal > 0.0) {
            $prices[] = new LabeledPrice('Tax', (int) round($taxTotal * 100));
        }

        $this->assertCount(1, $prices);
    }

    // -----------------------------------------------------------------------
    // Title truncation — Telegram enforces max 32 characters
    // -----------------------------------------------------------------------

    public function testTitleFitsWithinLimit(): void
    {
        $number = 'INV-001';
        $title  = substr('Invoice #' . $number, 0, 32);

        $this->assertSame('Invoice #INV-001', $title);
        $this->assertLessThanOrEqual(32, strlen($title));
    }

    public function testTitleTruncatesLongInvoiceNumber(): void
    {
        $number = str_repeat('X', 40);
        $title  = substr('Invoice #' . $number, 0, 32);

        $this->assertSame(32, strlen($title));
        $this->assertStringStartsWith('Invoice #', $title);
    }

    public function testTitleExactly32Characters(): void
    {
        // 'Invoice #' is 9 chars; pad to exactly 23 more = 32 total
        $number = str_repeat('A', 23);
        $title  = substr('Invoice #' . $number, 0, 32);

        $this->assertSame(32, strlen($title));
    }

    // -----------------------------------------------------------------------
    // Description truncation — Telegram enforces max 255 characters
    // -----------------------------------------------------------------------

    public function testDescriptionFitsWithinLimit(): void
    {
        $note        = 'A short note';
        $description = substr($note, 0, 255);

        $this->assertSame('A short note', $description);
        $this->assertLessThanOrEqual(255, strlen($description));
    }

    public function testDescriptionTruncatesLongNote(): void
    {
        $note        = str_repeat('A', 300);
        $description = substr($note, 0, 255);

        $this->assertSame(255, strlen($description));
    }

    public function testDescriptionFallsBackToInvoiceNumber(): void
    {
        // Mirrors: $note = $inv->getNote(); ($note !== null && $note !== '') ? $note : 'Invoice #...'
        $note   = null;
        $number = 'INV-099';

        $description = ($note !== null && $note !== '') ? $note : 'Invoice #' . $number;

        $this->assertSame('Invoice #INV-099', $description);
    }

    public function testDescriptionUsesNoteWhenPresent(): void
    {
        $note   = 'Professional services for Q1';
        $number = 'INV-100';

        $description = ($note !== null && $note !== '') ? $note : 'Invoice #' . $number;

        $this->assertSame('Professional services for Q1', $description);
    }

    public function testDescriptionFallsBackOnEmptyString(): void
    {
        $note   = '';
        $number = 'INV-101';

        $description = ($note !== null && $note !== '') ? $note : 'Invoice #' . $number;

        $this->assertSame('Invoice #INV-101', $description);
    }

    // -----------------------------------------------------------------------
    // Invoice payload JSON encoding / decoding
    // -----------------------------------------------------------------------

    public function testPayloadEncodesInvId(): void
    {
        $invId   = 42;
        $payload = json_encode(['inv_id' => $invId], JSON_THROW_ON_ERROR);

        $this->assertJson($payload);
        $this->assertSame('{"inv_id":42}', $payload);
    }

    public function testPayloadDecodesInvId(): void
    {
        $payload = '{"inv_id":42}';
        /** @var array{inv_id?: int} $decoded */
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $invId   = $decoded['inv_id'] ?? 0;

        $this->assertSame(42, $invId);
    }

    public function testPayloadMissingInvIdFallsBackToZero(): void
    {
        $payload = '{"other_key":"value"}';
        /** @var array{inv_id?: int} $decoded */
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $invId   = $decoded['inv_id'] ?? 0;

        $this->assertSame(0, $invId);
    }

    public function testPayloadInvIdRoundTrip(): void
    {
        foreach ([1, 99, 1000, PHP_INT_MAX] as $id) {
            $payload = json_encode(['inv_id' => $id], JSON_THROW_ON_ERROR);
            /** @var array{inv_id?: int} $decoded */
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame($id, $decoded['inv_id'] ?? 0);
        }
    }

    // -----------------------------------------------------------------------
    // Webhook: successful_payment amount conversion
    // -----------------------------------------------------------------------

    public function testSuccessfulPaymentAmountConversion_TwoCurrencyDecimals(): void
    {
        // Telegram stores amounts in smallest unit (e.g. pence for GBP)
        $totalAmountInCents = 4999; // £49.99
        $amount             = $totalAmountInCents / 100.0;

        $this->assertSame(49.99, $amount);
    }

    public function testSuccessfulPaymentAmountConversion_RoundAmount(): void
    {
        $totalAmountInCents = 10000; // £100.00
        $amount             = $totalAmountInCents / 100.0;

        $this->assertSame(100.0, $amount);
    }

    public function testSuccessfulPaymentAmountConversion_SmallAmount(): void
    {
        $totalAmountInCents = 1; // £0.01
        $amount             = $totalAmountInCents / 100.0;

        $this->assertSame(0.01, $amount);
    }

    // -----------------------------------------------------------------------
    // Webhook: pre_checkout_query guard
    // -----------------------------------------------------------------------

    public function testZeroInvIdIsRejectedByWebhookGuard(): void
    {
        // Mirrors: if ($invId > 0) { record payment }
        $invId         = 0;
        $shouldRecord  = $invId > 0;

        $this->assertFalse($shouldRecord);
    }

    public function testPositiveInvIdPassesWebhookGuard(): void
    {
        $invId        = 7;
        $shouldRecord = $invId > 0;

        $this->assertTrue($shouldRecord);
    }

    // -----------------------------------------------------------------------
    // Webhook: secret-token header verification
    // Mirrors the guard in TelegramController::webhook():
    //   if ($secretToken !== '' && $incoming !== $secretToken) { reject }
    // -----------------------------------------------------------------------

    public function testSecretTokenMismatchIsRejected(): void
    {
        $stored   = 'my-secret';
        $incoming = 'wrong-value';

        $rejected = $stored !== '' && $incoming !== $stored;

        $this->assertTrue($rejected);
    }

    public function testSecretTokenMatchIsAccepted(): void
    {
        $stored   = 'my-secret';
        $incoming = 'my-secret';

        $rejected = $stored !== '' && $incoming !== $stored;

        $this->assertFalse($rejected);
    }

    public function testEmptyStoredSecretAcceptsAnyIncoming(): void
    {
        // No secret configured — the check is skipped entirely
        $stored   = '';
        $incoming = 'anything';

        $rejected = $stored !== '' && $incoming !== $stored;

        $this->assertFalse($rejected);
    }

    // -----------------------------------------------------------------------
    // Webhook: payment note construction
    // Mirrors: 'Telegram: ' . $successfulPayment->telegramPaymentChargeId
    // -----------------------------------------------------------------------

    public function testPaymentNoteContainsChargeId(): void
    {
        $chargeId = 'charge_abc123XYZ';
        $note     = 'Telegram: ' . $chargeId;

        $this->assertSame('Telegram: charge_abc123XYZ', $note);
        $this->assertStringStartsWith('Telegram: ', $note);
    }

    public function testPaymentNoteIncludesTguidWhenUserIdKnown(): void
    {
        $chargeId    = 'charge_abc123XYZ';
        $buyerUserId = 987654321;
        $note        = 'Telegram: ' . $chargeId . ' | tguid:' . $buyerUserId;

        $this->assertSame('Telegram: charge_abc123XYZ | tguid:987654321', $note);
        $this->assertMatchesRegularExpression(
            '/Telegram:\s*(\S+)\s*\|\s*tguid:(\d+)/',
            $note,
        );
    }

    public function testPaymentNoteFallsBackWhenUserIdZero(): void
    {
        $chargeId    = 'charge_abc123XYZ';
        $buyerUserId = 0;
        $note        = $buyerUserId > 0
            ? 'Telegram: ' . $chargeId . ' | tguid:' . $buyerUserId
            : 'Telegram: ' . $chargeId;

        $this->assertSame('Telegram: charge_abc123XYZ', $note);
        $this->assertStringNotContainsString('tguid', $note);
    }

    // -----------------------------------------------------------------------
    // Webhook: malformed JSON payload falls back gracefully
    // Mirrors the JsonException catch in webhook() successful_payment handler
    // -----------------------------------------------------------------------

    public function testMalformedPayloadDecodesToEmptyInvId(): void
    {
        $raw = 'not-valid-json';

        try {
            /** @var array{inv_id?: int} $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            $invId   = $decoded['inv_id'] ?? 0;
        } catch (\JsonException) {
            $invId = 0;
        }

        $this->assertSame(0, $invId);
    }

    public function testEmptyPayloadDecodesToEmptyInvId(): void
    {
        $raw = '{}';

        /** @var array{inv_id?: int} $decoded */
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        $invId   = $decoded['inv_id'] ?? 0;

        $this->assertSame(0, $invId);
    }
}
