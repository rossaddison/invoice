<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder\Widget;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\Inv\InvRepository as InvRepo;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button as HtmlButton;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Input\Checkbox;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Select;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Widget\Widget;
use Yiisoft\Yii\DataView\Filter\Widget\DropdownFilter;
use Yiisoft\Yii\DataView\GridView\Column\Base\DataContext;
use Yiisoft\Yii\DataView\GridView\Column\CheckboxColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;
use Yiisoft\Yii\DataView\Pagination\PaginationWidgetInterface;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

final class SalesOrdersListWidget extends Widget
{
    private const string DOM_ID = 'SalesOrdersGridView';
    private const string ROUTE_INDEX = 'salesorder/index';

    private ?OffsetPaginator $paginator = null;
    private ?SoR $soR = null;
    private ?SoAR $soaR = null;
    private ?InvRepo $iR = null;
    private ?SR $sR = null;
    private string|\Stringable $csrf = '';
    private bool $visible = false;
    private string $groupBy = 'none';
    private string $gridSummary = '';
    private string $sortString = '-id';
    private int $status = 0;
    private string $salesOrderToolbar = '';
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    private array $optionsDataClientsDropdownFilter = [];

    public function __construct(
        private readonly CurrentRoute $currentRoute,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    // -------------------------------------------------------------------------
    // Immutable setters
    // -------------------------------------------------------------------------

    public function withPaginator(OffsetPaginator $paginator): static
    {
        $new = clone $this;
        $new->paginator = $paginator;
        return $new;
    }

    public function withSoR(SoR $soR): static
    {
        $new = clone $this;
        $new->soR = $soR;
        return $new;
    }

    public function withSoAR(SoAR $soaR): static
    {
        $new = clone $this;
        $new->soaR = $soaR;
        return $new;
    }

    public function withIR(InvRepo $iR): static
    {
        $new = clone $this;
        $new->iR = $iR;
        return $new;
    }

    public function withSR(SR $sR): static
    {
        $new = clone $this;
        $new->sR = $sR;
        return $new;
    }

    public function withCsrf(string|\Stringable $csrf): static
    {
        $new = clone $this;
        $new->csrf = $csrf;
        return $new;
    }

    public function withVisible(bool $visible): static
    {
        $new = clone $this;
        $new->visible = $visible;
        return $new;
    }

    public function withGroupBy(string $groupBy): static
    {
        $new = clone $this;
        $new->groupBy = $groupBy;
        return $new;
    }

    public function withGridSummary(string $gridSummary): static
    {
        $new = clone $this;
        $new->gridSummary = $gridSummary;
        return $new;
    }

    public function withSortString(string $sortString): static
    {
        $new = clone $this;
        $new->sortString = $sortString;
        return $new;
    }

    public function withStatus(int $status): static
    {
        $new = clone $this;
        $new->status = $status;
        return $new;
    }

    public function withSalesOrderToolbar(string $salesOrderToolbar): static
    {
        $new = clone $this;
        $new->salesOrderToolbar = $salesOrderToolbar;
        return $new;
    }

    /** @psalm-param array<array-key, array<array-key, string>|string> $options */
    public function withOptionsDataClientsDropdownFilter(array $options): static
    {
        $new = clone $this;
        $new->optionsDataClientsDropdownFilter = $options;
        return $new;
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    #[\Override]
    public function render(): string
    {
        if ($this->paginator === null || $this->soR === null
            || $this->soaR === null || $this->sR === null) {
            return '';
        }

        $groupBy        = $this->groupBy;
        $enableGrouping = $groupBy !== 'none';

        $htmxAttrs = [
            'hx-indicator'   => '#' . self::DOM_ID,
            'hx-target'      => '#' . self::DOM_ID,
            'hx-select'      => '#' . self::DOM_ID,
            'hx-replace-url' => 'true',
            'hx-swap'        => 'outerHTML',
        ];

        /** @var PaginationWidgetInterface<\Yiisoft\Data\Paginator\PaginatorInterface> */
        $pagination = OffsetPagination::widget()->addLinkAttributes([
            'hx-boost' => 'true',
            ...$htmxAttrs,
        ]);

        $urlCreator = new UrlCreator($this->urlGenerator);
        $urlCreator->__invoke([], OrderHelper::stringToArray($this->sortString));

        $getGroupValue = $this->makeGroupValueResolver($groupBy);
        $groupTotals   = $enableGrouping
            ? $this->computeGroupTotals($this->paginator, $getGroupValue)
            : [];

        $columns = $this->buildColumns();

        $gridView = GridView::widget()
            ->containerAttributes(['id' => self::DOM_ID, 'class' => 'position-relative'])
            ->bodyRowAttributes(['class' => 'align-middle'])
            ->tableAttributes(['class' => 'table table-striped text-center h-75',
                'id' => 'table-salesorder'])
            ->columns(...$columns)
            ->columnGrouping(true)
            ->dataReader($this->paginator)
            ->urlCreator($urlCreator)
            ->paginationWidget($pagination)
            ->sortableLinkAttributes(['hx-boost' => 'true', ...$htmxAttrs])
            ->filterFormAttributes(['hx-boost' => 'true', ...$htmxAttrs])
            ->sortableHeaderPrepend(
                '<div class="float-end text-secondary text-opacity-50">⭥</div>')
            ->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
            ->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
            ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
            ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
            ->summaryTemplate($this->gridSummary)
            ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
            ->noResultsText($this->translator->translate('no.records'))
            ->toolbar($this->buildToolbarString($enableGrouping));

        if ($enableGrouping) {
            $gridView = $this->applyGrouping($gridView, $getGroupValue, $groupTotals,
                $groupBy);
        }

        $output = $gridView->render();

        if ($enableGrouping) {
            $output .= $this->groupingScriptAndStyle();
        }

        return $output;
    }

    // -------------------------------------------------------------------------
    // Toolbar
    // -------------------------------------------------------------------------

    private function buildToolbarString(bool $enableGrouping): string
    {
        $ug = $this->urlGenerator;
        $t  = $this->translator;

        $toolbarReset = (new A())
            ->addAttributes(['type' => 'reset'])
            ->addClass('btn btn-primary me-1 ajax-loader')
            ->content(new I()->addClass('bi bi-bootstrap-reboot'))
            ->href($ug->generate($this->currentRoute->getName() ?? self::ROUTE_INDEX))
            ->id('btn-reset')
            ->render();

        $allVisible = (new A())
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                'title' => $t->translate('hide.or.unhide.columns')])
            ->addClass('btn btn-warning me-1 ajax-loader')
            ->content('↔️')
            ->href($ug->generate('setting/visible', ['origin' => 'salesorder']))
            ->id('btn-all-visible')
            ->render();

        $groupBySelect = (new Div())
            ->addClass('d-flex align-items-center gap-1')
            ->addAttributes(['role' => 'group'])
            ->content(
                (new Label())
                    ->addClass(
                        'btn btn-sm btn-outline-secondary active bi bi-collection me-1')
                    ->content(' ' . $t->translate('group.by') . ':')
                . (new Select())
                    ->addClass('form-select form-select-sm')
                    ->addAttributes([
                        'style'    => 'max-width: 150px;',
                        'onchange' => 'globalThis.location.href=\''
                            . $ug->generate(self::ROUTE_INDEX)
                            . '?groupBy=\' + this.value',
                    ])
                    ->optionsData([
                        'none'   => $t->translate('grouping.none'),
                        'status' => $t->translate('status'),
                        'client' => $t->translate('client'),
                        'date'   => $t->translate('date'),
                        'month'  => $t->translate('month'),
                        'year'   => $t->translate('year'),
                    ])
                    ->value($this->groupBy)
            )
            ->encode(false)
            ->render();

        $collapseExpand = $enableGrouping
            ? (new Div())
                ->addClass('btn-group ms-2')
                ->addAttributes(['role' => 'group'])
                ->content(
                    (new HtmlButton())
                        ->type('button')
                        ->addClass('btn btn-outline-secondary btn-sm')
                        ->addAttributes([
                            'onclick' => 'toggleAllGroups(false)',
                            'title'   => 'Collapse All Groups',
                        ])
                        ->content(new I()->addClass('bi bi-chevron-up'))
                    . (new HtmlButton())
                        ->type('button')
                        ->addClass('btn btn-outline-secondary btn-sm')
                        ->addAttributes([
                            'onclick' => 'toggleAllGroups(true)',
                            'title'   => 'Expand All Groups',
                        ])
                        ->content(new I()->addClass('bi bi-chevron-down'))
                )
                ->encode(false)
                ->render()
            : '';

        return (new Form())
                ->post($ug->generate(self::ROUTE_INDEX))
                ->csrf($this->csrf)
                ->open()
            . (new Div())
                ->addClass('d-flex align-items-center flex-wrap gap-2')
                ->content(
                    Html::openTag('div', ['class' => 'btn-group me-2', 'role' => 'group'])
                    . $allVisible
                    . $toolbarReset
                    . Html::closeTag('div')
                    . $this->buildStatusBar()
                    . $groupBySelect
                    . $collapseExpand
                )
                ->encode(false)
                ->render()
            . $this->salesOrderToolbar
            . (new Form())->close();
    }

