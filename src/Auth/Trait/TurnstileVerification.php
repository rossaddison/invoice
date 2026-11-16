<?php

declare(strict_types=1);

namespace App\Auth\Trait;

/**
 * @property-read \App\Invoice\Setting\SettingRepository $sR
 */
trait TurnstileVerification
{
    private const string TURNSTILE_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Verify a Cloudflare Turnstile token server-side.
     * Returns true unconditionally when no secret is configured (dev/localhost).
     */
    private function verifyTurnstile(string $token, string $remoteIp): bool
    {
        $secret = $this->sR->getSetting('turnstile_secret_key');
        if ($secret === '' || $secret === '0') {
            return true;
        }
        return $this->siteverify($secret, $token, $remoteIp);
    }

    /**
     * Fail-closed variant for the public signup forms: with no secret configured the check fails,
     * so a blanked or never-set secret cannot silently remove the bot check. Local development uses
     * Cloudflare's published test keys (secret 1x0000000000000000000000000000000AA always passes).
     */
    private function verifyTurnstileRequired(string $token, string $remoteIp): bool
    {
        $secret = $this->sR->getSetting('turnstile_secret_key');
        if ($secret === '' || $secret === '0') {
            return false;
        }
        return $this->siteverify($secret, $token, $remoteIp);
    }

    private function siteverify(string $secret, string $token, string $remoteIp): bool
    {
        if ($token === '') {
            return false;
        }
        $payload = http_build_query([
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $remoteIp,
        ]);
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $payload,
                'timeout' => 5,
            ],
        ]);
        $result = @file_get_contents(self::TURNSTILE_VERIFY_URL, false, $context);
        /** @var array{success?: bool} $json */
        $json = $result !== false ? (array) json_decode($result, true) : [];
        return ($json['success'] ?? false) === true;
    }
}
