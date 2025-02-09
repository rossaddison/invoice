<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Invoice\Helpers\DateHelper;
use App\Invoice\Entity\InvRecurring;
use App\Invoice\Setting\SettingRepository;
use Cycle\ORM\Select;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of InvRecurring
 * @extends Select\Repository<TEntity>
 */
final class InvRecurringRepository extends Select\Repository
{
    private EntityWriter $entityWriter;

    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
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
                ->withOrder(['id' => 'asc'])
        );
    }

    // cycle/ORM/src/Select fetchOne
    /**
     * @param string $id
     * @return TEntity|null
     */
    public function repoInvRecurringquery(string $id): ?InvRecurring
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne();
    }

    // The invoice is recurring if at least one id is found
    public function repoCount(string $inv_id): int
    {
        $query = $this->select()
                      ->where(['inv_id' => $inv_id]);
        return $query->count();
    }

    public function recur_frequencies(): array
    {
        return [
            '1D' => 'i.calendar_day_1',
            '2D' => 'i.calendar_day_2',
            '3D' => 'i.calendar_day_3',
            '4D' => 'i.calendar_day_4',
            '5D' => 'i.calendar_day_5',
            '6D' => 'i.calendar_day_6',
            '15D' => 'i.calendar_day_15',
            '30D' => 'i.calendar_day_30',
            '7D' => 'i.calendar_week_1',
            '14D' => 'i.calendar_week_2',
            '21D' => 'i.calendar_week_3',
            '28D' => 'i.calendar_week_4',
            '1M' => 'i.calendar_month_1',
            '2M' => 'i.calendar_month_2',
            '3M' => 'i.calendar_month_3',
            '4M' => 'i.calendar_month_4',
            '5M' => 'i.calendar_month_5',
            '6M' => 'i.calendar_month_6',
            '7M' => 'i.calendar_month_7',
            '8M' => 'i.calendar_month_8',
            '9M' => 'i.calendar_month_9',
            '10M' => 'i.calendar_month_10',
            '11M' => 'i.calendar_month_11',
            '1Y' => 'i.calendar_year_1',
            '2Y' => 'i.calendar_year_2',
            '3Y' => 'i.calendar_year_3',
            '4Y' => 'i.calendar_year_4',
            '5Y' => 'i.calendar_year_5',
        ];
    }

    // Recur invoices become active when the current date passes the recur_next_date ie. recur_next_date is less than current date
    // They remain active as long as the current date does not pass the recur_end_date or the recur_end_date has been stopped
    // ie. a zero mysql string date is inserted.
    // If they are active the button will indicate active on it. Use the base invoice hyperlink to go to the respective invoice

    /**
     * Get invrecurrings  that are active
     *
     * @psalm-return EntityReader
     */
    public function active(SettingRepository $s): EntityReader
    {
        $datehelper = new DateHelper($s);
        $query = $this->select()
                      ->where('next_date', '<', date($datehelper->style()))
                      ->orWhere('end_date', '>', date($datehelper->style()))
                      ->orWhere('end_date', '=', '0000-00-00');
        return $this->prepareDataReader($query);
    }

    public function CountActive(SettingRepository $s): int
    {
        $datehelper = new DateHelper($s);
        return $this->select()
                      ->where('next_date', '<', date($datehelper->style()))
                      ->orWhere('end_date', '>', date($datehelper->style()))
                      ->orWhere('end_date', '=', '0000-00-00')
                      ->count();
    }
}
