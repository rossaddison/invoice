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

    /**
     * inv/guest/calendar's worker-scoped source query -- the
     * repoWorkerVisible() of the calendar feature: same worker_id scoping
     * and non-draft status exclusion, but over a date range instead of a
     * single status. items.product.family is eager-loaded because
     * Trait\Calendar::calendarBucketInvoices() (reused here by
     * Trait\GuestCalendar) walks exactly that relation chain per invoice
     * via Inv::getFirstItemCategorySecondaryId() -- same N+1 CodeRabbit
     * caught on the staff-side repoDateRangeQuery() in PR #1248.
     */
    public function repoWorkerDateRangeQuery(
        \DateTimeImmutable $from,
        \DateTimeImmutable $toExclusive,
        int $workerId,
    ): EntityReader {
        $query = $this->select()
                ->load(['client', 'group', 'user', 'items.product.family'])
                ->where(['worker_id' => $workerId])
                ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                ->andWhere('date_created', '>=', $from->format('Y-m-d H:i:s'))
                ->andWhere('date_created', '<', $toExclusive->format('Y-m-d H:i:s'))
                ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * inv/guest/calendar's client-scoped source query -- the
     * repoGuestClientsPostDraft() of the calendar feature. See
     * repoWorkerDateRangeQuery()'s own docblock for the eager-load reason.
     */
    public function repoGuestClientsDateRangeQuery(
        \DateTimeImmutable $from,
        \DateTimeImmutable $toExclusive,
        array $clientIds,
    ): EntityReader {
        $query = $this->select()
                ->load(['client', 'group', 'user', 'items.product.family'])
                ->where(['client_id' => ['in' => new Parameter($clientIds)]])
                ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                ->andWhere('date_created', '>=', $from->format('Y-m-d H:i:s'))
                ->andWhere('date_created', '<', $toExclusive->format('Y-m-d H:i:s'))
                ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * inv/guest/calendar's day-block badges narrow down to this exact
     * date, scoped to the signed-in guest's own worker-/client-visible
     * invoices in one query -- deliberately not the shape
     * filterGuestClient()/applyGuestFilters()'s other filter methods use
     * (each rebuilds an unscoped $this->select() from scratch, relying on
     * the caller never doing that with an untrusted id), since narrowing
     * by a worker- or client-owned date must not risk exposing another
     * guest's invoices.
     *
     * Rollover-date rejection is shared with the staff-side
     * InvCombinedFilterTrait::applyExactDateCondition() via
     * exactDateLikePrefix() (PR #1252) rather than duplicated here.
     */
    public function filterGuestDateCreatedExact(
        string $filterDateCreatedExact,
        ?int $workerId,
        array $clientIds,
    ): EntityReader {
        $query = $this->select()->load(['client', 'group', 'user']);
        $query = $workerId !== null
            ? $query->where(['worker_id' => $workerId])
            : $query->where(['client_id' => ['in' => new Parameter($clientIds)]]);
        $query = $query->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
            ->where('deleted_at', null);

        // Shared with InvCombinedFilterTrait::applyExactDateCondition() --
        // see exactDateLikePrefix()'s own docblock for the rollover-date
        // rejection this used to duplicate.
        $query = $query->andWhere(
            'date_created',
            'like',
            $this->exactDateLikePrefix($filterDateCreatedExact),
        );
        return $this->prepareDataReader($query);
    }
}
