<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\HomeCareRunSheet;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\Enum\HomeCareRunSheetStatus;
use App\Invoice\HomeCareRunSheet\HomeCareRunSheetRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use DateTimeImmutable;

/**
 * One paper-signoff cycle for a HomeCare "current run" (the same
 * category_secondary_id + run_date pair HomeCareRunContext already selects
 * on inv/index): a spreadsheet snapshot of that run's invoices is exported,
 * printed, hand-annotated in the field (worker actually assigned, completion,
 * coded do-not-send reason), scanned back in, read by AI vision into
 * per-invoice adjustments (see HomeCareRunSheetItem), reviewed on a staging
 * screen, then applied back onto Inv — see HomeCareRunSheetStatus for the
 * lifecycle this walks through.
 */
#[Entity(repository: HomeCareRunSheetRepository::class)]
class HomeCareRunSheet
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $category_secondary_id = null,
        #[Column(type: 'date', nullable: false)]
        private ?DateTimeImmutable $run_date = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $exported_by_user_id = null,
        #[Column(type: 'string(20)', nullable: false, default: 'exported')]
        private string $status = 'exported',
        /**
         * Bare filename under SettingFileFolderTrait::
         * getHomeCareRunSheetsFolderAliases() — same convention as
         * CompanyPrivate::logo_filename, not a Cycle relation: Upload is
         * client-scoped (BelongsTo Client, nullable: false) and doesn't fit
         * a document that spans one run's many clients.
         */
        #[Column(type: 'string(255)', nullable: true)]
        private ?string $spreadsheet_file_name = null,
        #[Column(type: 'string(255)', nullable: true)]
        private ?string $scanned_file_name = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $scanned_by_user_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $applied_by_user_id = null,
        #[Column(type: 'datetime', nullable: true, typecast: 'datetime')]
        private ?DateTimeImmutable $scanned_at = null,
        #[Column(type: 'datetime', nullable: true, typecast: 'datetime')]
        private ?DateTimeImmutable $applied_at = null,
        #[Column(type: 'datetime', nullable: false, typecast: 'datetime')]
        private ?DateTimeImmutable $created_at = null,
    ) {
        $this->created_at ??= new DateTimeImmutable();
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'HomeCareRunSheet');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqCategorySecondaryId(): int
    {
        return $this->requireId($this->category_secondary_id, 'CategorySecondary');
    }

    public function setCategorySecondaryId(int $category_secondary_id): void
    {
        $this->category_secondary_id = $category_secondary_id;
    }

    public function getRunDate(): ?DateTimeImmutable
    {
        return $this->run_date;
    }

    public function setRunDate(DateTimeImmutable $run_date): void
    {
        $this->run_date = $run_date;
    }

    public function reqExportedByUserId(): int
    {
        return $this->requireId($this->exported_by_user_id, 'User');
    }

    public function setExportedByUserId(int $exported_by_user_id): void
    {
        $this->exported_by_user_id = $exported_by_user_id;
    }

    public function getStatus(): HomeCareRunSheetStatus
    {
        return HomeCareRunSheetStatus::tryFrom($this->status) ?? HomeCareRunSheetStatus::Exported;
    }

    public function setStatus(HomeCareRunSheetStatus $status): void
    {
        $this->status = $status->value;
    }

    public function getSpreadsheetFileName(): ?string
    {
        return $this->spreadsheet_file_name;
    }

    public function setSpreadsheetFileName(string $spreadsheet_file_name): void
    {
        $this->spreadsheet_file_name = $spreadsheet_file_name;
    }

    public function getScannedFileName(): ?string
    {
        return $this->scanned_file_name;
    }

    public function setScannedFileName(string $scanned_file_name): void
    {
        $this->scanned_file_name = $scanned_file_name;
    }

    public function getScannedByUserId(): ?int
    {
        return $this->scanned_by_user_id;
    }

    public function setScannedByUserId(int $scanned_by_user_id): void
    {
        $this->scanned_by_user_id = $scanned_by_user_id;
    }

    public function getAppliedByUserId(): ?int
    {
        return $this->applied_by_user_id;
    }

    public function setAppliedByUserId(int $applied_by_user_id): void
    {
        $this->applied_by_user_id = $applied_by_user_id;
    }

    public function getScannedAt(): ?DateTimeImmutable
    {
        return $this->scanned_at;
    }

    public function setScannedAt(DateTimeImmutable $scanned_at): void
    {
        $this->scanned_at = $scanned_at;
    }

    public function getAppliedAt(): ?DateTimeImmutable
    {
        return $this->applied_at;
    }

    public function setAppliedAt(DateTimeImmutable $applied_at): void
    {
        $this->applied_at = $applied_at;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at ?? new DateTimeImmutable();
    }

    /**
     * Records the scan upload and moves Exported → Scanned in one step —
     * the two always happen together, there's no meaningful "scanned but
     * file name not yet known" intermediate state to leave to the caller.
     */
    public function markScanned(string $fileName, int $byUserId, DateTimeImmutable $at): void
    {
        $this->scanned_file_name = $fileName;
        $this->scanned_by_user_id = $byUserId;
        $this->scanned_at = $at;
        $this->status = HomeCareRunSheetStatus::Scanned->value;
    }

    /**
     * Vision extraction has produced adjustments (written onto the
     * HomeCareRunSheetItem rows by the caller) — the batch itself just moves
     * to PendingReview so the staging screen knows to render.
     */
    public function markPendingReview(): void
    {
        $this->status = HomeCareRunSheetStatus::PendingReview->value;
    }

    /**
     * The office has confirmed the staged adjustments, they've been written
     * back onto Inv, and the run's invoices have been marked sent —
     * terminal state.
     */
    public function markApplied(int $byUserId, DateTimeImmutable $at): void
    {
        $this->applied_by_user_id = $byUserId;
        $this->applied_at = $at;
        $this->status = HomeCareRunSheetStatus::Applied->value;
    }
}
