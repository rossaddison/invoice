<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\WorldpayMerchant;

use App\Infrastructure\Persistence\{
    Inv\Inv, Trait\RequireId
};
use App\Invoice\WorldpayMerchant\WorldpayMerchantRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;

/**
 * Worldpay's own per-provider payment audit record, mirroring
 * SquareMerchant exactly and for the same reason: the generic
 * Merchant.provider_reference column (a single string(151)) can't hold
 * what Worldpay's orchestration Payments API genuinely needs to
 * persist — its `_links.self.href`/`_actions.*.href` values are long
 * opaque tokens (every real example seen in research was a long
 * base64-ish blob, well beyond typical UUID/reference length), and
 * `verifyPayment()`/`refund()` need that href directly rather than a
 * bare ID to re-fetch the payment's current state-dependent actions.
 *
 * Two provider-side identifiers are kept, serving two distinct
 * purposes — the same split SquareMerchant documents for order_id vs
 * payment_id:
 * - `payment_id`: Worldpay's own short `paymentId` (pattern
 *   `^[A-Za-z0-9_-]+$`), useful for logs/correlation and echoed back
 *   in some response shapes, but not itself callable.
 * - `self_href`: the `_links.self.href` query URL — what
 *   verifyPayment()/refund() actually GET/POST against. Stored as
 *   `text`, not `string(151)`, given the observed token lengths.
 *
 * `transaction_reference` is this app's own generated value (set on
 * the original POST /api/payments request), echoed back verbatim in
 * webhook `eventDetails.transactionReference` — the correlation key
 * used to resolve an incoming webhook event back to this row.
 */
#[Entity(repository: WorldpayMerchantRepository::class)]
class WorldpayMerchant
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
        private ?string $transaction_reference = null,
        #[Column(type: 'string(151)', nullable: true)]
        private ?string $payment_id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $self_href = null,
        /**
         * Whatever HATEOAS action href the payment's CURRENT 3DS step
         * needs next — `_actions.supply3dsDeviceData.href` while
         * `3dsDeviceDataRequired`, then overwritten with
         * `_actions.complete3dsChallenge.href` if the device-data step
         * comes back `3dsChallenged`. Deliberately not reconstructed by
         * string concatenation from self_href — these are genuine
         * opaque HATEOAS hrefs, not confirmed to follow a predictable
         * path pattern from the base payment resource. Read by whichever
         * request comes next (an AJAX call from the DDC step, or the
         * browser returning from the issuer's challenge page) to know
         * what to call. Null once the payment reaches a terminal state.
         */
        #[Column(type: 'text', nullable: true)]
        private ?string $pending_action_href = null,
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
        return $this->requireId($this->id, 'WorldpayMerchant');
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
     * Resolves an incoming webhook's `eventDetails.transactionReference`
     * back to this app's own invoice — set as `transactionReference` on
     * the original POST /api/payments request (see
     * WorldpayPaymentService::createPayment()).
     */
    public function getTransactionReference(): ?string
    {
        return $this->transaction_reference;
    }

    public function setTransactionReference(?string $transaction_reference): void
    {
        $this->transaction_reference = $transaction_reference;
    }

    public function getPaymentId(): ?string
    {
        return $this->payment_id;
    }

    public function setPaymentId(?string $payment_id): void
    {
        $this->payment_id = $payment_id;
    }

    /**
     * What verifyPayment()/refund() actually GET/POST against — see
     * PaymentRefundController's Worldpay dispatch and
     * WorldpayPaymentService::verifyPayment()/refund().
     */
    public function getSelfHref(): ?string
    {
        return $this->self_href;
    }

    public function setSelfHref(?string $self_href): void
    {
        $this->self_href = $self_href;
    }

    public function getPendingActionHref(): ?string
    {
        return $this->pending_action_href;
    }

    public function setPendingActionHref(?string $pending_action_href): void
    {
        $this->pending_action_href = $pending_action_href;
    }
}
