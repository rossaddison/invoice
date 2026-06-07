<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchaseEntry;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\PurchaseEntry\PurchaseEntryRepository as PER;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

#[Entity(repository: PER::class)]
#[Behavior\CreatedAt(field: 'created_at', column: 'created_at')]
#[Index(columns: ['date'])]
class PurchaseEntry
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;
    
    #[Column(type: 'datetime')]
    private DateTimeImmutable $created_at;

    public function __construct(
        #[Column(type: 'date', nullable: true)]
        private DateTimeImmutable|string|null $date = null,
        #[Column(type: 'string(200)', nullable: false)]
        private string $supplier = '',
        #[Column(type: 'string(500)', nullable: true)]
        private ?string $description = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0)]
        private float $amount_ex_vat = 0.00,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0)]
        private float $vat_amount = 0.00
    ) {
        $this->created_at = new DateTimeImmutable();
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

    public function getDate(): DateTimeImmutable|string|null
    {
        /** @var DateTimeImmutable|string|null $this->date */
        return $this->date;
    }

    public function setDate(?DateTimeImmutable $date): void
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

    public function setCreatedAt(string $date_created): void
    {
        $this->created_at =
                new DateTimeImmutable()
                ->createFromFormat('Y-m-d h:i:s', $date_created) ?:
                new DateTimeImmutable('now');
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }
}
