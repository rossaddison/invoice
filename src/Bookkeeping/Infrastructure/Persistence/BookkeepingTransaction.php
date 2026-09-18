<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\Persistence;

use App\Bookkeeping\Domain\BookkeepingTransactionType;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransactionRepository as BTR;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

/**
 * Cycle-attributed persisted counterpart of
 * App\Bookkeeping\Domain\BookkeepingTransaction -- deliberately shares
 * that class's name, disambiguated by namespace (the same idiom as e.g.
 * Domain\Order vs Infrastructure\Persistence\Order in other DDD
 * codebases). CycleBookkeepingTransactionRepository is the only class
 * that should ever import both; the Application layer sees only the
 * Domain one, through BookkeepingTransactionRepositoryInterface.
 *
 * Lines live in their own table (BookkeepingTransactionLine, BelongsTo
 * this entity) rather than a JSON column -- matches this app's existing
 * parent/child convention (Inv/InvItem, Quote/QuoteItem) rather than
 * inventing a new one for this module.
 *
 * $type is stored as a plain string column and re-typed to the enum on
 * the way out, matching App\Infrastructure\Persistence\StockMovement\
 * StockMovement's own established convention for the identical reason:
 * Cycle hydrates entities from raw column values, which are strings.
 */
#[Entity(repository: BTR::class)]
#[Index(columns: ['reference'], unique: true)]
#[Index(columns: ['inv_id'])]
#[Behavior\CreatedAt(field: 'created_at', column: 'created_at')]
class BookkeepingTransaction
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'datetime')]
    private DateTimeImmutable $created_at;

    #[BelongsTo(target: Inv::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;

    public function __construct(
        #[Column(type: 'string(30)', nullable: false)]
        private string $type,
        #[Column(type: 'string(100)', nullable: false)]
        private string $reference,
        #[Column(type: 'datetime', nullable: false)]
        private DateTimeImmutable $date,
        #[Column(type: 'string(3)', nullable: false)]
        private string $currency,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $inv_id = null,
        #[Column(type: 'string(30)', nullable: true)]
        private ?string $exported_provider_key = null,
        #[Column(type: 'string(255)', nullable: true)]
        private ?string $exported_provider_reference = null,
        #[Column(type: 'datetime', nullable: true)]
        private ?DateTimeImmutable $exported_at = null,
    ) {
        $this->created_at = new DateTimeImmutable();
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'BookkeepingTransaction');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getType(): BookkeepingTransactionType
    {
        return BookkeepingTransactionType::from($this->type);
    }

    public function setType(BookkeepingTransactionType $type): void
    {
        $this->type = $type->value;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function setInvId(?int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function getExportedProviderKey(): ?string
    {
        return $this->exported_provider_key;
    }

    public function setExportedProviderKey(?string $exported_provider_key): void
    {
        $this->exported_provider_key = $exported_provider_key;
    }

    public function getExportedProviderReference(): ?string
    {
        return $this->exported_provider_reference;
    }

    public function setExportedProviderReference(?string $exported_provider_reference): void
    {
        $this->exported_provider_reference = $exported_provider_reference;
    }

    public function getExportedAt(): ?DateTimeImmutable
    {
        return $this->exported_at;
    }

    public function setExportedAt(?DateTimeImmutable $exported_at): void
    {
        $this->exported_at = $exported_at;
    }

    public function isExported(): bool
    {
        return $this->exported_at !== null;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }
}
