<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\InvSentLog\InvSentLogRepository as ISLR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Widget\GridComponents;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Widget\Widget;
use Yiisoft\Yii\DataView\GridView\Column\ColumnInterface;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;
use Yiisoft\Yii\DataView\Pagination\PaginationWidgetInterface;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * Renders the paginated invoice grid.
 *
 * Column building is delegated to InvsColumnBuilder, group-by logic to
 * InvsGroupingHelper, and the toolbar to InvsToolbar — keeping this class
 * within the S1448 limit of 20 methods.
 *
 * The seven dropdown-filter option arrays are bundled into a single
 * InvsFilterOptions value object passed via withFilterOptions().
 */
final class InvsListWidget extends Widget
{
    private const string DOM_ID = 'InvsGridView';

    private ?OffsetPaginator $paginator = null;
    private ?IR $iR = null;
    private ?IRR $irR = null;
    private ?ISLR $islR = null;
    private ?QR $qR = null;
    private ?SOR $soR = null;
    private ?DLR $dlR = null;
    private ?SR $sR = null;
    private string|\Stringable $csrf = '';
    private int $decimalPlaces = 2;
    private bool $visible = false;
    private bool $visibleInvSentLogColumn = false;
    private string $groupBy = 'none';
    private int $clientCount = 0;
    private string $gridSummary = '';
    private string $sortString = '-id';
    private string $label = '';
    private ?InvsFilterOptions $filterOptions = null;

    public function __construct(
        private readonly CurrentRoute $currentRoute,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly GridComponents $gridComponents,
    ) {}

    // -------------------------------------------------------------------------
    // Immutable setters
    // -------------------------------------------------------------------------

    public function withPaginator(OffsetPaginator $paginator): static
    {
        $new = clone $this;
        $new->paginator = $paginator;
        return $new;
    }

    public function withIR(IR $iR): static
    {
        $new = clone $this;
        $new->iR = $iR;
        return $new;
    }

    public function withIrR(IRR $irR): static
    {
        $new = clone $this;
        $new->irR = $irR;
        return $new;
    }

    public function withIslR(ISLR $islR): static
    {
        $new = clone $this;
        $new->islR = $islR;
        return $new;
    }

    public function withQR(QR $qR): static
    {
        $new = clone $this;
        $new->qR = $qR;
        return $new;
    }

    public function withSoR(SOR $soR): static
    {
        $new = clone $this;
        $new->soR = $soR;
        return $new;
    }

