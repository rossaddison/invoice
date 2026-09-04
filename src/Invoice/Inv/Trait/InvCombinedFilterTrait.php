<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\HomeCareRunContext;
use App\Invoice\Inv\InvIndexFilter;
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * Combines every inv/index filter into a single Cycle query.
 *
 * Each filterXxx() method in InvFilterTrait calls $this->select() to start a
 * completely fresh query and returns its own EntityReader, so when
 * Trait\Index::indexApplyFilters() chained them by reassigning $invs, only
 * the last filter applied ever actually took effect — earlier ones were
 * silently discarded. This trait builds one Select query and threads it
 * through small per-concern helpers instead, so multiple filters AND
 * together correctly.
 *
 * Kept separate from InvFilterTrait rather than rewriting it in place,
 * because Guest.php still calls several of that trait's individual methods
 * independently for inv/guest, which has the same underlying issue but is
 * out of scope here.
 */
trait InvCombinedFilterTrait
{
    public function filterCombined(
        InvIndexFilter $filter,
        HomeCareRunContext $run,
        int $effectiveStatus,
    ): EntityReader {
        $query = $this->select()->load(['client', 'group', 'user']);
        if ($effectiveStatus > 0) {
            $query = $query->andWhere(['status_id' => $effectiveStatus]);
        }
        $query = $this->applyIdentifierConditions($query, $filter);
        $query = $this->applyAmountConditions($query, $filter);
        $query = $this->applyClientConditions($query, $filter);
        $query = $this->applyFamilyAndRunConditions($query, $filter, $run);
        $query = $query->andWhere('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    private function applyIdentifierConditions(Select $query, InvIndexFilter $filter): Select
    {
        if (isset($filter->filterInvNumber) && !empty($filter->filterInvNumber)) {
            $query = $query->andWhere(['number' => trim($filter->filterInvNumber)]);
        }
        if (isset($filter->filterCreditInvNumber) && !empty($filter->filterCreditInvNumber)) {
            $trimmed = trim($filter->filterCreditInvNumber);
            $parentInvs = $this->select()
                ->where('number', 'like', $trimmed . '%')
                ->where('deleted_at', null)
                ->fetchAll();
            $parentIds = [];
            /** @var Inv $parentInv */
            foreach ($parentInvs as $parentInv) {
                $parentIds[] = (string) $parentInv->reqId();
            }
            $query = $query->andWhere(['creditinvoice_parent_id' =>
                $parentIds === [] ? '0' : ['in' => new Parameter($parentIds)]]);
        }
        return $query;
    }

    private function applyAmountConditions(Select $query, InvIndexFilter $filter): Select
    {
        $hasTotal = isset($filter->filterInvAmountTotal) && !empty($filter->filterInvAmountTotal);
        $hasPaid = isset($filter->filterInvAmountPaid) && !empty($filter->filterInvAmountPaid);
        $hasBalance = isset($filter->filterInvAmountBalance) && !empty($filter->filterInvAmountBalance);
        if (!$hasTotal && !$hasPaid && !$hasBalance) {
            return $query;
        }
        $query = $query->load('invAmount');
        if ($hasTotal) {
            $query = $query->andWhere(
                'invAmount.total',
                'like',
                (float) $filter->filterInvAmountTotal . '%'
            );
        }
        if ($hasPaid) {
            $query = $query->andWhere(
                'invAmount.paid',
                'like',
                (float) $filter->filterInvAmountPaid . '%'
            );
        }
        if ($hasBalance) {
            $query = $query->andWhere(
                'invAmount.balance',
                'like',
                (float) $filter->filterInvAmountBalance . '%'
            );
        }
        return $query;
    }

    private function applyClientConditions(Select $query, InvIndexFilter $filter): Select
    {
        if (isset($filter->filterClient) && !empty($filter->filterClient)) {
            $query = $query->andWhere(['client.id' => (int) $filter->filterClient]);
        }
        if (isset($filter->filterClientGroup) && !empty($filter->filterClientGroup)) {
            $query = $query->andWhere(['client.client_group' => trim($filter->filterClientGroup)]);
        }
        if (isset($filter->filterClientAddress1) && !empty($filter->filterClientAddress1)) {
            $query = $query->andWhere(
                'client.client_address_1',
                'like',
                trim($filter->filterClientAddress1) . '%'
            );
        }
        if (isset($filter->filterDateCreatedYearMonth) && !empty($filter->filterDateCreatedYearMonth)) {
            $dateTimeImmutable = \DateTimeImmutable::createFromFormat(
                'Y-m',
                $filter->filterDateCreatedYearMonth
            );
            $query = $query->andWhere(
                'date_created',
                'like',
                $dateTimeImmutable instanceof \DateTimeImmutable
                    ? $dateTimeImmutable->format('Y-m') . '%' : ''
            );
        }
        return $query;
    }

    private function applyFamilyAndRunConditions(
        Select $query,
        InvIndexFilter $filter,
        HomeCareRunContext $run,
    ): Select {
        $wantsFamilyName = isset($filter->filterFamilyName) && !empty($filter->filterFamilyName);
        $wantsRun = $run->categorySecondaryId > 0;
        if (!$wantsFamilyName && !$wantsRun) {
            return $query;
        }

        [$familyNameIds, $runIds] = $this->collectFamilyAndRunIds($filter, $run, $wantsFamilyName, $wantsRun);

        if ($wantsFamilyName) {
            $query = $this->applyIdInCondition($query, $familyNameIds);
        }
        if ($wantsRun) {
            $query = $this->applyIdInCondition($query, $runIds);
            $query = $this->applyRunDateCondition($query, $run);
        }
        return $query;
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    private function collectFamilyAndRunIds(
        InvIndexFilter $filter,
        HomeCareRunContext $run,
        bool $wantsFamilyName,
        bool $wantsRun,
    ): array {
        $familyNameIds = [];
        $runIds = [];
        $familyName = trim((string) $filter->filterFamilyName);
        /** @var Inv $inv */
        foreach ($this->findAllPreloaded() as $inv) {
            if ($wantsFamilyName && $inv->getFirstItemFamilyName() === $familyName) {
                $familyNameIds[] = (string) $inv->reqId();
            }
            if ($wantsRun && $inv->getFirstItemCategorySecondaryId() === $run->categorySecondaryId) {
                $runIds[] = (string) $inv->reqId();
            }
        }
        return [$familyNameIds, $runIds];
    }

    /**
     * @param list<string> $ids
     */
    private function applyIdInCondition(Select $query, array $ids): Select
    {
        return $query->andWhere(['id' => $ids === [] ? '0' : ['in' => new Parameter($ids)]]);
    }

    private function applyRunDateCondition(Select $query, HomeCareRunContext $run): Select
    {
        if ($run->applyDateFilter && $run->lastRunDate !== '') {
            return $query->andWhere('date_created', '>=', $run->lastRunDate);
        }
        return $query;
    }
}
