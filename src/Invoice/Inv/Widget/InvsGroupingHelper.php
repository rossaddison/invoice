<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * Group-by helpers extracted from InvsListWidget to stay within the S1448 limit.
 */
final class InvsGroupingHelper
{
    /** @return \Closure(Inv): string */
    public static function makeGroupValueResolver(string $groupBy, IR $iR): \Closure
    {
        return static function (Inv $invoice) use ($iR, $groupBy): string {
            return match ($groupBy) {
                'client'       => $invoice->getClient()?->getClientFullName()
                    ?? 'Unknown Client',
                'status'       => $iR->getSpecificStatusArrayLabel(
                    (string) $invoice->reqStatusId()),
                'month'        => $invoice->getDateCreated()->format('Y-m'),
                'year'         => $invoice->getDateCreated()->format('Y'),
                'date'         => $invoice->getDateCreated()->format('Y-m-d'),
                'client_group' => $invoice->getClient()?->getClientGroup() ?? 'No Group',
                'amount_range' => match (true) {
                    ($invoice->getInvAmount()->getTotal() ?? 0) < 100  => '< $100',
                    ($invoice->getInvAmount()->getTotal() ?? 0) < 500  => '$100 - $500',
                    ($invoice->getInvAmount()->getTotal() ?? 0) < 1000 => '$500 - $1000',
                    default => '> $1000',
                },
                'peppol_workflow' => match (true) {
                    $invoice->getSoId() !== null    => 'Peppol (Quote → SO → Invoice)',
                    $invoice->getQuoteId() !== null => 'Quote → Invoice',
                    default                         => 'Standard Invoice',
                },
                default => 'No Group',
            };
        };
    }

    /**
     * @param  callable(Inv): string $getGroupValue
     * @return array<string, array{count: int, total: float, paid: float, balance: float}>
     */
    public static function computeGroupTotals(
        OffsetPaginator $paginator,
        callable $getGroupValue,
    ): array {
        $groupTotals = [];
        foreach ($paginator->read() as $invoice) {
            /** @var Inv $invoice */
            $gv = $getGroupValue($invoice);
            if (!isset($groupTotals[$gv])) {
                $groupTotals[$gv] = ['count' => 0, 'total' => 0.0,
                    'paid' => 0.0, 'balance' => 0.0];
            }
            $groupTotals[$gv]['count']++;
            $groupTotals[$gv]['total']   += $invoice->getInvAmount()->getTotal()   ?? 0.0;
            $groupTotals[$gv]['paid']    += $invoice->getInvAmount()->getPaid()    ?? 0.0;
            $groupTotals[$gv]['balance'] += $invoice->getInvAmount()->getBalance() ?? 0.0;
        }
        return $groupTotals;
    }

    /**
     * @param callable(Inv): string $getGroupValue
     * @param array<string, array{count: int, total: float, paid: float, balance: float}> $groupTotals
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
            static function (array|object $invoice) use (
                &$previousGroupValue,
                $getGroupValue,
                $groupTotals,
                $decimalPlaces,
                $groupBy,
                $sR,
                $columnCount,
            ): ?\Yiisoft\Html\Tag\Tr {
                /** @var Inv $invoice */
                $currentGroupValue = $getGroupValue($invoice);
                if ($previousGroupValue === $currentGroupValue) {
                    return null;
                }
                $previousGroupValue = $currentGroupValue;
                $gd  = $groupTotals[$currentGroupValue]
                    ?? ['count' => 0, 'total' => 0.0, 'paid' => 0.0, 'balance' => 0.0];
                $cur = $sR->getSetting('currency_symbol');
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
                                . '<span class="fs-5">'
                                . Html::encode(ucfirst($groupBy)) . ': '
                                . Html::encode($currentGroupValue) . '</span>'
                                . '<span class="badge bg-primary ms-2">'
                                . $gd['count'] . ' invoice'
                                . ($gd['count'] === 1 ? '' : 's') . '</span>'
                                . '</div>'
                                . '<div class="text-end">'
                                . '<small class="d-block">Total: <strong>'
                                . number_format($gd['total'], $decimalPlaces)
                                . ' ' . $cur . '</strong></small>'
                                . '<small class="d-block">Paid: <strong>'
                                . number_format($gd['paid'], $decimalPlaces)
                                . ' ' . $cur . '</strong></small>'
                                . '<small class="d-block">Balance: <strong>'
                                . number_format($gd['balance'], $decimalPlaces)
                                . ' ' . $cur . '</strong></small>'
                                . '</div>'
                                . '</div>'
                            )
                            ->encode(false)
                    );
            }
        );
    }
}
