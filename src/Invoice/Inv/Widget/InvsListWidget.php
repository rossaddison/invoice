<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvSentLog\InvSentLog;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\InvSentLog\InvSentLogRepository as ISLR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Widget\GridComponents;
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

final class InvsListWidget extends Widget
{
    private const string DOM_ID = 'InvsGridView';
    private const string ROUTE_INDEX = 'inv/index';
    private const string ROUTE_EDIT = 'inv/edit';
    private const string FILTER_CLASS = 'native-reset inv-filter';
    private const string AMOUNT_FILTER_CLASS = 'native-reset inv-amount-filter';
    private const string AMOUNT_FILTER_STYLE =
        'text-align: right; display: block; width: 100%;';

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
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    private array $optionsInvNumberDropDownFilter = [];
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    private array $optionsCreditInvNumberDropDownFilter = [];
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    private array $optionsFamilyNameDropDownFilter = [];
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    private array $optionsClientsDropDownFilter = [];
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    private array $optionsClientGroupDropDownFilter = [];
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    private array $optionsYearMonthDropDownFilter = [];
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    private array $optionsStatusDropDownFilter = [];

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

    /** @psalm-param array<array-key, array<array-key, string>|string> $options */
    public function withOptionsInvNumberDropDownFilter(array $options): static
    {
        $new = clone $this;
        $new->optionsInvNumberDropDownFilter = $options;
        return $new;
    }

    /** @psalm-param array<array-key, array<array-key, string>|string> $options */
    public function withOptionsCreditInvNumberDropDownFilter(array $options): static
    {
        $new = clone $this;
        $new->optionsCreditInvNumberDropDownFilter = $options;
        return $new;
    }

    /** @psalm-param array<array-key, array<array-key, string>|string> $options */
    public function withOptionsFamilyNameDropDownFilter(array $options): static
    {
        $new = clone $this;
        $new->optionsFamilyNameDropDownFilter = $options;
        return $new;
    }

    /** @psalm-param array<array-key, array<array-key, string>|string> $options */
    public function withOptionsClientsDropDownFilter(array $options): static
    {
        $new = clone $this;
        $new->optionsClientsDropDownFilter = $options;
        return $new;
    }

    /** @psalm-param array<array-key, array<array-key, string>|string> $options */
    public function withOptionsClientGroupDropDownFilter(array $options): static
    {
        $new = clone $this;
        $new->optionsClientGroupDropDownFilter = $options;
        return $new;
    }

    /** @psalm-param array<array-key, array<array-key, string>|string> $options */
    public function withOptionsYearMonthDropDownFilter(array $options): static
    {
        $new = clone $this;
        $new->optionsYearMonthDropDownFilter = $options;
        return $new;
    }

    /** @psalm-param array<array-key, array<array-key, string>|string> $options */
    public function withOptionsStatusDropDownFilter(array $options): static
    {
        $new = clone $this;
        $new->optionsStatusDropDownFilter = $options;
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

        $totalAmount  = 0.0;
        $totalPaid    = 0.0;
        $totalBalance = 0.0;
        foreach ($this->paginator->read() as $invoice) {
            /** @var Inv $invoice */
            $totalAmount  += $invoice->getInvAmount()->getTotal()   ?? 0.0;
            $totalPaid    += $invoice->getInvAmount()->getPaid()    ?? 0.0;
            $totalBalance += $invoice->getInvAmount()->getBalance() ?? 0.0;
        }

        $getGroupValue = $this->makeGroupValueResolver($groupBy);
        $groupTotals   = $enableGrouping
            ? $this->computeGroupTotals($this->paginator, $getGroupValue)
            : [];

        $columns     = $this->buildColumns($totalAmount, $totalPaid, $totalBalance);
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
            ->toolbar($this->buildToolbarString($enableGrouping));

        if ($enableGrouping) {
            $gridView = $this->applyGrouping(
                $gridView, $getGroupValue, $groupTotals,
                $this->decimalPlaces, $groupBy, $columnCount
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

    // -------------------------------------------------------------------------
    // Toolbar
    // -------------------------------------------------------------------------

    private function buildToolbarString(bool $enableGrouping): string
    {
        \assert($this->iR !== null && $this->sR !== null);
        $t  = $this->translator;
        $ug = $this->urlGenerator;
        $iR = $this->iR;
        $sR = $this->sR;

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
            ->href($ug->generate('setting/visible', ['origin' => 'inv']))
            ->id('btn-all-visible')
            ->render();

        $copyMultiple = (new A())
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'modal',
                'title' => Html::encode($t->translate('copy.invoice'))])
            ->addClass('btn btn-success')
            ->href('#modal-copy-inv-multiple')
            ->content('☑️' . $t->translate('copy.invoice'))
            ->id('btn-modal-copy-inv-multipe')
            ->render();

        $markAsSent = (new A())
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                'title' => Html::encode($t->translate('sent'))])
            ->addClass('btn btn-success')
            ->content('☑️' . $t->translate('sent') . $iR->getSpecificStatusArrayEmoji(2))
            ->id('btn-mark-as-sent')
            ->render();

