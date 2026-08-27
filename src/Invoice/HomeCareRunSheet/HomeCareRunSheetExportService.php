<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunSheet;

use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use App\Infrastructure\Persistence\HomeCareRunSheetItem\HomeCareRunSheetItem;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Dwelling\DwellingRepository as DwR;
use App\Invoice\Enum\DoNotSendReason;
use App\Invoice\HomeCareRunSheet\Exception\RunSheetCsvWriteException;
use App\Invoice\HomeCareRunSheetItem\HomeCareRunSheetItemRepository as RSIR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Setting\SettingRepository as SR;
use DateTimeImmutable;

/**
 * Step 1 of the run-sheet reconciliation (see project_homecare_run_signoff_design
 * memory): snapshot today's HomeCare run into a HomeCareRunSheet + one
 * HomeCareRunSheetItem per invoice, and write the printable CSV those items
 * represent to disk. CSV, not xlsx — matches the existing
 * fputcsv/php://temp convention already used for purchase-entry/invoice-copy
 * templates (see PurchaseEntryController::csvTemplate(),
 * MultipleCopy::csvTemplateInvCopy()) rather than introducing a new
 * spreadsheet dependency; the sheet only needs to be printable and
 * re-readable, not a real workbook.
 */
final readonly class HomeCareRunSheetExportService
{
    public function __construct(
        private IR $iR,
        private HomeCareRunSheetRepository $rsR,
        private RSIR $rsiR,
        private DwR $dwR,
        private SR $sR,
    ) {}

    /**
     * Idempotent: a run that already has an open (non-Applied) sheet returns
     * that one unchanged rather than snapshotting a second, overlapping
     * batch for the same category_secondary_id + run_date.
     */
    public function export(int $categorySecondaryId, DateTimeImmutable $runDate, int $exportedByUserId): HomeCareRunSheet
    {
        $existing = $this->rsR->repoOpenForRunquery($categorySecondaryId, $runDate);
        if ($existing !== null) {
            return $existing;
        }

        $invoices = $this->sortByRoute($this->iR->repoForHomeCareRunquery($categorySecondaryId, $runDate));

        $runSheet = new HomeCareRunSheet($categorySecondaryId, $runDate, $exportedByUserId);
        $this->rsR->save($runSheet);
        $runSheetId = $runSheet->reqId();

        foreach ($invoices as $inv) {
            $item = new HomeCareRunSheetItem($runSheetId, $inv->reqId(), $inv->getWorker()?->reqId());
            $this->rsiR->save($item);
        }

        $fileName = $this->writeCsv($runSheetId, $invoices);
        $runSheet->setSpreadsheetFileName($fileName);
        $this->rsR->save($runSheet);

        return $runSheet;
    }

    /**
     * @param array<int, Inv> $invoices
     * @return array<int, Inv>
     */
    private function sortByRoute(array $invoices): array
    {
        $houseNumber = function (Inv $inv): int {
            $dwellingId = $inv->getClientDwellingId();
            if ($dwellingId === null) {
                return PHP_INT_MAX;
            }
            return $this->dwR->repoDwellingQuery($dwellingId)?->getHouseNumberNumeric() ?? PHP_INT_MAX;
        };
        usort(
            $invoices,
            static fn (Inv $a, Inv $b): int => $houseNumber($a) <=> $houseNumber($b) ?: $a->reqId() <=> $b->reqId(),
        );
        return $invoices;
    }

    /**
     * @param array<int, Inv> $invoices
     */
    private function writeCsv(int $runSheetId, array $invoices): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new RunSheetCsvWriteException('Could not open a temp stream for the run sheet CSV');
        }
        fputcsv($handle, ['seq', 'invoice_id', 'client', 'house_no', 'assigned_worker',
            'actual_worker', 'completed_y_n', 'reason_code']);
        $seq = 1;
        foreach ($invoices as $inv) {
            $dwellingId = $inv->getClientDwellingId();
            $house = $dwellingId !== null ? $this->dwR->repoDwellingQuery($dwellingId)?->getHouseNumberDisplay() : '';
            $worker = $inv->getWorker();
            fputcsv($handle, [
                $seq++,
                $inv->reqId(),
                $inv->getClient()?->getClientFullName() ?? '',
                $house ?? '',
                $worker !== null ? $worker->getFirstname() . ' ' . $worker->getLastname() : '',
                '', // actual_worker — filled in by hand
                '', // completed_y_n — filled in by hand
                '', // reason_code — filled in by hand, see legend below
            ]);
        }
        fputcsv($handle, []);
        fputcsv($handle, ['reason_code legend (use exactly one of these if not completed):']);
        foreach (DoNotSendReason::cases() as $reason) {
            fputcsv($handle, [$reason->value]);
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        $fileName = 'runsheet_' . $runSheetId . '.csv';
        $targetPath = $this->sR->getHomeCareRunSheetsFolderAliases()->get('@homecare_run_sheets');
        file_put_contents($targetPath . DIRECTORY_SEPARATOR . $fileName, $csv);

        return $fileName;
    }
}
