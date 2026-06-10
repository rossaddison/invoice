<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder\Widget;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\GridView;

final class SalesOrdersGroupingRenderer
{
    public function __construct(
        private readonly SoR $soR,
        private readonly SR $sR,
    ) {
    }

    /** @return \Closure(SalesOrder): string */
    public function makeGroupValueResolver(string $groupBy): \Closure
    {
        $soR = $this->soR;
        return static function (SalesOrder $so) use ($soR, $groupBy): string {
            return match ($groupBy) {
                'client' => $so->getClient()?->getClientFullName() ?? 'Unknown Client',
                'status' => $soR->getSpecificStatusArrayLabel(
                    (string) $so->getStatusId()),
                'month'  => $so->getDateCreated()->format('Y-m'),
                'year'   => $so->getDateCreated()->format('Y'),
                'date'   => $so->getDateCreated()->format('Y-m-d'),
                default  => 'No Group',
            };
        };
    }

    /**
     * @param callable(SalesOrder): string $getGroupValue
     * @return array<string, array{count: int, total: float}>
     */
    public function computeGroupTotals(
        OffsetPaginator $paginator,
        callable $getGroupValue,
    ): array {
        $groupTotals = [];
        foreach ($paginator->read() as $so) {
            /** @var SalesOrder $so */
            $gv = $getGroupValue($so);
            if (!isset($groupTotals[$gv])) {
                $groupTotals[$gv] = ['count' => 0, 'total' => 0.0];
            }
            $groupTotals[$gv]['count']++;
            $soAmount = $so->getSalesOrderAmount();
            $groupTotals[$gv]['total'] += $soAmount->getTotal() ?? 0.0;
        }
        return $groupTotals;
    }

    /**
     * @param callable(SalesOrder): string $getGroupValue
     * @param array<string, array{count: int, total: float}> $groupTotals
     */
    public function applyGrouping(
        GridView $gridView,
        callable $getGroupValue,
        array $groupTotals,
        string $groupBy,
    ): GridView {
        $sR                 = $this->sR;
        $previousGroupValue = '';
        return $gridView->beforeRow(
            static function (array|object $so) use (
                &$previousGroupValue,
                $getGroupValue,
                $groupTotals,
                $groupBy,
                $sR,
            ): ?\Yiisoft\Html\Tag\Tr {
                \assert($so instanceof SalesOrder);
                $current = $getGroupValue($so);
                if ($previousGroupValue === $current) {
                    return null;
                }
                $previousGroupValue = $current;
                $gd  = $groupTotals[$current] ?? ['count' => 0, 'total' => 0.0];
                $cur = $sR->getSetting('currency_symbol');
                return \Yiisoft\Html\Html::tr()
                    ->addClass(
                        'group-header bg-secondary text-white fw-bold group-collapsible')
                    ->addAttributes(['onclick' => 'toggleGroupRows(this)'])
                    ->cells(
                        \Yiisoft\Html\Html::td()
                            ->addAttributes(['colspan' => '10'])
                            ->addClass('p-3')
                            ->content(
                                '<div class="d-flex justify-content-between align-items-center">'
                                . '<div>'
                                . '<i class="bi bi-chevron-down me-2 group-toggle-icon"></i>'
                                . '<i class="bi bi-folder2-open me-2"></i>'
                                . '<span class="fs-5">'
                                . Html::encode(ucfirst($groupBy)) . ': '
                                . Html::encode($current) . '</span>'
                                . '<span class="badge bg-primary ms-2">'
                                . $gd['count'] . ' order'
                                . ($gd['count'] === 1 ? '' : 's') . '</span>'
                                . '</div>'
                                . '<div class="text-end">'
                                . '<small class="d-block">Total: <strong>'
                                . number_format($gd['total'], 2)
                                . ' ' . Html::encode($cur) . '</strong></small>'
                                . '</div>'
                                . '</div>'
                            )
                            ->encode(false)
                    );
            }
        );
    }

    public function groupingScriptAndStyle(): string
    {
        $js = <<<'JS'
function toggleGroupRows(headerRow) {
    const icon = headerRow.querySelector('.group-toggle-icon');
    let nextRow = headerRow.nextElementSibling;
    let isCollapsed = icon.classList.contains('bi-chevron-right');
    if (isCollapsed) {
        icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
    } else {
        icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
    }
    while (nextRow && !nextRow.classList.contains('group-header')) {
        nextRow.style.display = isCollapsed ? '' : 'none';
        nextRow = nextRow.nextElementSibling;
    }
}
function toggleAllGroups(expand) {
    document.querySelectorAll('.group-header').forEach(header => {
        const icon = header.querySelector('.group-toggle-icon');
        let nextRow = header.nextElementSibling;
        if (expand) {
            icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
        } else {
            icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
        }
        while (nextRow && !nextRow.classList.contains('group-header')) {
            nextRow.style.display = expand ? '' : 'none';
            nextRow = nextRow.nextElementSibling;
        }
    });
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.group-header').forEach(h => h.style.cursor = 'pointer');
});
JS;

        $css = <<<'CSS'
.group-collapsible:hover { background-color: #495057 !important; cursor: pointer; }
.group-toggle-icon { transition: transform 0.2s ease; }
CSS;

        return Html::script($js)->type('module')->render()
            . Html::style($css)->render();
    }
}
