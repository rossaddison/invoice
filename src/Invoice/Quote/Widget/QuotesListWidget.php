<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Widget;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Data\Paginator\OffsetPaginator;
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
 * Renders the paginated quote grid.
 *
 * Column building is delegated to QuotesColumnBuilder, group-by logic to
 * QuotesGroupingHelper, and the toolbar to QuotesToolbar — keeping this class
 * within the S1448 limit of 20 methods.
 */
final class QuotesListWidget extends Widget
{
    private const string DOM_ID = 'QuotesGridView';

    private ?OffsetPaginator $paginator = null;
    private ?QR $qR = null;
    private ?SOR $soR = null;
    private ?SR $sR = null;
    private string|\Stringable $csrf = '';
    private int $decimalPlaces = 2;
    private bool $visible = false;
    private string $groupBy = 'none';
    private int $clientCount = 0;
    private string $gridSummary = '';
    private string $sortString = '-id';
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    private array $optionsDataClientsDropdownFilter = [];
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    private array $optionsDataStatusDropDownFilter = [];

    public function __construct(
        private readonly CurrentRoute $currentRoute,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {}

    public function withPaginator(OffsetPaginator $paginator): static
    {
        $new = clone $this;
        $new->paginator = $paginator;
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

    /** @psalm-param array<array-key, array<array-key, string>|string> $optionsData */
    public function withOptionsDataClientsDropdownFilter(array $optionsData): static
    {
        $new = clone $this;
        $new->optionsDataClientsDropdownFilter = $optionsData;
        return $new;
    }

    /** @psalm-param array<array-key, array<array-key, string>|string> $optionsData */
    public function withOptionsDataStatusDropDownFilter(array $optionsData): static
    {
        $new = clone $this;
        $new->optionsDataStatusDropDownFilter = $optionsData;
        return $new;
    }

    #[\Override]
    public function render(): string
    {
        if ($this->paginator === null || $this->qR === null
            || $this->soR === null || $this->sR === null) {
            return '';
        }

        $qR             = $this->qR;
        $soR            = $this->soR;
        $sR             = $this->sR;
        $decimalPlaces  = $this->decimalPlaces;
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

        $totalAmount = 0.0;
        foreach ($this->paginator->read() as $quote) {
            /** @var Quote $quote */
            $totalAmount += $quote->getQuoteAmount()?->getTotal() ?? 0.0;
        }

        $getGroupValue = QuotesGroupingHelper::makeGroupValueResolver($qR, $groupBy);
        $groupTotals   = $enableGrouping
            ? QuotesGroupingHelper::computeGroupTotals($this->paginator, $getGroupValue)
            : [];

        $columnBuilder = new QuotesColumnBuilder(
            $this->translator,
            $this->urlGenerator,
            $this->optionsDataClientsDropdownFilter,
            $this->optionsDataStatusDropDownFilter,
        );
        $columns = $columnBuilder->buildColumns($qR, $soR, $sR, $decimalPlaces, $totalAmount);

        $urlCreator = new UrlCreator($this->urlGenerator);
        $urlCreator->__invoke([], OrderHelper::stringToArray($this->sortString));

        $columnCount = count(array_filter(
            $columns, static fn(ColumnInterface $col): bool => $col->isVisible()
        ));

        $tableClass = ($this->visible ? 'table-responsive' : 'table')
            . ' table-bordered table-striped h-75';

        $gridView = GridView::widget()
            ->containerAttributes(['id' => self::DOM_ID, 'class' => 'position-relative'])
            ->bodyRowAttributes(['class' => 'align-left'])
            ->tableAttributes(['class' => $tableClass, 'id' => 'table-quote'])
            ->columns(...$columns)
            ->columnGrouping(true)
            ->dataReader($this->paginator)
            ->urlCreator($urlCreator)
            ->paginationWidget($pagination)
            ->sortableLinkAttributes(['hx-boost' => 'true', ...$htmxAttrs])
            ->filterFormAttributes(['hx-boost' => 'true', ...$htmxAttrs])
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
            ->toolbar(QuotesToolbar::build(
                $this->translator,
                $this->urlGenerator,
                $this->currentRoute,
                $this->csrf,
                $this->clientCount,
                $this->groupBy,
                $enableGrouping,
            ));

        if ($enableGrouping) {
            $gridView = QuotesGroupingHelper::applyGrouping(
                $gridView, $getGroupValue, $groupTotals,
                $decimalPlaces, $groupBy, $columnCount, $sR
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
}
