<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

/**
 * Checkout.com webhook signature verification. Ground-truthed against
 * Checkout.com's own published guidance (support.checkout.com/hc/en-us/
 * articles/29686673313426-Verify-a-webhook-was-sent-by-Checkout-com):
 * the `Cko-Signature` header is a hex-encoded (Base16) HMAC-SHA256 of the
 * *raw* request body, keyed by the webhook's own signing key (a value
 * generated when the webhook endpoint is configured in Checkout.com's
 * Hub — a separate credential from the account's API secret key, the
 * same separation Stripe/Adyen/GoCardless/Square all also use for their
 * own webhook secrets).
 *
 * Deliberately raw-body-first, matching Checkout.com's own explicit
 * warning that recomputing the signature against a
 * decoded-then-re-encoded payload can silently change numeric precision
 * and produce a false mismatch — the same class of gotcha already hit
 * for this app's other JSON-body-signed gateways (PayPal, Adyen,
 * Mercado Pago), so `CheckoutComWebhookHandler` always signs/decodes in
 * that order too.
 */
final class CheckoutComSignatureService
{
    public function verifyWebhookSignature(string $rawBody, string $signatureHeader, string $signingKey): bool
    {
        if ($signatureHeader === '' || $signingKey === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $signingKey);

        return hash_equals($expected, $signatureHeader);
    }
}
