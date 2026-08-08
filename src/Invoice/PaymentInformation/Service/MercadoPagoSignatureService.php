<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

/**
 * Mercado Pago webhook signature verification — ground-truthed by reading
 * `MercadoPago\Webhook\WebhookSignatureValidator`'s real executable code
 * directly from the official `mercadopago/sdk-php` SDK
 * (github.com/mercadopago/sdk-php, an actively maintained first-party
 * package — read for research purposes only; not installed, see
 * MercadoPagoPaymentService's own docblock for the full reasoning).
 *
 * Confirmed directly from `WebhookSignatureValidator::validate()`:
 * - The `x-signature` header is a comma-separated `key=value` list, e.g.
 *   `"ts=1704908010,v1=618c85345248dd820d5fd456117c2ab2ef8677530c393a"` —
 *   only the `ts` and `v1` (or other `v\d+`) pairs matter.
 * - The signed manifest is built as `"id:{dataId};request-id:{requestId};ts:{ts};"`,
 *   with the `id:`/`request-id:` segments omitted entirely (not just blank)
 *   when `data.id`/`x-request-id` are absent — never assume both are
 *   present.
 * - HMAC-SHA256 of that manifest using the webhook secret (configured in
 *   Mercado Pago's own dashboard under "Tus Integraciones", a separate
 *   credential from the API access token), compared via `hash_equals()`.
 * - `data.id` should be lowercased before building the manifest — per the
 *   SDK's own parameter docblock ("Lowercased before HMAC"); the actual
 *   `normalize()` helper in the SDK class only trims, so this is a
 *   caller responsibility the SDK documents but doesn't enforce itself —
 *   done explicitly here instead, matching Mercado Pago's documented
 *   intent rather than the SDK's own implementation gap.
 *
 * **QR Code notifications are not signed by Mercado Pago at all** (per the
 * SDK class's own docblock) — this validator is only ever used for the
 * Checkout Pro payment webhook this app actually integrates.
 */
final class MercadoPagoSignatureService
{
    public function verifyWebhookSignature(
        string $xSignatureHeader,
        ?string $xRequestId,
        ?string $dataId,
        string $secret,
    ): bool {
        if ($xSignatureHeader === '' || $secret === '') {
            return false;
        }

        [$ts, $hashes] = $this->parseSignatureHeader($xSignatureHeader);
        if ($ts === null || !isset($hashes['v1'])) {
            return false;
        }

        $manifest = $this->buildManifest(
            $dataId !== null ? strtolower($dataId) : null,
            $xRequestId,
            $ts,
        );
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $hashes['v1']);
    }

    /**
     * @return array{0: string|null, 1: array<string, string>}
     */
    private function parseSignatureHeader(string $header): array
    {
        $hashes = [];
        $ts = null;

        foreach (explode(',', $header) as $part) {
            $pieces = explode('=', $part, 2);
            if (count($pieces) !== 2) {
                continue;
            }

            $key = strtolower(trim($pieces[0]));
            $value = trim($pieces[1]);
            if ($key === '' || $value === '') {
                continue;
            }

            if ($key === 'ts') {
                $ts = $value;
            } elseif (preg_match('/^v\d+$/', $key)) {
                $hashes[$key] = $value;
            }
        }

        return [$ts, $hashes];
    }

    private function buildManifest(?string $dataId, ?string $requestId, string $ts): string
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
}
