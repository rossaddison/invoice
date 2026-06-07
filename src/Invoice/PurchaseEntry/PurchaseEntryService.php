<?php

declare(strict_types=1);

namespace App\Invoice\PurchaseEntry;

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;

final readonly class PurchaseEntryService
{
    public function __construct(private PurchaseEntryRepository $repository)
    {
    }

    public function saveEntry(PurchaseEntry $entry, array $body): void
    {
        $datetime = new \DateTimeImmutable();
        if (isset($body['date'])) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d',
                (string) $body['date']) ?: $datetime;
            $entry->setDate($date);
        }
        $entry->setSupplier((string) ($body['supplier'] ?? ''));
        $entry->setDescription(isset($body['description'])
            && $body['description'] !== '' ? (string) $body['description'] : null);
        $entry->setAmountExVat((float) ($body['amount_ex_vat'] ?? 0));
        $entry->setVatAmount((float) ($body['vat_amount'] ?? 0));
        if (!$entry->isPersisted()) {
            $entry->setCreatedAt((new \DateTimeImmutable())->format('Y-m-d H:i:s'));
        }
        $this->repository->save($entry);
    }

    public function deleteEntry(PurchaseEntry $entry): void
    {
        $this->repository->delete($entry);
    }

    /**
     * Returns a VAT quarter label (e.g. "Q1 2025/2026") for a given date and
     * tax year start month. Q1 begins in $startMonth; each quarter spans 3 months.
     * Entries whose month precedes $startMonth belong to the prior tax year's Q4.
     */
    public static function vatQuarterLabel(\DateTimeImmutable $date, int $startMonth): string
    {
        $m       = (int) $date->format('n');
        $y       = (int) $date->format('Y');
        $quarter = (int) floor((($m - $startMonth + 12) % 12) / 3) + 1;
        $taxYear = $m < $startMonth ? $y - 1 : $y;
        return sprintf('Q%d %d/%d', $quarter, $taxYear, $taxYear + 1);
    }
}
