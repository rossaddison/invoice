<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\PaystackSignatureService;
use Testo\Assert;
use Testo\Test;

/**
 * Covers PaystackSignatureService: the `X-Paystack-Signature` webhook
 * formula, ground-truthed against `yabacon/paystack-php`'s real executable
 * `Event::validFor()` method — see the class's own docblock. Pure signature
 * computation, no network I/O.
 */
#[Test]
final class PaystackSignatureServiceTest
{
    private function service(): PaystackSignatureService
    {
        return new PaystackSignatureService();
    }

    public function verifyWebhookSignatureAcceptsACorrectlySignedBody(): void
    {
        $rawBody = '{"event":"charge.success","data":{"reference":"inv-123"}}';
        $secretKey = 'sk_test_abc123';
        $signature = hash_hmac('sha512', $rawBody, $secretKey);

        Assert::true($this->service()->verifyWebhookSignature($rawBody, $signature, $secretKey));
    }

    public function verifyWebhookSignatureRejectsATamperedBody(): void
    {
        $secretKey = 'sk_test_abc123';
        $signature = hash_hmac('sha512', '{"event":"charge.success","data":{"reference":"inv-123"}}', $secretKey);

        Assert::false($this->service()->verifyWebhookSignature(
            '{"event":"charge.success","data":{"reference":"inv-999-tampered"}}',
            $signature,
            $secretKey,
        ));
    }

    public function verifyWebhookSignatureRejectsAWrongSecretKey(): void
    {
        $rawBody = '{"event":"charge.success","data":{"reference":"inv-123"}}';
        $signature = hash_hmac('sha512', $rawBody, 'sk_test_abc123');

        Assert::false($this->service()->verifyWebhookSignature($rawBody, $signature, 'sk_test_a-different-key'));
    }

    public function verifyWebhookSignatureRejectsAnEmptySignatureHeader(): void
    {
        Assert::false($this->service()->verifyWebhookSignature('{}', '', 'sk_test_abc123'));
    }

    public function verifyWebhookSignatureRejectsAnEmptySecretKey(): void
    {
        $rawBody = '{}';
        $signature = hash_hmac('sha512', $rawBody, 'sk_test_abc123');

        Assert::false($this->service()->verifyWebhookSignature($rawBody, $signature, ''));
    }
}
