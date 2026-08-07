<?php

declare(strict_types=1);

use App\Invoice\InvRecurring\CronTokenRepository;
use Yiisoft\Auth\AuthenticatorInterface;
use Yiisoft\Auth\Method\HttpBearer;

/**
 * Backs `Yiisoft\Auth\Middleware\Authentication` for the one route it's
 * actually applied to in this app: `invrecurring/cron`
 * (config/common/routes/routes-inv-recurring.php). See
 * docs/INVRECURRING_CRON_BEARER_AUTH_AUGUST_2026.md for the full story —
 * this app previously referenced Authentication::class across most of its
 * routes with no AuthenticatorInterface binding at all, which crashed
 * every one of them (docs/AUTHENTICATION_DI_CRASH_FIX_AUGUST_2026.md).
 * This binding is the correct, narrow use of the middleware that fix's own
 * writeup said was the one legitimate case for it: a program (a cron
 * scheduler), not a person, making a single standalone request.
 *
 * This binding existing app-wide is harmless on its own — it only takes
 * effect on whichever routes explicitly apply
 * `->middleware(Authentication::class)`, which today is exactly one.
 */
return [
    AuthenticatorInterface::class => static fn (CronTokenRepository $cronTokenRepository): HttpBearer =>
        new HttpBearer($cronTokenRepository),
];