    public function withDlR(DLR $dlR): static
    {
        $new = clone $this;
        $new->dlR = $dlR;
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

    public function withDecimalPlaces(int $decimalPlaces): static
    {
        $new = clone $this;
        $new->decimalPlaces = $decimalPlaces;
        return $new;
    }

    public function withVisible(bool $visible): static
    {
        $new = clone $this;
        $new->visible = $visible;
        return $new;
    }

    public function withVisibleInvSentLogColumn(bool $visible): static
    {
        $new = clone $this;
        $new->visibleInvSentLogColumn = $visible;
        return $new;
    }

    public function withGroupBy(string $groupBy): static
    {
        $new = clone $this;
        $new->groupBy = $groupBy;
        return $new;
    }

    public function withClientCount(int $clientCount): static
    {
        $new = clone $this;
        $new->clientCount = $clientCount;
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

    public function withLabel(string $label): static
    {
        $new = clone $this;
        $new->label = $label;
        return $new;
    }

    public function withFilterOptions(InvsFilterOptions $filterOptions): static
    {
        $new = clone $this;
        $new->filterOptions = $filterOptions;
        return $new;
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    #[\Override]
    public function render(): string
    {
        if ($this->paginator === null || $this->iR === null
            || $this->irR === null || $this->islR === null || $this->sR === null) {
            return '';
        }

        $groupBy        = $this->groupBy;
        $enableGrouping = $groupBy !== 'none';

        $htmxAttrs = [
            'hx-indicator'   => '#' . self::DOM_ID,
            'hx-target'      => '#' . self::DOM_ID,
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

        $getGroupValue = InvsGroupingHelper::makeGroupValueResolver($groupBy, $this->iR);

        // Computed groupings (quick_pay, amount_range, peppol_workflow) derive their
        // group key from runtime values, not DB columns, so rows arrive in an order
        // that scatters same-group items. Load all items, sort by group key in PHP,
        // then re-paginate so beforeRow sees adjacent same-group rows.
        /** @var OffsetPaginator<array-key, array<array-key, mixed>|object> $gridDataReader */
        $gridDataReader = $enableGrouping && InvsGroupingHelper::isComputedGrouping($groupBy)
            ? $this->buildSortedPaginator($this->paginator, $groupBy, $getGroupValue)
            : $this->paginator;

        $totalAmount  = 0.0;
        $totalPaid    = 0.0;
        $totalBalance = 0.0;
        foreach ($gridDataReader->read() as $invoice) {
            /** @var Inv $invoice */
            $totalAmount  += $invoice->getInvAmount()->getTotal()   ?? 0.0;
            $totalPaid    += $invoice->getInvAmount()->getPaid()    ?? 0.0;
            $totalBalance += $invoice->getInvAmount()->getBalance() ?? 0.0;
        }

        $groupTotals = $enableGrouping
            ? InvsGroupingHelper::computeGroupTotals($gridDataReader, $getGroupValue)
            : [];

        $columnBuilder = new InvsColumnBuilder(
            $this->translator,
            $this->urlGenerator,
            $this->gridComponents,
            $this->filterOptions ?? new InvsFilterOptions(),
            $this->visible,
            $this->visibleInvSentLogColumn,
        );
        $columns = $columnBuilder->buildColumns(new InvsColumnParams(
            iR:           $this->iR,
            irR:          $this->irR,
            islR:         $this->islR,
            sR:           $this->sR,
            dp:           $this->decimalPlaces,
            totalAmount:  $totalAmount,
            totalPaid:    $totalPaid,
            totalBalance: $totalBalance,
            qR:           $this->qR,
            soR:          $this->soR,
            dlR:          $this->dlR,
        ));

        $columnCount = count(array_filter(
            $columns, static fn(ColumnInterface $col): bool => $col->isVisible()
        ));

        $tableClass = ($this->visible ? 'table-responsive' : 'table')
            . ' table-bordered table-striped h-75';

        $gridView = GridView::widget()
            ->containerAttributes(['id' => self::DOM_ID, 'class' => 'position-relative'])
            ->bodyRowAttributes(['class' => 'align-left'])
            ->tableAttributes(['class' => $tableClass, 'id' => 'table-invoice'])
            ->columns(...$columns)
            ->columnGrouping(true)
            ->dataReader($gridDataReader)
            ->urlCreator($urlCreator)
            ->paginationWidget($pagination)
            ->sortableLinkAttributes(['hx-boost' => 'true', ...$htmxAttrs])
            ->filterFormAttributes(['hx-boost' => 'true', ...$htmxAttrs])
            ->sortableHeaderPrepend(
                '<div class="float-end text-secondary text-opacity-50">⭥</div>')
            ->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
            ->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
            ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
            ->footerRowAttributes(['class' => 'card-footer bg-success text-white fw-bold'])
            ->enableFooter(true)
            ->emptyCell($this->translator->translate('not.set'))
            ->emptyCellAttributes(['style' => 'color:red'])
            ->summaryAttributes([
                'class' =>
                'mt-3 me-3 summary d-flex justify-content-between align-items-center',
            ])
            ->summaryTemplate(
                '<div class="d-flex align-items-center">'
                . $this->gridSummary . '</div>'
            )
            ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
            ->noResultsText($this->translator->translate('no.records'))
            ->toolbar(InvsToolbar::build(new InvsToolbarParams(
                translator:     $this->translator,
                urlGenerator:   $this->urlGenerator,
                currentRoute:   $this->currentRoute,
                csrf:           $this->csrf,
                iR:             $this->iR,
                sR:             $this->sR,
                clientCount:    $this->clientCount,
                groupBy:        $this->groupBy,
                enableGrouping: $enableGrouping,
            )));

        if ($enableGrouping) {
            $gridView = InvsGroupingHelper::applyGrouping(
                $gridView, $getGroupValue, $groupTotals,
                $this->decimalPlaces, $groupBy, $columnCount, $this->sR
            );
        }

        $output = '';
        if ($this->visible) {
            $output .= '<div class="text-start">';
        }
        $output .= $gridView->render();
        if ($this->visible) {
            $output .= '</div>';
        }
        return $output;
    }

    /**
     * Loads all filtered items, sorts them by computed group key in PHP, and
     * returns a fresh OffsetPaginator over the sorted list — keeping the original
     * page size and current page so pagination UI remains correct.
     *
     * @param OffsetPaginator<array-key, array<array-key, mixed>|object> $paginator
     * @param callable(Inv): string $getGroupValue
     * @return OffsetPaginator<array-key, array<array-key, mixed>|object>
     */
    private function buildSortedPaginator(
        OffsetPaginator $paginator,
        string $groupBy,
        callable $getGroupValue,
    ): OffsetPaginator {
        $total = $paginator->getTotalItems();
        if ($total > 0) {
            /** @var list<Inv> $allItems */
            $allItems = iterator_to_array($paginator->withPageSize($total)->read());
            usort($allItems, static fn (Inv $a, Inv $b): int =>
                strcmp(
                    InvsGroupingHelper::groupSortKey($groupBy, $getGroupValue($a)),
                    InvsGroupingHelper::groupSortKey($groupBy, $getGroupValue($b)),
                )
            );
        } else {
            $allItems = [];
        }

        return (new OffsetPaginator(new IterableDataReader($allItems)))
            ->withPageSize($paginator->getPageSize())
            ->withCurrentPage($paginator->getCurrentPage());
    }
}
