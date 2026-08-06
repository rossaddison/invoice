<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

/**
 * How a payment actually got recorded — surfaced as an emoji prefix on the
 * Payment note (see OnlinePaymentRecorderService::recordSuccess()) so an
 * admin looking at the payment list/detail view can tell at a glance which
 * mechanism confirmed it, without having to dig through logs.
 *
 * Almost every gateway in this app is Webhook-only: RobokassaWebhookHandler,
 * YookassaWebhookHandler, PaystackWebhookHandler, RazorpayWebhookHandler,
 * PaypalWebhookHandler, MollieWebhookHandler, StripeWebhookHandler,
 * AdyenWebhookHandler, and GoCardlessWebhookHandler all record exclusively
 * from an async, signature-verified server-to-server notification — see
 * PaymentRecordContext's default. Only two call sites in this app actually
 * record from a synchronous redirect/form-POST instead, both in
 * PaymentInformationController: Braintree's Drop-in form POST
 * (renderBraintreePostResponse(), which never has a webhook at all — see
 * its own docblock) and Mollie's legacy pre-webhook fallback path in
 * mollieComplete() (kept only as a belt-and-braces guard now that
 * MollieWebhookHandler exists — see that method's own comments).
 */
enum PaymentRecordChannel: string
{
    case Webhook = 'webhook';
    case Redirect = 'redirect';

    public function emoji(): string
    {
        return match ($this) {
            self::Webhook => '🪝',
            // "Right Arrow Curving Left" (U+21A9 U+FE0F) — the classic
            // reply/return arrow, chosen over a plain rightwards arrow so
            // it reads unambiguously as "came back via a redirect" rather
            // than a directional/navigation cue.
            self::Redirect => '↩️',
        };
    }
}
