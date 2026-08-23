<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Invoice\Setting\SettingRepository;

/**
 * Resolves TrueLayer's own gateway configuration — Client Id/Secret,
 * signing key id/PEM, the fixed Return Url, and the sandbox flag — from
 * this app's own Settings table. Extracted out of `TrueLayerPaymentService`
 * itself purely to keep that class under SonarQube's per-class method-count
 * limit (php:S1448 — the guard-clause splitting done for php:S1142
 * elsewhere in that class pushed it to 24 methods); this class carries no
 * behavior of its own beyond reading and decoding Settings values, matching
 * exactly what those six methods already did inline before the split.
 *
 * `returnUrl()`'s own docblock (kept verbatim from before the move)
 * explains why it's a fixed Setting value, never dynamically generated —
 * see `TrueLayerPaymentService`'s own class docblock for the wider
 * TrueLayer integration context.
 */
final readonly class TrueLayerCredentials
{
    public function __construct(
        private SettingRepository $settings,
    ) {
    }

    public function isSandbox(): bool
    {
        return $this->settings->getSetting('gateway_truelayer_sandbox') === '1';
    }

    /**
     * A fixed URL manually entered in Settings, never dynamically
     * generated per-invoice — TrueLayer requires
     * `authorization_flow.redirect.return_uri` to exactly match a Redirect
     * URI pre-registered in Console (Settings > Redirect URIs), confirmed
     * directly against TrueLayer's own docs. Dynamically generating it via
     * this app's own UrlGeneratorInterface would risk it varying between
     * requests depending on the Locale middleware's per-request default
     * argument state (the same mechanism behind the entire August 2026
     * Checkout.com "Pay Now" redirect-loop investigation) — a fixed
     * Setting sidesteps that risk entirely rather than merely arguing it
     * away. Not encrypted — the 'returnUrl' field is 'text' type, since a
     * fixed redirect URL is not secret.
     */
    public function returnUrl(): string
    {
        return $this->settings->getSetting('gateway_truelayer_returnUrl') ?: '';
    }

    public function clientId(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_truelayer_clientId') ?: '');
    }

    public function clientSecret(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_truelayer_clientSecret') ?: '');
    }

    public function signingKid(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_truelayer_signingKid') ?: '');
    }

    public function privateKey(): string
    {
        return (string) $this->settings->decode(
            $this->settings->getSetting('gateway_truelayer_privateKey') ?: '');
    }
}
