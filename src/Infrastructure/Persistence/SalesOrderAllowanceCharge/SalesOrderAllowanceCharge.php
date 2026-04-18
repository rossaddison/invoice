<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SalesOrderAllowanceCharge;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\SalesOrderAllowanceCharge\SalesOrderAllowanceChargeRepository
    as SOACR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: SOACR::class)]
class SalesOrderAllowanceCharge
{
    #[BelongsTo(
        target: AllowanceCharge::class,
        nullable: false,
        fkAction: 'NO ACTION'
    )]
    private ?AllowanceCharge $allowance_charge = null;

    #[BelongsTo(
        target: SalesOrder::class,
        nullable: false,
        fkAction: 'NO ACTION'
    )]
    private ?SalesOrder $sales_order = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $sales_order_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $allowance_charge_id = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $amount = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $vat_or_tax = null,
    ) {
    }

    /**
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqId(): int
    {
        if ($this->id === null) {
            throw new \LogicException(
                'SalesOrderAllowanceCharge has no ID (not persisted yet)'
            );
        }

        return $this->id;
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getAllowanceCharge(): ?AllowanceCharge
    {
        return $this->allowance_charge;
    }

    public function setAllowanceCharge(?AllowanceCharge $allowance_charge): void
    {
        $this->allowance_charge = $allowance_charge;
    }

    public function getSalesOrder(): ?SalesOrder
    {
        return $this->sales_order;
    }

    public function setSalesOrder(?SalesOrder $sales_order): void
    {
        $this->sales_order = $sales_order;
    }

    public function getSalesOrderId(): ?int
    {
        return $this->sales_order_id;
    }

    public function setSalesOrderId(int $sales_order_id): void
    {
        $this->sales_order_id = $sales_order_id;
    }

    public function getAllowanceChargeId(): ?int
    {
        return $this->allowance_charge_id;
    }

    public function setAllowanceChargeId(int $allowance_charge_id): void
    {
        $this->allowance_charge_id = $allowance_charge_id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getVatOrTax(): ?float
    {
        return $this->vat_or_tax;
    }

    public function setVatOrTax(float $vat_or_tax): void
    {
        $this->vat_or_tax = $vat_or_tax;
    }
}
