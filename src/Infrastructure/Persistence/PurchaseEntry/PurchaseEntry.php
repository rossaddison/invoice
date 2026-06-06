<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchaseEntry;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\PurchaseEntry\PurchaseEntryRepository as PER;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity(repository: PER::class)]
#[Index(columns: ['date'])]
class PurchaseEntry
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'date', nullable: false)]
        private string $date = '',
        #[Column(type: 'string(200)', nullable: false)]
        private string $supplier = '',
        #[Column(type: 'string(500)', nullable: true)]
        private ?string $description = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0)]
        private float $amount_ex_vat = 0.00,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0)]
        private float $vat_amount = 0.00,
        #[Column(type: 'datetime', nullable: false)]
        private string $created_at = '',
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, self::class);
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    public function getSupplier(): string
    {
        return $this->supplier;
    }

    public function setSupplier(string $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getAmountExVat(): float
    {
        return $this->amount_ex_vat;
    }

    public function setAmountExVat(float $amount_ex_vat): void
    {
        $this->amount_ex_vat = $amount_ex_vat;
    }

    public function getVatAmount(): float
    {
        return $this->vat_amount;
    }

    public function setVatAmount(float $vat_amount): void
    {
        $this->vat_amount = $vat_amount;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = $created_at;
    }
}
