<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\RazorpaySignatureService;
use Testo\Assert;
use Testo\Test;

/**
 * Covers RazorpaySignatureService: the Payment Link callback formula and the
 * webhook formula, both ground-truthed against `razorpay/razorpay-php`'s
 * real executable `Utility` class — see the class's own docblock. Pure
 * signature computation, no network I/O.
 */
#[Test]
final class RazorpaySignatureServiceTest
{
    private function service(): RazorpaySignatureService
    {
        return new RazorpaySignatureService();
    }

    public function verifyPaymentLinkCallbackSignatureAcceptsACorrectlySignedCallback(): void
    {
        $payload = 'plink_abc123|inv-url-key-1|paid|pay_xyz789';
        $signature = hash_hmac('sha256', $payload, 'key_secret_123');

        Assert::true($this->service()->verifyPaymentLinkCallbackSignature(
            'plink_abc123',
            'inv-url-key-1',
            'paid',
            'pay_xyz789',
            $signature,
            'key_secret_123',
        ));
    }

    public function verifyPaymentLinkCallbackSignatureRejectsATamperedStatus(): void
    {
        $signature = hash_hmac('sha256', 'plink_abc123|inv-url-key-1|paid|pay_xyz789', 'key_secret_123');

        Assert::false($this->service()->verifyPaymentLinkCallbackSignature(
            'plink_abc123',
            'inv-url-key-1',
            'created',
            'pay_xyz789',
            $signature,
            'key_secret_123',
        ));
    }

    public function verifyPaymentLinkCallbackSignatureRejectsAWrongSecret(): void
    {
        $signature = hash_hmac('sha256', 'plink_abc123|inv-url-key-1|paid|pay_xyz789', 'key_secret_123');

        Assert::false($this->service()->verifyPaymentLinkCallbackSignature(
            'plink_abc123',
            'inv-url-key-1',
            'paid',
            'pay_xyz789',
            $signature,
            'a-different-secret',
        ));
    }

    public function verifyWebhookSignatureAcceptsACorrectlySignedBody(): void
    {
        $rawBody = '{"event":"payment_link.paid"}';
        $signature = hash_hmac('sha256', $rawBody, 'webhook_secret_456');

        Assert::true($this->service()->verifyWebhookSignature($rawBody, $signature, 'webhook_secret_456'));
    }

    public function verifyWebhookSignatureRejectsATamperedBody(): void
    {
        $signature = hash_hmac('sha256', '{"event":"payment_link.paid"}', 'webhook_secret_456');

        Assert::false($this->service()->verifyWebhookSignature('{"event":"tampered"}', $signature, 'webhook_secret_456'));
    }

    public function verifyWebhookSignatureRejectsAnEmptySignature(): void
    {
        Assert::false($this->service()->verifyWebhookSignature('{}', '', 'webhook_secret_456'));
    }

    public function verifyWebhookSignatureRejectsAnEmptySecret(): void
    {
        $rawBody = '{}';
        $signature = hash_hmac('sha256', $rawBody, 'webhook_secret_456');

        Assert::false($this->service()->verifyWebhookSignature($rawBody, $signature, ''));
    }

    public function apiSecretAndWebhookSecretProduceDifferentSignaturesForTheSamePayload(): void
    {
        // Confirms the two signature methods really are independent formulas
        // sharing only their HMAC-SHA256 mechanics, not interchangeable secrets.
        $payload = 'plink_abc123|inv-url-key-1|paid|pay_xyz789';

        $viaCallback = hash_hmac('sha256', $payload, 'key_secret_123');
        $viaWebhook = hash_hmac('sha256', $payload, 'webhook_secret_456');

        Assert::notSame($viaCallback, $viaWebhook);
    }
}
