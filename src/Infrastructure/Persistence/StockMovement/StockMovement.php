<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\StockMovement;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\Enum\StockMovementType;
use App\Invoice\StockMovement\StockMovementRepository as SMR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

/**
 * One row per stock-affecting event for a Product — a ledger, not a mutable
 * counter, so "why did stock go from 40 to 12" stays answerable (matches
 * how this app already treats money via InvItemAmount rather than a single
 * running total). Current stock for a product is the sum of its
 * `quantity_delta` rows (positive = stock in, negative = stock out) —
 * see `StockMovementRepository::currentBalance()`.
 *
 * `type = sale` rows are written when an Inv reaches status_id 4 (paid),
 * not at draft or sent — a sent-but-unpaid invoice must not commit stock.
 * That write is intended to happen from one centralized place
 * (`InvService::markInvoicePaid()`, not yet built) rather than the ~17
 * separate payment-gateway webhook handlers that currently call
 * `$invoice->setStatusId(4)` directly — see
 * docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md.
 */
#[Entity(repository: SMR::class)]
#[Index(columns: ['product_id'])]
#[Index(columns: ['inv_id'])]
#[Behavior\CreatedAt(field: 'created_at', column: 'created_at')]
class StockMovement
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'datetime')]
    private DateTimeImmutable $created_at;

    #[BelongsTo(target: Product::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Product $product = null;

    // Optional — the invoice/order that caused this movement (sale/return).
    // Null for a receipt or a manual adjustment with no invoice involved.
    #[BelongsTo(target: Inv::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $product_id = null,
        #[Column(type: 'string(20)', nullable: false, default: 'adjustment')]
        private string $type = StockMovementType::Adjustment->value,
        #[Column(type: 'decimal(20,2)', nullable: false)]
        private float $quantity_delta = 0.00,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $inv_id = null,
        #[Column(type: 'string(500)', nullable: true)]
        private ?string $note = null,
    ) {
        $this->created_at = new DateTimeImmutable();
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'StockMovement');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function reqProductId(): int
    {
        return $this->requireId($this->product_id, 'Product');
    }

    public function setProductId(int $product_id): void
    {
        $this->product_id = $product_id;
    }

    public function getType(): StockMovementType
    {
        return StockMovementType::from($this->type);
    }

    public function setType(StockMovementType $type): void
    {
        $this->type = $type->value;
    }

    public function getQuantityDelta(): float
    {
        return $this->quantity_delta;
    }

    public function setQuantityDelta(float $quantity_delta): void
    {
        $this->quantity_delta = $quantity_delta;
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }
}
