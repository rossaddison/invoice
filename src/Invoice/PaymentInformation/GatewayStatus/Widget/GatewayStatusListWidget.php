<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\GatewayStatus\Widget;

use App\Widget\NoOpFilterFactory;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Widget\Widget;
use Yiisoft\Yii\DataView\Filter\Widget\DropdownFilter;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;
use Yiisoft\Yii\DataView\YiiRouter\UrlParameterProvider;

/**
 * Renders the public payment-gateway coverage table with the same grid
 * mechanics (sortable columns, pagination, htmx-free plain GridView) as the
 * app's other list widgets — see `App\User\Widget\UsersListWidget` for the
 * closest sibling this mirrors. Mobile-stacking (data-label attributes) uses
 * the same site-wide CSS convention documented in
 * docs/BOOTSTRAP5_TABLE_MOBILE_STACKING.md.
 *
 * Row shape expected from the paginator's underlying reader — plain arrays,
 * not GatewayStatus entities directly, so DataColumn's property-based
 * sorting works against simple array keys rather than needing the
 * `'getX()'`-suffix convention ArrayHelper::getValue requires for objects
 * with private properties.
 *
 * @psalm-type GatewayStatusRow = array{
 *     name: string,
 *     regions: string,
 *     sdk_version: string|null,
 *     last_updated: string,
 *     sandbox_status: string|null,
 *     sandbox_tested_at: string|null,
 *     live_tested_at: string|null,
 *     region_priority: int,
 *     needs_retest: bool,
 * }
 */
final class GatewayStatusListWidget extends Widget
{
    private const string DOM_ID = 'GatewayStatusGridView';

    /**
     * @var OffsetPaginator<array-key, GatewayStatusRow>|null
     */
    private ?OffsetPaginator $paginator = null;

    /** @var list<string> */
    private array $regionOptions = [];

