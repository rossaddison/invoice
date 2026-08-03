<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Invoice\Helpers\DateHelper;
use App\Infrastructure\Persistence\InvRecurring\InvRecurring;
use App\Invoice\Setting\SettingRepository;
use Cycle\ORM\Select;
use Cycle\ORM\Select\QueryBuilder;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of InvRecurring
 * @extends Select\Repository<TEntity>
 */
final class InvRecurringRepository extends Select\Repository
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
     * Get invrecurrings  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
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
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    public function save(array|InvRecurring|null $invrecurring): void
    {
        $this->entityWriter->write([$invrecurring]);
    }

    public function delete(array|InvRecurring|null $invrecurring): void
    {
        $this->entityWriter->delete([$invrecurring]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    // cycle/ORM/src/Select fetchOne
    /**
     * @param int $id
     * @return TEntity|null
     */
    public function repoInvRecurringquery(int $id): ?InvRecurring
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne();
    }

    // The invoice is recurring if at least one id is found
    public function repoCount(int $inv_id): int
    {
        $query = $this->select()
                      ->where(['inv_id' => $inv_id]);
        return $query->count();
    }

    public function recurFrequencies(): array
    {
        return [
            '1D' => 'calendar.day.1',
            '2D' => 'calendar.day.2',
            '3D' => 'calendar.day.3',
            '4D' => 'calendar.day.4',
            '5D' => 'calendar.day.5',
            '6D' => 'calendar.day.6',
            '15D' => 'calendar.day.15',
            '30D' => 'calendar.day.30',
            '7D' => 'calendar.week.1',
            '14D' => 'calendar.week.2',
            '21D' => 'calendar.week.3',
            '28D' => 'calendar.week.4',
            '1M' => 'calendar.month.1',
            '2M' => 'calendar.month.2',
            '3M' => 'calendar.month.3',
            '4M' => 'calendar.month.4',
            '5M' => 'calendar.month.5',
            '6M' => 'calendar.month.6',
            '7M' => 'calendar.month.7',
            '8M' => 'calendar.month.8',
            '9M' => 'calendar.month.9',
            '10M' => 'calendar.month.10',
            '11M' => 'calendar.month.11',
            '1Y' => 'calendar.year.1',
            '2Y' => 'calendar.year.2',
            '3Y' => 'calendar.year.3',
            '4Y' => 'calendar.year.4',
            '5Y' => 'calendar.year.5',
        ];
    }

    // Recur invoices are due when today has reached or passed "next", and
    // haven't been stopped ("end" left unset) or already run past "end".
    // If they are active the button will indicate active on it. Use the base invoice hyperlink to go to the respective invoice

    /**
     * Get invrecurrings that are due to generate an invoice today.
     *
     * @psalm-return EntityReader
     */
    public function active(): EntityReader
    {
        $query = $this->dueQuery();
        return $this->prepareDataReader($query);
    }

    public function CountActive(): int
    {
        return $this->dueQuery()->count();
    }

    /**
     * @return Select<InvRecurring>
     */
    private function dueQuery(): Select
    {
        $today = date('Y-m-d');
        return $this->select()
            ->where('next', '<=', $today)
            ->where(static function (QueryBuilder $q) use ($today): void {
                $q->where('end', null)->orWhere('end', '>=', $today);
            });
    }
}
