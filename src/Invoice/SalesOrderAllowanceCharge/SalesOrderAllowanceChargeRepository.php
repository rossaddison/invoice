<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderAllowanceCharge;

use App\Invoice\Entity\SalesOrderAllowanceCharge;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of SalesOrderAllowanceCharge
 * @extends Select\Repository<TEntity>
 */
final class SalesOrderAllowanceChargeRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(
        Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get salesorderallowancecharges  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load('allowance_charge');
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
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrderAllowanceCharge|null $salesorderallowancecharge
     * @psalm-param TEntity $salesorderallowancecharge
     * @throws Throwable
     */
    public function save(
        array|SalesOrderAllowanceCharge|null $salesorderallowancecharge): void
    {
        $this->entityWriter->write([$salesorderallowancecharge]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrderAllowanceCharge|null $salesorderallowancecharge

     * @throws Throwable
     */
    public function delete(
        array|SalesOrderAllowanceCharge|null $salesorderallowancecharge): void
    {
        $this->entityWriter->delete([$salesorderallowancecharge]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @param string $id
     * @psalm-return TEntity|null
     * @return SalesOrderAllowanceCharge|null
     */
    public function repoSalesOrderAllowanceChargeLoadedquery(string $id):
        ?SalesOrderAllowanceCharge
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $salesorderNumber
     * @psalm-return EntityReader
     */
    public function repoSalesOrderNumberQuery(string $salesorderNumber):
        EntityReader
    {
        $query = $this->select()
                      ->load('sales_order')
                      ->where(['salesorder.number' => $salesorderNumber]);
        return $this->prepareDataReader($query);
    }

    public function repoReasonCodeQuery(string $reasonCode): EntityReader
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['allowance_charge.reason_code' => $reasonCode]);
        return $this->prepareDataReader($query);
    }

    public function repoReasonQuery(string $reason): EntityReader
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['allowance_charge.reason' => $reason]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param string $sales_order_id
     * @psalm-return EntityReader
     */
    public function repoACSOquery(string $sales_order_id): EntityReader
    {
        $query = $this->select()
                      ->load('allowance_charge')
                      ->where(['sales_order_id' => $sales_order_id]);
        return $this->prepareDataReader($query);
    }

    public function getPackHandleShipTotal(string $salesorder_id): array
    {
        $all = $this->repoACSOquery($salesorder_id);
        $totalAmount = 0.00;
        $totalTax = 0.00;
        /**
         * @var SalesOrderAllowanceCharge $each
         */
        foreach ($all as $each) {
            $amount  = $each->getAmount();
            $tax = $each->getVatOrTax();
            if ($each->getAllowanceCharge()?->getIdentifier()) {
                $totalAmount += (float) $amount;
                $totalTax += (float) $tax;

            } else {
                $totalAmount -= (float) $amount;
                $totalTax -= (float) $tax;
            }
        }
        return ['totalAmount' => $totalAmount, 'totalTax' => $totalTax];
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

    public function repoACQCount(string $sales_order_id): int
    {
        $query = $this->select()
                      ->where(['sales_order_id' => $sales_order_id]);
        return $query->count();
    }
}
