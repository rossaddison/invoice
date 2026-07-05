<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;

interface InvRepositoryInterface
{
    public function save(array|Inv|null $inv): void;
}
