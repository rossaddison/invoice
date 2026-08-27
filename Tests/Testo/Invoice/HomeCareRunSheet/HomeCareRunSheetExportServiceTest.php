<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareRunSheet;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Infrastructure\Persistence\HomeCareRunSheetItem\HomeCareRunSheetItem;
use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\Dwelling\DwellingRepository as DwR;
use App\Invoice\Enum\DoNotSendReason;
use App\Invoice\HomeCareRunSheet\HomeCareRunSheetExportService;
use App\Invoice\HomeCareRunSheet\HomeCareRunSheetRepository as RSR;
use App\Invoice\HomeCareRunSheetItem\HomeCareRunSheetItemRepository as RSIR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Setting\SettingRepository as SR;
use DateTimeImmutable;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;

/**
 * Covers HomeCareRunSheetExportService::export() — Step 1 of the run-sheet
 * reconciliation (see project_homecare_run_signoff_design memory): the
 * idempotent re-fetch of an already-open batch, route-order sorting by
 * Dwelling::house_number_numeric (nulls/no-dwelling last per sortByRoute()'s
 * own PHP_INT_MAX fallback), and the printable CSV written to disk. Real
 * temp-directory file I/O, same convention as UploadServiceTest's
 * deleteUpload coverage — no existing precedent in this suite for mocking
 * the filesystem itself.
 */
#[Test]
final class HomeCareRunSheetExportServiceTest
{
    private function client(?int $dwellingId = null, string $fullName = 'Jane Doe'): Client
    {
        $client = new Client();
        $client->setClientFullName($fullName);
        if ($dwellingId !== null) {
            $client->setDwellingId($dwellingId);
        }
        return $client;
    }

    private function inv(int $id, Client $client, ?Worker $worker = null): Inv
    {
        $inv = new Inv();
        $inv->setId($id);
        $inv->setClient($client);
        if ($worker !== null) {
            $inv->setWorker($worker);
        }
        return $inv;
    }

    private function dwelling(int $id, int $houseNumber): Dwelling
    {
        $dwelling = new Dwelling(house_number_numeric: $houseNumber);
        $dwelling->setId($id);
        return $dwelling;
    }

    public function returnsTheExistingOpenBatchWithoutCreatingASecondOne(): void
    {
        $existing = new HomeCareRunSheet();
        $existing->setId(7);
        $runDate = new DateTimeImmutable('2026-08-26');

        /** @var RSR&m\MockInterface $rsR */
        $rsR = m::mock(RSR::class);
        $rsR->shouldReceive('repoOpenForRunquery')->once()->with(9, $runDate)->andReturn($existing);
        $rsR->shouldNotReceive('save');

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        $iR->shouldNotReceive('repoForHomeCareRunquery');

        /** @var RSIR&m\MockInterface $rsiR */
        $rsiR = m::mock(RSIR::class);
        $rsiR->shouldNotReceive('save');

        /** @var DwR&m\MockInterface $dwR */
        $dwR = m::mock(DwR::class);
        $dwR->shouldNotReceive('repoDwellingQuery');

        /** @var SR&m\MockInterface $sR */
        $sR = m::mock(SR::class);
        $sR->shouldNotReceive('getHomeCareRunSheetsFolderAliases');

        $service = new HomeCareRunSheetExportService($iR, $rsR, $rsiR, $dwR, $sR);

        Assert::same($existing, $service->export(9, $runDate, 1));
    }

