<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\GatewayStatus\Widget;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Widget\Widget;
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
 * }
 */
final class GatewayStatusListWidget extends Widget
{
    private const string DOM_ID = 'GatewayStatusGridView';

    /**
     * @var OffsetPaginator<array-key, GatewayStatusRow>|null
     */
    private ?OffsetPaginator $paginator = null;

    public function __construct(
        private readonly CurrentRoute $currentRoute,
        private readonly UrlGeneratorInterface $urlGenerator,
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

    #[\Override]
    public function render(): string
    {
        if ($this->paginator === null) {
            return '';
        }

        /** @var \Yiisoft\Yii\DataView\Pagination\PaginationWidgetInterface<\Yiisoft\Data\Paginator\PaginatorInterface> $pagination */
        $pagination = OffsetPagination::widget();

        $gridView = GridView::widget()
            ->containerAttributes(['id' => self::DOM_ID, 'class' => 'position-relative'])
            ->tableAttributes(['class' => 'table table-striped align-middle'])
            ->dataReader($this->paginator)
            ->urlParameterProvider(new UrlParameterProvider($this->currentRoute))
            ->urlCreator(new UrlCreator($this->urlGenerator))
            ->paginationWidget($pagination)
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
                    'regions',
                    header: 'Regions',
                    withSorting: false,
                    content: self::regionsCell(...),
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
                    'sandbox_status',
                    header: 'Sandbox Tested',
                    withSorting: true,
                    content: self::sandboxStatusCell(...),
                    encodeContent: false,
                    bodyAttributes: ['data-label' => 'Sandbox Tested'],
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
    private static function nameCell(array $row): string
    {
        return Html::encode($row['name']);
    }

    /**
     * @param GatewayStatusRow $row
     */
    private static function regionsCell(array $row): string
    {
        return Html::encode($row['regions']);
    }

    /**
     * @param GatewayStatusRow $row
     */
    private static function sdkVersionCell(array $row): string
    {
        return Html::encode($row['sdk_version'] ?? '—');
    }

    /**
     * @param GatewayStatusRow $row
     */
    private static function lastUpdatedCell(array $row): string
    {
        return Html::encode($row['last_updated']);
    }

    /**
     * @param GatewayStatusRow $row
     */
    private static function sandboxStatusCell(array $row): string
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
     * @param GatewayStatusRow $row
     */
    private static function liveTestedAtCell(array $row): string
    {
        return Html::encode($row['live_tested_at'] ?? 'Not yet live tested');
    }
}
