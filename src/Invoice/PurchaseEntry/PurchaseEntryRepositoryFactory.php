<?php

declare(strict_types=1);

namespace App\Invoice\PurchaseEntry;

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * Builds the PurchaseEntryRepository for the container (see config/common/di/purchase-entry.php).
 *
 * PurchaseEntryRepository extends Select\Repository and needs a Select<PurchaseEntry>, which the DI container
 * cannot resolve on its own. The construction lives here, in src/, rather than in a closure in the config
 * file, because only src/ is covered by the tests.
 */
final class PurchaseEntryRepositoryFactory
{
    public static function create(ORMInterface $orm, EntityWriter $entityWriter): PurchaseEntryRepository
    {
        return new PurchaseEntryRepository(
            new Select($orm, PurchaseEntry::class),
            $entityWriter,
        );
    }
}
