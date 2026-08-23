<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\WorldpaySignatureService;
use Testo\Assert;
use Testo\Test;

/**
 * Covers WorldpaySignatureService: the `Event-Signature:
 * {keyId}/{hashFunction}/{signature}` HMAC-SHA256 format, confirmed
 * directly from Worldpay's real
 * `docs.worldpay.com/access/products/events/signature` page — see the
 * service's own docblock. Pure signature computation, no network I/O.
 */
#[Test]
final class WorldpaySignatureServiceTest
{
    private function service(): WorldpaySignatureService
    {
        return new WorldpaySignatureService();
    }

    public function verifyWebhookSignatureAcceptsACorrectlySignedBody(): void
    {
        $body = '{"eventId":"evt_1"}';
        $signature = hash_hmac('sha256', $body, 'test_secret');
        $header = "12345/SHA256/{$signature}";

        Assert::true($this->service()->verifyWebhookSignature($body, $header, 'test_secret'));
    }

    public function verifyWebhookSignatureIsCaseInsensitiveOnTheHashFunctionName(): void
    {
        $body = '{"eventId":"evt_1"}';
        $signature = hash_hmac('sha256', $body, 'test_secret');
        $header = "12345/sha256/{$signature}";

        Assert::true($this->service()->verifyWebhookSignature($body, $header, 'test_secret'));
    }

    public function verifyWebhookSignatureRejectsATamperedBody(): void
    {
        $signature = hash_hmac('sha256', '{"eventId":"evt_1"}', 'test_secret');
        $header = "12345/SHA256/{$signature}";

        Assert::false($this->service()->verifyWebhookSignature('{"eventId":"evt_2"}', $header, 'test_secret'));
    }

    public function verifyWebhookSignatureRejectsAWrongSecret(): void
    {
        $body = '{"eventId":"evt_1"}';
        $signature = hash_hmac('sha256', $body, 'test_secret');
        $header = "12345/SHA256/{$signature}";

        Assert::false($this->service()->verifyWebhookSignature($body, $header, 'a-different-secret'));
    }

    public function verifyWebhookSignatureRejectsAnUnsupportedHashFunction(): void
    {
        $body = '{"eventId":"evt_1"}';
        $signature = hash_hmac('sha512', $body, 'test_secret');
        $header = "12345/SHA512/{$signature}";

        Assert::false($this->service()->verifyWebhookSignature($body, $header, 'test_secret'));
    }

    public function verifyWebhookSignatureRejectsAMalformedHeaderMissingASegment(): void
    {
        Assert::false($this->service()->verifyWebhookSignature('{}', '12345/SHA256', 'test_secret'));
    }

    public function verifyWebhookSignatureRejectsAnEmptyHeader(): void
    {
        Assert::false($this->service()->verifyWebhookSignature('{}', '', 'test_secret'));
    }

    public function verifyWebhookSignatureRejectsAnEmptySecret(): void
    {
        $body = '{"eventId":"evt_1"}';
        $signature = hash_hmac('sha256', $body, 'test_secret');
        $header = "12345/SHA256/{$signature}";

        Assert::false($this->service()->verifyWebhookSignature($body, $header, ''));
    }

    public function verifyWebhookSignatureRejectsAnEmptySignatureSegment(): void
    {
        Assert::false($this->service()->verifyWebhookSignature('{}', '12345/SHA256/', 'test_secret'));
    }

    public function verifyWebhookSignatureRejectsATooShortHeaderWithNoSlashes(): void
    {
        Assert::false($this->service()->verifyWebhookSignature('{}', 'not-a-real-header', 'test_secret'));
    }
}
