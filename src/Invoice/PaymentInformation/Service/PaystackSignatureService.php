<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

/**
 * Paystack webhook signature verification, ground-truthed by reading the
 * `Yabacon\Paystack\Event` class's real executable `validFor()` method
 * directly from `yabacon/paystack-php` (github.com/yabacon/paystack-php,
 * read for research purposes only — not installed; see
 * PaystackPaymentService's docblock for why no SDK is used here).
 *
 * Confirmed: the `X-Paystack-Signature` header is
 * `hash_hmac('sha512', $rawBody, $secretKey)` — signed with the merchant's
 * own API **secret key** (`sk_test_.../sk_live_...`), not a separate
 * webhook-specific secret. The reference implementation compares with a
 * plain `===`; this class uses `hash_equals()` instead for a timing-safe
 * comparison, matching every other signature check already in this app
 * (Robokassa, GoCardless, YooKassa's error-envelope-adjacent checks).
 */
final class PaystackSignatureService
{
    public function verifyWebhookSignature(string $rawBody, string $signatureHeader, string $secretKey): bool
    {
        if ($signatureHeader === '' || $secretKey === '') {
            return false;
        }

        $expected = hash_hmac('sha512', $rawBody, $secretKey);

        return hash_equals($expected, $signatureHeader);
    }
}
