<?php

declare(strict_types=1);

namespace App\Invoice\PaymentPeppol;

use App\Invoice\Entity\PaymentPeppol;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of PaymentPeppol
 * @extends Select\Repository<TEntity>
 */
final class PaymentPeppolRepository extends Select\Repository
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
     * Get paymentpeppols  without filter
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

    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|PaymentPeppol|null $paymentpeppol
     * @psalm-param TEntity $paymentpeppol
     * @throws Throwable
     */
    public function save(array|PaymentPeppol|null $paymentpeppol): void
    {
        $this->entityWriter->write([$paymentpeppol]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|PaymentPeppol|null $paymentpeppol

     * @throws Throwable
     */
    public function delete(array|PaymentPeppol|null $paymentpeppol): void
    {
        $this->entityWriter->delete([$paymentpeppol]);
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
     * @return PaymentPeppol|null
     */
    public function repoPaymentPeppolLoadedquery(string $id): PaymentPeppol|null
    {
        $query = $this->select()->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
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