    private function buildStatusBar(): string
    {
        $ug      = $this->urlGenerator;
        $t       = $this->translator;
        $soR     = $this->soR;
        $status  = $this->status;

        \assert($soR !== null);

        $buttons = Html::a(
            $t->translate('all'),
            $ug->generate(self::ROUTE_INDEX, ['page' => 1, 'status' => 0]),
            ['class' => 'btn ' . ($status === 0 ? 'btn-primary' : 'btn-secondary')],
        );

        for ($i = 1; $i <= 10; $i++) {
            $label = $soR->getSpecificStatusArrayLabel((string) $i);
            $class = $soR->getSpecificStatusArrayClass($i);
            $buttons .= Html::a(
                $label,
                $ug->generate(self::ROUTE_INDEX, ['page' => 1, 'status' => $i]),
                ['class' => 'btn ' . ($status === $i
                    ? 'btn-primary'
                    : 'label ' . $class)],
            );
        }

        return (new Div())
            ->addClass('btn-group index-options')
            ->content($buttons)
            ->encode(false)
            ->render();
    }

    // -------------------------------------------------------------------------
    // Columns
    // -------------------------------------------------------------------------

    /** @return list<DataColumn|CheckboxColumn> */
    private function buildColumns(): array
    {
        \assert($this->soR !== null && $this->soaR !== null && $this->sR !== null);

        $soR  = $this->soR;
        $soaR = $this->soaR;
        $sR   = $this->sR;
        $iR   = $this->iR;
        $ug   = $this->urlGenerator;
        $t    = $this->translator;
        $vis  = $this->visible;
        $opts = $this->optionsDataClientsDropdownFilter;

        return [
            $this->buildCheckboxColumn(),
            new DataColumn(
                'id',
                header: $t->translate('id'),
                content: static fn(SalesOrder $m): string => (string) $m->reqId(),
                visible: $vis,
            ),
            new DataColumn(
                header: $t->translate('view'),
                content: static function (SalesOrder $m) use ($ug): A {
                    return Html::a(
                        Html::tag('i', '', ['class' => 'bi-eye']),
                        $ug->generate('salesorder/view', ['id' => $m->reqId()]),
                        ['hx-boost' => 'false'],
                    );
                },
            ),
            $this->buildStatusColumn($soR, $ug),
            $this->buildNumberColumn($ug),
            $this->buildQuoteColumn($ug, $t, $vis),
            $this->buildInvoiceColumn($ug, $t, $iR, $vis),
            $this->buildDateCreatedColumn($t, $vis),
            $this->buildClientColumn($t, $opts),
            $this->buildTotalColumn($sR, $soaR, $t, $vis),
        ];
    }

