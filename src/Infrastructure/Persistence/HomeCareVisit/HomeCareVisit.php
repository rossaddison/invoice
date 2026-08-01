<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\HomeCareVisit;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\HomeCareVisit\HomeCareVisitRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use DateTimeImmutable;

/**
 * One row per client per calendar day a homecare-cleaning QR scan was
 * processed — the idempotency/audit record the auto-invoice facility is
 * built around. The unique (client_id, visited_at) index is the actual
 * concurrency guard: two near-simultaneous scans for the same client on the
 * same day can only ever result in one row, so only one of them proceeds to
 * generate an invoice (see HomeCareVisitRepository::tryCreatePendingVisit()
 * and docs/HOMECARE_AUTOINVOICE_PITFALLS_AUGUST_2026.md).
 *
 * Eligibility itself is also anchored on this table rather than re-scanning
 * the client's whole invoice history: an unrelated invoice or credit note an
 * admin raises for other reasons never touches this table, so it can no
 * longer silently block the automation the way scanning "any invoice since
 * last payment" did.
 */
#[Entity(repository: HomeCareVisitRepository::class)]
#[Index(columns: ['client_id', 'visited_at'], unique: true)]
class HomeCareVisit
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $client_id = null,
        #[Column(type: 'date', nullable: false)]
        private ?DateTimeImmutable $visited_at = null,
        /**
         * 'pending' | 'generated' | 'not_eligible' | 'contact_us'.
         * 'pending' only ever exists mid-request, between the row insert and
         * the eligibility decision being written back in the same request.
         */
        #[Column(type: 'string(20)', nullable: false, default: 'pending')]
        private string $outcome = 'pending',
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $invoice_id = null,
        /**
         * Free-text detail for admin visibility only — never shown to the
         * customer, whose result page stays deliberately generic. e.g. "no
         * active linked user account", "no Service-type item on template
         * invoice", "last generated invoice not yet paid".
         */
        #[Column(type: 'string(255)', nullable: true)]
        private ?string $reason = null,
        #[Column(type: 'datetime', nullable: false, typecast: 'datetime')]
        private ?DateTimeImmutable $created_at = null,
    ) {
        $this->created_at ??= new DateTimeImmutable();
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'HomeCareVisit');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqClientId(): int
    {
        return $this->requireId($this->client_id, 'Client');
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getVisitedAt(): ?DateTimeImmutable
    {
        return $this->visited_at;
    }

    public function setVisitedAt(DateTimeImmutable $visited_at): void
    {
        $this->visited_at = $visited_at;
    }

    public function getOutcome(): string
    {
        return $this->outcome;
    }

    public function setOutcome(string $outcome): void
    {
        $this->outcome = $outcome;
    }

    public function getInvoiceId(): ?int
    {
        return $this->invoice_id;
    }

    public function setInvoiceId(?int $invoice_id): void
    {
        $this->invoice_id = $invoice_id;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at ?? new DateTimeImmutable();
    }
}