    public function exportSnapshotsInvoicesInRouteOrderAndWritesAPrintableCsv(): void
    {
        $runDate = new DateTimeImmutable('2026-08-26');
        $tempDir = sys_get_temp_dir() . '/homecare-run-sheets-' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            $worker = new Worker(firstname: 'Amy', lastname: 'Lee');
            $worker->setId(7);

            // Deliberately out of route order and including a client with no
            // dwelling at all, to exercise sortByRoute()'s PHP_INT_MAX
            // fallback (must sort last, not first/crash).
            $invHighHouse = $this->inv(311, $this->client(101, 'John Smith'));
            $invLowHouse = $this->inv(312, $this->client(102, 'Amy Jones'), $worker);
            $invNoDwelling = $this->inv(313, $this->client(null, 'No Dwelling Client'));

            /** @var IR&m\MockInterface $iR */
            $iR = m::mock(IR::class);
            $iR->shouldReceive('repoForHomeCareRunquery')->once()->with(9, $runDate)
                ->andReturn([$invHighHouse, $invLowHouse, $invNoDwelling]);

            /** @var DwR&m\MockInterface $dwR */
            $dwR = m::mock(DwR::class);
            $dwR->shouldReceive('repoDwellingQuery')->with(101)->andReturn($this->dwelling(101, 12));
            $dwR->shouldReceive('repoDwellingQuery')->with(102)->andReturn($this->dwelling(102, 5));

            $savedItems = [];
            /** @var RSIR&m\MockInterface $rsiR */
            $rsiR = m::mock(RSIR::class);
            $rsiR->shouldReceive('save')->times(3)
                ->with(m::type(HomeCareRunSheetItem::class))
                ->andReturnUsing(function (HomeCareRunSheetItem $item) use (&$savedItems): void {
                    $savedItems[] = $item;
                });

            /** @var RSR&m\MockInterface $rsR */
            $rsR = m::mock(RSR::class);
            $rsR->shouldReceive('repoOpenForRunquery')->once()->with(9, $runDate)->andReturn(null);
            $rsR->shouldReceive('save')->twice()
                ->with(m::type(HomeCareRunSheet::class))
                ->andReturnUsing(function (HomeCareRunSheet $runSheet): void {
                    if (!$runSheet->isPersisted()) {
                        $runSheet->setId(99);
                    }
                });

            $aliases = new Aliases(['@homecare_run_sheets' => $tempDir]);
            /** @var SR&m\MockInterface $sR */
            $sR = m::mock(SR::class);
            $sR->shouldReceive('getHomeCareRunSheetsFolderAliases')->once()->andReturn($aliases);

            $service = new HomeCareRunSheetExportService($iR, $rsR, $rsiR, $dwR, $sR);
            $runSheet = $service->export(9, $runDate, 1);

            Assert::same(99, $runSheet->reqId());
            Assert::same(9, $runSheet->reqCategorySecondaryId());
            Assert::same('runsheet_99.csv', $runSheet->getSpreadsheetFileName());

            // Route order: house 5 before house 12 before no-dwelling (last).
            Assert::same(3, count($savedItems));
            Assert::same(312, $savedItems[0]->reqInvId());
            Assert::same(7, $savedItems[0]->getExpectedWorkerId());
            Assert::same(311, $savedItems[1]->reqInvId());
            Assert::null($savedItems[1]->getExpectedWorkerId());
            Assert::same(313, $savedItems[2]->reqInvId());
            foreach ($savedItems as $item) {
                Assert::same(99, $item->reqRunSheetId());
            }

            $csvPath = $tempDir . DIRECTORY_SEPARATOR . 'runsheet_99.csv';
            Assert::true(file_exists($csvPath));
            $lines = file($csvPath, FILE_IGNORE_NEW_LINES);
            Assert::false($lines === false);
            /** @var list<string> $lines */
            $rows = array_map('str_getcsv', $lines);

            Assert::same(
                ['seq', 'invoice_id', 'client', 'house_no', 'assigned_worker', 'actual_worker', 'completed_y_n', 'reason_code'],
                $rows[0],
            );
            Assert::same(['1', '312', 'Amy Jones', '5', 'Amy Lee', '', '', ''], $rows[1]);
            Assert::same(['2', '311', 'John Smith', '12', '', '', '', ''], $rows[2]);
            Assert::same(['3', '313', 'No Dwelling Client', '', '', '', '', ''], $rows[3]);
            // fputcsv([]) writes a bare blank line; str_getcsv('') on this
            // PHP version parses that back as [null], not an empty array.
            Assert::same([null], $rows[4]);
            Assert::same(
                ['reason_code legend (use exactly one of these if not completed):'],
                $rows[5],
            );
            $expectedReasonRows = array_map(
                static fn (DoNotSendReason $r): array => [$r->value],
                DoNotSendReason::cases(),
            );
            Assert::same($expectedReasonRows, array_slice($rows, 6));
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
