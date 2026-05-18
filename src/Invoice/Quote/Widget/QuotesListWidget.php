<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Widget;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Widget\PageSizeLimiter;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button as HtmlButton;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H4;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Input\Checkbox;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Select;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Widget\Widget;
use Yiisoft\Yii\DataView\Filter\Widget\DropdownFilter;
use Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\Base\DataContext;
use Yiisoft\Yii\DataView\GridView\Column\CheckboxColumn;
use Yiisoft\Yii\DataView\GridView\Column\ColumnInterface;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;
use Yiisoft\Yii\DataView\Pagination\PaginationWidgetInterface;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

final class QuotesListWidget extends Widget
{
    private const string DOM_ID = 'QuotesGridView';
    private const string CSS_DROPDOWN_STATUS_ITEM =
        'dropdown-item quote-status-item';
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

        $qR            = $this->qR;
        $soR           = $this->soR;
        $sR            = $this->sR;
        $urlGenerator  = $this->urlGenerator;
        $translator    = $this->translator;
        $decimalPlaces = $this->decimalPlaces;
        $groupBy       = $this->groupBy;
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

        $quoteIndex      = 'quote/index';
        $currentRouteName = $this->currentRoute->getName() ?? $quoteIndex;

        [$toolbarReset, $allVisible, $enabledAddBtn, $disabledAddBtn]
            = $this->buildToolbarButtons($currentRouteName, $urlGenerator, $translator);

        $totalAmount = 0.0;
        foreach ($this->paginator->read() as $quote) {
            /** @var Quote $quote */
            $totalAmount += $quote->getQuoteAmount()?->getTotal() ?? 0.0;
        }

        $getGroupValue = $this->makeGroupValueResolver($qR, $groupBy);
        $groupTotals   = $enableGrouping
            ? $this->computeGroupTotals($this->paginator, $getGroupValue)
            : [];

        $columns = $this->buildColumns(
            $qR, $soR, $sR, $decimalPlaces, $totalAmount, $urlGenerator, $translator
        );

        $urlCreator = new UrlCreator($urlGenerator);
        $urlCreator->__invoke([], OrderHelper::stringToArray($this->sortString));

        $changeStatusDropdown = $this->buildChangeStatusDropdown($translator);
        $toolbarString = $this->buildToolbarString(
            $toolbarReset, $allVisible, $enabledAddBtn, $disabledAddBtn,
            $enableGrouping, $changeStatusDropdown
        );

        $columnCount = count(array_filter(
            $columns, static fn(ColumnInterface $col): bool => $col->isVisible()
        ));

        $tableOrTableResponsive = $this->visible ? 'table-responsive' : 'table';

        $gridView = GridView::widget()
            ->containerAttributes(['id' => self::DOM_ID, 'class' => 'position-relative'])
            ->bodyRowAttributes(['class' => 'align-left'])
            ->tableAttributes([
                'class' => $tableOrTableResponsive . ' table-bordered table-striped h-75',
                'id'    => 'table-quote',
            ])
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
            ->emptyCell($translator->translate('not.set'))
            ->emptyCellAttributes(['style' => 'color:red'])
            ->summaryAttributes([
                'class' =>
                'mt-3 me-3 summary d-flex justify-content-between align-items-center',
            ])
            ->summaryTemplate(
                '<div class="d-flex align-items-center">'
                . PageSizeLimiter::buttons(
                    $this->currentRoute, $sR, $translator, $urlGenerator, 'quote'
                )
                . ' ' . $this->gridSummary . '</div>'
            )
            ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
            ->noResultsText($translator->translate('no.records'))
            ->toolbar($toolbarString);

