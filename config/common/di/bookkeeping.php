<?php

declare(strict_types=1);

use App\Bookkeeping\Application\BookkeepingGatewayInterface;
use App\Bookkeeping\Application\BookkeepingTransactionRepositoryInterface;
use App\Bookkeeping\Infrastructure\Persistence\CycleBookkeepingTransactionRepository;
use App\Bookkeeping\Infrastructure\QuickBooks\QuickBooksGateway;

/**
 * QuickBooks is this app's chosen fallback bookkeeping provider (see
 * project_bookkeeping_ddd_module_design memory). Swapping providers later
 * is a one-line change here, matching BookkeepingService's own "exactly
 * one gateway injected, selection is a DI-config concern" design.
 *
 * No Intuit::class binding lives here deliberately -- yiisoft/config's
 * Merger throws a hard error on a duplicate top-level key across the
 * files that make up one group (confirmed live: defining Intuit::class
 * both here and in config/web/di/yii-auth-client.php broke the whole
 * 'di-web' group with an ErrorException, it does not silently "last one
 * wins"). QuickBooksGateway instead constructs its own Intuit instance
 * directly in its constructor default (same `new HttpClient()`-as-
 * default-param pattern already used by every other gateway class in
 * this app), so it works from console context without needing any
 * shared container binding at all.
 */
return [
    BookkeepingTransactionRepositoryInterface::class => CycleBookkeepingTransactionRepository::class,
    BookkeepingGatewayInterface::class => QuickBooksGateway::class,
];