    private function buildCheckboxColumn(): CheckboxColumn
    {
        return new CheckboxColumn(
            content: static function (Checkbox $input, DataContext $context): string {
                $so = $context->data;
                if (!$so instanceof SalesOrder) {
                    return '';
                }
                return $input
                    ->addAttributes([
                        'id'             => (string) $so->reqId(),
                        'name'           => 'checkbox[]',
                        'data-bs-toggle' => 'tooltip',
                        'title'          => '',
                    ])
                    ->value((string) $so->reqId())
                    ->render();
            },
            multiple: true,
        );
    }

    private function buildStatusColumn(SoR $soR, UrlGeneratorInterface $ug): DataColumn
    {
        return new DataColumn(
            'status_id',
            content: static function (SalesOrder $m) use ($soR, $ug): string {
                $statusId = $m->getStatusId();
                if (null === $statusId) {
                    return Html::tag('span')->render();
                }
                $label   = $soR->getSpecificStatusArrayLabel((string) $statusId);
                $class   = $soR->getSpecificStatusArrayClass($statusId);
                $spanTag = Html::tag('span', $label, ['class' => 'badge text-bg-' . $class])->render();
                if (7 === $statusId) {
                    return Html::tag('a', $spanTag, [
                        'href'     => $ug->generate('salesorder/soToInvoice',
                            ['id' => $m->reqId()]),
                        'class'    => 'text-decoration-none',
                        'hx-boost' => 'false',
                    ])->render();
                }
                return $spanTag;
            },
            encodeContent: false,
        );
    }

