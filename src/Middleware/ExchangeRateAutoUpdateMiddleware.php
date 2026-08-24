<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Invoice\Helpers\Peppol\ExchangeRateUpdateService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Session\SessionInterface;

/**
 * Wired into `RoutePermission::invoiceGroup()` — every `/invoice/*`
 * request, any permission level — rather than threaded through
 * `App\Invoice\BaseController`'s constructor: that class is extended by
 * ~80 controllers, each explicitly forwarding every base constructor
 * param by hand (see e.g. `InvSentLogController`'s own `parent::
 * __construct(...)` call), so a new required dependency there would mean
 * editing every one of them just to wire a background housekeeping call.
 * A single shared middleware needs none of that.
 *
 * `ExchangeRateUpdateService::updateIfDue()` already no-ops unless the
 * `auto_update_exchange_rate` setting is on and it hasn't run yet today
 * (both are cheap, cached `SettingRepository` reads), so gating this on
 * `tfa_verified` alone — not a specific permission — is enough: on any
 * day it's actually due, the first authenticated staff/observer page
 * load triggers it once, and every other request that same day is a
 * near-free no-op.
 */
final class ExchangeRateAutoUpdateMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ExchangeRateUpdateService $exchangeRateUpdateService,
        private readonly SessionInterface $session,
    ) {
    }

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if ($this->session->get('tfa_verified') === true) {
            $this->exchangeRateUpdateService->updateIfDue();
        }
        return $handler->handle($request);
    }
}
