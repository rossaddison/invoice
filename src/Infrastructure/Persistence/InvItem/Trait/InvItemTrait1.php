<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvItem\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\Task\Task;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use DateTime;
use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait InvItemTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'InvItem');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }

    //set relation $taxrate
    public function setTaxRate(?TaxRate $taxrate): void
    {
        $this->tax_rate = $taxrate;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    //set relation $product
    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    //set relation $task
    public function setTask(?Task $task): void
    {
        $this->task = $task;
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }

    public function reqInvId(): int
    {
        return $this->requireId($this->inv_id, 'Inv');
    }

    public function setInvId(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    // not all inv_item's have a corresponding so_item so could be null
    public function getSoItemId(): ?int
    {
        return $this->so_item_id;
    }

    public function setSoItemId(int $so_item_id): void
    {
        $this->so_item_id = $so_item_id;
    }

    public function reqTaxRateId(): int
    {
        return $this->requireId($this->tax_rate_id, 'TaxRate');
    }
}
