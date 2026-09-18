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
 */
return [
    BookkeepingTransactionRepositoryInterface::class => CycleBookkeepingTransactionRepository::class,
    BookkeepingGatewayInterface::class => QuickBooksGateway::class,
];
