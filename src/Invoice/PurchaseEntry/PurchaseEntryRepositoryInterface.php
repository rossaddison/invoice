<?php

declare(strict_types=1);

namespace App\Invoice\PurchaseEntry;

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;

interface PurchaseEntryRepositoryInterface
{
    public function save(array|PurchaseEntry|null $entry): void;

    public function delete(array|PurchaseEntry|null $entry): void;
}
