<?php

declare(strict_types=1);

namespace App\Invoice\CategorySecondary;

use App\Invoice\Entity\CategorySecondary;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of CategorySecondary
 *
 * @extends Select\Repository<TEntity>
 */
final class CategorySecondaryRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get categorysecondarys  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('category_primary');

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

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @psalm-param TEntity $categorysecondary
     *
     * @throws \Throwable
     */
    public function save(array|CategorySecondary|null $categorysecondary): void
    {
        $this->entityWriter->write([$categorysecondary]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|CategorySecondary|null $categorysecondary): void
    {
        $this->entityWriter->delete([$categorysecondary]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoCategoryPrimaryIdQuery(string $category_primary_id): EntityReader
    {
        $select = $this->select();
        $query  = $select
            ->where(['category_primary_id' => $category_primary_id]);

        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoCategorySecondaryQuery(string $id): ?CategorySecondary
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoCategorySecondaryLoadedQuery(string $id): ?CategorySecondary
    {
        $query = $this->select()
            ->load('category_primary')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function optionsDataCategorySecondaries(): array
    {
        $categorySecondaries            = $this->findAllPreloaded();
        $optionsDataCategorySecondaries = [];
        /**
         * @var CategorySecondary $categorySecondary
         */
        foreach ($categorySecondaries as $categorySecondary) {
            $categorySecondaryId = $categorySecondary->getId();
            if (null !== $categorySecondaryId) {
                $optionsDataCategorySecondaries[$categorySecondaryId] = ($categorySecondary->getName() ?? '');
            }
        }

        return $optionsDataCategorySecondaries;
    }

    public function optionsDataCategorySecondariesWithCategoryPrimaryId(string $category_primary_id): array
    {
        $categorySecondaries            = $this->repoCategoryPrimaryIdQuery($category_primary_id);
        $optionsDataCategorySecondaries = [];
        /**
         * @var CategorySecondary $categorySecondary
         */
        foreach ($categorySecondaries as $categorySecondary) {
            $categorySecondaryId = $categorySecondary->getId();
            if (null !== $categorySecondaryId) {
                $optionsDataCategorySecondaries[$categorySecondaryId] = ($categorySecondary->getName() ?? '');
            }
        }

        return $optionsDataCategorySecondaries;
    }

    public function repoCount(string $id): int
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->count();
    }
}
