<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunSheetItem;

use App\Infrastructure\Persistence\HomeCareRunSheetItem\HomeCareRunSheetItem;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of HomeCareRunSheetItem
 * @extends Select\Repository<TEntity>
 */
final class HomeCareRunSheetItemRepository extends Select\Repository implements HomeCareRunSheetItemRepositoryInterface
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @param array|HomeCareRunSheetItem|null $item
     * @throws Throwable
     */
    #[\Override]
    public function save(array|HomeCareRunSheetItem|null $item): void
    {
        $this->entityWriter->write([$item]);
    }

    /**
     * @param array|HomeCareRunSheetItem|null $item
     * @throws Throwable
     */
    #[\Override]
    public function delete(array|HomeCareRunSheetItem|null $item): void
    {
        $this->entityWriter->delete([$item]);
    }

    #[\Override]
    public function repoForRunSheetquery(int $runSheetId): array
    {
        return $this->select()
            ->where(['run_sheet_id' => $runSheetId])
            ->orderBy('id', 'ASC')
            ->fetchAll();
    }

    /**
     * Filtered in PHP rather than SQL — see
     * HomeCareRunSheetItem::hasDetectedChange() for what "changed" means and
     * why it's a predicate method rather than a query condition: the same
     * check is also used by the Apply step, and a three-way nullable-column
     * comparison is far more readable in PHP than as portable SQL NULL-safe
     * inequality across columns anyway.
     */
    #[\Override]
    public function repoChangedForRunSheetquery(int $runSheetId): array
    {
        return array_values(array_filter(
            $this->repoForRunSheetquery($runSheetId),
            static fn (HomeCareRunSheetItem $item): bool => $item->hasDetectedChange(),
        ));
    }
}
