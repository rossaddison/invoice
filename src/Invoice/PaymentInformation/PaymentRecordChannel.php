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
            // High Voltage Sign (U+26A1) -- deliberately NOT the literal
            // hook emoji (U+1FA9D 🪝) originally used here. That's a
            // 4-byte UTF-8 character; this app's live MySQL connection
            // was found to silently negotiate a narrower charset that
            // can't store it, so every real webhook-driven payment wrote
            // this note and crashed the insert (MySQL 1366) before the
            // invoice could be marked paid -- broke automatic paid-marking
            // for every webhook gateway (Mollie included), not just
            // PayPal, the one that surfaced it first. U+26A1 sits in the
            // Miscellaneous Symbols block, part of the Basic Multilingual
            // Plane, so it's at most 3 bytes in UTF-8 and safe regardless
            // of the connection's actual negotiated charset. See
            // docs/MYSQL_CONNECTION_CHARSET_BUG_AUGUST_2026.md -- that
            // connection-level gap is real and still needs a proper fix,
            // this just stops it from silently breaking every payment in
            // the meantime.
            self::Webhook => '⚡',
            // "Right Arrow Curving Left" (U+21A9 U+FE0F) — the classic
            // reply/return arrow, chosen over a plain rightwards arrow so
            // it reads unambiguously as "came back via a redirect" rather
            // than a directional/navigation cue. Also BMP-safe (both
            // codepoints ≤ U+FFFF), confirmed for the same reason above.
            self::Redirect => '↩️',
        };
    }
}
