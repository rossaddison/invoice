<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Widget;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * Group-by helpers extracted from QuotesListWidget to stay within the S1448 limit.
 */
final class QuotesGroupingHelper
{
    /** @return \Closure(Quote): string */
    public static function makeGroupValueResolver(QR $qR, string $groupBy): \Closure
    {
        return static function (Quote $quote) use ($qR, $groupBy): string {
            return match ($groupBy) {
                'client'       => $quote->getClient()?->getClientFullName() ?? 'Unknown Client',
                'status'       => $qR->getSpecificStatusArrayLabel((string) $quote->reqStatusId()),
                'month'        => $quote->getDateCreated()->format('Y-m'),
                'year'         => $quote->getDateCreated()->format('Y'),
                'date'         => $quote->getDateCreated()->format('Y-m-d'),
                'client_group' => $quote->getClient()?->getClientGroup() ?? 'No Group',
                'amount_range' => match (true) {
                    ($quote->getQuoteAmount()?->getTotal() ?? 0) < 100  => '< $100',
                    ($quote->getQuoteAmount()?->getTotal() ?? 0) < 500  => '$100 - $500',
                    ($quote->getQuoteAmount()?->getTotal() ?? 0) < 1000 => '$500 - $1000',
                    default => '> $1000',
                },
                default => 'No Group',
            };
        };
    }

    /**
     * @param  callable(Quote): string $getGroupValue
     * @return array<string, array{count: int, total: float}>
     */
    public static function computeGroupTotals(
        OffsetPaginator $paginator,
        callable $getGroupValue,
    ): array {
        $groupTotals = [];
        foreach ($paginator->read() as $quote) {
            /** @var Quote $quote */
            $gv = $getGroupValue($quote);
            if (!isset($groupTotals[$gv])) {
                $groupTotals[$gv] = ['count' => 0, 'total' => 0.0];
            }
            $groupTotals[$gv]['count']++;
            $groupTotals[$gv]['total'] += $quote->getQuoteAmount()?->getTotal() ?? 0.0;
        }
        return $groupTotals;
    }

    /**
     * @param callable(Quote): string $getGroupValue
     * @param array<string, array{count: int, total: float}> $groupTotals
     */
    public static function applyGrouping(
        GridView $gridView,
        callable $getGroupValue,
        array $groupTotals,
        int $decimalPlaces,
        string $groupBy,
        int $columnCount,
        SR $sR,
    ): GridView {
        $previousGroupValue = '';
        return $gridView->beforeRow(
            static function (array|object $quote) use (
                &$previousGroupValue,
                $getGroupValue,
                $groupTotals,
                $decimalPlaces,
                $groupBy,
                $sR,
                $columnCount,
            ): ?\Yiisoft\Html\Tag\Tr {
                /** @var Quote $quote */
                $currentGroupValue = $getGroupValue($quote);
                if ($previousGroupValue === $currentGroupValue) {
                    return null;
                }
                $previousGroupValue = $currentGroupValue;
                $groupData      = $groupTotals[$currentGroupValue]
                    ?? ['count' => 0, 'total' => 0.0];
                $currencySymbol = $sR->getSetting('currency_symbol');
                return \Yiisoft\Html\Html::tr()
                    ->addClass(
                        'group-header bg-secondary text-white fw-bold group-collapsible')
                    ->addAttributes(['onclick' => 'toggleGroupRows(this)'])
                    ->cells(
                        \Yiisoft\Html\Html::td()
                            ->addAttributes(['colspan' => (string) $columnCount])
                            ->addClass('p-3')
                            ->content(
                                '<div class="d-flex justify-content-between align-items-center">'
                                . '<div>'
                                . '<i class="bi bi-chevron-down me-2 group-toggle-icon"></i>'
                                . '<i class="bi bi-folder2-open me-2"></i>'
                                . '<span class="fs-5">' . Html::encode(ucfirst($groupBy))
                                    . ': ' . Html::encode($currentGroupValue) . '</span>'
                                . '<span class="badge bg-primary ms-2">' . $groupData['count']
                                    . ' quote'
                                    . ($groupData['count'] === 1 ? '' : 's') . '</span>'
                                . '</div>'
                                . '<div class="text-end">'
                                . '<small class="d-block">Total: <strong>'
                                    . number_format($groupData['total'], $decimalPlaces)
                                    . ' ' . $currencySymbol . '</strong></small>'
                                . '</div>'
                                . '</div>'
                            )
                            ->encode(false)
                    );
            }
        );
    }
}
