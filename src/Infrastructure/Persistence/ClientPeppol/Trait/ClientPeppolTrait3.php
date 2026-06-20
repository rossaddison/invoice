<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ClientPeppol\Trait;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait ClientPeppolTrait3
{

    public function setSupplierAssignedAccountId(string $input): void
    {
        $this->supplier_assigned_accountid = $input;
    }

    public function getBuyerReference(): string
    {
        return $this->buyer_reference;
    }

    public function setBuyerReference(string $input): void
    {
        $this->buyer_reference = $input;
    }
}