    private function buildNumberColumn(UrlGeneratorInterface $ug): DataColumn
    {
        return new DataColumn(
            'number',
            content: static fn(SalesOrder $m): A =>
                Html::a(
                    $m->getNumber() ?? '#',
                    $ug->generate('salesorder/view', ['id' => $m->reqId()]),
                    ['class' => 'text-decoration-none', 'hx-boost' => 'false'],
                ),
        );
    }

    private function buildQuoteColumn(
        UrlGeneratorInterface $ug,
        TranslatorInterface $t,
        bool $visible,
    ): DataColumn {
        return new DataColumn(
            'quote_id',
            header: $t->translate('quote'),
            content: static function (SalesOrder $m) use ($ug): string|A {
                $quote = $m->getQuote();
                return $quote
                    ? Html::a(
                        $quote->getNumber() ?? '#',
                        $ug->generate('quote/view', ['id' => $quote->reqId()]),
                        ['class' => 'text-decoration-none', 'hx-boost' => 'false'],
                    )
                    : '';
            },
            visible: $visible,
        );
    }

    private function buildInvoiceColumn(
        UrlGeneratorInterface $ug,
        TranslatorInterface $t,
        ?InvRepo $iR,
        bool $visible,
    ): DataColumn {
        return new DataColumn(
            'inv_id',
            header: $t->translate('invoice'),
            content: static function (SalesOrder $m) use ($ug, $iR): string|A {
                if (!$m->hasLinkedInvoice() || $iR === null) {
                    return '';
                }
                $invId = $m->reqInvId();
                $inv   = $iR->repoInvUnloadedquery($invId);
                return $inv
                    ? Html::a(
                        $inv->getNumber() ?? '#',
                        $ug->generate('inv/view', ['id' => $invId]),
                        ['class' => 'text-decoration-none', 'hx-boost' => 'false'],
                    )
                    : '';
            },
            visible: $visible,
        );
    }

    private function buildDateCreatedColumn(TranslatorInterface $t, bool $visible): DataColumn
    {
        return new DataColumn(
            'date_created',
            header: $t->translate('date.created'),
            content: static fn(SalesOrder $m): string =>
                $m->getDateCreated() instanceof \DateTimeImmutable
                    ? $m->getDateCreated()->format('Y-m-d')
                    : '',
            encodeContent: true,
            visible: $visible,
        );
    }

    /**
     * @psalm-param array<array-key, array<array-key, string>|string> $opts
     */
    private function buildClientColumn(
        TranslatorInterface $t,
        array $opts,
    ): DataColumn {
        return new DataColumn(
            property: 'filterClient',
            header: $t->translate('client'),
            content: static fn(SalesOrder $m): string =>
                Html::encode($m->getClient()?->getClientFullName() ?? ''),
            encodeContent: false,
            filter: DropdownFilter::widget()
                ->addAttributes(['name' => 'client_id', 'class' => 'native-reset'])
                ->optionsData($opts),
            withSorting: false,
        );
    }

    private function buildTotalColumn(
        SR $sR,
        SoAR $soaR,
        TranslatorInterface $t,
        bool $visible,
    ): DataColumn {
        return new DataColumn(
            'id',
            header: $t->translate('total'),
            content: static function (SalesOrder $m) use ($sR, $soaR): string {
                $soId     = $m->reqId();
                $soAmount = $soaR->repoSalesOrderAmountCount($soId) > 0
                    ? $soaR->repoSalesOrderquery($soId)
                    : null;
                return $sR->formatCurrency(
                    null !== $soAmount ? ($soAmount->getTotal() ?? 0.00) : 0.00
                );
            },
            visible: $visible,
        );
    }

    // -------------------------------------------------------------------------
    // Grouping helpers
    // -------------------------------------------------------------------------

    /** @return \Closure(SalesOrder): string */
    private function makeGroupValueResolver(string $groupBy): \Closure
    {
        \assert($this->soR !== null);
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
    private function computeGroupTotals(
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
    private function applyGrouping(
        GridView $gridView,
        callable $getGroupValue,
        array $groupTotals,
        string $groupBy,
    ): GridView {
        \assert($this->sR !== null);
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

    private function groupingScriptAndStyle(): string
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
