<?php

declare(strict_types=1);

namespace App\Invoice\PurchaseEntry;

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of PurchaseEntry
 * @extends Select\Repository<TEntity>
 */
final class PurchaseEntryRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        return $this->prepareDataReader($this->select());
    }

    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }

    private function getSort(): Sort
    {
        return Sort::only(['id', 'date', 'supplier'])->withOrder(['date' => 'desc']);
    }

    /**
     * @param array|PurchaseEntry|null $entry
     * @throws Throwable
     */
    public function save(array|PurchaseEntry|null $entry): void
    {
        $this->entityWriter->write([$entry]);
    }

    /**
     * @param array|PurchaseEntry|null $entry
     * @throws Throwable
     */
    public function delete(array|PurchaseEntry|null $entry): void
    {
        $this->entityWriter->delete([$entry]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'date', 'supplier'])
                ->withOrder(['date' => 'desc']),
        );
    }

    /**
     * @param int $id
     * @return PurchaseEntry|null
     * @psalm-return TEntity|null
     */
    public function repoFindById(int $id): ?PurchaseEntry
    {
        return $this->select()->where(['id' => $id])->fetchOne() ?: null;
    }

    public function count(): int
    {
        return $this->select()->count();
    }

    /**
     * Sum vat_amount and amount_ex_vat for entries whose date falls within [start, end].
     * Feeds VAT100 Box 4 (input VAT) and Box 7 (purchases ex-VAT).
     *
     * @param string $start YYYY-MM-DD
     * @param string $end   YYYY-MM-DD
     * @return array{input_vat: float, purchases_ex_vat: float}
     */
    public function repoVatTotalsForPeriod(string $start, string $end): array
    {
        $query = $this->select()
            ->where('date', '>=', $start)
            ->andWhere('date', '<=', $end);

        $inputVat = 0.0;
        $purchasesExVat = 0.0;

        /** @var PurchaseEntry $entry */
        foreach ((new EntityReader($query))->read() as $entry) {
            $inputVat += $entry->getVatAmount();
            $purchasesExVat += $entry->getAmountExVat();
        }

        return [
            'input_vat'       => round($inputVat, 2),
            'purchases_ex_vat' => round($purchasesExVat, 2),
        ];
    }

    /**
     * @param string $start YYYY-MM-DD
     * @param string $end   YYYY-MM-DD
     * @psalm-return EntityReader
     */
    public function repoFindForPeriod(string $start, string $end): EntityReader
    {
        $query = $this->select()
            ->where('date', '>=', $start)
            ->andWhere('date', '<=', $end);
        return $this->prepareDataReader($query);
    }
}
