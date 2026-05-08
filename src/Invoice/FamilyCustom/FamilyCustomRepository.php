<?php

declare(strict_types=1);

namespace App\Invoice\FamilyCustom;

use App\Infrastructure\Persistence\FamilyCustom\FamilyCustom;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of FamilyCustom
 * @extends Select\Repository<TEntity>
 */
final class FamilyCustomRepository extends Select\Repository
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
     * Get familycustoms  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                ->load('family')
                ->load('custom_field');
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
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|FamilyCustom|null $familycustom
     * @throws Throwable
     */
    public function save(array|FamilyCustom|null $familycustom): void
    {
        $this->entityWriter->write([$familycustom]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|FamilyCustom|null $familycustom
     * @throws Throwable
     */
    public function delete(array|FamilyCustom|null $familycustom): void
    {
        $this->entityWriter->delete([$familycustom]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @param int $id
     * @return FamilyCustom|null
     */
    public function repoFamilyCustomquery(int $id): ?FamilyCustom
    {
        $query = $this->select()
                      ->load('family')
                      ->load('custom_field')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param int $family_id
     * @return int
     */
    public function repoFamilyCount(int $family_id): int
    {
        $query = $this->select()
                      ->where(['family_id' => $family_id]);
        return $query->count();
    }

    /**
     * @param int $family_id
     * @param int $custom_field_id
     * @return FamilyCustom|null
     */
    public function repoFormValuequery(int $family_id, int $custom_field_id): ?FamilyCustom
    {
        $query = $this->select()
                      ->where(['family_id' => $family_id])
                      ->andWhere(['custom_field_id' => $custom_field_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param int $family_id
     * @param int $custom_field_id
     * @return int
     */
    public function repoFamilyCustomCount(int $family_id, int $custom_field_id): int
    {
        $query = $this->select()
                      ->where(['family_id' => $family_id])
                      ->andWhere(['custom_field_id' => $custom_field_id]);
        return $query->count();
    }

    /**
     * Get all fields that have been setup for a particular family
     *
     * @psalm-return EntityReader
     */
    public function repoFields(int $family_id): EntityReader
    {
        $query = $this->select()->where(['family_id' => $family_id]);
        return $this->prepareDataReader($query);
    }
}
