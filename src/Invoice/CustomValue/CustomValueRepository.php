<?php

declare(strict_types=1);

namespace App\Invoice\CustomValue;

use App\Invoice\Entity\CustomField;
use App\Invoice\Entity\CustomValue;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of CustomValue
 *
 * @extends Select\Repository<TEntity>
 */
final class CustomValueRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get customvalues  without filter.
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

    public function save(array|CustomValue|null $customvalue): void
    {
        $this->entityWriter->write([$customvalue]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|CustomValue|null $customvalue): void
    {
        $this->entityWriter->delete([$customvalue]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoCustomValuequery(string $id): ?CustomValue
    {
        $query = $this->select()->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoCount(string $id): int
    {
        return $this->select()
            ->where(['id' => $id])
            ->count();
    }

    /**
     * Get customvalues  with filter.
     *
     * @psalm-return EntityReader
     */
    public function repoCustomFieldquery(int $custom_field_id): EntityReader
    {
        $query = $this->select()->where(['custom_field_id' => $custom_field_id]);

        return $this->prepareDataReader($query);
    }

    public function repoCustomFieldquery_count(int $custom_field_id): int
    {
        return $this->select()
            ->where(['custom_field_id' => $custom_field_id])
            ->count();
    }

    public function attach_hard_coded_custom_field_values_to_custom_field(EntityReader $custom_fields): array
    {
        $custom_values = [];
        /** @var CustomField $custom_field */
        foreach ($custom_fields as $custom_field) {
            if (in_array($custom_field->getType(), ['SINGLE-CHOICE', 'MULTIPLE-CHOICE'])) {
                // build the $custom_values array with the eg. dropdown values for the field whether it be a multiple-choice field or a single-choice field
                $custom_values[$custom_field->getId()] = $this->repoCustomFieldquery((int) $custom_field->getId());
            }
        }

        return $custom_values;
    }
}