    public function __construct(
        private readonly CurrentRoute $currentRoute,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @param OffsetPaginator<array-key, GatewayStatusRow> $paginator
     */
    public function withPaginator(OffsetPaginator $paginator): static
    {
        $new = clone $this;
        $new->paginator = $paginator;
        return $new;
    }

    /**
     * @param list<string> $regionOptions Every region present across all
     *   gateways (unfiltered) -- the dropdown always lists every option,
     *   not just the ones remaining after the current filter narrows the
     *   grid, same as the hand-rolled <select> this replaces already did.
     */
    public function withRegionOptions(array $regionOptions): static
    {
        $new = clone $this;
        $new->regionOptions = $regionOptions;
        return $new;
    }

    // No withFilter()/current-filter-value setter needed: DropdownFilter's
    // own renderFilter(Context $context) reads $context->value, which
    // GridView populates itself from the same UrlParameterProvider passed
    // to ->urlParameterProvider() below, keyed by each DataColumn's own
    // property name -- confirmed against MakeFilterContext::getQueryValue()
    // and InvsListWidget, which never passes InvIndexFilter into itself
    // either for exactly this reason.

    #[\Override]
    public function render(): string
    {
        if ($this->paginator === null) {
            return '';
        }

        /** @var \Yiisoft\Yii\DataView\Pagination\PaginationWidgetInterface<\Yiisoft\Data\Paginator\PaginatorInterface> $pagination */
        $pagination = OffsetPagination::widget();

        // GridView/BaseListView's own default summaryTemplate ('Page
        // <b>{currentPage}</b> of <b>{totalPages}</b>') relies on a
        // translator it builds itself (IdMessageReader + Intl/Simple
        // MessageFormatter) when none is passed to the constructor — no
        // other list widget in this app relies on that path (every one
        // overrides summaryTemplate() with a pre-built string instead, see
        // e.g. InvsListWidget/FamilyListWidget), and it doesn't actually
        // substitute the placeholders here, rendering the raw
        // 'Page {currentPage} of {totalPages}' text verbatim. Matching the
        // established pattern: compute the summary ourselves with this
        // app's own translator, which is known to work correctly.
        $summary = sprintf(
            $this->translator->translate('gateway.status.page.summary'),
            $this->paginator->getCurrentPage(),
            $this->paginator->getTotalPages(),
        );

        $gridView = GridView::widget()
            ->containerAttributes(['id' => self::DOM_ID, 'class' => 'position-relative'])
            ->tableAttributes(['class' => 'table table-striped align-middle'])
            ->dataReader($this->paginator)
            ->urlParameterProvider(new UrlParameterProvider($this->currentRoute))
            ->urlCreator(new UrlCreator($this->urlGenerator))
            ->paginationWidget($pagination)
            ->summaryTemplate($summary)
            ->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50">⭥</div>')
            ->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
            ->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
            ->noResultsText('No gateways found.')
            ->columns(
                new DataColumn(
                    'name',
                    header: 'Gateway',
                    withSorting: true,
                    content: self::nameCell(...),
                    bodyAttributes: ['data-label' => 'Gateway'],
                ),
                new DataColumn(
                    // Native filter row (yiisoft/yii-dataview#355's
                    // useInlineJs(false) + this app's own
                    // NoOpFilterFactory) rather than a separate <form>
                    // above the table -- mirrors
                    // InvsColumnBuilder::buildColumns()'s filterClient
                    // column exactly. property is the filter's own GET
                    // param name (App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusFilter::$filterRegion),
                    // not the row's data key, so this column gives up
                    // native sorting the same way filterClient does --
                    // it was never sortable before this change either.
                    'filterRegion',
                    header: 'Regions',
                    withSorting: false,
                    content: self::regionsCell(...),
                    filter: DropdownFilter::widget()
                        ->addAttributes(['aria-label' => 'Filter by region'])
                        ->optionsData(['' => 'All regions', ...array_combine(
                            $this->regionOptions,
                            array_map('ucwords', $this->regionOptions),
                        )])
                        ->useInlineJs(false),
                    filterFactory: new NoOpFilterFactory(),
                    bodyAttributes: ['data-label' => 'Regions'],
                ),
                new DataColumn(
                    'sdk_version',
                    header: 'SDK Version',
                    withSorting: true,
                    content: self::sdkVersionCell(...),
                    bodyAttributes: ['data-label' => 'SDK Version'],
                ),
                new DataColumn(
                    'last_updated',
                    header: 'Last Updated',
                    withSorting: true,
                    content: self::lastUpdatedCell(...),
                    bodyAttributes: ['data-label' => 'Last Updated'],
                ),
                new DataColumn(
                    // Same property-is-the-filter-name/not-sortable
                    // tradeoff as the regions column above.
                    'filterSandboxStatus',
                    header: 'Sandbox Tested',
                    withSorting: false,
                    content: self::sandboxStatusCell(...),
                    encodeContent: false,
                    filter: DropdownFilter::widget()
                        ->addAttributes(['aria-label' => 'Filter by sandbox status'])
                        ->optionsData([
                            '' => 'Any',
                            'pass' => 'Passing',
                            'fail' => 'Failing',
                        ])
                        ->useInlineJs(false),
                    filterFactory: new NoOpFilterFactory(),
                    bodyAttributes: ['data-label' => 'Sandbox Tested'],
                ),
                new DataColumn(
                    'filterNeedsRetest',
                    header: 'Retest?',
                    withSorting: false,
                    content: self::needsRetestCell(...),
                    encodeContent: false,
                    filter: DropdownFilter::widget()
                        ->addAttributes(['aria-label' => 'Filter by retest status'])
                        ->optionsData([
                            '' => 'Any',
                            'yes' => 'Needs retest',
                            'no' => 'Up to date',
                        ])
                        ->useInlineJs(false),
                    filterFactory: new NoOpFilterFactory(),
                    bodyAttributes: ['data-label' => 'Retest?'],
                ),
                new DataColumn(
                    'live_tested_at',
                    header: 'Live Tested',
                    withSorting: true,
                    content: self::liveTestedAtCell(...),
                    bodyAttributes: ['data-label' => 'Live Tested'],
                ),
            );

        return $gridView->render();
    }

    /**
     * @param GatewayStatusRow $row
     */
    private static function nameCell(array $row): string // NOSONAR: php:S1144 — used via self::nameCell(...) first-class callable in render(), which this analyzer doesn't trace
    {
        return Html::encode($row['name']);
    }

    /**
     * @param GatewayStatusRow $row
     */
    private static function regionsCell(array $row): string // NOSONAR: php:S1144 — used via self::regionsCell(...) first-class callable in render(), which this analyzer doesn't trace
    {
        return Html::encode($row['regions']);
    }

    /**
     * @param GatewayStatusRow $row
     */
    private static function sdkVersionCell(array $row): string // NOSONAR: php:S1144 — used via self::sdkVersionCell(...) first-class callable in render(), which this analyzer doesn't trace
    {
        return Html::encode($row['sdk_version'] ?? '—');
    }

    /**
     * @param GatewayStatusRow $row
     */
    private static function lastUpdatedCell(array $row): string // NOSONAR: php:S1144 — used via self::lastUpdatedCell(...) first-class callable in render(), which this analyzer doesn't trace
    {
        return Html::encode($row['last_updated']);
    }

    /**
     * @param GatewayStatusRow $row
     */
    private static function sandboxStatusCell(array $row): string // NOSONAR: php:S1144 — used via self::sandboxStatusCell(...) first-class callable in render(), which this analyzer doesn't trace
    {
        $badge = match ($row['sandbox_status']) {
            'pass' => Html::span('Sandbox tested ✓', ['class' => 'badge text-bg-success']),
            'fail' => Html::span('Sandbox check failing', ['class' => 'badge text-bg-danger']),
            default => Html::span('Not yet sandbox tested', ['class' => 'badge text-bg-secondary']),
        };
        $date = $row['sandbox_tested_at'] === null
            ? ''
            : Html::tag('div', $row['sandbox_tested_at'], ['class' => 'small text-muted'])->render();
        return $badge->render() . $date;
    }

    /**
     * True when this gateway's SDK version was bumped after -- or without
     * ever having -- a real live payment run recorded against it (see
     * App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusRow::needsRetestSinceUpdate()'s
     * own docblock for why this is deliberately shown publicly).
     *
     * NOSONAR php:S1144 justification: used via self::needsRetestCell(...)
     * first-class callable in render(), which this analyzer doesn't trace.
     *
     * @param GatewayStatusRow $row
     */
    private static function needsRetestCell(array $row): string // NOSONAR: php:S1144
    {
        return $row['needs_retest']
            ? Html::span(
                '⚠️ Updated since last live test',
                ['class' => 'badge text-bg-warning'],
            )->render()
            : '';
    }

    /**
     * @param GatewayStatusRow $row
     */
    private static function liveTestedAtCell(array $row): string // NOSONAR: php:S1144 — used via self::liveTestedAtCell(...) first-class callable in render(), which this analyzer doesn't trace
    {
        return Html::encode($row['live_tested_at'] ?? 'Not yet live tested');
    }
}
