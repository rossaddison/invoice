<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use App\Invoice\Entity\Family;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of Family
 *
 * @extends Select\Repository<TEntity>
 */
final class FamilyRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get families without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();

        return $this->prepareDataReader($query);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function save(array|Family|null $family): void
    {
        $this->entityWriter->write([$family]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|Family|null $family): void
    {
        $this->entityWriter->delete([$family]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'family_name'])
                ->withOrder(['family_name' => 'asc']),
        );
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoFamilyquery(string $family_id): ?Family
    {
        $query = $this
            ->select()
            ->where(['id' => $family_id]);

        return $query->fetchOne() ?: null;
    }

    public function repoCategoryPrimaryAndSecondaryQuery(string $category_primary_id, string $category_secondary_id): EntityReader
    {
        $select = $this->select();
        $query  = $select
            ->where(['category_primary_id' => $category_primary_id])
            ->andWhere(['category_secondary_id' => $category_secondary_id]);

        return $this->prepareDataReader($query);
    }

    public function repoCategorySecondaryIdQuery(string $category_secondary_id): EntityReader
    {
        $select = $this->select();
        $query  = $select
            ->where(['category_secondary_id' => $category_secondary_id]);

        return $this->prepareDataReader($query);
    }

    public function optionsDataFamilyNamesWithCategorySecondaryId(string $category_secondary_id): array
    {
        $familyNames            = $this->repoCategorySecondaryIdQuery($category_secondary_id);
        $optionsDataFamilyNames = [];
        /**
         * @var Family $family
         */
        foreach ($familyNames as $family) {
            $familyId = $family->getFamily_id();
            if (null !== $familyId) {
                $optionsDataFamilyNames[$familyId] = ($family->getFamily_name() ?? '');
            }
        }

        return $optionsDataFamilyNames;
    }

    /**
     * @psalm-return TEntity|null
     */
    public function withName(string $family_name): ?Family
    {
        $query = $this
            ->select()
            ->where(['family_name' => $family_name]);

        return $query->fetchOne() ?: null;
    }

    public function repoTestDataCount(): int
    {
        return $this->select()
            ->count();
    }
}
