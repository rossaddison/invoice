<?php

declare(strict_types=1);

namespace App\Invoice\ClientPeppol;

use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;

interface ClientPeppolRepositoryInterface
{
    public function findByEndpointId(string $endpointId, string $schemeId): ?ClientPeppol;
}
