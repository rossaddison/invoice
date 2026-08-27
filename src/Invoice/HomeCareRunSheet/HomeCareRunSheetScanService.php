<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunSheet;

use Anthropic\Messages\Base64ImageSource\MediaType;
use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use App\Invoice\HomeCareRunSheetItem\HomeCareRunSheetItemRepository as RSIR;
use App\Invoice\Setting\SettingRepository as SR;
use DateTimeImmutable;

/**
 * Step 3 of the run-sheet reconciliation (see project_homecare_run_signoff_design
 * memory), in full: stores the scanned/photographed run sheet against its
 * batch, runs HomeCareRunSheetVisionService to populate each item's
 * detected_* fields, then moves the batch Scanned → PendingReview so the
 * staging screen (Step 4, not yet built) knows to render. One call, one
 * transaction-shaped unit of work — a scan is never left recorded without
 * either a successful read or a batch left in Scanned for retry.
 */
final readonly class HomeCareRunSheetScanService
{
    public function __construct(
        private HomeCareRunSheetRepository $rsR,
        private RSIR $rsiR,
        private HomeCareRunSheetVisionService $vision,
        private SR $sR,
    ) {}

    public function scan(HomeCareRunSheet $runSheet, string $imageBytes, MediaType $mediaType, int $byUserId): void
    {
        $fileName = $this->writeScan($runSheet->reqId(), $imageBytes, $mediaType);
        $runSheet->markScanned($fileName, $byUserId, new DateTimeImmutable('now'));
        $this->rsR->save($runSheet);

        $items = $this->rsiR->repoForRunSheetquery($runSheet->reqId());
        $this->vision->readScan($items, $imageBytes, $mediaType);

        $runSheet->markPendingReview();
        $this->rsR->save($runSheet);
    }

    private function writeScan(int $runSheetId, string $imageBytes, MediaType $mediaType): string
    {
        $extension = match ($mediaType) {
            MediaType::IMAGE_JPEG => 'jpg',
            MediaType::IMAGE_PNG => 'png',
            MediaType::IMAGE_GIF => 'gif',
            MediaType::IMAGE_WEBP => 'webp',
        };
        $fileName = 'runsheet_' . $runSheetId . '_scan.' . $extension;
        $targetPath = $this->sR->getHomeCareRunSheetsFolderAliases()->get('@homecare_run_sheets');
        file_put_contents($targetPath . DIRECTORY_SEPARATOR . $fileName, $imageBytes);

        return $fileName;
    }
}
