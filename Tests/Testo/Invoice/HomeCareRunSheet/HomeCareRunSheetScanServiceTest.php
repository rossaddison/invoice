<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareRunSheet;

use Anthropic\Messages\Base64ImageSource\MediaType;
use App\Infrastructure\Persistence\HomeCareRunSheetItem\HomeCareRunSheetItem;
use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use App\Invoice\Enum\HomeCareRunSheetStatus;
use App\Invoice\HomeCareRunSheet\HomeCareRunSheetRepository as RSR;
use App\Invoice\HomeCareRunSheet\HomeCareRunSheetScanService;
use App\Invoice\HomeCareRunSheet\HomeCareRunSheetVisionService;
use App\Invoice\HomeCareRunSheetItem\HomeCareRunSheetItemRepository as RSIR;
use App\Invoice\Setting\SettingRepository as SR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;

/**
 * Covers HomeCareRunSheetScanService::scan() — Step 3 of the run-sheet
 * reconciliation (see project_homecare_run_signoff_design memory): the scan
 * is written to disk and the batch moves Scanned → PendingReview around a
 * mocked HomeCareRunSheetVisionService::readScan() call, since that class's
 * own real behaviour (a live Anthropic API call) has no precedent for
 * interception in this suite — see HomeCareRunSheetVisionServiceTest. Real
 * temp-directory file I/O, same convention as
 * HomeCareRunSheetExportServiceTest/UploadServiceTest.
 */
#[Test]
final class HomeCareRunSheetScanServiceTest
{
    public function writesTheScanAndMovesScannedThenPendingReviewAroundTheVisionRead(): void
    {
        $tempDir = sys_get_temp_dir() . '/homecare-run-sheets-' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            $runSheet = new HomeCareRunSheet();
            $runSheet->setId(5);

            $items = [new HomeCareRunSheetItem(run_sheet_id: 5, inv_id: 311)];

            /** @var RSIR&m\MockInterface $rsiR */
            $rsiR = m::mock(RSIR::class);
            $rsiR->shouldReceive('repoForRunSheetquery')->once()->with(5)->andReturn($items);

            /** @var HomeCareRunSheetVisionService&m\MockInterface $vision */
            $vision = m::mock(HomeCareRunSheetVisionService::class);
            $vision->shouldReceive('readScan')->once()
                ->with($items, 'fake-image-bytes', MediaType::IMAGE_JPEG);

            $aliases = new Aliases(['@homecare_run_sheets' => $tempDir]);
            /** @var SR&m\MockInterface $sR */
            $sR = m::mock(SR::class);
            $sR->shouldReceive('getHomeCareRunSheetsFolderAliases')->once()->andReturn($aliases);

            $statusesAtSave = [];
            /** @var RSR&m\MockInterface $rsR */
            $rsR = m::mock(RSR::class);
            $rsR->shouldReceive('save')->twice()->with($runSheet)
                ->andReturnUsing(function (HomeCareRunSheet $rs) use (&$statusesAtSave): void {
                    $statusesAtSave[] = $rs->getStatus();
                });

            $service = new HomeCareRunSheetScanService($rsR, $rsiR, $vision, $sR);
            $service->scan($runSheet, 'fake-image-bytes', MediaType::IMAGE_JPEG, 4);

            Assert::same([HomeCareRunSheetStatus::Scanned, HomeCareRunSheetStatus::PendingReview], $statusesAtSave);
            Assert::same(HomeCareRunSheetStatus::PendingReview, $runSheet->getStatus());
            Assert::same('runsheet_5_scan.jpg', $runSheet->getScannedFileName());
            Assert::same(4, $runSheet->getScannedByUserId());

            $scanPath = $tempDir . DIRECTORY_SEPARATOR . 'runsheet_5_scan.jpg';
            Assert::true(file_exists($scanPath));
            Assert::same('fake-image-bytes', file_get_contents($scanPath));
        } finally {
            $files = glob($tempDir . DIRECTORY_SEPARATOR . '*');
            if ($files !== false) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
            @rmdir($tempDir);
        }
    }

    public function fileExtensionMatchesEachMediaType(): void
    {
        /** @var list<array{MediaType, string}> $cases */
        $cases = [
            [MediaType::IMAGE_JPEG, 'jpg'],
            [MediaType::IMAGE_PNG, 'png'],
            [MediaType::IMAGE_GIF, 'gif'],
            [MediaType::IMAGE_WEBP, 'webp'],
        ];

        $tempDir = sys_get_temp_dir() . '/homecare-run-sheets-' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            $id = 5;
            foreach ($cases as [$mediaType, $extension]) {
                $runSheet = new HomeCareRunSheet();
                $runSheet->setId($id);

                /** @var RSIR&m\MockInterface $rsiR */
                $rsiR = m::mock(RSIR::class);
                $rsiR->shouldReceive('repoForRunSheetquery')->once()->with($id)->andReturn([]);

                /** @var HomeCareRunSheetVisionService&m\MockInterface $vision */
                $vision = m::mock(HomeCareRunSheetVisionService::class);
                $vision->shouldReceive('readScan')->once();

                $aliases = new Aliases(['@homecare_run_sheets' => $tempDir]);
                /** @var SR&m\MockInterface $sR */
                $sR = m::mock(SR::class);
                $sR->shouldReceive('getHomeCareRunSheetsFolderAliases')->once()->andReturn($aliases);

                /** @var RSR&m\MockInterface $rsR */
                $rsR = m::mock(RSR::class);
                $rsR->shouldReceive('save')->twice()->with($runSheet);

                $service = new HomeCareRunSheetScanService($rsR, $rsiR, $vision, $sR);
                $service->scan($runSheet, 'bytes', $mediaType, 1);

                Assert::same('runsheet_' . $id . '_scan.' . $extension, $runSheet->getScannedFileName());
                Assert::true(file_exists($tempDir . DIRECTORY_SEPARATOR . 'runsheet_' . $id . '_scan.' . $extension));
                $id++;
            }
        } finally {
            $files = glob($tempDir . DIRECTORY_SEPARATOR . '*');
            if ($files !== false) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
            @rmdir($tempDir);
        }
    }
}
