<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvTaxRate;

use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Infrastructure\Persistence\{
    Inv\Inv, TaxRate\TaxRate, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository:ITRR::class)]

class InvTaxRate
{
    use RequireId;
 
    #[BelongsTo(target: Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;

    #[BelongsTo(target: TaxRate::class, nullable: false, fkAction: 'NO ACTION')]
    private ?TaxRate $tax_rate = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)',
        nullable: false)]
        private ?int $inv_id = null,
        #[Column(type: 'integer(11)',
        nullable: false)]
        private ?int $tax_rate_id = null,
        #[Column(type: 'integer(1)',
        nullable: false, default: 0)]
        private ?int $include_item_tax = null,
        #[Column(type: 'decimal(20,2)',
        nullable: false, default: 0.00)]
        private ?float $inv_tax_rate_amount = 0.00)
    {
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }

    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }

    public function setTaxRate(?TaxRate $tax_rate): void
    {
        $this->tax_rate = $tax_rate;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'InvTaxRate');
    }
    
    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }
    
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqInvId(): int
    {
        return $this->requireId($this->inv_id, 'Inv');
    }

    public function setInvId(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function reqTaxRateId(): int
    {
        return $this->requireId($this->tax_rate_id, 'TaxRate');
    }

    public function setTaxRateId(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }

    public function getIncludeItemTax(): ?int
    {
        return $this->include_item_tax;
    }

    public function setIncludeItemTax(int $include_item_tax): void
    {
        $this->include_item_tax = $include_item_tax;
    }

    public function getInvTaxRateAmount(): ?float
    {
        return $this->inv_tax_rate_amount;
    }

    public function setInvTaxRateAmount(float $inv_tax_rate_amount): void
    {
        $this->inv_tax_rate_amount = $inv_tax_rate_amount;
    }
}
