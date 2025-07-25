<?php

declare(strict_types=1);

namespace App\Invoice\CategoryPrimary;

use App\Invoice\Entity\CategoryPrimary;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of CategoryPrimary
 * @extends Select\Repository<TEntity>
 */
final class CategoryPrimaryRepository extends Select\Repository
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
     * Get categoryprimarys without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|CategoryPrimary|null $categoryPrimary
     * @throws Throwable
     */
    public function save(array|CategoryPrimary|null $categoryPrimary): void
    {
        $this->entityWriter->write([$categoryPrimary]);
    }

    /**
     * @param array|CategoryPrimary|null $categoryPrimary
     */
    public function delete(array|CategoryPrimary|null $categoryPrimary): void
    {
        $this->entityWriter->delete([$categoryPrimary]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'name'])
                ->withOrder(['name' => 'asc']),
        );
    }

    /**
     * @return CategoryPrimary|null
     *
     * @psalm-return TEntity|null
     */
    public function repoCategoryPrimaryQuery(string $id): CategoryPrimary|null
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @return array
     */
    public function optionsDataCategoryPrimaries(): array
    {
        $categoryPrimaries = $this->findAllPreloaded();
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
