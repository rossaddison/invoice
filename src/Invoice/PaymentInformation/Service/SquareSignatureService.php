<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

/**
 * Square webhook signature verification, ground-truthed against Square's
 * own current developer docs (`developer.squareup.com/docs/webhooks/
 * step3validate`, reachable and fetched directly this session — unlike
 * Paystack's 403s).
 *
 * Confirmed: the `x-square-hmacsha256-signature` header is
 * `base64_encode(hash_hmac('sha256', $notificationUrl . $rawBody,
 * $signatureKey, true))` — unlike every other HMAC-signed gateway in this
 * app, Square hashes the **notification URL concatenated with the raw
 * body**, not the raw body alone, and the result is base64-encoded rather
 * than hex. `$notificationUrl` must match exactly what's configured for
 * this webhook subscription in the Square Developer Dashboard (scheme,
 * host, and path all significant) — see SquareWebhookHandler's own
 * docblock for how that URL is constructed.
 */
final class SquareSignatureService
{
    public function verifyWebhookSignature(
        string $notificationUrl,
        string $rawBody,
        string $signatureHeader,
        string $signatureKey,
    ): bool {
        if ($signatureHeader === '' || $signatureKey === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $notificationUrl . $rawBody, $signatureKey, true));

        return hash_equals($expected, $signatureHeader);
    }
}
