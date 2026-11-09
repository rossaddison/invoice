<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

use App\Infrastructure\Persistence\HomeCareRunDay\HomeCareRunDay;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of HomeCareRunDay
 * @extends Select\Repository<TEntity>
 */
final class HomeCareRunDayRepository extends Select\Repository implements HomeCareRunDayRepositoryInterface
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @param array|HomeCareRunDay|null $runDay
     * @throws Throwable
     */
    #[\Override]
    public function save(array|HomeCareRunDay|null $runDay): void
    {
        $this->entityWriter->write([$runDay]);
    }

    /**
     * @param array|HomeCareRunDay|null $runDay
     * @throws Throwable
     */
    #[\Override]
    public function delete(array|HomeCareRunDay|null $runDay): void
    {
        $this->entityWriter->delete([$runDay]);
    }

    #[\Override]
    public function repoDatequery(string $date): ?HomeCareRunDay
    {
        return $this->select()
            ->where(['run_date' => $date])
            ->fetchOne() ?: null;
    }

    #[\Override]
    public function repoRangequery(string $from, string $toExclusive): array
    {
        return $this->select()
            ->where('run_date', '>=', $from)
            ->andWhere('run_date', '<', $toExclusive)
            ->orderBy('run_date', 'ASC')
            ->fetchAll();
    }
}
