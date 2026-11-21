<?php

declare(strict_types=1);

use App\Invoice\PurchaseEntry\PurchaseEntryRepository;
use App\Invoice\PurchaseEntry\PurchaseEntryRepositoryFactory;
use App\Invoice\PurchaseEntry\PurchaseEntryRepositoryInterface;

return [
    PurchaseEntryRepositoryInterface::class => PurchaseEntryRepository::class,

    // PurchaseEntryRepository extends Select\Repository and needs Select<PurchaseEntry>,
    // which the DI container cannot auto-resolve — wire it explicitly here.
    PurchaseEntryRepository::class => [PurchaseEntryRepositoryFactory::class, 'create'],
];
