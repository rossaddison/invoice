<?php

declare(strict_types=1);

namespace App\Invoice\Product\Widget;

use App\Infrastructure\Persistence\Product\Product;
use App\Invoice\ProductClient\ProductClientRepository as PcR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Widget\GridComponents;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Widget\Widget;
use Yiisoft\Yii\DataView\Filter\Widget\DropdownFilter;
use Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;
use Yiisoft\Yii\DataView\Pagination\PaginationWidgetInterface;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

final class ProductsListWidget extends Widget
{
    private const string DOM_ID = 'ProductsGridView';
    private const string ROUTE_INDEX = 'product/index';

    private ?OffsetPaginator $paginator = null;
    private ?SR $sR = null;
    private ?PcR $pcR = null;
    private string|\Stringable $csrf = '';
    private bool $visible = false;
    private string $gridSummary = '';
    private string $sortString = '-id';
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    private array $optionsDataProductsDropdownFilter = [];
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    private array $optionsDataFamiliesDropdownFilter = [];

    public function __construct(
        private readonly CurrentRoute $currentRoute,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly GridComponents $gridComponents,
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

    public function withSR(SR $sR): static
    {
        $new = clone $this;
        $new->sR = $sR;
        return $new;
    }

    public function withPcR(PcR $pcR): static
    {
        $new = clone $this;
        $new->pcR = $pcR;
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

    /** @psalm-param array<array-key, array<array-key, string>|string> $options */
    public function withOptionsDataProductsDropdownFilter(array $options): static
    {
        $new = clone $this;
        $new->optionsDataProductsDropdownFilter = $options;
        return $new;
    }

    /** @psalm-param array<array-key, array<array-key, string>|string> $options */
    public function withOptionsDataFamiliesDropdownFilter(array $options): static
    {
        $new = clone $this;
        $new->optionsDataFamiliesDropdownFilter = $options;
        return $new;
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    #[\Override]
    public function render(): string
    {
        if ($this->paginator === null || $this->sR === null || $this->pcR === null) {
            return '';
        }

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

        $columns = $this->buildColumns();

        $visible = $this->visible;

        return GridView::widget()
            ->containerAttributes(['id' => self::DOM_ID, 'class' => 'position-relative'])
            ->bodyRowAttributes(['class' => 'align-middle'])
            ->tableAttributes([
                'class' => ($visible ? 'table-responsive' : 'table') . ' table-striped text-center h-75',
                'id'    => 'table-product',
            ])
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
            ->emptyCell($this->translator->translate('not.set'))
            ->emptyCellAttributes(['style' => 'color:red'])
            ->toolbar($this->buildToolbarString())
            ->render();
    }

    // -------------------------------------------------------------------------
    // Toolbar
    // -------------------------------------------------------------------------

    private function buildToolbarString(): string
    {
        $ug = $this->urlGenerator;
        $t  = $this->translator;

        $allVisible = (new A())
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                'title' => $t->translate('hide.or.unhide.columns')])
            ->addClass('btn btn-warning me-1 ajax-loader')
            ->content('↔️')
            ->href($ug->generate('setting/visible', ['origin' => 'product']))
            ->id('btn-all-visible')
            ->render();

        $toolbarReset = (new A())
            ->addAttributes(['type' => 'reset'])
            ->addClass('btn btn-danger me-1 ajax-loader')
            ->content(new I()->addClass('bi bi-bootstrap-reboot'))
            ->href($ug->generate($this->currentRoute->getName() ?? self::ROUTE_INDEX))
            ->id('btn-reset')
            ->render();

        $addProduct = (new A())
            ->href($ug->generate('product/add'))
            ->addClass('btn btn-info')
            ->addAttributes(['hx-boost' => 'false'])
            ->content('➕')
            ->render();

        return (new Form())
                ->post($ug->generate(self::ROUTE_INDEX))
                ->csrf($this->csrf)
                ->open()
            . (new Div())
                ->addClass('float-start')
                ->content(
                    '<h4 class="me-3 d-inline-block">' . $t->translate('products') . '</h4>'
                    . '<div class="btn-group me-2" role="group">'
                    . $allVisible
                    . $toolbarReset
                    . $addProduct
                    . '</div>'
                )
                ->encode(false)
                ->render()
            . (new Form())->close();
    }

    // -------------------------------------------------------------------------
    // Columns
    // -------------------------------------------------------------------------

    /** @return list<DataColumn|ActionColumn> */
    private function buildColumns(): array
    {
        \assert($this->sR !== null && $this->pcR !== null);

        $sR           = $this->sR;
        $pcR          = $this->pcR;
        $ug           = $this->urlGenerator;
        $t            = $this->translator;
        $visible      = $this->visible;
        $gridComps    = $this->gridComponents;
        $optionsFam   = $this->optionsDataFamiliesDropdownFilter;
        $optionsProd  = $this->optionsDataProductsDropdownFilter;

        return [
            new DataColumn(
                'id',
                header: $t->translate('id'),
                content: static fn(Product $m): string => Html::encode((string) $m->reqId()),
                withSorting: true,
            ),
            $this->buildFamilyColumn($t, $optionsFam),
            new DataColumn(
                property: 'product_name',
                header: $t->translate('product.name'),
                encodeHeader: true,
                content: static fn(Product $m): string => Html::encode($m->getProductName()),
                visible: true,
                withSorting: false,
            ),
            $this->buildClientAssociationsColumn($pcR, $t, $ug, $gridComps),
            $this->buildSkuColumn($t, $optionsProd),
            new DataColumn(
                property: 'product_description',
                header: $t->translate('product.description'),
                content: static fn(Product $m): string =>
                    Html::encode(ucfirst($m->getProductDescription() ?? '')),
                visible: true,
                withSorting: true,
            ),
            $this->buildPriceColumn($sR, $t),
            new DataColumn(
                property: 'product_price_base_quantity',
                header: $t->translate('product.price.base.quantity'),
                content: static fn(Product $m): string =>
                    Html::encode((string) $m->getProductPriceBaseQuantity()),
                visible: $visible,
                withSorting: true,
            ),
            new DataColumn(
                property: 'product_unit',
                header: $t->translate('product.unit'),
                content: static fn(Product $m): string =>
                    Html::encode(ucfirst($m->getUnit()?->getUnitName() ?? '')),
                visible: true,
            ),
            new DataColumn(
                property: 'tax_rate_id',
                header: $t->translate('tax.rate'),
                content: static fn(Product $m): string =>
                    ($m->getTaxrate()?->reqId() > 0)
                        ? Html::encode($m->getTaxrate()?->getTaxRateName() ?? '')
                        : $t->translate('none'),
                visible: $visible,
                withSorting: true,
            ),
            new DataColumn(
                header: $t->translate('product.property.add'),
                content: static fn(Product $m): A =>
                    Html::a(
                        Html::tag('i', '', ['class' => 'bi-plus dropdown-item text-decoration-none']),
                        $ug->generate('productproperty/add', ['product_id' => $m->reqId()]),
                        ['hx-boost' => 'false'],
                    ),
                encodeContent: false,
                visible: $visible,
            ),
            $this->buildActionColumn($ug, $t),
        ];
    }

    /**
     * @psalm-param array<array-key, array<array-key, string>|string> $optionsFam
     */
    private function buildFamilyColumn(TranslatorInterface $t, array $optionsFam): DataColumn
    {
        return new DataColumn(
            property: 'family_id',
            header: $t->translate('family.name'),
            encodeHeader: true,
            content: static fn(Product $m): string =>
                Html::encode($m->getFamily()?->getFamilyName() ?? ''),
            /** @psalm-suppress MixedArgumentTypeCoercion */
            filter: DropdownFilter::widget()
                ->addAttributes(['name' => 'family_id', 'class' => 'native-reset'])
                ->optionsData($optionsFam),
            visible: true,
            withSorting: true,
        );
    }

    private function buildClientAssociationsColumn(
        PcR $pcR,
        TranslatorInterface $t,
        UrlGeneratorInterface $ug,
        GridComponents $gridComps,
    ): DataColumn {
        return new DataColumn(
            property: 'client_associations',
            header: $t->translate('recent.clients'),
            encodeHeader: true,
            content: static function (Product $m) use ($pcR, $t, $ug, $gridComps): string {
                $productClients = $pcR->findByProductId($productId = $m->reqId());
                $m->setProductClients();
                foreach ($productClients as $productClient) {
                    $m->addProductClient($productClient);
                }
                $clientCount = $m->getProductClients()->count();
                if ($clientCount === 0) {
                    return '';
                }
                $collapseId = 'clients-product-' . $productId;
                $buttonHtml = Html::tag(
                    'button',
                    '➡️ ' . Html::encode((string) $clientCount),
                    [
                        'type'            => 'button',
                        'class'           => 'btn btn-sm btn-outline-primary me-2',
                        'data-bs-toggle'  => 'collapse',
                        'data-bs-target'  => '#' . $collapseId,
                        'aria-expanded'   => 'true',
                        'aria-controls'   => $collapseId,
                    ]
                );
                $tableHtml = $gridComps->gridMiniTableOfClientsForProduct(
                    $m, 4, $t, $ug,
                );
                $collapseHtml = (new Div())
                    ->id($collapseId)
                    ->addClass('collapse show mt-2')
                    ->content($tableHtml)
                    ->encode(false)
                    ->render();
                return $buttonHtml . $collapseHtml;
            },
            encodeContent: false,
        );
    }

    /**
     * @psalm-param array<array-key, array<array-key, string>|string> $optionsProd
     */
    private function buildSkuColumn(TranslatorInterface $t, array $optionsProd): DataColumn
    {
        return new DataColumn(
            property: 'product_sku',
            header: $t->translate('product.sku'),
            encodeHeader: true,
            content: static fn(Product $m): string => Html::encode($m->getProductSku()),
            /** @psalm-suppress MixedArgumentTypeCoercion */
            filter: DropdownFilter::widget()
                ->addAttributes(['name' => 'product_sku', 'class' => 'native-reset'])
                ->optionsData($optionsProd),
            visible: true,
            withSorting: false,
        );
    }

    private function buildPriceColumn(SR $sR, TranslatorInterface $t): DataColumn
    {
        return new DataColumn(
            property: 'product_price',
            header: $t->translate('product.price') . ' ( ' . $sR->getSetting('currency_symbol') . ' ) ',
            content: static fn(Product $m): string =>
                Html::encode((string) $m->getProductPrice()),
            filter: TextInputFilter::widget()
                ->addAttributes(['style' => 'max-width: 50px', 'class' => 'native-reset']),
            visible: true,
            withSorting: false,
        );
    }

    private function buildActionColumn(
        UrlGeneratorInterface $ug,
        TranslatorInterface $t,
    ): ActionColumn {
        return new ActionColumn(
            before: Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
            after: Html::closeTag('div'),
            buttons: [
                new ActionButton(
                    content: '🔎',
                    url: static fn(Product $m): string =>
                        $ug->generate('product/view', ['id' => $m->reqId()]),
                    attributes: [
                        'data-bs-toggle' => 'tooltip',
                        'title'          => $t->translate('view'),
                        'class'          => 'btn btn-outline-primary btn-sm',
                        'hx-boost'       => 'false',
                    ],
                ),
                new ActionButton(
                    content: '✎',
                    url: static fn(Product $m): string =>
                        $ug->generate('product/edit', ['id' => $m->reqId()]),
                    attributes: [
                        'data-bs-toggle' => 'tooltip',
                        'title'          => $t->translate('edit'),
                        'class'          => 'btn btn-outline-warning btn-sm',
                        'hx-boost'       => 'false',
                    ],
                ),
                new ActionButton(
                    content: '❌',
                    url: static fn(Product $m): string =>
                        $ug->generate('product/delete', ['id' => $m->reqId()]),
                    attributes: [
                        'title'   => $t->translate('delete'),
                        'onclick' => "return confirm('" . $t->translate('delete.record.warning') . "');",
                        'class'   => 'btn btn-outline-danger btn-sm',
                        'hx-boost' => 'false',
                    ],
                ),
            ],
        );
    }
}