        if ($enableGrouping) {
            $gridView = $this->applyGrouping(
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

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function buildToolbarButtons(
        string $currentRouteName,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
    ): array {
        $toolbarReset = (new A())
            ->addAttributes(['type' => 'reset'])
            ->addClass('btn btn-primary me-1 ajax-loader')
            ->content(new I()->addClass('bi bi-bootstrap-reboot'))
            ->href($urlGenerator->generate($currentRouteName))
            ->id('btn-reset')
            ->render();

        $allVisible = (new A())
            ->addAttributes([
                'type'           => 'reset',
                'data-bs-toggle' => 'tooltip',
                'title'          => $translator->translate('hide.or.unhide.columns'),
            ])
            ->addClass('btn btn-warning me-1 ajax-loader')
            ->content('↔️')
            ->href($urlGenerator->generate('setting/visible', ['origin' => 'quote']))
            ->id('btn-all-visible')
            ->render();

        $btnStyle = 'text-decoration:none; background-color: #ffffff !important;'
        . ' border: 2px solid #b19cd9 !important; color: #b19cd9 !important; font-weight: 500;';

        $enabledAddBtn = (new A())
            ->addAttributes([
                'class'          => 'btn',
                'data-bs-toggle' => 'modal',
                'style'          => $btnStyle,
            ])
            ->content('➕')
            ->href('#modal-add-quote')
            ->id('btn-enabled-quote-add-button')
            ->render();

        $disabledAddBtn = (new A())
            ->addAttributes([
                'class'          => 'btn',
                'data-bs-toggle' => 'tooltip',
                'title'          => $translator->translate('add.client'),
                'disabled'       => 'disabled',
                'style'          => $btnStyle . ' opacity: 0.5;',
            ])
            ->content('➕')
            ->href('#modal-add-quote')
            ->id('btn-disabled-quote-add-button')
            ->render();

        return [$toolbarReset, $allVisible, $enabledAddBtn, $disabledAddBtn];
    }

    private function buildCheckboxColumn(TranslatorInterface $translator): CheckboxColumn
    {
        return new CheckboxColumn(
            content: static function (Checkbox $input, DataContext $context)
                use ($translator): string {
                $quote = $context->data;
                if (!$quote instanceof Quote) {
                    return '';
                }
                $id = $quote->reqId();
                return $input
                    ->addAttributes([
                        'id'             => $id,
                        'name'           => 'checkbox[]',
                        'data-bs-toggle' => 'tooltip',
                        'title'          => ($quote->getQuoteAmount()?->getTotal() ?? 0) == 0
                            ? $translator->translate(
                                'index.checkbox.add.some.items.to.enable')
                            : '',
                    ])
                    ->value($id)
                    ->disabled(($quote->getQuoteAmount()?->getTotal() ?? 0) > 0 ? false : true)
                    ->render();
            },
            multiple: true,
        );
    }

    private function buildActionColumn(
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
    ): ActionColumn {
        return new ActionColumn(
            before: Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
            after: Html::closeTag('div'),
            buttons: [
                new ActionButton(
                    content: '🔎',
                    url: static fn(Quote $model): string =>
                        $urlGenerator->generate('quote/view', ['id' => $model->reqId()]),
                    attributes: [
                        'data-bs-toggle' => 'tooltip',
                        'title'          => $translator->translate('view'),
                        'class'          => 'btn btn-outline-primary btn-sm',
                    ],
                ),
                new ActionButton(
                    content: '✎',
                    url: static fn(Quote $model): string =>
                        $urlGenerator->generate('quote/edit', ['id' => $model->reqId()]),
                    attributes: [
                        'data-bs-toggle' => 'tooltip',
                        'title'          => $translator->translate('edit'),
                        'class'          => 'btn btn-outline-warning btn-sm',
                    ],
                ),
                new ActionButton(
                    content: static fn(Quote $model): string =>
                        ($model->getSoId() == 0 && $model->getInvId() == 0) ? '❌' : '🚫',
                    url: static function (Quote $model) use ($urlGenerator): string {
                        return $model->getSoId() == 0 && $model->getInvId() == 0
                            ? $urlGenerator->generate('quote/delete', ['id' => $model->reqId()])
                            : '';
                    },
                    attributes: static function (Quote $model) use ($translator): array {
                        if ($model->getSoId() == 0 && $model->getInvId() == 0) {
                            return [
                                'onclick'        => 'return confirm('
                                    . (string) json_encode(
                                    $translator->translate('delete.record.warning'))
                                    . ');',
                                'data-bs-toggle' => 'tooltip',
                                'title'          =>
                                   $translator->translate('delete.quote.single'),
                                'class'          => 'btn btn-outline-danger btn-sm',
                            ];
                        }
                        return [
                            'disabled'       => true,
                            'data-bs-toggle' => 'tooltip',
                            'title'          =>
                                $translator->translate('delete.quote.derived'),
                            'class'          => 'btn btn-secondary btn-sm disabled',
                        ];
                    },
                ),
            ],
        );
    }

    private function buildStatusColumn(QR $qR, TranslatorInterface $translator): DataColumn
    {
        return new DataColumn(
            property: 'filterStatus',
            header: '<span data-bs-toggle="tooltip" data-bs-html="false" title="'
                . Html::encode('🌎 ' . $translator->translate('all') . '<br/>🗋 '
                    . $translator->translate('draft') . '<br/>📨 '
                    . $translator->translate('sent') . '<br/>👀 '
                    . $translator->translate('viewed') . '<br/>✅ '
                    . $translator->translate('approved') . '<br/>❌ '
                    . $translator->translate('rejected') . '<br/>🚫 '
                    . $translator->translate('canceled')) . '">📊 '
                . $translator->translate('status') . '</span>',
            encodeHeader: false,
            content: static function (Quote $model) use ($qR): string {
                $statusId = $model->reqStatusId();
                $label    = $qR->getSpecificStatusArrayLabel((string) $statusId);
                $class    = $qR->getSpecificStatusArrayClass((string) $statusId);
                return '<span data-bs-toggle="tooltip" title="'
                    . Html::encode($label) . '" class="badge text-bg-' . $class . '">'
                    . Html::encode($label) . '</span>';
            },
            filter: DropdownFilter::widget()
                ->addAttributes(['name' => 'status', 'class' => 'native-reset'])
                ->optionsData($this->optionsDataStatusDropDownFilter),
            encodeContent: false,
            withSorting: true,
            visible: true,
        );
    }

    private function buildSoColumn(SOR $soR, UrlGeneratorInterface $urlGenerator): DataColumn
    {
        return new DataColumn(
            'so_id',
            header: $this->translator->translate('salesorder.number.status'),
            content: static function (Quote $model) use ($soR, $urlGenerator): A {
                $soId = $model->getSoId();
                $so   = $soId > 0 ? $soR->repoSalesOrderUnloadedquery($soId) : null;
                if (null !== $so) {
                    $number   = $so->getNumber();
                    $statusId = $so->getStatusId();
                    if (null !== $number && $statusId > 0) {
                        return (new A())
                            ->addAttributes([
                                'style' => 'text-decoration:none',
                                'class' => 'badge text-bg-'
                                    . $soR->getSpecificStatusArrayClass($statusId),
                            ])
                            ->content($number
                                    . ' '
                                    . $soR->getSpecificStatusArrayLabel((string) $statusId))
                            ->href($urlGenerator->generate('salesorder/view', ['id' => $soId]));
                    }
                    if ($model->getSoId() === 0 && $model->reqStatusId() === 7 && $statusId > 0) {
                        return (new A())
                            ->addAttributes(['class' => 'btn btn-warning'])
                            ->content($soR->getSpecificStatusArrayLabel((string) $statusId))
                            ->href('');
                    }
                }
                return new A();
            },
            encodeContent: false,
        );
    }

    private function buildQuoteNumberColumn(
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
    ): DataColumn {
        return new DataColumn(
            property: 'filterQuoteNumber',
            header: $translator->translate('quote.number'),
            content: static fn(Quote $model): A =>
                Html::a(
                    $model->getNumber() ?? '#',
                    $urlGenerator->generate('quote/view', ['id' => $model->reqId()]),
                    ['style' => 'text-decoration:none'],
                ),
            encodeContent: false,
            filter: TextInputFilter::widget()->addAttributes(['style' => 'max-width: 80px']),
        );
    }

    private function buildClientColumn(TranslatorInterface $translator): DataColumn
    {
        return new DataColumn(
            property: 'filterClient',
            header: $translator->translate('client'),
            content: static function (Quote $model): string {
                $clientName    = $model->getClient()?->getClientName();
                $clientSurname = $model->getClient()?->getClientSurname();
                if (null !== $clientName && null !== $clientSurname) {
                    return Html::encode($clientName . str_repeat(' ', 2) . $clientSurname);
                }
                return '';
            },
            encodeContent: false,
            filter: DropdownFilter::widget()
                ->addAttributes(['name' => 'filterClient', 'class' => 'native-reset'])
                ->optionsData($this->optionsDataClientsDropdownFilter),
            withSorting: false,
        );
    }

    private function buildTotalColumn(SR $sR, int $decimalPlaces, float $totalAmount): DataColumn
    {
        return new DataColumn(
            property: 'filterQuoteAmountTotal',
            header: $this->translator->translate('total')
                . ' ➡️ '
                . $sR->getSetting('currency_symbol'),
            content: static function (Quote $model) use ($decimalPlaces): Label {
                $quoteTotal = $model->getQuoteAmount()?->getTotal();
                return (new Label())
                    ->attributes([
                        'class' => ($model->getQuoteAmount()?->getTotal() ?? 0.0) > 0.0
                            ? 'label label-success'
                            : 'label label-warning',
                    ])
                    ->content(Html::encode(
                        null !== $quoteTotal
                            ? number_format($quoteTotal, $decimalPlaces)
                            : number_format(0, $decimalPlaces)
                    ));
            },
            encodeContent: false,
            filter: TextInputFilter::widget()
                ->addAttributes(['style' => 'max-width: 50px',
                    'class' => 'native-reset']),
            withSorting: false,
            footer: (new Span())
                ->addAttributes(['style' =>
                    'text-align: right; display: block; width: 100%;'])
                ->content(number_format($totalAmount, $decimalPlaces))
                ->render(),
        );
    }

    /**
     * @return ColumnInterface[]
     */
    private function buildColumns(
        QR $qR,
        SOR $soR,
        SR $sR,
        int $decimalPlaces,
        float $totalAmount,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
    ): array {
        return [
            $this->buildCheckboxColumn($translator),
            new DataColumn(
                'id',
                header: $translator->translate('id'),
                content: static fn(Quote $model): string => (string) $model->reqId(),
                withSorting: true,
            ),
            $this->buildActionColumn($urlGenerator, $translator),
            $this->buildStatusColumn($qR, $translator),
            $this->buildSoColumn($soR, $urlGenerator),
            $this->buildQuoteNumberColumn($urlGenerator, $translator),
            new DataColumn(
                'date_created',
                header: $translator->translate('date.created'),
                content: static fn(Quote $model): string =>
                    $model->getDateCreated()->format('Y-m-d'),
                withSorting: true,
            ),
            new DataColumn(
                'date_expires',
                content: static fn(Quote $model): string =>
                    $model->getDateExpires()->format('Y-m-d'),
                withSorting: true,
            ),
            new DataColumn(
                'date_required',
                content: static fn(Quote $model): string =>
                    $model->getDateRequired()->format('Y-m-d'),
            ),
            $this->buildClientColumn($translator),
            $this->buildTotalColumn($sR, $decimalPlaces, $totalAmount),
        ];
    }

    private function buildChangeStatusDropdown(TranslatorInterface $translator): string
    {
        return Html::openTag('div', ['class' => 'btn-group me-2', 'role' => 'group'])
            . Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            . Html::openTag('button', [
                'type'               => 'button',
                'class'              => 'btn btn-success dropdown-toggle',
                'data-bs-toggle'     => 'dropdown',
                'aria-expanded'      => 'false',
                'id'                 => 'btn-quote-change-status',
                'data-bs-auto-close' => 'true',
            ])
            . '☑️ ' . Html::encode($translator->translate('status'))
            . Html::closeTag('button')
            . Html::openTag('ul', [
                'class'           => 'dropdown-menu',
                'aria-labelledby' => 'btn-quote-change-status',
            ])
            . Html::openTag('li')
            . Html::tag('a', '🗋 ' . Html::encode($translator->translate('draft')),
                ['class' => self::CSS_DROPDOWN_STATUS_ITEM,
                    'data-status-id' => '1', 'href' => '#'])
            . Html::closeTag('li')
            . Html::openTag('li')
            . Html::tag('a', '📨 ' . Html::encode($translator->translate('sent')),
                ['class' => self::CSS_DROPDOWN_STATUS_ITEM,
                    'data-status-id' => '2', 'href' => '#'])
            . Html::closeTag('li')
            . Html::openTag('li')
            . Html::tag('a', '👀 ' . Html::encode($translator->translate('viewed')),
                ['class' => self::CSS_DROPDOWN_STATUS_ITEM,
                    'data-status-id' => '3', 'href' => '#'])
            . Html::closeTag('li')
            . Html::openTag('li')
            . Html::tag('a', '✅ ' . Html::encode($translator->translate('approved')),
                ['class' => self::CSS_DROPDOWN_STATUS_ITEM,
                    'data-status-id' => '4', 'href' => '#'])
            . Html::closeTag('li')
            . Html::openTag('li')
            . Html::tag('a', '❌ ' . Html::encode($translator->translate('rejected')),
                ['class' => self::CSS_DROPDOWN_STATUS_ITEM,
                    'data-status-id' => '5', 'href' => '#'])
            . Html::closeTag('li')
            . Html::openTag('li')
            . Html::tag('a', '🚫 ' . Html::encode($translator->translate('canceled')),
                ['class' => self::CSS_DROPDOWN_STATUS_ITEM,
                    'data-status-id' => '6', 'href' => '#'])
            . Html::closeTag('li')
            . Html::closeTag('ul')
            . Html::closeTag('div')
            . Html::closeTag('div');
    }

    private function buildToolbarString(
        string $toolbarReset,
        string $allVisible,
        string $enabledAddBtn,
        string $disabledAddBtn,
        bool $enableGrouping,
        string $changeStatusDropdown,
    ): string {
        $translator   = $this->translator;
        $urlGenerator = $this->urlGenerator;
        $quoteIndex   = 'quote/index';

        $collapseExpandButtons = $enableGrouping
            ? (new Div())
                ->addClass('btn-group ms-2')
                ->addAttributes(['role' => 'group'])
                ->content(
                    (new HtmlButton())
                        ->type('button')
                        ->addClass('btn btn-outline-secondary btn-sm')
                        ->addAttributes(['onclick' => 'toggleAllGroups(false)',
                            'title' => 'Collapse All Groups'])
                        ->content(new I()->addClass('bi bi-chevron-up'))
                    . (new HtmlButton())
                        ->type('button')
                        ->addClass('btn btn-outline-secondary btn-sm')
                        ->addAttributes(['onclick' => 'toggleAllGroups(true)',
                            'title' => 'Expand All Groups'])
                        ->content(new I()->addClass('bi bi-chevron-down'))
                )
                ->encode(false)
                ->render()
            : '';

        $groupBySelect = (new Div())
            ->addClass('btn-group ms-3')
            ->addAttributes(['role' => 'group'])
            ->content(
                (new Label())
                    ->addClass('btn btn-outline-secondary active bi bi-collection me-1')
                    ->content(' ' . $translator->translate('group.by') . ':')
                . (new Select())
                    ->addClass('form-select group-by-select')
                    ->addAttributes([
                        'style'         => 'max-width: 150px;',
                        'data-base-url' => $urlGenerator->generate($quoteIndex),
                    ])
                    ->optionsData([
                        'none'         => $translator->translate('grouping.none'),
                        'status'       => $translator->translate('status'),
                        'client'       => $translator->translate('client'),
                        'client_group' => $translator->translate('client.group'),
                        'month'        => $translator->translate('month'),
                        'year'         => $translator->translate('year'),
                        'date'         => $translator->translate('date'),
                        'amount_range' => 'Amount Range',
                    ])
                    ->value($this->groupBy)
            )
            ->encode(false)
            ->render();

        return (new Form())->post(
                $urlGenerator->generate($quoteIndex))->csrf($this->csrf)->open()
            . (new Div())->addClass('float-start')->content(
                (new H4())
                    ->addClass('me-3 d-inline-block')
                    ->content($translator->translate('quote'))
                . Html::openTag('div', ['class' => 'btn-group me-2', 'role' => 'group'])
                . $allVisible
                . $toolbarReset
                . ($this->clientCount == 0 ? $disabledAddBtn : $enabledAddBtn)
                . Html::closeTag('div')
                . $changeStatusDropdown
                . $groupBySelect
                . $collapseExpandButtons
            )->encode(false)->render()
            . (new Form())->close();
    }

    /** @return \Closure(Quote): string */
    private function makeGroupValueResolver(QR $qR, string $groupBy): \Closure
    {
        return static function (Quote $quote) use ($qR, $groupBy): string {
            return match ($groupBy) {
                'client'       => $quote->getClient()?->getClientFullName() ??
                    'Unknown Client',
                'status'       =>
                    $qR->getSpecificStatusArrayLabel((string) $quote->reqStatusId()),
                'month'        => $quote->getDateCreated()->format('Y-m'),
                'year'         => $quote->getDateCreated()->format('Y'),
                'date'         => $quote->getDateCreated()->format('Y-m-d'),
                'client_group' => $quote->getClient()?->getClientGroup() ?? 'No Group',
                'amount_range' => match (true) {
                    ($quote->getQuoteAmount()?->getTotal() ?? 0) < 100  => '< $100',
                    ($quote->getQuoteAmount()?->getTotal() ?? 0) < 500  => '$100 - $500',
                    ($quote->getQuoteAmount()?->getTotal() ?? 0) < 1000 => '$500 - $1000',
                    default => '> $1000',
                },
                default => 'No Group',
            };
        };
    }

    /**
     * @param callable(Quote): string $getGroupValue
     * @return array<string, array{count: int, total: float}>
     */
    private function computeGroupTotals(OffsetPaginator $paginator, callable $getGroupValue): array
    {
        $groupTotals = [];
        foreach ($paginator->read() as $quote) {
            /** @var Quote $quote */
            $gv = $getGroupValue($quote);
            if (!isset($groupTotals[$gv])) {
                $groupTotals[$gv] = ['count' => 0, 'total' => 0.0];
            }
            $groupTotals[$gv]['count']++;
            $groupTotals[$gv]['total'] += $quote->getQuoteAmount()?->getTotal() ?? 0.0;
        }
        return $groupTotals;
    }

    /**
     * @param callable(Quote): string $getGroupValue
     */
    private function applyGrouping(
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
            static function (array|object $quote) use (
                &$previousGroupValue,
                $getGroupValue,
                $groupTotals,
                $decimalPlaces,
                $groupBy,
                $sR,
                $columnCount,
            ): ?\Yiisoft\Html\Tag\Tr {
                /** @var Quote $quote */
                $currentGroupValue = $getGroupValue($quote);
                if ($previousGroupValue === $currentGroupValue) {
                    return null;
                }
                $previousGroupValue = $currentGroupValue;
                /** @var array{count: int, total: float} $groupData */
                $groupData      = $groupTotals[$currentGroupValue] ?? ['count' => 0, 'total' => 0.0];
                $currencySymbol = $sR->getSetting('currency_symbol');
                return \Yiisoft\Html\Html::tr()
                    ->addClass('group-header bg-secondary text-white fw-bold group-collapsible')
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
                                . '<span class="fs-5">' . Html::encode(ucfirst($groupBy))
                                    . ': ' . Html::encode($currentGroupValue) . '</span>'
                                . '<span class="badge bg-primary ms-2">' . $groupData['count']
                                    . ' quote' . ($groupData['count'] === 1 ? '' : 's') . '</span>'
                                . '</div>'
                                . '<div class="text-end">'
                                . '<small class="d-block">Total: <strong>'
                                    . number_format($groupData['total'], $decimalPlaces)
                                    . ' ' . $currencySymbol . '</strong></small>'
                                . '</div>'
                                . '</div>'
                            )
                            ->encode(false)
                    );
            }
        );
    }
}
