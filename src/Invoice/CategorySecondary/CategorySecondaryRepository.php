<?php

declare(strict_types=1);

namespace App\Invoice\CategorySecondary;

use App\Invoice\Entity\CategorySecondary;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of CategorySecondary
 * @extends Select\Repository<TEntity>
 */
final class CategorySecondaryRepository extends Select\Repository
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
     * Get categorysecondarys  without filter
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

    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|CategorySecondary|null $categorysecondary
     * @psalm-param TEntity $categorysecondary
     * @throws Throwable
     */
    public function save(array|CategorySecondary|null $categorysecondary): void
    {
        $this->entityWriter->write([$categorysecondary]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|CategorySecondary|null $categorysecondary

     * @throws Throwable
     */
    public function delete(array|CategorySecondary|null $categorysecondary): void
    {
        $this->entityWriter->delete([$categorysecondary]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    public function repoCategoryPrimaryIdQuery(string $category_primary_id): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->where(['category_primary_id' => $category_primary_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param string $id
     * @psalm-return TEntity|null
     * @return CategorySecondary|null
     */
    public function repoCategorySecondaryQuery(string $id): CategorySecondary|null
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }
    
    /**
     * @param string $id
     * @psalm-return TEntity|null
     * @return CategorySecondary|null
     */
    public function repoCategorySecondaryLoadedQuery(string $id): CategorySecondary|null
    {
        $query = $this->select()
                      ->load('category_primary')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }
    
    /**
     * @return array
     */
    public function optionsDataCategorySecondaries(): array
    {
        $categorySecondaries = $this->findAllPreloaded();
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
    
    /**
     * @return array
     */
    public function optionsDataCategorySecondariesWithCategoryPrimaryId(string $category_primary_id): array
    {
        $categorySecondaries = $this->repoCategoryPrimaryIdQuery($category_primary_id);
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

    /**
     * @param string $id
     * @return int
     */
    public function repoCount(string $id): int
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return $query->count();
    }
}
