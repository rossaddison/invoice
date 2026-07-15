<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Infrastructure\Persistence\InvItem\InvItem;

interface InvItemRepositoryInterface
{
    public function save(array|InvItem|null $invitem): void;

    public function repoInvItemIdquery(int $inv_id): iterable;
}