        $markSentAsDraft = $sR->getSetting('disable_read_only') === '0'
            ? (new A())
                ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                    'title' => Html::encode(
                        $t->translate('security.disable.read.only.info')),
                    'disabled' => 'disabled', 'style' => 'text-decoration:none'])
                ->addClass('btn btn-success')
                ->content('☑️' . $t->translate('draft') . $iR->getSpecificStatusArrayEmoji(1))
                ->id('btn-mark-sent-as-draft')
                ->render()
            : (new A())
                ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                    'title' => Html::encode($t->translate('draft')),
                    'style' => 'text-decoration:none'])
                ->addClass('btn btn-success')
                ->content('☑️' . $t->translate('draft') . $iR->getSpecificStatusArrayEmoji(1))
                ->id('btn-mark-sent-as-draft')
                ->render();

        $markRecurring = (new A())
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'modal'])
            ->addClass('btn btn-info')
            ->href('#create-recurring-multiple')
            ->content('☑️' . $t->translate('recurring') . '♻️')
            ->render();

        $addBtn = $this->clientCount > 0
            ? (new A())
                ->addAttributes(['class' => 'btn btn-info', 'data-bs-toggle' => 'modal',
                    'style' => 'text-decoration:none'])
                ->content('➕')
                ->href('#modal-add-inv')
                ->id('btn-enabled-invoice-add-button')
                ->render()
            : (new A())
                ->addAttributes(['class' => 'btn btn-info', 'data-bs-toggle' => 'tooltip',
                    'title' => $t->translate('add.client'),
                    'disabled' => 'disabled', 'style' => 'text-decoration:none'])
                ->content('➕')
                ->href('#modal-add-inv')
                ->id('btn-disabled-invoice-add-button')
                ->render();

        $groupBySelect = (new Div())
            ->addClass('btn-group ms-3')
            ->addAttributes(['role' => 'group'])
            ->content(
                (new Label())
                    ->addClass('btn btn-outline-secondary active bi bi-collection me-1')
                    ->content(' ' . $t->translate('group.by') . ':')
                . (new Select())
                    ->addClass('form-select group-by-select')
                    ->addAttributes([
                        'style'         => 'max-width: 150px;',
                        'data-base-url' => $ug->generate(self::ROUTE_INDEX),
                    ])
                    ->optionsData([
                        'none'         => $t->translate('grouping.none'),
                        'status'       => $t->translate('status'),
                        'client'       => $t->translate('client'),
                        'client_group' => $t->translate('client.group'),
                        'month'        => $t->translate('month'),
                        'year'         => $t->translate('year'),
                        'date'         => $t->translate('date'),
                        'amount_range' => 'Amount Range',
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

        return (new Form())
                ->post($ug->generate(self::ROUTE_INDEX))
                ->csrf($this->csrf)
                ->open()
            . (new Div())->addClass('float-start')->content(
                (new H4())
                    ->addClass('me-3 d-inline-block')
                    ->content($t->translate('invoice'))
                . Html::openTag('div', ['class' => 'btn-group me-2', 'role' => 'group'])
                . $allVisible
                . $toolbarReset
                . $copyMultiple
                . $markAsSent
                . $markSentAsDraft
                . $markRecurring
                . $addBtn
                . Html::closeTag('div')
                . $groupBySelect
                . $collapseExpand
            )->encode(false)->render()
            . (new Form())->close();
    }

    // -------------------------------------------------------------------------
    // Column builders — each ≤ 3 explicit parameters
    // -------------------------------------------------------------------------

    private function buildCheckboxColumn(): CheckboxColumn
    {
        $t = $this->translator;
        return new CheckboxColumn(
            content: static function (Checkbox $input, DataContext $context)
                use ($t): string {
                $inv = $context->data;
                if (!$inv instanceof Inv) {
                    return '';
                }
                $id = $inv->reqId();
                return $input
                    ->addAttributes([
                        'id'             => $id,
                        'name'           => 'checkbox[]',
                        'data-bs-toggle' => 'tooltip',
                        'title'          => $inv->getInvAmount()->getTotal() == 0
                            ? $t->translate(
                                'index.checkbox.add.some.items.to.enable')
                            : '',
                    ])
                    ->value($id)
                    ->disabled($inv->getInvAmount()->getTotal() > 0 ? false : true)
                    ->render();
            },
            multiple: true,
        );
    }

    private function buildEditColumn(SR $sR): ActionColumn
    {
        $ug = $this->urlGenerator;
        $t  = $this->translator;
        return new ActionColumn(
            header: '',
            before: Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
            after:  Html::closeTag('div'),
            buttons: [
                new ActionButton(
                    content: static function (Inv $inv) use ($sR): string {
                        $ro  = $inv->getIsReadOnly() ? 'true' : 'false';
                        $dRO = $sR->getSetting('disable_read_only');
                        $st  = (string) $inv->reqStatusId();
                        /** @psalm-var array<string, array<string, array<string, string>>> $map */
                        $map = [
                            'false' => ['0' => ['1' => '✎'], '1' => ['1' => '❗✎']],
                            'true'  => ['0' => ['2' => '🚫'], '1' => ['2' => '❗']],
                        ];
                        return $map[$ro][$dRO][$st] ?? '🚫';
                    },
                    url: static function (Inv $inv) use ($sR, $ug): string {
                        $ro  = $inv->getIsReadOnly() ? 'true' : 'false';
                        $dRO = $sR->getSetting('disable_read_only');
                        $st  = (string) $inv->reqStatusId();
                        $id  = $inv->reqId();
                        $url = $ug->generate(self::ROUTE_EDIT, ['id' => $id]);
                        /** @psalm-var array<string, array<string, array<string, string>>> $map */
                        $map = [
                            'false' => ['0' => ['1' => $url], '1' => ['1' => $url]],
                            'true'  => ['0' => ['2' => ''], '1' => ['2' => $url]],
                        ];
                        return $map[$ro][$dRO][$st] ?? '';
                    },
                    attributes: static function (Inv $inv) use ($sR, $t): array {
                        $ro  = $inv->getIsReadOnly() ? 'true' : 'false';
                        $dRO = $sR->getSetting('disable_read_only');
                        $st  = (string) $inv->reqStatusId();
                        /** @psalm-var array<string, array<string, array<string, array<string, string>>>> $map */
                        $map = [
                            'false' => [
                                '0' => ['1' => ['data-bs-toggle' => 'tooltip',
                                    'title' => $t->translate('edit'),
                                    'class' => 'btn btn-outline-warning btn-sm']],
                                '1' => ['1' => ['data-bs-toggle' => 'tooltip',
                                    'title' => $t->translate(
                                        'security.disable.read.only.true.draft.check.and.mark'),
                                    'class' => 'btn btn-warning btn-sm']],
                            ],
                            'true' => [
                                '0' => ['2' => ['data-bs-toggle' => 'tooltip',
                                    'title' => $t->translate('sent'),
                                    'disabled' => 'disabled',
                                    'aria-disabled' => 'true',
                                    'class' => 'btn btn-secondary btn-sm disabled',
                                    'style' => 'pointer-events:none']],
                                '1' => ['2' => ['data-bs-toggle' => 'tooltip',
                                    'title' => $t->translate(
                                        'security.disable.read.only.true.sent.check.and.mark'),
                                    'class' => 'btn btn-outline-danger btn-sm']],
                            ],
                        ];
                        return $map[$ro][$dRO][$st] ?? [];
                    },
                ),
            ],
        );
    }

    private function buildPdfEmailColumn(): ActionColumn
    {
        $ug = $this->urlGenerator;
        $t  = $this->translator;
        return new ActionColumn(
            header: '',
            before: Html::openTag('div', ['class' => 'dropdown'])
                . Html::openTag('button', [
                    'class' => 'btn btn-info dropdown-toggle', 'type' => 'button',
                    'id' => 'dropdownMenuButton', 'data-toggle' => 'dropdown',
                    'aria-haspopup' => 'true', 'aria-expanded' => 'false',
                ])
                . Html::closeTag('button')
                . Html::openTag('div', [
                    'class' => 'dropdown-menu',
                    'aria-labelledby' => 'dropdownMenuButton',
                ])
                . Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
            buttons: [
                new ActionButton(
                    url: static fn(Inv $inv): string =>
                        $ug->generate('inv/pdfDashboardExcludeCf', ['id' => $inv->reqId()]),
                    attributes: ['data-bs-toggle' => 'tooltip', 'target' => '_blank',
                        'title' => $t->translate('download.pdf'),
                        'class' => 'bi bi-file-pdf btn btn-outline-danger btn-sm dropdown-item'],
                ),
                new ActionButton(
                    url: static fn(Inv $inv): string =>
                        $ug->generate('inv/pdfDashboardIncludeCf', ['id' => $inv->reqId()]),
                    attributes: ['data-bs-toggle' => 'tooltip', 'target' => '_blank',
                        'title' => $t->translate('download.pdf') . '➡️'
                            . $t->translate('custom.field'),
                        'class' => 'bi bi-file-pdf-fill btn btn-danger btn-sm dropdown-item'],
                ),
                new ActionButton(
                    content: '📨',
                    url: static function (Inv $inv) use ($ug): string {
                        return $inv->reqStatusId() !== 1
                            ? $ug->generate('inv/emailStage0', ['id' => $inv->reqId()])
                            : '';
                    },
                    attributes: ['data-bs-toggle' => 'tooltip',
                        'title' => $t->translate('email.warning.draft'),
                        'class' => 'btn btn-outline-primary btn-lg dropdown-item'],
                ),
            ],
            after: Html::closeTag('div') . Html::closeTag('div') . Html::closeTag('div'),
        );
    }

    private function buildDeleteColumn(SR $sR): ActionColumn
    {
        $ug = $this->urlGenerator;
        $t  = $this->translator;
        return new ActionColumn(
            header: '🗑️',
            before: Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
            after:  Html::closeTag('div'),
            buttons: [
                new ActionButton(
                    content: '🗑️',
                    url: static function (Inv $inv) use ($sR, $ug): string {
                        return $inv->getIsReadOnly() === false
                            && $sR->getSetting('disable_read_only') === '0'
                            && $inv->getSoId() === null
                            && $inv->getQuoteId() === null
                            ? $ug->generate('inv/delete', ['id' => $inv->reqId()])
                            : '';
                    },
                    attributes: static function (Inv $inv) use ($sR, $t): array {
                        if ($inv->getIsReadOnly() === false
                            && $sR->getSetting('disable_read_only') === '0'
                            && $inv->getSoId() === null
                            && $inv->getQuoteId() === null) {
                            return ['data-bs-toggle' => 'tooltip',
                                'title' => $t->translate('delete'),
                                'class' => 'btn btn-outline-danger btn-sm',
                                'onclick' => 'return confirm('
                                    . (string) json_encode(
                                        $t->translate('delete.record.warning'))
                                    . ');'];
                        }
                        return ['data-bs-toggle' => 'tooltip',
                            'title' => $t->translate('delete'),
                            'disabled' => 'disabled', 'aria-disabled' => 'true',
                            'class' => 'btn btn-secondary btn-sm disabled',
                            'style' => 'pointer-events:none'];
                    },
                ),
            ],
            visible: $this->visible,
        );
    }

    private function buildInvNumberColumn(): DataColumn
    {
        $ug = $this->urlGenerator;
        $t  = $this->translator;
        return new DataColumn(
            property: 'filterInvNumber',
            header: $t->translate('number'),
            content: static function (Inv $model) use ($ug): A {
                return (new A())
                    ->addAttributes([
                        'class' => 'btn btn-primary btn-lg',
                        'style' => 'text-decoration:none',
                    ])
                    ->content(($model->getNumber() ?? '#') . ' 🔍'
                        . $model->getFirstItemFamilyProductName())
                    ->href($ug->generate('inv/view', ['id' => $model->reqId()]));
            },
            encodeContent: false,
            filter: DropdownFilter::widget()
                ->addAttributes(['id' => 'filter-inv-number', 'name' => 'number',
                    'class' => self::FILTER_CLASS,
                    'aria-label' => 'Filter by invoice number',
                    'title' => $t->translate('number')])
                ->optionsData($this->optionsInvNumberDropDownFilter),
            withSorting: false,
        );
    }

    private function buildFamilyNameColumn(): DataColumn
    {
        $t = $this->translator;
        return new DataColumn(
            property: 'filterFamilyName',
            header: $t->translate('family.name'),
            content: static fn(Inv $model): string => $model->getFirstItemFamilyName(),
            encodeContent: false,
            filter: DropdownFilter::widget()
                ->addAttributes(['id' => 'filter-family-name', 'name' => 'number',
                    'class' => self::FILTER_CLASS,
                    'aria-label' => 'Filter by family name',
                    'title' => $t->translate('family.name')])
                ->optionsData($this->optionsFamilyNameDropDownFilter),
            withSorting: false,
        );
    }

    private function buildDateCreatedYearMonthColumn(): DataColumn
    {
        $t      = $this->translator;
        $header = $t->translate(
            'datetime.immutable.date.created.mySql.format.year.month.filter');
        return new DataColumn(
            property: 'filterDateCreatedYearMonth',
            header: $header,
            content: static fn(Inv $model): string =>
                $model->getDateCreated()->format('Y-m-d'),
            filter: DropdownFilter::widget()
                ->addAttributes(['id' => 'filter-year-month', 'name' => 'number',
                    'class' => self::FILTER_CLASS,
                    'aria-label' => 'Filter by year-month',
                    'title' => $header])
                ->optionsData($this->optionsYearMonthDropDownFilter),
            withSorting: false,
            visible: $this->visible,
        );
    }

    private function buildStatusColumn(IR $iR, IRR $irR, SR $sR): DataColumn
    {
        $t = $this->translator;
        $header = '<span data-bs-toggle="tooltip" data-bs-html="false" title="'
            . Html::encode(
                '🌎 ' . $t->translate('all')
                . '<br/>🗋 ' . $t->translate('draft')
                . '<br/>📨 ' . $t->translate('sent')
                . '<br/>👀 ' . $t->translate('viewed')
                . '<br/>😀 ' . $t->translate('paid')
                . '<br/>🏦 ' . $t->translate('overdue')
                . '<br/>📋 ' . $t->translate('unpaid')
                . '<br/>📃 ' . $t->translate('reminder')
                . '<br/>📄 ' . $t->translate('letter')
                . '<br/>⚖️ ' . $t->translate('claim')
                . '<br/>🏛️ ' . $t->translate('judgement')
                . '<br/>👮 ' . $t->translate('enforcement')
                . '<br/>🛑️ ' . $t->translate('credit.invoice.for.invoice')
                . '<br/>❎ ' . $t->translate('loss'))
            . '">📊 ' . $t->translate('status') . '</span>';
        return new DataColumn(
            property: 'filterStatus',
            header: $header,
            encodeHeader: false,
            content: static function (Inv $model)
                use ($iR, $irR, $sR, $t): string {
                $statusId = $model->reqStatusId();
                $emoji    = $iR->getSpecificStatusArrayEmoji($statusId);
                $label    = $iR->getSpecificStatusArrayLabel((string) $statusId);
                if ($model->getIsReadOnly()
                    && $sR->getSetting('disable_read_only') == '0') {
                    $label .= ' 🚫';
                }
                if ($irR->repoCount($model->reqId()) > 0) {
                    $label .= ' ' . $t->translate('recurring') . ' 🔄';
                }
                return '<span data-bs-toggle="tooltip" title="'
                    . Html::encode($label) . '" class="badge text-bg-'
                    . $iR->getSpecificStatusArrayClass($statusId) . '">'
                    . $emoji . Html::encode($label) . '</span>';
            },
            filter: DropdownFilter::widget()
                ->addAttributes(['id' => 'filter-status', 'name' => 'status',
                    'class' => self::FILTER_CLASS,
                    'aria-label' => 'Filter by status',
                    'title' => $t->translate('status')])
                ->optionsData($this->optionsStatusDropDownFilter),
            encodeContent: false,
            withSorting: false,
            visible: $this->visible,
        );
    }

    private function buildClientActiveColumn(): DataColumn
    {
        $ug = $this->urlGenerator;
        $t  = $this->translator;
        return new DataColumn(
            header: (new Label())->content('🔛️')
                ->addAttributes(['data-bs-toggle' => 'tooltip',
                    'title' => $t->translate('active')])
                ->render(),
            encodeHeader: false,
            property: 'id',
            content: static fn(Inv $model): A =>
                (new A())
                    ->addAttributes(['style' => 'text-decoration:none'])
                    ->href($ug->generate('client/edit', [
                        'id' => $model->getClient()?->reqId(), 'origin' => 'inv',
                    ]))
                    ->content($model->getClient()?->getClientActive() ? '✅' : '❌'),
        );
    }

    private function buildCreditNoteColumn(IR $iR): DataColumn
    {
        $ug = $this->urlGenerator;
        $t  = $this->translator;
        return new DataColumn(
            header: (new Label())->content('💳')
                ->addAttributes(['data-bs-toggle' => 'tooltip',
                    'title' => $t->translate('credit.invoice.for.invoice')])
                ->render(),
            encodeHeader: false,
            property: 'filterCreditInvNumber',
            content: static function (Inv $model) use ($iR, $ug): A {
                $parentId = $model->getCreditinvoiceParentId();
                if ($parentId !== null) {
                    $parent = $iR->repoInvUnLoadedquery($parentId);
                    if (null !== $parent) {
                        return (new A())
                            ->addAttributes(['style' => 'text-decoration:none'])
                            ->content(($parent->getNumber() ?? '#') . '💳')
                            ->href($ug->generate('inv/view', ['id' => $parentId]));
                    }
                }
                return (new A())->content('')->href('');
            },
            encodeContent: false,
            filter: DropdownFilter::widget()
                ->addAttributes(['id' => 'filter-credit-inv-number',
                    'class' => self::FILTER_CLASS,
                    'aria-label' => 'Filter by credit note parent invoice',
                    'title' => $t->translate('credit.invoice.for.invoice')])
                ->optionsData($this->optionsCreditInvNumberDropDownFilter),
            withSorting: false,
            visible: $this->visible,
        );
    }

    private function buildSentLogToggleColumn(ISLR $islR): DataColumn
    {
        $toggleAnchor = (new A())
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                'title' => $this->translator->translate('hide.or.unhide.columns')])
            ->addClass('btn btn-info me-1 ajax-loader')
            ->content('↔️')
            ->href($this->urlGenerator->generate('setting/toggleinvsentlogcolumn'))
            ->id('btn-all-visible');
        return new DataColumn(
            'invsentlogs',
            header: (new Label())->content('↔️')
                ->addAttributes(['data-bs-toggle' => 'tooltip', 'title' => 'toggle'])
                ->render(),
            encodeHeader: false,
            content: static function (Inv $model) use ($islR, $toggleAnchor): string|A {
                return $islR->repoInvSentLogEmailedCountForEachInvoice(
                    $model->reqId()) > 0 ? $toggleAnchor : '0 📧';
            },
            encodeContent: false,
        );
    }

    private function buildSentLogCountColumn(ISLR $islR): DataColumn
    {
        $ug = $this->urlGenerator;
        $t  = $this->translator;
        return new DataColumn(
            'invsentlogs',
            header: (new Label())->content('➡️📧')
                ->addAttributes(['data-bs-toggle' => 'tooltip',
                    'title' => $t->translate('email.logs.with.filter')])
                ->render(),
            encodeHeader: false,
            content: static function (Inv $model) use ($islR, $ug, $t): string|A {
                $count = $islR->repoInvSentLogEmailedCountForEachInvoice(
                    $model->reqId());
                if ($count > 0) {
                    return (new A())
                        ->addAttributes(['type' => 'reset',
                            'data-bs-toggle' => 'tooltip',
                            'title' => $t->translate('email.logs')])
                        ->addClass('btn btn-success me-1')
                        ->content((string) $count)
                        ->href($ug->generate('invsentlog/index', [],
                            ['filterInvNumber' => $model->getNumber()]))
                        ->id('btn-all-visible');
                }
                return '0 📧';
            },
            encodeContent: false,
            visible: $this->visible,
        );
    }

    private function buildSentLogTableColumn(ISLR $islR): DataColumn
    {
        $ug             = $this->urlGenerator;
        $gridComponents = $this->gridComponents;
        $t              = $this->translator;
        return new DataColumn(
            header: (new Label())->content('|||')
                ->addAttributes(['data-bs-toggle' => 'tooltip',
                    'title' => $t->translate('email.logs.table')])
                ->render(),
            encodeHeader: false,
            content: static function (Inv $model)
                use ($islR, $ug, $gridComponents): string {
                $modelId     = $model->reqId();
                $invSentLogs = $islR->repoInvSentLogForEachInvoice($modelId);
                $model->setInvSentLogs();
                /** @var InvSentLog $invSentLog */
                foreach ($invSentLogs as $invSentLog) {
                    $model->addInvSentLog($invSentLog);
                }
                return $gridComponents->gridMiniTableOfInvSentLogsForInv(
                    $model, 4, $ug);
            },
            visible: $this->visibleInvSentLogColumn,
            encodeContent: false,
        );
    }

    private function buildClientColumn(): DataColumn
    {
        $t = $this->translator;
        return new DataColumn(
            property: 'filterClient',
            header: $t->translate('client'),
            content: static fn(Inv $model): string =>
                Html::encode($model->getClient()?->getClientFullName()),
            encodeContent: false,
            filter: DropdownFilter::widget()
                ->addAttributes(['id' => 'filter-client', 'name' => 'client_id',
                    'class' => self::FILTER_CLASS,
                    'aria-label' => 'Filter by client',
                    'title' => $t->translate('client')])
                ->optionsData($this->optionsClientsDropDownFilter),
            withSorting: false,
        );
    }

    private function buildClientGroupColumn(): DataColumn
    {
        $t = $this->translator;
        return new DataColumn(
            property: 'filterClientGroup',
            header: $t->translate('client.group'),
            content: static fn(Inv $model): string =>
                $model->getClient()?->getClientGroup() ?? '',
            filter: DropdownFilter::widget()
                ->addAttributes(['id' => 'filter-client-group', 'name' => 'number',
                    'class' => self::FILTER_CLASS,
                    'aria-label' => 'Filter by client group',
                    'title' => $t->translate('client.group')])
                ->optionsData($this->optionsClientGroupDropDownFilter),
            withSorting: false,
        );
    }

    private function buildTotalColumn(SR $sR, int $dp, float $totalAmount): DataColumn
    {
        $t = $this->translator;
        return new DataColumn(
            property: 'filterInvAmountTotal',
            header: $t->translate('total') . '➡️' . $sR->getSetting('currency_symbol'),
            content: static function (Inv $model) use ($dp): Label {
                $total = $model->getInvAmount()->getTotal();
                return (new Label())
                    ->attributes(['class' => $total > 0.00
                        ? 'badge bg-success' : 'badge bg-warning text-dark'])
                    ->content(Html::encode(null !== $total
                        ? number_format($total, $dp)
                        : number_format(0, $dp)));
            },
            encodeContent: false,
            filter: TextInputFilter::widget()->addAttributes([
                'id' => 'filter-amount-total', 'class' => self::AMOUNT_FILTER_CLASS,
                'aria-label' => 'Filter by total amount',
                'title' => $t->translate('total'),
                'placeholder' => $t->translate('total')]),
            withSorting: false,
            footer: (new Span())->addClass('inv-footer-amount')
                ->addAttributes(['style' => self::AMOUNT_FILTER_STYLE])
                ->content(
                    Html::tag('small', $t->translate('total') . ':',
                        ['class' => 'inv-footer-label'])
                    . ' ' . $sR->getSetting('currency_symbol')
                    . ' ' . number_format($totalAmount, $dp))
                ->encode(false)->render(),
        );
    }

    private function buildPaidColumn(SR $sR, int $dp, float $totalPaid): DataColumn
    {
        $t = $this->translator;
        return new DataColumn(
            property: 'filterInvAmountPaid',
            header: $t->translate('paid') . '➡️' . $sR->getSetting('currency_symbol'),
            content: static function (Inv $model) use ($dp): Label {
                $paid  = $model->getInvAmount()->getPaid();
                $value = (null !== $paid && $paid > 0.00) ? $paid : 0.00;
                $class = ($model->getInvAmount()->getPaid()
                    < $model->getInvAmount()->getTotal())
                    ? 'badge bg-danger' : 'badge bg-success';
                return (new Label())
                    ->attributes(['class' => $class])
                    ->content(Html::encode(number_format($value, $dp)));
            },
            encodeContent: false,
            filter: TextInputFilter::widget()->addAttributes([
                'id' => 'filter-amount-paid', 'class' => self::AMOUNT_FILTER_CLASS,
                'aria-label' => 'Filter by paid amount',
                'title' => $t->translate('paid'),
                'placeholder' => $t->translate('paid')]),
            withSorting: false,
            footer: (new Span())->addClass('inv-footer-amount')
                ->addAttributes(['style' => self::AMOUNT_FILTER_STYLE])
                ->content(
                    Html::tag('small', $t->translate('paid') . ':',
                        ['class' => 'inv-footer-label'])
                    . ' ' . $sR->getSetting('currency_symbol')
                    . ' ' . number_format($totalPaid, $dp))
                ->encode(false)->render(),
        );
    }

    private function buildBalanceColumn(SR $sR, int $dp, float $totalBalance): DataColumn
    {
        $t = $this->translator;
        return new DataColumn(
            property: 'filterInvAmountBalance',
            header: $t->translate('balance') . '➡️' . $sR->getSetting('currency_symbol'),
            content: static function (Inv $model) use ($dp): Label {
                $bal   = $model->getInvAmount()->getBalance();
                $value = (null !== $bal && $bal > 0.00) ? $bal : 0.00;
                return (new Label())
                    ->attributes(['class' => $bal > 0.00
                        ? 'badge bg-success' : 'badge bg-warning text-dark'])
                    ->content(Html::encode(number_format($value, $dp)));
            },
            encodeContent: false,
            filter: TextInputFilter::widget()->addAttributes([
                'id' => 'filter-amount-balance', 'class' => self::AMOUNT_FILTER_CLASS,
                'aria-label' => 'Filter by balance amount',
                'title' => $t->translate('balance'),
                'placeholder' => $t->translate('balance')]),
            withSorting: false,
            footer: (new Span())->addClass('inv-footer-amount')
                ->addAttributes(['style' => self::AMOUNT_FILTER_STYLE])
                ->content(
                    Html::tag('small', $t->translate('balance') . ':',
                        ['class' => 'inv-footer-label'])
                    . ' ' . $sR->getSetting('currency_symbol')
                    . ' ' . number_format($totalBalance, $dp))
                ->encode(false)->render(),
        );
    }

    private function buildDeliveryAddColumn(): DataColumn
    {
        $ug = $this->urlGenerator;
        return new DataColumn(
            header: '🚚',
            content: static fn(Inv $model): A =>
                Html::a(Html::tag('i', '', ['class' => 'bi-plus']),
                    $ug->generate('del/add',
                        ['client_id' => $model->reqClientId()],
                        ['origin' => 'inv', 'origin_id' => $model->reqId(),
                            'action' => 'index'])),
            encodeContent: false,
            visible: $this->visible,
            withSorting: false,
        );
    }

    private function buildQuoteLinkColumn(QR $qR): DataColumn
    {
        $ug = $this->urlGenerator;
        $t  = $this->translator;
        return new DataColumn(
            'quote_id',
            header: $t->translate('quote.number.status'),
            content: static function (Inv $model) use ($qR, $ug): string|A {
                $quoteId = (int) $model->getQuoteId();
                $quote   = $qR->repoQuoteUnloadedquery($quoteId);
                if (null !== $quote) {
                    $statusId = $quote->reqStatusId();
                    return Html::a(
                        ($quote->getNumber() ?? '#') . ' '
                            . $qR->getSpecificStatusArrayLabel((string) $statusId),
                        $ug->generate('quote/view', ['id' => $quoteId]),
                        ['style' => 'text-decoration:none',
                            'class' => 'label '
                                . $qR->getSpecificStatusArrayClass((string) $statusId)],
                    );
                }
                return '';
            },
            visible: $this->visible,
            withSorting: false,
        );
    }

    private function buildSoLinkColumn(SOR $soR): DataColumn
    {
        $ug = $this->urlGenerator;
        $t  = $this->translator;
        return new DataColumn(
            'so_id',
            header: $t->translate('salesorder.number.status'),
            content: static function (Inv $model) use ($soR, $ug): string|A {
                $soId = $model->getSoId();
                $so   = $soR->repoSalesOrderUnloadedquery((int) $soId);
                if (null !== $so) {
                    $statusId = $so->getStatusId();
                    if (null !== $statusId) {
                        return Html::a(
                            ($so->getNumber() ?? '#') . ' '
                                . $soR->getSpecificStatusArrayLabel((string) $statusId),
                            $ug->generate('salesorder/view', ['id' => $soId]),
                            ['style' => 'text-decoration:none',
                                'class' => 'label '
                                    . $soR->getSpecificStatusArrayClass($statusId)],
                        );
                    }
                }
                return '';
            },
            visible: $this->visible,
            withSorting: false,
        );
    }

    private function buildDeliveryLocationColumn(DLR $dlR): DataColumn
    {
        $t = $this->translator;
        return new DataColumn(
            'delivery_location_id',
            header: $t->translate('delivery.location.global.location.number'),
            content: static function (Inv $model) use ($dlR): string {
                $dlId = $model->getDeliveryLocationId();
                $dl   = ($dlId !== null && $dlR->repoCount($dlId) > 0)
                    ? $dlR->repoDeliveryLocationquery($dlId)
                    : null;
                return null !== $dl
                    ? Html::encode($dl->getGlobalLocationNumber())
                    : '';
            },
            encodeContent: false,
            visible: $this->visible,
            withSorting: false,
        );
    }

    /**
     * @return ColumnInterface[]
     */
    private function buildColumns(
        float $totalAmount,
        float $totalPaid,
        float $totalBalance,
    ): array {
        \assert($this->iR !== null && $this->irR !== null
            && $this->islR !== null && $this->sR !== null);

        $iR   = $this->iR;
        $irR  = $this->irR;
        $islR = $this->islR;
        $sR   = $this->sR;
        $dp   = $this->decimalPlaces;
        $t    = $this->translator;
        $vis  = $this->visible;

        $columns = [
            $this->buildCheckboxColumn(),
            $this->buildEditColumn($sR),
            $this->buildPdfEmailColumn(),
            $this->buildInvNumberColumn(),
            $this->buildFamilyNameColumn(),
            $this->buildDateCreatedYearMonthColumn(),
            $this->buildStatusColumn($iR, $irR, $sR),
            $this->buildClientActiveColumn(),
            $this->buildCreditNoteColumn($iR),
            $this->buildSentLogToggleColumn($islR),
            $this->buildSentLogCountColumn($islR),
            $this->buildSentLogTableColumn($islR),
            $this->buildClientColumn(),
            new DataColumn('client_number',
                header: $t->translate('client.number'),
                content: static fn(Inv $m): string =>
                    Html::encode($m->getClient()?->getClientNumber()),
                encodeContent: false),
            new DataColumn(
                property: 'filterClientAddress1',
                header: $t->translate('street.address'),
                content: static fn(Inv $m): string =>
                    Html::encode($m->getClient()?->getClientAddress1()),
                encodeContent: false,
                filter: TextInputFilter::widget()->addAttributes([
                    'id' => 'filter-address-1', 'class' => self::FILTER_CLASS,
                    'aria-label' => 'Filter by street address',
                    'title' => $t->translate('street.address'),
                    'placeholder' => $t->translate('street.address')])),
            new DataColumn('client_address_2',
                header: $t->translate('street.address.2'),
                content: static fn(Inv $m): string =>
                    Html::encode($m->getClient()?->getClientAddress2()),
                encodeContent: false),
            $this->buildClientGroupColumn(),
            new DataColumn('time_created',
                header: $t->translate('datetime.immutable.time.created'),
                content: static fn(Inv $m): string =>
                    $m->getTimeCreated()->format('H:i:s'),
                visible: $vis),
            new DataColumn('date_modified',
                header: $t->translate('datetime.immutable.date.modified'),
                content: static function (Inv $m): Label {
                    $cls = $m->getDateModified() <> $m->getDateCreated()
                        ? 'badge bg-danger' : 'badge bg-success';
                    return (new Label())
                        ->attributes(['class' => $cls])
                        ->content(Html::encode($m->getDateModified()->format('Y-m-d')));
                },
                encodeContent: false,
                visible: $vis),
            new DataColumn('date_due',
                header: $t->translate('due.date'),
                content: static function (Inv $m): Label {
                    $now = new \DateTimeImmutable('now');
                    $due = $m->getDateDue();
                    return (new Label())
                        ->attributes(['class' => $due > $now
                            ? 'badge bg-success' : 'badge bg-warning text-dark'])
                        ->content(Html::encode($due->format('Y-m-d')));
                },
                encodeContent: false,
                withSorting: true,
                visible: $vis),
            $this->buildTotalColumn($sR, $dp, $totalAmount),
            $this->buildPaidColumn($sR, $dp, $totalPaid),
            $this->buildBalanceColumn($sR, $dp, $totalBalance),
            $this->buildDeliveryAddColumn(),
            $this->buildDeleteColumn($sR),
        ];

        if ($this->qR !== null) {
            $columns[] = $this->buildQuoteLinkColumn($this->qR);
        }
        if ($this->soR !== null) {
            $columns[] = $this->buildSoLinkColumn($this->soR);
        }
        if ($this->dlR !== null) {
            $columns[] = $this->buildDeliveryLocationColumn($this->dlR);
        }

        return $columns;
    }

    // -------------------------------------------------------------------------
    // Group-by helpers
    // -------------------------------------------------------------------------

    /** @return \Closure(Inv): string */
    private function makeGroupValueResolver(string $groupBy): \Closure
    {
        \assert($this->iR !== null);
        $iR = $this->iR;
        return static function (Inv $invoice) use ($iR, $groupBy): string {
            return match ($groupBy) {
                'client'       => $invoice->getClient()?->getClientFullName()
                    ?? 'Unknown Client',
                'status'       => $iR->getSpecificStatusArrayLabel(
                    (string) $invoice->reqStatusId()),
                'month'        => $invoice->getDateCreated()->format('Y-m'),
                'year'         => $invoice->getDateCreated()->format('Y'),
                'date'         => $invoice->getDateCreated()->format('Y-m-d'),
                'client_group' => $invoice->getClient()?->getClientGroup() ?? 'No Group',
                'amount_range' => match (true) {
                    ($invoice->getInvAmount()->getTotal() ?? 0) < 100    => '< $100',
                    ($invoice->getInvAmount()->getTotal() ?? 0) < 500    => '$100 - $500',
                    ($invoice->getInvAmount()->getTotal() ?? 0) < 1000   => '$500 - $1000',
                    default => '> $1000',
                },
                default => 'No Group',
            };
        };
    }

    /**
     * @param  callable(Inv): string $getGroupValue
     * @return array<string, array{count: int, total: float, paid: float, balance: float}>
     */
    private function computeGroupTotals(
        OffsetPaginator $paginator,
        callable $getGroupValue,
    ): array {
        $groupTotals = [];
        foreach ($paginator->read() as $invoice) {
            /** @var Inv $invoice */
            $gv = $getGroupValue($invoice);
            if (!isset($groupTotals[$gv])) {
                $groupTotals[$gv] = ['count' => 0, 'total' => 0.0,
                    'paid' => 0.0, 'balance' => 0.0];
            }
            $groupTotals[$gv]['count']++;
            $groupTotals[$gv]['total']   += $invoice->getInvAmount()->getTotal()   ?? 0.0;
            $groupTotals[$gv]['paid']    += $invoice->getInvAmount()->getPaid()    ?? 0.0;
            $groupTotals[$gv]['balance'] += $invoice->getInvAmount()->getBalance() ?? 0.0;
        }
        return $groupTotals;
    }

    /**
     * @param callable(Inv): string $getGroupValue
     */
    private function applyGrouping(
        GridView $gridView,
        callable $getGroupValue,
        array $groupTotals,
        int $decimalPlaces,
        string $groupBy,
        int $columnCount,
    ): GridView {
        \assert($this->sR !== null);
        $sR                 = $this->sR;
        $previousGroupValue = '';
        return $gridView->beforeRow(
            static function (array|object $invoice) use (
                &$previousGroupValue,
                $getGroupValue,
                $groupTotals,
                $decimalPlaces,
                $groupBy,
                $sR,
                $columnCount,
            ): ?\Yiisoft\Html\Tag\Tr {
                /** @var Inv $invoice */
                $currentGroupValue = $getGroupValue($invoice);
                if ($previousGroupValue === $currentGroupValue) {
                    return null;
                }
                $previousGroupValue = $currentGroupValue;
                /** @var array{count: int, total: float, paid: float, balance: float} $gd */
                $gd  = $groupTotals[$currentGroupValue]
                    ?? ['count' => 0, 'total' => 0.0, 'paid' => 0.0, 'balance' => 0.0];
                $cur = $sR->getSetting('currency_symbol');
                return \Yiisoft\Html\Html::tr()
                    ->addClass(
                        'group-header bg-secondary text-white fw-bold group-collapsible')
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
                                . '<span class="fs-5">'
                                . Html::encode(ucfirst($groupBy)) . ': '
                                . Html::encode($currentGroupValue) . '</span>'
                                . '<span class="badge bg-primary ms-2">'
                                . $gd['count'] . ' invoice'
                                . ($gd['count'] === 1 ? '' : 's') . '</span>'
                                . '</div>'
                                . '<div class="text-end">'
                                . '<small class="d-block">Total: <strong>'
                                . number_format($gd['total'], $decimalPlaces)
                                . ' ' . $cur . '</strong></small>'
                                . '<small class="d-block">Paid: <strong>'
                                . number_format($gd['paid'], $decimalPlaces)
                                . ' ' . $cur . '</strong></small>'
                                . '<small class="d-block">Balance: <strong>'
                                . number_format($gd['balance'], $decimalPlaces)
                                . ' ' . $cur . '</strong></small>'
                                . '</div>'
                                . '</div>'
                            )
                            ->encode(false)
                    );
            }
        );
    }
}
