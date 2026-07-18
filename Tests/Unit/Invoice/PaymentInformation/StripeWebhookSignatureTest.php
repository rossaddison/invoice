<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\PaymentInformation;

use PHPUnit\Framework\TestCase;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Exercises the exact signature-verification primitive
 * StripePaymentService::verifyWebhookSignature() delegates to
 * (Stripe\Webhook::constructEvent()). This is pure HMAC computation with no
 * network I/O, so it's fully testable without a live Stripe API or a mocked
 * SettingRepository.
 *
 * Note: StripePaymentService itself isn't instantiated here because its
 * constructor requires the concrete `final` SettingRepository class (not
 * SettingRepositoryInterface), which PHPUnit cannot generate a test double
 * for — that's a pre-existing constructor-design constraint, unrelated to
 * this change.
 */
final class StripeWebhookSignatureTest extends TestCase
{
    private const string SECRET = 'whsec_test_secret';

    private function signedHeader(string $payload, int $timestamp): string
    {
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, self::SECRET);

        return 't=' . $timestamp . ',v1=' . $signature;
    }

    public function testValidSignatureIsAccepted(): void
    {
        $payload = '{"id":"evt_1","type":"payment_intent.succeeded"}';
        $header  = $this->signedHeader($payload, time());

        $event = Webhook::constructEvent($payload, $header, self::SECRET);

        self::assertSame('evt_1', $event->id);
        self::assertSame('payment_intent.succeeded', $event->type);
    }

    public function testTamperedPayloadIsRejected(): void
    {
        $originalPayload = '{"id":"evt_1","type":"payment_intent.succeeded"}';
        $header          = $this->signedHeader($originalPayload, time());
        $tamperedPayload = '{"id":"evt_1","type":"payment_intent.payment_failed"}';

        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEvent($tamperedPayload, $header, self::SECRET);
    }

    public function testWrongSecretIsRejected(): void
    {
        $payload = '{"id":"evt_1","type":"payment_intent.succeeded"}';
        $header  = $this->signedHeader($payload, time());

        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEvent($payload, $header, 'whsec_wrong_secret');
    }
}
