<?php

declare(strict_types=1);

namespace App\Auth\Trait;

/**
 * @property-read \App\Invoice\Setting\SettingRepository $sR
 */
trait TurnstileVerification
{
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
        $result = @file_get_contents(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            false,
            $context,
        );
        if ($result === false) {
            return false;
        }
        /** @var array{success?: bool} $json */
        $json = (array) json_decode($result, true);
        return ($json['success'] ?? false) === true;
    }
}
