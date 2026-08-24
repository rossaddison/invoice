<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

/** What ExchangeRateUpdateService::updateIfDue() actually did, for callers that want to tell the user (e.g. App\Webshop\Currency\CurrencyController::refreshRate()) rather than just fire-and-forget it (App\Middleware\ExchangeRateAutoUpdateMiddleware). */
enum ExchangeRateUpdateResult
{
    case Updated;
    case AlreadyCurrent;
    case Disabled;
    case FetchFailed;
}
