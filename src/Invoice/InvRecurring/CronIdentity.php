<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use Yiisoft\Auth\IdentityInterface;

/**
 * The "identity" behind a valid cron Bearer token — not a real user account,
 * just a fixed marker `Yiisoft\Auth\Middleware\Authentication` requires
 * something non-null to consider the request authenticated.
 *
 * @see CronTokenRepository
 */
final class CronIdentity implements IdentityInterface
{
    #[\Override]
    public function getId(): ?string
    {
        return 'cron';
    }
}
