<?php

declare(strict_types=1);

namespace App\Invoice\InvTaxRate;

use App\Invoice\Entity\InvTaxRate;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of InvTaxRate
 *
 * @extends Select\Repository<TEntity>
 */
final class InvTaxRateRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get invtaxrates  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
            ->load('inv')
            ->load('tax_rate');

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
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throwable
     */
    public function save(array|InvTaxRate|null $invtaxrate): void
    {
        $this->entityWriter->write([$invtaxrate]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throwable
     */
    public function delete(array|InvTaxRate|null $invtaxrate): void
    {
        $this->entityWriter->delete([$invtaxrate]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    // find all inv tax rates assigned to specific inv. Normally only one but just in case more than one assigned
    // used in inv/view to determine if a 'one-off'  inv tax rate acquired from tax rates is to be applied to the inv
    // inv tax rates are children of their parent tax rate and are normally used when all products use the same tax rate ie. no item tax

    public function repoCount(?string $inv_id): int
    {
        return $this->select()
            ->where(['inv_id' => $inv_id])
            ->count();
    }

    /**
     * @return TEntity|null
     */
    public function repoInvTaxRatequery(string $id): ?InvTaxRate
    {
        $query = $this->select()
            ->load('inv')
            ->load('tax_rate')
            ->where(['id' => $id]);

        return $query->fetchOne();
    }

    // find all inv tax rates used for a specific inv normally to apply include_item_tax
    // (see function calculate_inv_taxes in NumberHelper
    // load 'tax rate' so that we can use tax_rate_id through the BelongTo relation in the Entity
    // to access the parent tax rate table's percent name and percentage
    // which we will use in inv/view
    public function repoInvquery(string $inv_id): EntityReader
    {
        $query = $this->select()->load('tax_rate')->where(['inv_id' => $inv_id]);

        return $this->prepareDataReader($query);
    }

    /**
     * @return TEntity|null
     */
    public function repoTaxRatequery(string $tax_rate_id): ?InvTaxRate
    {
        $query = $this->select()->load('tax_rate')->where(['tax_rate_id' => $tax_rate_id]);

        return $query->fetchOne();
    }

    public function repoGetInvTaxRateAmounts(string $inv_id): EntityReader
    {
        $query = $this->select()
            ->where(['inv_id' => $inv_id]);

        return $this->prepareDataReader($query);
    }

    public function repoUpdateInvTaxTotal(string $inv_id): float
    {
        $getTaxRateAmounts = $this->repoGetInvTaxRateAmounts($inv_id);
        $total             = 0.00;
        /** @var array $item */
        foreach ($getTaxRateAmounts as $item) {
            /**
             * @var string $key
             * @var float  $value
             */
            foreach ($item as $key => $value) {
                if ('inv_tax_rate_amount' === $key) {
                    $total += $value;
                }
            }
        }

        return $total;
    }
}
