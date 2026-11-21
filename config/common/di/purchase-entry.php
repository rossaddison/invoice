<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use App\Invoice\PurchaseEntry\PurchaseEntryRepository;
use App\Invoice\PurchaseEntry\PurchaseEntryRepositoryInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

return [
    PurchaseEntryRepositoryInterface::class => PurchaseEntryRepository::class,

    // PurchaseEntryRepository extends Select\Repository and needs Select<PurchaseEntry>,
    // which the DI container cannot auto-resolve — wire it explicitly here.
    PurchaseEntryRepository::class => static function (
        ORMInterface $orm,
        EntityWriter $entityWriter,
    ): PurchaseEntryRepository {
        return new PurchaseEntryRepository(
            new Select($orm, PurchaseEntry::class),
            $entityWriter,
        );
    },
];
