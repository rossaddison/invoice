<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SquareMerchant;

use App\Infrastructure\Persistence\{
    Inv\Inv, Trait\RequireId
};
use App\Invoice\SquareMerchant\SquareMerchantRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;

/**
 * Square's own per-provider payment audit record — the first of what's
 * meant to become one entity per gateway (see
 * docs/SQUARE_MERCHANT_PER_PROVIDER_ENTITY_AUGUST_2026.md), replacing the
 * generic Merchant table for Square specifically. Square payments no
 * longer write a Merchant row at all; they write here instead.
 *
 * Exists because the generic Merchant.provider_reference (a single string
 * column) can't hold what Square genuinely needs to persist: two distinct
 * provider-side identifiers serving two distinct purposes —
 * `order_id` (what a webhook payload carries, needed to resolve back to
 * this app's own invoice url_key) and `payment_id` (what Square's refund
 * API is actually keyed by). Previously `payment_id` alone was stored
 * (so refunds worked) and `order_id`→invoice resolution was done via a
 * live `GET /v2/orders/{id}` re-fetch every webhook call, never
 * persisted anywhere — see SquarePaymentService::getOrderReferenceId(),
 * which this entity does not change.
 */
#[Entity(repository: SquareMerchantRepository::class)]
class SquareMerchant
{
    use RequireId;

    #[BelongsTo(target: Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;

    #[Column(type: 'date', nullable: false)]
    private mixed $date = '';

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        #[Column(type: 'boolean', nullable: true, default: 1)]
        private ?bool $successful = true,
        #[Column(type: 'string(151)', nullable: false)]
        private string $response = '',
        #[Column(type: 'string(151)', nullable: false)]
        private string $reference = '',
        #[Column(type: 'string(151)', nullable: true)]
        private ?string $order_id = null,
        #[Column(type: 'string(151)', nullable: true)]
        private ?string $payment_id = null,
    ) {
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'SquareMerchant');
    }

    public function isPersisted(): bool
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

    public function getSuccessful(): ?bool
    {
        return $this->successful;
    }

    public function setSuccessful(bool $successful): void
    {
        $this->successful = $successful;
    }

    public function getDate(): string|DateTimeImmutable
    {
        /** @var DateTimeImmutable|string $this->date */
        return $this->date;
    }

    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function setResponse(string $response): void
    {
        $this->response = $response;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }

    /**
     * Resolves a webhook's `order_id` back to this app's own invoice
     * url_key (set as the Order's own `reference_id` at Payment Link
     * creation time — see SquarePaymentService::createPayment()).
     */
    public function getOrderId(): ?string
    {
        return $this->order_id;
    }

    public function setOrderId(?string $order_id): void
    {
        $this->order_id = $order_id;
    }

    /**
     * What Square's refund API (`POST /v2/refunds`) is actually keyed by
     * — see PaymentRefundController's Square dispatch.
     */
    public function getPaymentId(): ?string
    {
        return $this->payment_id;
    }

    public function setPaymentId(?string $payment_id): void
    {
        $this->payment_id = $payment_id;
    }
}
