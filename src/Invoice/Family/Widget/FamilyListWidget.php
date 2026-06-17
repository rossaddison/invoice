<?php

declare(strict_types=1);

namespace App\Invoice\Family\Widget;

use App\Infrastructure\Persistence\Family\Family;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Input\Checkbox;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Widget\Widget;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\Base\DataContext;
use Yiisoft\Yii\DataView\GridView\Column\CheckboxColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;
use Yiisoft\Yii\DataView\Pagination\PaginationWidgetInterface;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

final class FamilyListWidget extends Widget
{
    private const string DOM_ID = 'FamilyGridView';
    private const string ROUTE_INDEX = 'family/index';
    private const string DIV_FLOAT = 'float-end m-3';

    private ?OffsetPaginator $paginator = null;
    private ?CategoryPrimaryRepository $cpR = null;
    private ?CategorySecondaryRepository $csR = null;
    private string|\Stringable $csrf = '';
    private string $gridSummary = '';
    private string $sortString = '-id';

    public function __construct(
        private readonly CurrentRoute $currentRoute,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function withPaginator(OffsetPaginator $paginator): static
    {
        $new = clone $this;
        $new->paginator = $paginator;
        return $new;
    }

    public function withCpR(CategoryPrimaryRepository $cpR): static
    {
        $new = clone $this;
        $new->cpR = $cpR;
        return $new;
    }

    public function withCsR(CategorySecondaryRepository $csR): static
    {
        $new = clone $this;
        $new->csR = $csR;
        return $new;
    }

    public function withCsrf(string|\Stringable $csrf): static
    {
        $new = clone $this;
        $new->csrf = $csrf;
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

    #[\Override]
    public function render(): string
    {
        if ($this->paginator === null || $this->cpR === null || $this->csR === null) {
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

        return GridView::widget()
            ->containerAttributes(['id' => self::DOM_ID, 'class' => 'position-relative'])
            ->bodyRowAttributes(['class' => 'align-middle'])
            ->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-family'])
            ->columns(...$this->buildColumns())
            ->dataReader($this->paginator)
            ->urlCreator($urlCreator)
            ->paginationWidget($pagination)
            ->sortableLinkAttributes(['hx-boost' => 'true', ...$htmxAttrs])
            ->filterFormAttributes(['hx-boost' => 'true', ...$htmxAttrs])
            ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
            ->header($this->translator->translate('families'))
            ->multiSort(true)
            ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
            ->summaryTemplate('<div class="d-flex align-items-center">' . $this->gridSummary . '</div>')
            ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
            ->noResultsText($this->translator->translate('no.records'))
            ->toolbar($this->buildToolbarString())
            ->render();
    }

    private function buildToolbarString(): string
    {
        $ug = $this->urlGenerator;
        $t  = $this->translator;

        $generateProductsButton = (new A())
            ->addAttributes(['type' => 'reset'])
            ->addClass('btn btn-success')
            ->href('#')
            ->content('☑️' . $t->translate('generate') . ' ' . $t->translate('products') . '🏭')
            ->id('btn-generate-products')
            ->render();

        $toolbarFilter = (new A())
            ->addAttributes(['type' => 'reset'])
            ->addClass('family_filters_submit btn btn-info me-1')
            ->content(new I()->addClass('bi bi-bootstrap-reboot'))
            ->href('#family_filters_submit')
            ->id('family_filters_submit')
            ->render();

        $toolbarReset = (new A())
            ->addAttributes(['type' => 'reset'])
            ->addClass('btn btn-danger me-1 ajax-loader')
            ->content(new I()->addClass('bi bi-bootstrap-reboot'))
            ->href($ug->generate($this->currentRoute->getName() ?? self::ROUTE_INDEX))
            ->id('btn-reset')
            ->render();

        $addFamily = (new A())
            ->href($ug->generate('family/add'))
            ->addClass('btn btn-info')
            ->addAttributes(['hx-boost' => 'false'])
            ->content('➕')
            ->render();

        return (new Form())
                ->post($ug->generate(self::ROUTE_INDEX))
                ->csrf($this->csrf)
                ->open()
            . $addFamily
            . (new Div())->addClass(self::DIV_FLOAT)->content($generateProductsButton)->encode(false)->render()
            . (new Div())->addClass(self::DIV_FLOAT)->content($toolbarFilter)->encode(false)->render()
            . (new Div())->addClass(self::DIV_FLOAT)->content($toolbarReset)->encode(false)->render()
            . (new Form())->close();
    }

    /** @return list<CheckboxColumn|DataColumn|ActionColumn> */
    private function buildColumns(): array
    {
        \assert($this->cpR !== null && $this->csR !== null);

        $cpR = $this->cpR;
        $csR = $this->csR;
        $ug  = $this->urlGenerator;
        $t   = $this->translator;

        return [
            new CheckboxColumn(
                content: static function (Checkbox $input, DataContext $context): string {
                    $family = $context->data;
                    if (($family instanceof Family) && (($id = $family->reqId()) > 0)) {
                        return $input
                            ->addAttributes([
                                'id'             => $id,
                                'name'           => 'family_ids[]',
                                'data-bs-toggle' => 'tooltip',
                            ])
                            ->value($id)
                            ->disabled(
                                null !== $family->getFamilyCommalist()
                                && null !== $family->getFamilyProductprefix()
                                    ? false : true
                            )
                            ->render();
                    }
                    return '';
                },
                multiple: false,
            ),
            new DataColumn(
                property: 'id',
                header: $t->translate('id'),
                content: static fn (Family $m): string => Html::encode($m->reqId()),
                withSorting: true,
            ),
            new DataColumn(
                property: 'family_name',
                header: $t->translate('family'),
                content: static fn (Family $m): string =>
                    '<span data-family-name>' . Html::encode($m->getFamilyName() ?? '') . '</span>',
                encodeContent: false,
                withSorting: true,
            ),
            new DataColumn(
                property: 'family_commalist',
                header: $t->translate('family.comma.list'),
                content: static fn (Family $m): string =>
                    '<span data-family-commalist>' . Html::encode($m->getFamilyCommalist() ?? '') . '</span>',
                encodeContent: false,
                withSorting: true,
            ),
            new DataColumn(
                property: 'family_productprefix',
                header: $t->translate('family.product.prefix'),
                content: static fn (Family $m): string =>
                    '<span data-family-prefix>' . Html::encode($m->getFamilyProductprefix() ?? '') . '</span>',
                encodeContent: false,
                withSorting: true,
            ),
            new DataColumn(
                'category_primary_id',
                header: $t->translate('category.primary'),
                content: static function (Family $m) use ($cpR, $t): string {
                    $cp = $cpR->repoCategoryPrimaryQuery($m->getCategoryPrimaryId());
                    return null !== $cp
                        ? ($cp->getName() ?? $t->translate('not.set'))
                        : $t->translate('not.set');
                },
            ),
            new DataColumn(
                'category_secondary_id',
                header: $t->translate('category.secondary'),
                content: static function (Family $m) use ($csR, $t): string {
                    $cs = $csR->repoCategorySecondaryQuery($m->getCategorySecondaryId());
                    return null !== $cs
                        ? ($cs->getName() ?? $t->translate('not.set'))
                        : $t->translate('not.set');
                },
            ),
            $this->buildActionColumn($ug, $t),
        ];
    }

    private function buildActionColumn(UrlGeneratorInterface $ug, TranslatorInterface $t): ActionColumn
    {
        return new ActionColumn(
            before: Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
            after: Html::closeTag('div'),
            buttons: [
                new ActionButton(
                    content: '🔎',
                    url: static fn (Family $m): string =>
                        $ug->generate('family/view', ['id' => $m->reqId()]),
                    attributes: [
                        'class'          => 'btn btn-outline-secondary btn-sm',
                        'data-bs-toggle' => 'tooltip',
                        'title'          => $t->translate('view'),
                        'hx-boost'       => 'false',
                    ],
                ),
                new ActionButton(
                    content: '✎',
                    url: static fn (Family $m): string =>
                        $ug->generate('family/edit', ['id' => $m->reqId()]),
                    attributes: [
                        'class'          => 'btn btn-outline-primary btn-sm',
                        'data-bs-toggle' => 'tooltip',
                        'title'          => $t->translate('edit'),
                        'hx-boost'       => 'false',
                    ],
                ),
                new ActionButton(
                    content: '❌',
                    url: static fn (Family $m): string =>
                        $ug->generate('family/delete', ['id' => $m->reqId()]),
                    attributes: [
                        'class'   => 'btn btn-outline-danger btn-sm',
                        'title'   => $t->translate('delete'),
                        'onclick' => "return confirm('" . $t->translate('delete.record.warning') . "');",
                        'hx-boost' => 'false',
                    ],
                ),
            ],
        );
    }
}
