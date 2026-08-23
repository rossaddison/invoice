<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

/**
 * Worldpay webhook signature verification — confirmed directly from
 * Worldpay's real `docs.worldpay.com/access/products/events/signature`
 * page (not a guessed/secondhand format):
 *
 * - Header: `Event-Signature: {keyId}/{hashFunction}/{signature}`.
 * - **Can contain multiple entries, comma-separated** (e.g. during a
 *   shared-secret rotation window Worldpay may sign with both the old
 *   and new key): `1/SHA256/XXXX,2/SHA256/YYYY`. The docs explicitly
 *   warn the order isn't guaranteed. This app only ever configures one
 *   `webhookSecret` (no keyId-to-secret lookup table, same
 *   single-secret shape as every other gateway here), so the correct
 *   behaviour is to try every comma-separated entry against that one
 *   secret and accept if *any* of them verifies — not to assume the
 *   first (or only) entry is the relevant one.
 * - `hashFunction` is currently always `SHA256`.
 * - `signature` is an HMAC of the raw webhook body, using a shared
 *   secret obtained (alongside the webhook URL itself) from a Worldpay
 *   Implementation Manager — this product's own metadata is
 *   Enterprise-only, unlike the core Payments API.
 *
 * No shared signature-verification interface exists across this app's
 * 15 other gateways, by established convention — one small, standalone
 * class per gateway. Structurally closest to
 * MercadoPagoSignatureService (a composite/delimited header that must
 * be parsed before the HMAC comparison), just split on `,`/`/` instead
 * of `,`/`=`.
 */
final class WorldpaySignatureService
{
    public function verifyWebhookSignature(string $rawBody, string $eventSignatureHeader, string $secret): bool
    {
        if ($eventSignatureHeader === '' || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        foreach (explode(',', $eventSignatureHeader) as $entry) {
            if ($this->entryMatches(trim($entry), $expected)) {
                return true;
            }
        }

        return false;
    }

    private function entryMatches(string $entry, string $expected): bool
    {
        $parts = explode('/', $entry, 3);
        if (count($parts) !== 3) {
            return false;
        }

        [, $hashFunction, $signature] = $parts;
        if ($signature === '' || strtoupper($hashFunction) !== 'SHA256') {
            return false;
        }

        return hash_equals($expected, $signature);
    }
}
