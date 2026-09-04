<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\PaymentInformationQueryHelper;
use PHPUnit\Framework\TestCase;
use Yiisoft\Translator\TranslatorInterface;

final class PaymentInformationQueryHelperTest extends TestCase
{
    // ── extractProviderLower ─────────────────────────────────────────────────

    public function testExtractProviderLowerReturnsLowercasedProvider(): void
    {
        self::assertSame('paypal', PaymentInformationQueryHelper::extractProviderLower('Open_Banking_With_PayPal'));
    }

    public function testExtractProviderLowerReturnsNullWhenPatternDoesNotMatch(): void
    {
        self::assertNull(PaymentInformationQueryHelper::extractProviderLower('gateway_stripe'));
    }

    public function testExtractProviderLowerReturnsNullForEmptyString(): void
    {
        self::assertNull(PaymentInformationQueryHelper::extractProviderLower(''));
    }

    // ── isStripeStillProcessing ──────────────────────────────────────────────

    public function testIsStripeStillProcessingFalseWhenAlreadyPaid(): void
    {
        self::assertFalse(PaymentInformationQueryHelper::isStripeStillProcessing(true, 'processing'));
    }

    public function testIsStripeStillProcessingTrueWhenNotPaidAndProcessing(): void
    {
        self::assertTrue(PaymentInformationQueryHelper::isStripeStillProcessing(false, 'processing'));
    }

    public function testIsStripeStillProcessingTrueWhenNotPaidAndRedirectSucceeded(): void
    {
        // The redirect can say "succeeded" before our own webhook has confirmed it — still processing from our side.
        self::assertTrue(PaymentInformationQueryHelper::isStripeStillProcessing(false, 'succeeded'));
    }

    public function testIsStripeStillProcessingFalseWhenNotPaidAndCanceled(): void
    {
        self::assertFalse(PaymentInformationQueryHelper::isStripeStillProcessing(false, 'canceled'));
    }

    // ── stripeCompleteHeading ────────────────────────────────────────────────

    private function makeTranslator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('translate')->willReturnCallback(
            static fn (string|\Stringable $id): string => $id . ':%s',
        );

        return $translator;
    }

    public function testStripeCompleteHeadingUsesSuccessMessageWhenPaid(): void
    {
        $heading = PaymentInformationQueryHelper::stripeCompleteHeading(
            $this->makeTranslator(),
            true,
            'INV-001',
            'succeeded',
        );

        self::assertSame('online.payment.payment.successful:INV-001', $heading);
    }

    public function testStripeCompleteHeadingUsesProcessingMessageWhenStillProcessing(): void
    {
        $heading = PaymentInformationQueryHelper::stripeCompleteHeading(
            $this->makeTranslator(),
            false,
            'INV-002',
            'processing',
        );

        self::assertSame('online.payment.payment.processing:INV-002', $heading);
    }

    public function testStripeCompleteHeadingUsesFailedMessageOtherwise(): void
    {
        $heading = PaymentInformationQueryHelper::stripeCompleteHeading(
            $this->makeTranslator(),
            false,
            'INV-003',
            'requires_payment_method',
        );

        self::assertSame('online.payment.payment.failed:INV-003', $heading);
    }
}
