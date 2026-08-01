<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Infrastructure\Persistence\Client\Client;

interface ClientRepositoryInterface
{
    public function repoClientqueryOrig(int $id): ?Client;
}
