<?php

declare(strict_types=1);

namespace App\Invoice\Report;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Helpers\ClientHelper;

final class QuartersClientContext
{
    public function __construct(
        public readonly Client $client,
        public readonly ClientHelper $clienthelper,
        public readonly int $client_id,
    ) {
    }
}
