<?php

declare(strict_types=1);

namespace App\Invoice\InvSentLog;

use App\Invoice\Entity\InvSentLog;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of InvSentLog
 *
 * @extends Select\Repository<TEntity>
 */
final class InvSentLogRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get invsentlogs  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
            ->load('inv');

        return $this->prepareDataReader($query);
    }

    /**
     * Select all email logs sent by email to this user
     * One user can have more than one client e.g. Accountant paying off several client's invoices.
     */
    public function withUser(string $user_id): EntityReader
    {
        $query = $this->select()
            ->load('inv')
            ->where(['inv.user_id' => $user_id]);

        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return TEntity
     */
    public function repoInvSentLogLoadedquery(string $id): InvSentLog
    {
        $query = $this->select()
            ->load('inv')
            ->where(['id' => $id]);

        return $query->fetchOne();
    }

    public function repoInvSentLogForEachInvoice(string $inv_id): EntityReader
    {
        $query = $this->select()
            ->load('inv')
            ->where(['inv_id' => $inv_id]);

        return $this->prepareDataReader($query);
    }

    /**
     * Used in: inv/index.php to determine how many emails have been sent for each invoice.
     */
    public function repoInvSentLogEmailedCountForEachInvoice(string $inv_id): int
    {
        $query = $this->select()
            ->load('inv')
            ->where(['inv_id' => $inv_id]);

        return $query->count();
    }

    /**
     * Load all email logs associated with this selected dropdown invoice number.
     */
    public function filterInvNumber(string $invNumber): EntityReader
    {
        $select = $this->select()->load('inv');
        $query  = $select->where(['inv.number' => ltrim(rtrim($invNumber))]);

        return $this->prepareDataReader($query);
    }

    /**
     * Load all email logs associated with this selected dropdown client id.
     */
    public function filterClient(string $client_id): EntityReader
    {
        $select = $this->select();
        $query  = $select->where(['client_id' => $client_id]);

        return $this->prepareDataReader($query);
    }

    /**
     * Load all email logs associated with the selected invoice number and client id.
     */
    public function filterInvNumberWithClient(string $invNumber, string $client_id): EntityReader
    {
        $select = $this->select()->load('inv');
        $query  = $select->where(['client_id' => $client_id])->andWhere(['inv.number' => $invNumber]);

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
        return Sort::only(['id'])->withOrder(['id' => 'desc']);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @psalm-param TEntity $invsentlog
     *
     * @throws \Throwable
     */
    public function save(array|InvSentLog|null $invsentlog): void
    {
        $this->entityWriter->write([$invsentlog]);
    }

    public function delete(array|InvSentLog|null $invsentlog): void
    {
        $this->entityWriter->delete([$invsentlog]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'desc']),
        );
    }
}
