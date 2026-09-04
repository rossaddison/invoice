<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Reader\Sort;

trait InvGuestTrait
{
    /**
     * @return Inv|null
     */
    public function repoUrlKeyGuestLoaded(string $url_key): ?Inv
    {
        $query = $this->select()
                       ->load('client')
                       ->where(['url_key' => $url_key])
                       ->andWhere(
                           ['status_id' => ['in' => new Parameter([2,3,4])]]
                       )
                       ->where('deleted_at', null);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $url_key
     * @return int
     */
    public function repoUrlKeyGuestCount(string $url_key): int
    {
        return $this->select()
                      ->where(['url_key' => $url_key])
                      ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                      ->where('deleted_at', null)
                      ->count();
    }

    public function repoClientGuestCount(int $inv_id, array $user_client = []): Select
    {
        return $this->select()
                      ->where(['id' => $inv_id])
                      ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                      ->andWhere(['client_id' => ['in' => new Parameter(
                          $user_client
                      )]])
                      ->where('deleted_at', null);
    }

    /**
     * @param int $status_id
     * @param array $user_client
     * @return EntityReader
     */
    public function repoGuestClientsPostDraft(int $status_id, array $user_client = []): EntityReader
    {
        if ($status_id > 0) {
            $query = $this->select()
                    ->where(['status_id' => $status_id])
                    ->where(['client_id' => ['in' => new Parameter($user_client)]])
                    ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                    ->where('deleted_at', null);
            return $this->prepareDataReader($query);
        }
        $query = $this->select()
                     ->where(['client_id' => ['in' => new Parameter($user_client)]])
                     ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                     ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * Returns sent/viewed (unpaid) invoices for a set of client IDs.
     * @param array $clientIds
     * @return Inv[]
     */
    public function repoUnpaidByClientIds(array $clientIds): array
    {
        if (empty($clientIds)) {
            return [];
        }
        return $this->select()
                    ->load('invAmount')
                    ->where(['client_id' => ['in' => new Parameter($clientIds)]])
                    ->andWhere(['status_id' => ['in' => new Parameter([2, 3])]])
                    ->where('deleted_at', null)
                    ->fetchAll();
    }

    public function guestVisible(): EntityReader
    {
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * Invoices currently allocated to a HomeCare worker — the worker-scoped
     * counterpart to repoGuestClientsPostDraft(), keyed on worker_id instead
     * of client_id.
     *
     * Sorted by worker_allocated_at ascending — earliest-allocated first —
     * rather than the shared prepareDataReader()'s id-desc default. Staff
     * allocates invoices to a worker one at a time from inv/index while
     * walking a street-ordered list (family-street-order.ts); the order
     * those allocations happened in is this worker's definitive cleaning
     * order (see Inv::$worker_allocated_at's own docblock), and inv/guest
     * needs to actually reflect it. Pre-existing rows allocated before this
     * column existed have a null timestamp and sort first (MySQL's default
     * ASC null ordering) — a harmless one-time transition artifact, not a
     * correctness issue.
     *
     * @param int $status_id
     * @param int $worker_id
     * @return EntityReader
     */
    public function repoWorkerVisible(int $status_id, int $worker_id): EntityReader
    {
        if ($status_id > 0) {
            $query = $this->select()
                    ->where(['status_id' => $status_id])
                    ->where(['worker_id' => $worker_id])
                    ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                    ->where('deleted_at', null);
            return $this->workerVisibleDataReader($query);
        }
        $query = $this->select()
                     ->where(['worker_id' => $worker_id])
                     ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                     ->where('deleted_at', null);
        return $this->workerVisibleDataReader($query);
    }

    private function workerVisibleDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['worker_allocated_at'])
                ->withOrder(['worker_allocated_at' => 'asc']),
        );
    }
}
