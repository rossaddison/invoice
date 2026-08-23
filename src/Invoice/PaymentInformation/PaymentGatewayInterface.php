<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

/**
 * Common surface every online payment gateway integration exposes, regardless
 * of how its own payment-initiation flow works internally.
 */
interface PaymentGatewayInterface
{
    /**
     * Matches the suffix used in the `gateway_{driver}_enabled` setting key,
     * e.g. 'stripe', 'braintree', 'adyen'.
     */
    public function getDriverKey(): string;

    /**
     * True when the credentials this gateway needs to operate are present.
     */
    public function isConfigured(): bool;

    /**
     * Authoritatively confirms whether a payment identified by the given
     * provider reference (PaymentIntent id, transaction id, checkout session
     * id, etc.) has actually completed, by asking the provider — never by
     * trusting client-supplied input alone.
     */
    public function verifyPayment(string $providerReference): PaymentVerificationResult;

    /**
     * Issues a full refund of $amount against a previously completed
     * payment, identified by the same provider reference verifyPayment()
     * takes (PaymentIntent id, pspReference, transaction id, etc.). Never
     * throws on a provider-side failure — that's reported via
     * PaymentRefundResult::$refunded/$message so the caller can flash it.
     */
    public function refund(string $providerReference, float $amount): PaymentRefundResult;
}
