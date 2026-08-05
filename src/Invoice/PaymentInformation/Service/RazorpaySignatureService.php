<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

/**
 * Razorpay signature verification for both the Payment Link redirect
 * callback and the async webhook — ground-truthed by reading
 * `Razorpay\Api\Utility`'s real executable code directly from the official
 * `razorpay/razorpay-php` SDK (github.com/razorpay/razorpay-php, an actively
 * maintained first-party package — read for research purposes only; not
 * installed, since it depends on `rmccue/requests` rather than the Guzzle
 * client every other gateway in this app is built and tested against, and
 * ships no mockable test double for that HTTP layer — see
 * RazorpayPaymentService's own docblock for the full reasoning).
 *
 * Confirmed directly from `Utility::verifyPaymentSignature()` /
 * `Utility::verifySignature()`:
 * - Payment Link callback: payload =
 *   `"{payment_link_id}|{payment_link_reference_id}|{payment_link_status}|{payment_id}"`,
 *   verified as `hash_equals(hash_hmac('sha256', $payload, $keySecret), $signature)`
 *   — signed with the merchant's own API **key secret**, carried back to the
 *   browser as the `razorpay_signature` query parameter on `callback_url`.
 * - Webhook: `Utility::verifyWebhookSignature($payload, $signature, $secret)`
 *   is a thin wrapper around the exact same `verifySignature()` formula, just
 *   with the raw request body as `$payload` and a **separate webhook secret**
 *   (configured in the Razorpay Dashboard when setting up the webhook, not
 *   the API key secret) — confirmed via the SDK's `Webhook` entity class
 *   existing as a distinct credential from `Api::getSecret()`.
 *
 * The `X-Razorpay-Signature` header name itself is NOT independently
 * re-confirmed against Razorpay's own primary docs this session (its
 * dedicated webhook-signature-validation doc page 404'd on fetch) — it is
 * based on well-established, consistently-documented general public
 * knowledge of Razorpay's webhook implementation, not a primary-source
 * confirmation. The `payment_link.paid` event name and its
 * `payload.payment_link.entity`/`payload.payment.entity` nesting ARE
 * confirmed directly from Razorpay's own current API docs
 * (`razorpay.com/docs/webhooks/payment-links/`) — see
 * RazorpayWebhookHandler's docblock.
 */
final class RazorpaySignatureService
{
    public function verifyPaymentLinkCallbackSignature(
        string $paymentLinkId,
        string $paymentLinkReferenceId,
        string $paymentLinkStatus,
        string $paymentId,
        string $signature,
        string $keySecret,
    ): bool {
        $payload = $paymentLinkId . '|' . $paymentLinkReferenceId . '|' . $paymentLinkStatus . '|' . $paymentId;

        return $this->verify($payload, $signature, $keySecret);
    }

    public function verifyWebhookSignature(string $rawBody, string $signatureHeader, string $webhookSecret): bool
    {
        return $this->verify($rawBody, $signatureHeader, $webhookSecret);
    }

    private function verify(string $payload, string $signature, string $secret): bool
    {
        if ($signature === '' || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
