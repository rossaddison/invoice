<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation;

use App\Invoice\PaymentInformation\Service\MercadoPagoSignatureService;
use Testo\Assert;
use Testo\Test;

/**
 * Covers MercadoPagoSignatureService: the `x-signature` HMAC formula,
 * ground-truthed against `mercadopago/sdk-php`'s real executable
 * `Webhook\WebhookSignatureValidator` class — see the service's own
 * docblock. Pure signature computation, no network I/O.
 */
#[Test]
final class MercadoPagoSignatureServiceTest
{
    private function service(): MercadoPagoSignatureService
    {
        return new MercadoPagoSignatureService();
    }

    /**
     * Mirrors MercadoPagoSignatureService's own private buildManifest() —
     * duplicated deliberately so a bug in the implementation can't also be
     * baked into the test's expectation.
     */
    private function manifest(?string $dataId, ?string $requestId, string $ts): string
    {
        $parts = [];
        if ($dataId !== null && $dataId !== '') {
            $parts[] = 'id:' . $dataId;
        }
        if ($requestId !== null && $requestId !== '') {
            $parts[] = 'request-id:' . $requestId;
        }
        $parts[] = 'ts:' . $ts;
        return implode(';', $parts) . ';';
    }

    public function verifyWebhookSignatureAcceptsACorrectlySignedNotification(): void
    {
        $manifest = $this->manifest('12345', 'req-abc-123', '1704908010');
        $v1 = hash_hmac('sha256', $manifest, 'test_secret');
        $header = "ts=1704908010,v1={$v1}";

        Assert::true($this->service()->verifyWebhookSignature($header, 'req-abc-123', '12345', 'test_secret'));
    }

    public function verifyWebhookSignatureLowercasesDataIdBeforeHashing(): void
    {
        // Signed against the lowercased id, per Mercado Pago's own
        // documented convention — verify() must lowercase its $dataId
        // argument to match, even when the caller passes it mixed-case.
        $manifest = $this->manifest('abc123', null, '1704908010');
        $v1 = hash_hmac('sha256', $manifest, 'test_secret');
        $header = "ts=1704908010,v1={$v1}";

        Assert::true($this->service()->verifyWebhookSignature($header, null, 'ABC123', 'test_secret'));
    }

    public function verifyWebhookSignatureOmitsMissingIdAndRequestIdSegmentsEntirely(): void
    {
        // Neither data.id nor x-request-id present — manifest is just "ts:...;",
        // not "id:;request-id:;ts:...;" with empty segments.
        $manifest = $this->manifest(null, null, '1704908010');
        $v1 = hash_hmac('sha256', $manifest, 'test_secret');
        $header = "ts=1704908010,v1={$v1}";

        Assert::true($this->service()->verifyWebhookSignature($header, null, null, 'test_secret'));
    }

    public function verifyWebhookSignatureRejectsATamperedDataId(): void
    {
        $manifest = $this->manifest('12345', 'req-abc-123', '1704908010');
        $v1 = hash_hmac('sha256', $manifest, 'test_secret');
        $header = "ts=1704908010,v1={$v1}";

        Assert::false($this->service()->verifyWebhookSignature($header, 'req-abc-123', '99999', 'test_secret'));
    }

    public function verifyWebhookSignatureRejectsATamperedTimestamp(): void
    {
        $manifest = $this->manifest('12345', 'req-abc-123', '1704908010');
        $v1 = hash_hmac('sha256', $manifest, 'test_secret');
        // Header carries a different ts than what was actually signed.
        $header = "ts=9999999999,v1={$v1}";

        Assert::false($this->service()->verifyWebhookSignature($header, 'req-abc-123', '12345', 'test_secret'));
    }

    public function verifyWebhookSignatureRejectsAWrongSecret(): void
    {
        $manifest = $this->manifest('12345', 'req-abc-123', '1704908010');
        $v1 = hash_hmac('sha256', $manifest, 'test_secret');
        $header = "ts=1704908010,v1={$v1}";

        Assert::false($this->service()->verifyWebhookSignature($header, 'req-abc-123', '12345', 'a-different-secret'));
    }

    public function verifyWebhookSignatureRejectsAMissingV1Hash(): void
    {
        Assert::false($this->service()->verifyWebhookSignature('ts=1704908010', 'req-abc-123', '12345', 'test_secret'));
    }

    public function verifyWebhookSignatureRejectsAMissingTimestamp(): void
    {
        Assert::false($this->service()->verifyWebhookSignature('v1=somehash', 'req-abc-123', '12345', 'test_secret'));
    }

    public function verifyWebhookSignatureRejectsAnEmptyHeader(): void
    {
        Assert::false($this->service()->verifyWebhookSignature('', 'req-abc-123', '12345', 'test_secret'));
    }

    public function verifyWebhookSignatureRejectsAnEmptySecret(): void
    {
        $manifest = $this->manifest('12345', 'req-abc-123', '1704908010');
        $v1 = hash_hmac('sha256', $manifest, 'test_secret');
        $header = "ts=1704908010,v1={$v1}";

        Assert::false($this->service()->verifyWebhookSignature($header, 'req-abc-123', '12345', ''));
    }

    public function verifyWebhookSignaturePicksTheV1HashAmongMultipleVersions(): void
    {
        $manifest = $this->manifest('12345', null, '1704908010');
        $v1 = hash_hmac('sha256', $manifest, 'test_secret');
        // A hypothetical future v2 hash present alongside v1 — the
        // implementation must not accidentally pick it up instead.
        $header = "ts=1704908010,v2=some-future-hash,v1={$v1}";

        Assert::true($this->service()->verifyWebhookSignature($header, null, '12345', 'test_secret'));
    }
}
