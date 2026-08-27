<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\HomeCareRunSheetItem;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\HomeCareRunSheetItem\HomeCareRunSheetItemRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;

/**
 * One invoice's row within a HomeCareRunSheet, snapshotted at export time —
 * pinning what actually went out on paper (expected_worker_id) so a later
 * diff against AI-vision-detected values (detected_*) is meaningful even if
 * the live run on inv/index has since changed. detected_* stay null until
 * the scanned sheet has been read; $accepted lets the office drop a
 * misread/unwanted row on the staging screen before HomeCareRunSheet is
 * applied — see HomeCareRunSheet's own docblock for the full lifecycle.
 */
#[Entity(repository: HomeCareRunSheetItemRepository::class)]
#[Index(columns: ['run_sheet_id'])]
class HomeCareRunSheetItem
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $run_sheet_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $expected_worker_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $detected_worker_id = null,
        #[Column(type: 'bool', typecast: 'bool', nullable: true)]
        private ?bool $detected_completed = null,
        /**
         * A DoNotSendReason::value, or null when no reason was detected
         * (i.e. the row was read as completed).
         */
        #[Column(type: 'string(30)', nullable: true)]
        private ?string $detected_reason_code = null,
        #[Column(type: 'bool', typecast: 'bool', default: true)]
        private bool $accepted = true,
    ) {}

    public function reqId(): int
    {
        return $this->requireId($this->id, 'HomeCareRunSheetItem');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqRunSheetId(): int
    {
        return $this->requireId($this->run_sheet_id, 'HomeCareRunSheet');
    }

    public function setRunSheetId(int $run_sheet_id): void
    {
        $this->run_sheet_id = $run_sheet_id;
    }

    public function reqInvId(): int
    {
        return $this->requireId($this->inv_id, 'Inv');
    }

    public function setInvId(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function getExpectedWorkerId(): ?int
    {
        return $this->expected_worker_id;
    }

    public function setExpectedWorkerId(int $expected_worker_id): void
    {
        $this->expected_worker_id = $expected_worker_id;
    }

    public function getDetectedWorkerId(): ?int
    {
        return $this->detected_worker_id;
    }

    public function getDetectedCompleted(): ?bool
    {
        return $this->detected_completed;
    }

    public function getDetectedReasonCode(): ?string
    {
        return $this->detected_reason_code;
    }

    /**
     * Records one row's vision-extraction result in one step — worker,
     * completion, and reason always arrive together from the same read,
     * there's no meaningful partial state between them.
     */
    public function setDetection(?int $workerId, bool $completed, ?string $reasonCode): void
    {
        $this->detected_worker_id = $workerId;
        $this->detected_completed = $completed;
        $this->detected_reason_code = $reasonCode;
    }

    public function getAccepted(): bool
    {
        return $this->accepted;
    }

    public function setAccepted(bool $accepted): void
    {
        $this->accepted = $accepted;
    }

    /**
     * True when the vision read actually differs from what was printed:
     * a different worker, an incomplete read, or a reason code — the single
     * predicate both the staging-screen query
     * (HomeCareRunSheetItemRepository::repoChangedForRunSheetquery()) and
     * the Apply step key off, so the two can never quietly disagree about
     * what counts as "changed". Gated on detected_completed rather than
     * detected_worker_id — completed is a required field in the vision
     * schema, always set once a row has actually been read, whereas
     * detected_worker_id can legitimately be null on a read row (illegible
     * handwriting) while the row is still very much "changed" (e.g. marked
     * incomplete with a reason code but no readable worker name).
     */
    public function hasDetectedChange(): bool
    {
        return $this->detected_completed !== null
            && ($this->detected_worker_id !== $this->expected_worker_id
                || $this->detected_completed === false
                || $this->detected_reason_code !== null);
    }
}
