<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Invoice\Quote\QuoteIndexFilter;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * Combines every quote/index filter into a single Cycle query — the same
 * fix already applied to Inv (see project_inv_index_filter_combining_fix
 * memory). Before this, Trait\Index::indexApplyFilters() reassigned $quotes
 * to whatever QuoteFilterTrait's filterXxx() methods returned, and each of
 * those calls $this->select() to start a completely fresh query — so
 * picking a second filter silently discarded the first, and *both* silently
 * discarded the status filter quotesStatusWithSort() had already applied.
 * The one existing filterQuoteNumberAndQuoteAmountTotal() special-case was
 * the same tell it was for Inv: someone had already half-noticed.
 *
 * Kept separate from QuoteFilterTrait rather than rewriting it in place,
 * because Guest.php still calls several of that trait's individual methods
 * independently for quote/guest, which has the same underlying issue but is
 * out of scope here (identical reasoning to InvCombinedFilterTrait).
 */
trait QuoteCombinedFilterTrait
{
    /**
     * @param array<array-key, mixed> $queryParams
     */
    public function filterCombined(
        QuoteIndexFilter $filter,
        array $queryParams,
        int $effectiveStatus,
    ): EntityReader {
        $query = $this->select()->load(['client', 'group', 'user']);
        if ($effectiveStatus > 0) {
            $query = $query->andWhere(['status_id' => $effectiveStatus]);
        }
        $query = $this->applyQuoteNumberCondition($query, $queryParams);
        $query = $this->applyQuoteAmountTotalCondition($query, $queryParams);
        $query = $this->applyClientCondition($query, $filter);
        return $this->prepareDataReader($query);
    }

    /**
     * @param array<array-key, mixed> $queryParams
     */
    private function applyQuoteNumberCondition(Select $query, array $queryParams): Select
    {
        $quoteNumber = isset($queryParams['filterQuoteNumber']) ? trim((string) $queryParams['filterQuoteNumber']) : '';
        if ($quoteNumber === '') {
            return $query;
        }
        return $query->andWhere(['number' => $quoteNumber]);
    }

    /**
     * @param array<array-key, mixed> $queryParams
     */
    private function applyQuoteAmountTotalCondition(Select $query, array $queryParams): Select
    {
        $quoteAmountTotal = isset($queryParams['filterQuoteAmountTotal'])
            ? trim((string) $queryParams['filterQuoteAmountTotal']) : '';
        if ($quoteAmountTotal === '') {
            return $query;
        }
        return $query->load('quoteAmount')->andWhere(['quoteAmount.total' => $quoteAmountTotal]);
    }

    private function applyClientCondition(Select $query, QuoteIndexFilter $filter): Select
    {
        if (!isset($filter->filterClient) || empty($filter->filterClient)) {
            return $query;
        }
        $nameParts = explode(' ', $filter->filterClient);
        $firstName = $nameParts[0];
        $secondName = $nameParts[1] ?? '';
        return $query->load(['client'])
            ->andWhere(['client.client_name' => $firstName])
            ->andWhere(['client.client_surname' => $secondName]);
    }
}
