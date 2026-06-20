<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SalesOrder\Trait;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\Group\Group;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Infrastructure\Persistence\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait SalesOrderTrait4
{

    public function getItems(): ArrayCollection
    {
        return $this->items;
    }

    public function getSalesOrderAmount(): SalesOrderAmount
    {
        return $this->sales_order_amount;
    }
}
