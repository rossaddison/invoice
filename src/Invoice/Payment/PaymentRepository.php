<?php

declare(strict_types=1);

namespace App\Invoice\Payment;

use App\Invoice\Entity\Payment;
// Cycle
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
// Yiisoft
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of Payment
 *
 * @extends Select\Repository<TEntity>
 */
final class PaymentRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get payments  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('inv')
            ->load('payment_method');

        return $this->prepareDataReader($query);
    }

    // Find all payments associated with a user's clients ie. their client list / client_id_array

    /**
     * Get payments  with filter.
     *
     * @psalm-return EntityReader
     */
    public function findOneUserManyClientsPayments(array $client_id_array): EntityReader
    {
        $query = $this->select()
            ->load('inv')
            ->where(['inv.client_id' => ['in' => new Parameter($client_id_array)]]);

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
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function save(array|Payment|null $payment): void
    {
        $this->entityWriter->write([$payment]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|Payment|null $payment): void
    {
        $this->entityWriter->delete([$payment]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'desc']),
        );
    }

    /**
     * @psalm-return EntityReader
     */
    public function repoPaymentInvLoadedAll(int $list_limit)
    {
        $query = $this->select()
            ->load('inv')
            ->limit($list_limit);

        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function repoPaymentAmountFilter(string $paymentAmount)
    {
        $query = $this->select()
            ->where(['amount' => $paymentAmount]);

        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function repoPaymentDateFilter(string $paymentDate)
    {
        $query = $this->select()
            ->where(['payment_date' => $paymentDate]);

        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function repoPaymentAmountWithDateFilter(string $paymentAmount, string $paymentDate)
    {
        $query = $this->select()
            ->where(['payment_date' => $paymentDate])
            ->andWhere(['amount' => $paymentAmount]);

        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoPaymentquery(string $id): ?Payment
    {
        $query = $this->select()
            ->load('inv')
            ->load('payment_method')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoPaymentLoaded_from_to_count(string $from, string $to): int
    {
        return $this->select()
            ->load('inv')
            ->load('payment_method')
            ->where('payment_date', '>=', $from)
            ->andWhere('payment_date', '<=', $to)
            ->count();
    }

    public function repoPaymentLoaded_from_to(string $from, string $to): EntityReader
    {
        $query = $this->select()
            ->load('inv')
            ->load('payment_method')
            ->where('payment_date', '>=', $from)
            ->andWhere('payment_date', '<=', $to);

        return $this->prepareDataReader($query);
    }

    /**
     * Get payments  without filter.
     *
     * @psalm-return EntityReader
     */
    public function repoInvquery(string $inv_id): EntityReader
    {
        $query = $this->select()
            ->where(['inv_id' => $inv_id]);

        return $this->prepareDataReader($query);
    }

    public function repoCount(string $inv_id): int
    {
        return $this->select()
            ->where(['inv_id' => $inv_id])
            ->count();
    }
}
