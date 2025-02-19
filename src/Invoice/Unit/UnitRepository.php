<?php

declare(strict_types=1);

namespace App\Invoice\Unit;

use App\Invoice\Entity\Unit;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Unit
 * @extends Select\Repository<TEntity>
 */
final class UnitRepository extends Select\Repository
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
     * Get units without filter
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
     * @param array|Unit|null $unit
     * @throws Throwable
     */
    public function save(array|Unit|null $unit): void
    {
        $this->entityWriter->write([$unit]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Unit|null $unit
     * @throws Throwable
     */
    public function delete(array|Unit|null $unit): void
    {
        $this->entityWriter->delete([$unit]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'unit_name', 'unit_name_plrl'])
                ->withOrder(['unit_name' => 'asc'])
        );
    }

    /**
     * @param string $unit_id
     * @return int
     */
    public function repoCount(string $unit_id): int
    {
        return $this->select()
                      ->where(['id' => $unit_id])
                      ->count();
    }

    /**
     * @return Unit|null
     *
     * @psalm-return TEntity|null
     */
    public function repoUnitquery(string $unit_id): Unit|null
    {
        $query = $this
            ->select()
            ->where(['id' => $unit_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $unit_name
     * @return Unit|null
     */
    public function withName(string $unit_name): Unit|null
    {
        $query = $this
            ->select()
            ->where(['unit_name' => $unit_name]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * Return either the singular unit name or the plural unit name,
     * depending on the quantity
     *
     * @param string $unit_id
     * @param int $quantity
     * @return string|Unit|null
     */
    public function singular_or_plural_name(string $unit_id, int $quantity): string|Unit|null
    {
        if ((int)$unit_id === 0) {
            return '';
        }
        $unit = $this->repoUnitquery($unit_id);
        if ($unit) {
            if ($quantity == -1 || $quantity == 1) {
                return $unit->getUnit_name();
            }
            return $unit->getUnit_name_plrl();
        }
        return null;
    }

    /**
     * @return int
     */
    public function repoTestDataCount(): int
    {
        return $this->select()
                      ->count();
    }
}
