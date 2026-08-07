<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvRecurring;

use App\Invoice\InvRecurring\CronIdentity;
use App\Invoice\InvRecurring\CronTokenRepository;
use App\Invoice\Setting\SettingRepository as SR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers CronTokenRepository — the AuthenticatorInterface backing for the
 * `invrecurring/cron` route's Bearer-token authentication. See
 * docs/INVRECURRING_CRON_BEARER_AUTH_AUGUST_2026.md for the full
 * before/after story this replaces (a hand-rolled `!==` comparison against
 * a URL query parameter).
 */
#[Test]
final class CronTokenRepositoryTest
{
    private function repositoryWithCronKey(string $cronKey): CronTokenRepository
    {
        /** @var SR&m\MockInterface $sR */
        $sR = m::mock(SR::class);
        $sR->shouldReceive('getSetting')->with('cron_key')->andReturn($cronKey);

        return new CronTokenRepository($sR);
    }

    public function matchingTokenReturnsACronIdentity(): void
    {
        $repository = $this->repositoryWithCronKey('correct-horse-battery-staple');

        $identity = $repository->findIdentityByToken('correct-horse-battery-staple');

        Assert::instanceOf($identity, CronIdentity::class);
        Assert::same('cron', $identity?->getId());
    }

    public function wrongTokenReturnsNull(): void
    {
        $repository = $this->repositoryWithCronKey('correct-horse-battery-staple');

        Assert::null($repository->findIdentityByToken('wrong-token'));
    }

    public function emptyTokenReturnsNullEvenWhenCronKeyIsAlsoEmpty(): void
    {
        // Guards against an unconfigured (empty-string) cron_key setting
        // ever matching an equally-empty Authorization header — both sides
        // must be a real, non-empty value.
        $repository = $this->repositoryWithCronKey('');

        Assert::null($repository->findIdentityByToken(''));
    }

    public function emptyCronKeySettingRejectsEveryToken(): void
    {
        $repository = $this->repositoryWithCronKey('');

        Assert::null($repository->findIdentityByToken('anything'));
    }
}
