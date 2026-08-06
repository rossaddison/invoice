<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\SquareSignatureService;
use Testo\Assert;
use Testo\Test;

/**
 * Covers SquareSignatureService: the x-square-hmacsha256-signature webhook
 * formula, ground-truthed against Square's own current developer docs
 * (developer.squareup.com/docs/webhooks/step3validate) — see the class's
 * own docblock. Pure signature computation, no network I/O.
 */
#[Test]
final class SquareSignatureServiceTest
{
    private function service(): SquareSignatureService
    {
        return new SquareSignatureService();
    }

    public function verifyWebhookSignatureAcceptsACorrectlySignedNotification(): void
    {
        $notificationUrl = 'https://invoice.example/paymentinformation/squareWebhook';
        $rawBody = '{"type":"payment.updated"}';
        $signatureKey = 'webhook-signature-key-123';
        $signature = base64_encode(hash_hmac('sha256', $notificationUrl . $rawBody, $signatureKey, true));

        Assert::true($this->service()->verifyWebhookSignature($notificationUrl, $rawBody, $signature, $signatureKey));
    }

    public function verifyWebhookSignatureRejectsATamperedBody(): void
    {
        $notificationUrl = 'https://invoice.example/paymentinformation/squareWebhook';
        $signatureKey = 'webhook-signature-key-123';
        $signature = base64_encode(hash_hmac(
            'sha256',
            $notificationUrl . '{"type":"payment.updated"}',
            $signatureKey,
            true,
        ));

        Assert::false($this->service()->verifyWebhookSignature(
            $notificationUrl,
            '{"type":"payment.updated","tampered":true}',
            $signature,
            $signatureKey,
        ));
    }

    public function verifyWebhookSignatureRejectsAMismatchedNotificationUrl(): void
    {
        // Confirms the URL itself is really part of the hashed payload —
        // a signature computed for one webhook subscription URL must not
        // validate against a different one.
        $rawBody = '{"type":"payment.updated"}';
        $signatureKey = 'webhook-signature-key-123';
        $signature = base64_encode(hash_hmac(
            'sha256',
            'https://invoice.example/paymentinformation/squareWebhook' . $rawBody,
            $signatureKey,
            true,
        ));

        Assert::false($this->service()->verifyWebhookSignature(
            'https://invoice.example/some-other-path',
            $rawBody,
            $signature,
            $signatureKey,
        ));
    }

    public function verifyWebhookSignatureRejectsAWrongSignatureKey(): void
    {
        $notificationUrl = 'https://invoice.example/paymentinformation/squareWebhook';
        $rawBody = '{"type":"payment.updated"}';
        $signature = base64_encode(hash_hmac('sha256', $notificationUrl . $rawBody, 'webhook-signature-key-123', true));

        Assert::false($this->service()->verifyWebhookSignature($notificationUrl, $rawBody, $signature, 'a-different-key'));
    }

    public function verifyWebhookSignatureRejectsAnEmptySignatureHeader(): void
    {
        Assert::false($this->service()->verifyWebhookSignature('https://invoice.example/x', '{}', '', 'webhook-signature-key-123'));
    }

    public function verifyWebhookSignatureRejectsAnEmptySignatureKey(): void
    {
        $notificationUrl = 'https://invoice.example/x';
        $rawBody = '{}';
        $signature = base64_encode(hash_hmac('sha256', $notificationUrl . $rawBody, 'webhook-signature-key-123', true));

        Assert::false($this->service()->verifyWebhookSignature($notificationUrl, $rawBody, $signature, ''));
    }
}
