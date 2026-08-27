<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunSheet;

use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use App\Invoice\Enum\HomeCareRunSheetStatus;
use Cycle\ORM\Select;
use DateTimeImmutable;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of HomeCareRunSheet
 * @extends Select\Repository<TEntity>
 */
final class HomeCareRunSheetRepository extends Select\Repository implements HomeCareRunSheetRepositoryInterface
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @param array|HomeCareRunSheet|null $runSheet
     * @throws Throwable
     */
    #[\Override]
    public function save(array|HomeCareRunSheet|null $runSheet): void
    {
        $this->entityWriter->write([$runSheet]);
    }

    /**
     * @param array|HomeCareRunSheet|null $runSheet
     * @throws Throwable
     */
    #[\Override]
    public function delete(array|HomeCareRunSheet|null $runSheet): void
    {
        $this->entityWriter->delete([$runSheet]);
    }

    #[\Override]
    public function repoLoadedquery(int $id): ?HomeCareRunSheet
    {
        return $this->select()
            ->where(['id' => $id])
            ->fetchOne() ?: null;
    }

    #[\Override]
    public function repoOpenForRunquery(int $categorySecondaryId, DateTimeImmutable $runDate): ?HomeCareRunSheet
    {
        return $this->select()
            ->where([
                'category_secondary_id' => $categorySecondaryId,
                'run_date' => $runDate->format('Y-m-d'),
                'status' => ['!=' => HomeCareRunSheetStatus::Applied->value],
            ])
            ->orderBy('id', 'DESC')
            ->fetchOne() ?: null;
    }

    #[\Override]
    public function repoRecentquery(int $limit): iterable
    {
        return $this->select()
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }
}
