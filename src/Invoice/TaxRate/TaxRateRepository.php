<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use App\Invoice\Entity\TaxRate;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of TaxRate
 * @extends Select\Repository<TEntity>
 */
final class TaxRateRepository extends Select\Repository
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
     * Get taxRates without filter
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
     * @param array|TaxRate|null $taxRate
     * @throws Throwable
     */
    public function save(array|TaxRate|null $taxRate): void
    {
        $this->entityWriter->write([$taxRate]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|TaxRate|null $taxRate
     * @throws Throwable
     */
    public function delete(array|TaxRate|null $taxRate): void
    {
        $this->entityWriter->delete([$taxRate]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'tax_rate_name'])
                ->withOrder(['tax_rate_name' => 'asc']),
        );
    }

    /**
     * @param string $tax_rate_id
     * @return TaxRate|null
     */
    public function repoTaxRatequery(string $tax_rate_id): null|TaxRate
    {
        $query = $this
            ->select()
            ->where(['id' => $tax_rate_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $tax_rate_name
     * @return TaxRate|null
     */
    public function withName(string $tax_rate_name): TaxRate|null
    {
        $query = $this
            ->select()
            ->where(['tax_rate_name' => $tax_rate_name]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $tax_rate_id
     * @return int
     */
    public function repoCount(string $tax_rate_id): int
    {
        return $this->select()
                      ->where(['id' => $tax_rate_id])
                      ->count();
    }

    /**
     * @return int
     */
    public function repoCountAll(): int
    {
        return $this->select()
                         ->count();
    }

    /**
     * @return array
     */
    public function optionsDataTaxRates(): array
    {
        $taxRates = $this->findAllPreloaded();
        $optionsDataTaxRates = [];
        /**
         * @var TaxRate $taxRate
         */
        foreach ($taxRates as $taxRate) {
            $taxRateId = $taxRate->getTaxRateId();
            if (null !== $taxRateId) {
                $optionsDataTaxRates[$taxRateId] = ($taxRate->getTaxRateName() ?? '') . '  ' . (string) ($taxRate->getTaxRatePercent() ?? '');
            }
        }
        return $optionsDataTaxRates;
    }
}
