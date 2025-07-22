<?php

declare(strict_types=1);

namespace App\Invoice\Unit;

use App\Invoice\Entity\Unit;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of Unit
 *
 * @extends Select\Repository<TEntity>
 */
final class UnitRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get units without filter.
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
    public function save(array|Unit|null $unit): void
    {
        $this->entityWriter->write([$unit]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|Unit|null $unit): void
    {
        $this->entityWriter->delete([$unit]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'unit_name', 'unit_name_plrl'])
                ->withOrder(['unit_name' => 'asc']),
        );
    }

    public function repoCount(string $unit_id): int
    {
        return $this->select()
            ->where(['id' => $unit_id])
            ->count();
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoUnitquery(string $unit_id): ?Unit
    {
        $query = $this
            ->select()
            ->where(['id' => $unit_id]);

        return $query->fetchOne() ?: null;
    }

    public function withName(string $unit_name): ?Unit
    {
        $query = $this
            ->select()
            ->where(['unit_name' => $unit_name]);

        return $query->fetchOne() ?: null;
    }

    /**
     * Return either the singular unit name or the plural unit name,
     * depending on the quantity.
     */
    public function singular_or_plural_name(string $unit_id, int $quantity): string|Unit|null
    {
        if (0 === (int) $unit_id) {
            return '';
        }
        $unit = $this->repoUnitquery($unit_id);
        if ($unit) {
            if (-1 == $quantity || 1 == $quantity) {
                return $unit->getUnit_name();
            }

            return $unit->getUnit_name_plrl();
        }

        return null;
    }

    public function repoTestDataCount(): int
    {
        return $this->select()
            ->count();
    }
}
