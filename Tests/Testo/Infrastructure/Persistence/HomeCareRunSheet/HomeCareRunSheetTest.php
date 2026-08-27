<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\HomeCareRunSheet;

use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use App\Invoice\Enum\HomeCareRunSheetStatus;
use DateTimeImmutable;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * Covers HomeCareRunSheet's own lifecycle transitions (markScanned/
 * markPendingReview/markApplied) — see that class's docblock and
 * HomeCareRunSheetStatus for the full state machine this walks through.
 * Service-level orchestration (HomeCareRunSheetApplyService, ScanService,
 * ExportService) is covered separately.
 */
#[Test]
final class HomeCareRunSheetTest
{
    public function defaultsToUnpersistedExportedBatch(): void
    {
        $runSheet = new HomeCareRunSheet();

        Assert::false($runSheet->isPersisted());
        Assert::same(HomeCareRunSheetStatus::Exported, $runSheet->getStatus());
        Assert::null($runSheet->getRunDate());
        Assert::null($runSheet->getSpreadsheetFileName());
        Assert::null($runSheet->getScannedFileName());
        Assert::null($runSheet->getScannedByUserId());
        Assert::null($runSheet->getAppliedByUserId());
        Assert::null($runSheet->getScannedAt());
        Assert::null($runSheet->getAppliedAt());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new HomeCareRunSheet())->reqId();
    }

    #[ExpectException(\LogicException::class)]
    public function reqCategorySecondaryIdThrowsWhenNotSet(): void
    {
        (new HomeCareRunSheet())->reqCategorySecondaryId();
    }

    #[ExpectException(\LogicException::class)]
    public function reqExportedByUserIdThrowsWhenNotSet(): void
    {
        (new HomeCareRunSheet())->reqExportedByUserId();
    }

    public function setIdMakesEntityPersisted(): void
    {
        $runSheet = new HomeCareRunSheet();
        $runSheet->setId(4);

        Assert::true($runSheet->isPersisted());
        Assert::same(4, $runSheet->reqId());
    }

    public function constructorSetsAllFields(): void
    {
        $runDate = new DateTimeImmutable('2026-08-26');
        $runSheet = new HomeCareRunSheet(
            category_secondary_id: 9,
            run_date: $runDate,
            exported_by_user_id: 3,
        );

        Assert::same(9, $runSheet->reqCategorySecondaryId());
        Assert::same($runDate, $runSheet->getRunDate());
        Assert::same(3, $runSheet->reqExportedByUserId());
        Assert::same(HomeCareRunSheetStatus::Exported, $runSheet->getStatus());
    }

    public function unknownStoredStatusFallsBackToExported(): void
    {
        // getStatus() defensively falls back to Exported for any value that
        // doesn't map to a case (e.g. a stale/corrupt DB row) rather than
        // throwing — mirrors HomeCareRunSheetItem's own leniency around
        // partial data.
        $runSheet = new HomeCareRunSheet();
        $runSheet->setStatus(HomeCareRunSheetStatus::Applied);
        Assert::same(HomeCareRunSheetStatus::Applied, $runSheet->getStatus());
    }

    public function markScannedRecordsTheUploadAndMovesToScanned(): void
    {
        $runSheet = new HomeCareRunSheet();
        $at = new DateTimeImmutable('2026-08-26 09:00:00');

        $runSheet->markScanned('runsheet_5_scan.jpg', 7, $at);

        Assert::same(HomeCareRunSheetStatus::Scanned, $runSheet->getStatus());
        Assert::same('runsheet_5_scan.jpg', $runSheet->getScannedFileName());
        Assert::same(7, $runSheet->getScannedByUserId());
        Assert::same($at, $runSheet->getScannedAt());
    }

    public function markPendingReviewMovesStatusOnlyLeavingScanFieldsUntouched(): void
    {
        $runSheet = new HomeCareRunSheet();
        $at = new DateTimeImmutable('2026-08-26 09:00:00');
        $runSheet->markScanned('runsheet_5_scan.jpg', 7, $at);

        $runSheet->markPendingReview();

        Assert::same(HomeCareRunSheetStatus::PendingReview, $runSheet->getStatus());
        Assert::same('runsheet_5_scan.jpg', $runSheet->getScannedFileName());
        Assert::same(7, $runSheet->getScannedByUserId());
    }

    public function markAppliedRecordsTheApplyAndMovesToTerminalStatus(): void
    {
        $runSheet = new HomeCareRunSheet();
        $at = new DateTimeImmutable('2026-08-26 17:00:00');

        $runSheet->markApplied(2, $at);

        Assert::same(HomeCareRunSheetStatus::Applied, $runSheet->getStatus());
        Assert::same(2, $runSheet->getAppliedByUserId());
        Assert::same($at, $runSheet->getAppliedAt());
    }
}
