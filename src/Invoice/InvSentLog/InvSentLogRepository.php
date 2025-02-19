<?php

declare(strict_types=1);

namespace App\Invoice\InvSentLog;

use App\Invoice\Entity\InvSentLog;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of InvSentLog
 * @extends Select\Repository<TEntity>
 */
final class InvSentLogRepository extends Select\Repository
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
     * Get invsentlogs  without filter
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
     * One user can have more than one client e.g. Accountant paying off several client's invoices
     * @param string $user_id
     * @return EntityReader
     */
    public function withUser(string $user_id): EntityReader
    {
        $query = $this->select()
                      ->load('inv')
                      ->where(['inv.user_id' => $user_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @return InvSentLog
     *
     * @psalm-return TEntity
     */
    public function repoInvSentLogLoadedquery(string $id): InvSentLog
    {
        $query = $this->select()
                      ->load('inv')
                      ->where(['id' => $id]);
        return $query->fetchOne();
    }

    /**
     * @param string $inv_id
     * @return EntityReader
     */
    public function repoInvSentLogForEachInvoice(string $inv_id): EntityReader
    {
        $query = $this->select()
                      ->load('inv')
                      ->where(['inv_id' => $inv_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * Used in: inv/index.php to determine how many emails have been sent for each invoice
     * @param string $inv_id
     * @return int
     */
    public function repoInvSentLogEmailedCountForEachInvoice(string $inv_id): int
    {
        $query = $this->select()
                      ->load('inv')
                      ->where(['inv_id' => $inv_id]);
        return $query->count();
    }

    /**
     * Load all email logs associated with this selected dropdown invoice number
     * @param string $invNumber
     * @return EntityReader
     */
    public function filterInvNumber(string $invNumber): EntityReader
    {
        $select = $this->select()->load('inv');
        $query = $select->where(['inv.number' => ltrim(rtrim($invNumber))]);
        return $this->prepareDataReader($query);
    }

    /**
     * Load all email logs associated with this selected dropdown client id
     * @param string $client_id
     * @return EntityReader
     */
    public function filterClient(string $client_id): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['client_id' => $client_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * Load all email logs associated with the selected invoice number and client id
     * @param string $invNumber
     * @param string $client_id
     * @return EntityReader
     */
    public function filterInvNumberWithClient(string $invNumber, string $client_id): EntityReader
    {
        $select = $this->select()->load('inv');
        $query = $select->where(['client_id' => $client_id])->andWhere(['inv.number' => $invNumber]);
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
        return Sort::only(['id'])->withOrder(['id' => 'desc']);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|InvSentLog|null $invsentlog
     * @psalm-param TEntity $invsentlog
     * @throws Throwable
     */
    public function save(array|InvSentLog|null $invsentlog): void
    {
        $this->entityWriter->write([$invsentlog]);
    }

    /**
     * @param array|InvSentLog|null $invsentlog
     */
    public function delete(array|InvSentLog|null $invsentlog): void
    {
        $this->entityWriter->delete([$invsentlog]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'desc'])
        );
    }
}
