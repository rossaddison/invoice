<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\InvGuestFilter;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Reader\Sort;

trait InvGuestTrait
{
    /**
     * Statuses a HomeCare worker sees on inv/guest: everything a customer
     * guest sees (2-13) plus drafts (1). A draft stays draft until the job is
     * completed, so the worker must see their allocated drafts to complete
     * them. Customer-scoped queries keep excluding drafts.
     */
    private const array WORKER_VISIBLE_STATUSES = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];

    private const array GUEST_VISIBLE_STATUSES = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];

    /**
     * Restricts a query to the invoices allocated to a worker: the legacy
     * single Inv.worker_id, OR any InvWorkerAllocation row of theirs (a split
     * front/back house has no single worker_id). One query, no extra round
     * trip; verified against MySQL for each combination.
     *
     * The lead (the one worker who allocates the run) also sees every house
     * of the run -- any invoice whose client has a Dwelling -- because he
     * cannot allocate a house he cannot see.
     *
     * @param Select<Inv> $query
     * @return Select<Inv>
     */
    private function whereAllocatedToWorker(Select $query, int $workerId, bool $lead = false): Select
    {
        $scope = [
            ['worker_id' => $workerId],
            ['id' => ['in' => new Fragment(
                '(SELECT inv_id FROM inv_worker_allocation WHERE worker_id = ?)',
                $workerId
            )]],
        ];
        if ($lead) {
            $scope[] = ['client.dwelling_id' => ['!=' => null]];
        }
        return $query->where(['@or' => $scope]);
    }

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
    public function repoGuestClientsPostDraft(
        int $status_id,
        array $user_client = [],
        ?InvGuestFilter $filter = null,
    ): EntityReader {
        if ($status_id > 0) {
            $query = $this->select()
                    ->where(['status_id' => $status_id])
                    ->where(['client_id' => ['in' => new Parameter($user_client)]])
                    ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                    ->where('deleted_at', null);
        } else {
            $query = $this->select()
                         ->where(['client_id' => ['in' => new Parameter($user_client)]])
                         ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                         ->where('deleted_at', null);
        }
        return $this->prepareDataReader($this->narrowGuestQuery($query, $filter));
    }

    /**
     * Adds inv/guest's list filters to a query that is ALREADY limited to what this guest may see (their clients'
     * invoices, or the invoices allocated to their worker).
     *
     * The filters used to be separate queries (filterInvNumber(), filterGuestClient(), ...), each started from an
     * unscoped $this->select() and then used INSTEAD of the guest's list, so adding ?filterInvNumber=... or
     * ?filterClient=<any client id> to the address listed any client's invoices. A filter can only narrow here:
     * every condition is added with AND to the scoped query, so it can never widen it.
     *
     * Same "empty means no filter" rule as before (so '0' is not a filter), and the amount filters keep matching
     * from the start of the number.
     *
     * @param Select<Inv> $query
     * @return Select<Inv>
     */
    private function narrowGuestQuery(Select $query, ?InvGuestFilter $filter): Select
    {
        if (null === $filter) {
            return $query;
        }
        $number = (string) $filter->filterInvNumber;
        $total = (string) $filter->filterInvAmountTotal;
        if (self::guestFilterGiven($number)) {
            $query = $query->andWhere(['number' => trim($number)]);
        }
        if (self::guestFilterGiven($filter->filterCreditInvNumber)) {
            $parentIds = $this->creditParentIds((string) $filter->filterCreditInvNumber);
            $query = $parentIds === []
                ? $query->andWhere(['id' => '0'])
                : $query->andWhere(['creditinvoice_parent_id' => ['in' => new Parameter($parentIds)]]);
        }
        if (self::guestFilterGiven($total)) {
            // with an invoice number as well, the total has always had to match exactly rather than from the start
            $query = self::guestFilterGiven($number)
                ? $query->load('invAmount')->andWhere(['invAmount.total' => (float) $total])
                : $query->load('invAmount')->andWhere('invAmount.total', 'like', (float) $total . '%');
        }
        if (self::guestFilterGiven($filter->filterInvAmountPaid)) {
            $query = $query->load('invAmount')
                ->andWhere('invAmount.paid', 'like', (float) $filter->filterInvAmountPaid . '%');
        }
        if (self::guestFilterGiven($filter->filterInvAmountBalance)) {
            $query = $query->load('invAmount')
                ->andWhere('invAmount.balance', 'like', (float) $filter->filterInvAmountBalance . '%');
        }
        if (self::guestFilterGiven($filter->filterClient)) {
            // one of this guest's own clients, or nothing: the scope is already in the query
            $query = $query->andWhere(['client_id' => (int) $filter->filterClient]);
        }
        return $query;
    }

    /** What empty() has always meant for these filters: not given when null, '' or '0'. */
    private static function guestFilterGiven(?string $value): bool
    {
        return null !== $value && '' !== $value && '0' !== $value;
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
     * counterpart to repoGuestClientsPostDraft(), keyed on the worker's
     * allocation (legacy worker_id or an InvWorkerAllocation row) instead of
     * client_id, and including drafts (see WORKER_VISIBLE_STATUSES).
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
     * @param bool $lead true for the one lead worker, who sees every HomeCare house
     * @return EntityReader
     */
    public function repoWorkerVisible(
        int $status_id,
        int $worker_id,
        bool $lead = false,
        ?InvGuestFilter $filter = null,
    ): EntityReader {
        if ($status_id > 0) {
            $query = $this->whereAllocatedToWorker($this->select(), $worker_id, $lead)
                    ->andWhere(['status_id' => $status_id])
                    ->andWhere(['status_id' => ['in' => new Parameter(self::WORKER_VISIBLE_STATUSES)]])
                    ->where('deleted_at', null);
        } else {
            $query = $this->whereAllocatedToWorker($this->select(), $worker_id, $lead)
                         ->andWhere(['status_id' => ['in' => new Parameter(self::WORKER_VISIBLE_STATUSES)]])
                         ->where('deleted_at', null);
        }
        return $this->workerVisibleDataReader($this->narrowGuestQuery($query, $filter));
    }

    /**
     * Every live invoice that still carries a legacy single `worker_id` --
     * the source rows for the one-off carry-over into InvWorkerAllocation
     * (see InvWorkerAllocationService::backfillLegacy()).
     *
     * @return list<\App\Infrastructure\Persistence\Inv\Inv>
     */
    public function repoWithWorkerAllocated(): array
    {
        return $this->select()
            ->where(['worker_id' => ['!=' => null]])
            ->where('deleted_at', null)
            ->fetchAll();
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
     * repoWorkerVisible() of the calendar feature: same allocation scoping
     * and status set (drafts included), but over a date range instead of a
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
        bool $lead = false,
    ): EntityReader {
        $query = $this->whereAllocatedToWorker(
            $this->select()->load(['client', 'group', 'user', 'items.product.family']),
            $workerId,
            $lead
        )
                ->andWhere(['status_id' => ['in' => new Parameter(self::WORKER_VISIBLE_STATUSES)]])
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
        bool $lead = false,
    ): EntityReader {
        $query = $this->select()->load(['client', 'group', 'user']);
        $query = $workerId !== null
            ? $this->whereAllocatedToWorker($query, $workerId, $lead)
            : $query->where(['client_id' => ['in' => new Parameter($clientIds)]]);
        $statuses = $workerId !== null ? self::WORKER_VISIBLE_STATUSES : self::GUEST_VISIBLE_STATUSES;
        $query = $query->andWhere(['status_id' => ['in' => new Parameter($statuses)]])
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
