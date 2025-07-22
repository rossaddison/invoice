<?php

declare(strict_types=1);

namespace App\Invoice\CategoryPrimary;

use App\Invoice\Entity\CategoryPrimary;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of CategoryPrimary
 *
 * @extends Select\Repository<TEntity>
 */
final class CategoryPrimaryRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get categoryprimarys without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();

        return $this->prepareDataReader($query);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function save(array|CategoryPrimary|null $categoryPrimary): void
    {
        $this->entityWriter->write([$categoryPrimary]);
    }

    public function delete(array|CategoryPrimary|null $categoryPrimary): void
    {
        $this->entityWriter->delete([$categoryPrimary]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'name'])
                ->withOrder(['name' => 'asc']),
        );
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoCategoryPrimaryQuery(string $id): ?CategoryPrimary
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function optionsDataCategoryPrimaries(): array
    {
        $categoryPrimaries            = $this->findAllPreloaded();
        $optionsDataCategoryPrimaries = [];
        /**
         * @var CategoryPrimary $categoryPrimary
         */
        foreach ($categoryPrimaries as $categoryPrimary) {
            $categoryPrimaryId = $categoryPrimary->getId();
            if (null !== $categoryPrimaryId) {
                $optionsDataCategoryPrimaries[$categoryPrimaryId] = ($categoryPrimary->getName() ?? '');
            }
        }

        return $optionsDataCategoryPrimaries;
    }
}
