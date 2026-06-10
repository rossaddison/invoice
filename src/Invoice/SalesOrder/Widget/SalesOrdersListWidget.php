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
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Select;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Widget\Widget;
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

        $domId     = '#' . self::DOM_ID;
        $htmxAttrs = [
            'hx-indicator'   => $domId,
            'hx-target'      => $domId,
            'hx-select'      => $domId,
            'hx-replace-url' => 'true',
            'hx-swap'        => 'outerHTML',
        ];
        $htmxBoostAttrs = ['hx-boost' => 'true', ...$htmxAttrs];

        /** @var PaginationWidgetInterface<\Yiisoft\Data\Paginator\PaginatorInterface> */
        $pagination = OffsetPagination::widget()->addLinkAttributes($htmxBoostAttrs);

        $urlCreator = new UrlCreator($this->urlGenerator);
        $urlCreator->__invoke([], OrderHelper::stringToArray($this->sortString));

        $groupingRenderer = new SalesOrdersGroupingRenderer($this->soR, $this->sR);
        $getGroupValue    = $groupingRenderer->makeGroupValueResolver($groupBy);
        $groupTotals      = $enableGrouping
            ? $groupingRenderer->computeGroupTotals($this->paginator, $getGroupValue)
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
            ->sortableLinkAttributes($htmxBoostAttrs)
            ->filterFormAttributes($htmxBoostAttrs)
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
            $gridView = $groupingRenderer->applyGrouping($gridView, $getGroupValue,
                $groupTotals, $groupBy);
        }

        $output = $gridView->render();

        if ($enableGrouping) {
            $output .= $groupingRenderer->groupingScriptAndStyle();
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
                    $this->groupToggleButton(false)
                    . $this->groupToggleButton(true)
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

    private function groupToggleButton(bool $expand): string
    {
        return (new HtmlButton())
            ->type('button')
            ->addClass('btn btn-outline-secondary btn-sm')
            ->addAttributes([
                'onclick' => $expand ? 'toggleAllGroups(true)' : 'toggleAllGroups(false)',
                'title'   => $expand ? 'Expand All Groups' : 'Collapse All Groups',
            ])
            ->content(new I()->addClass($expand ? 'bi bi-chevron-down' : 'bi bi-chevron-up'))
            ->render();
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

    /** @return list<DataColumn|\Yiisoft\Yii\DataView\GridView\Column\CheckboxColumn> */
    private function buildColumns(): array
    {
        \assert($this->soR !== null && $this->soaR !== null && $this->sR !== null);
        $ug      = $this->urlGenerator;
        $t       = $this->translator;
        $vis     = $this->visible;
        $builder = new SalesOrdersColumnBuilder(
            $ug, $t, $vis, $this->iR,
            $this->sR, $this->soR, $this->soaR,
            $this->optionsDataClientsDropdownFilter,
        );
        return [
            $builder->buildCheckboxColumn(),
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
            $builder->buildStatusColumn(),
            $builder->buildNumberColumn(),
            $builder->buildQuoteColumn(),
            $builder->buildInvoiceColumn(),
            $builder->buildDateCreatedColumn(),
            $builder->buildClientColumn(),
            $builder->buildTotalColumn(),
        ];
    }
}
