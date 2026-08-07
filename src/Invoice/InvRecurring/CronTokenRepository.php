<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;

/**
 * Backs the `invrecurring/cron` route's Bearer-token authentication —
 * deliberately narrow, not the app's general-purpose Token/Identity system
 * (see App\Auth\TokenRepository), since this endpoint has exactly one
 * caller (an external cron scheduler) and no real user account behind it.
 *
 * Reuses the existing `cron_key` setting value as the token content —
 * unchanged from the previous URL-query-parameter design — only the
 * transport changes: `Authorization: Bearer <cron_key>` instead of
 * `?cron_key=<cron_key>` in the URL, so the secret no longer sits in the
 * URL where it leaks into shell history, cron logs, and web server access
 * logs.
 *
 * @see CronIdentity
 * @see \App\Invoice\InvRecurring\InvRecurringController::cron()
 */
final class CronTokenRepository implements IdentityWithTokenRepositoryInterface
{
    public function __construct(private readonly SR $sR) {}

    #[\Override]
    public function findIdentityByToken(string $token, ?string $type = null): ?IdentityInterface
    {
        if ($token === '') {
            return null;
        }

        $cronKey = $this->sR->getSetting('cron_key');

        // hash_equals() for constant-time comparison — the previous `!==`
        // query-parameter check was timing-attack-observable, however
        // impractical that attack is over a real network.
        return $cronKey !== '' && hash_equals($cronKey, $token) ? new CronIdentity() : null;
    }
}
