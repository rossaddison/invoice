<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\Trait\TurnstileVerification;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers the branches of TurnstileVerification that decide, without a network call,
 * whether a signup POST is let through. The siteverify HTTP call itself is not covered:
 * its URL is a private constant, so it cannot be pointed at a fake.
 */
#[Test]
final class TurnstileVerificationTest
{
    /** @return \Closure(string, string): bool */
    private function makeVerifier(string $secret): \Closure
    {
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with('turnstile_secret_key')->andReturn($secret);

        $verifier = new class ($sR) {
            use TurnstileVerification;

            public function __construct(private readonly SettingRepository $sR)
            {
            }

            public function check(string $token, string $ip): bool
            {
                return $this->verifyTurnstile($token, $ip);
            }

            public function checkRequired(string $token, string $ip): bool
            {
                return $this->verifyTurnstileRequired($token, $ip);
            }
        };

        return static fn (string $token, string $ip, bool $required = false): bool
            => $required ? $verifier->checkRequired($token, $ip) : $verifier->check($token, $ip);
    }

    public function noSecretConfiguredLetsEveryRequestThroughEvenWithoutAToken(): void
    {
        // Characterisation of the lenient check still used by login, forgot, reset and change
        // password: fail-OPEN when no secret is set.
        Assert::true($this->makeVerifier('')('', '203.0.113.9'));
    }

    public function aSecretOfZeroIsTreatedAsNoSecret(): void
    {
        Assert::true($this->makeVerifier('0')('', '203.0.113.9'));
    }

    public function aConfiguredSecretRejectsAMissingTokenWithoutCallingCloudflare(): void
    {
        Assert::false($this->makeVerifier('real-secret')('', '203.0.113.9'));
    }

    public function theRequiredCheckFailsClosedWhenNoSecretIsConfigured(): void
    {
        // even with a token present: nothing can be verified, so nothing passes
        Assert::false($this->makeVerifier('')('any-token', '203.0.113.9', true));
        Assert::false($this->makeVerifier('')('', '203.0.113.9', true));
    }

    public function theRequiredCheckTreatsASecretOfZeroAsNoSecret(): void
    {
        Assert::false($this->makeVerifier('0')('any-token', '203.0.113.9', true));
    }

    public function theRequiredCheckStillRejectsAMissingTokenWhenASecretIsSet(): void
    {
        Assert::false($this->makeVerifier('real-secret')('', '203.0.113.9', true));
    }
}
