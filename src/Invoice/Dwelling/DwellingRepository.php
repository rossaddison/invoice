<?php

declare(strict_types=1);

namespace App\Invoice\Dwelling;

use App\Infrastructure\Persistence\Dwelling\Dwelling;
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Dwelling
 * @extends Select\Repository<TEntity>
 */
final class DwellingRepository extends Select\Repository
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
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        return $this->prepareDataReader($this->select());
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Dwelling|null $dwelling
     * @throws Throwable
     */
    public function save(array|Dwelling|null $dwelling): void
    {
        $this->entityWriter->write([$dwelling]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Dwelling|null $dwelling
     * @throws Throwable
     */
    public function delete(array|Dwelling|null $dwelling): void
    {
        $this->entityWriter->delete([$dwelling]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'house_number_numeric'])
                ->withOrder(['house_number_numeric' => 'asc']),
        );
    }

    /**
     * @return Dwelling|null
     *
     * @psalm-return TEntity|null
     */
    public function repoDwellingQuery(int $id): ?Dwelling
    {
        $query = $this
            ->select()
            ->where(['id' => $id]);
        return $query->fetchOne() ?: null;
    }

    /**
     * All Dwellings on a given street (Family), ordered for round-walking: street position established by
     * Family.street_sort_order elsewhere, house order here by house_number_numeric then house_number_suffix
     * so "12" sorts before "12A" before "13" — a single string column couldn't do that.
     */
    public function repoByFamilyIdQuery(int $family_id): EntityReader
    {
        $query = $this->select()
            ->where(['family_id' => $family_id])
            ->orderBy(['house_number_numeric' => 'asc', 'house_number_suffix' => 'asc']);
        return new EntityReader($query);
    }

    /**
     * The actual Dwelling row for a given street + house number, if one already exists — used by
     * {@see \App\Invoice\Dwelling\DwellingService::findOrCreateDwelling()} to resolve (not just detect)
     * an existing match. See {@see self::repoDwellingWithFamilyIdAndHouseNumberQuery()} for the
     * count-only variant used by bulk-generation dedup checks.
     *
     * @return Dwelling|null
     *
     * @psalm-return TEntity|null
     */
    public function repoDwellingByFamilyIdAndHouseNumberQuery(
        int $family_id,
        int $house_number_numeric,
        ?string $house_number_suffix,
    ): ?Dwelling {
        $query = $this->select()
            ->where(['family_id' => $family_id])
            ->andWhere(['house_number_numeric' => $house_number_numeric])
            ->andWhere(['house_number_suffix' => $house_number_suffix]);
        return $query->fetchOne() ?: null;
    }

    /**
     * Dedup guard for bulk generation (commalist/spreadsheet import) — mirrors
     * ProductRepository::repoProductWithFamilyIdQuery()'s role for the old Product-per-house approach.
     */
    public function repoDwellingWithFamilyIdAndHouseNumberQuery(
        int $family_id,
        int $house_number_numeric,
        ?string $house_number_suffix,
    ): int {
        $query = $this->select()
            ->where(['family_id' => $family_id])
            ->andWhere(['house_number_numeric' => $house_number_numeric])
            ->andWhere(['house_number_suffix' => $house_number_suffix]);
        return $query->count();
    }

    /**
     * Dwellings on a street excluding the given (already-claimed) ids — the worker canvassing dropdown's
     * "which houses can I sign up new interest at" list. Callers get the claimed-id list from
     * ClientRepository and pass it in here, composed at the service layer
     * ({@see \App\Invoice\Dwelling\DwellingService::repoUnclaimedDwellings()}) — this repository stays
     * self-contained and doesn't reach across into the Client table itself, matching every other
     * repository in this codebase. Not a stored flag on Dwelling — see the class docblock on
     * {@see Dwelling} for why.
     *
     * @param list<int> $claimedDwellingIds
     */
    public function repoUnclaimedByFamilyIdQuery(int $family_id, array $claimedDwellingIds): EntityReader
    {
        $query = $this->select()
            ->where(['family_id' => $family_id]);
        if ($claimedDwellingIds !== []) {
            $query = $query->andWhere(['id' => ['not in' => new Parameter($claimedDwellingIds)]]);
        }
        $query = $query->orderBy(['house_number_numeric' => 'asc', 'house_number_suffix' => 'asc']);
        return new EntityReader($query);
    }

    /**
     * @return int
     */
    public function repoCount(int $id): int
    {
        return $this->select()
            ->where(['id' => $id])
            ->count();
    }
}
