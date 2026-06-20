<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Client\Trait;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Invoice\Client\ClientRepository;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\ProductClient\ProductClient;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait ClientTrait5
{

    public function getDeliveryLocations(): ArrayCollection
    {
        return $this->delivery_locations;
    }

    public function getInvs(): ArrayCollection
    {
        return $this->invs;
    }

    public function setInvs(): void
    {
        $this->invs = new ArrayCollection();
    }

    public function addInv(Inv $inv): void
    {
        $this->invs[] = $inv;
    }
}
