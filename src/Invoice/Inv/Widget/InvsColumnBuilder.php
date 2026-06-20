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
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Input\Checkbox;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\DataView\Filter\Widget\DropdownFilter;
use Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\Base\DataContext;
use Yiisoft\Yii\DataView\GridView\Column\CheckboxColumn;
use Yiisoft\Yii\DataView\GridView\Column\ColumnInterface;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

/**
 * Column builders extracted from InvsListWidget to stay within S1448 (≤ 20 methods)
 * and S138 (≤ 150 lines per function).
 *
 * Methods that share a domain concern are grouped into array-returning helpers
 * (buildSentLogColumns, buildAmountColumns, buildDateColumns,
 * buildOptionalLinkColumns) and spread into the main $columns array.
 */
final class InvsColumnBuilder
{
    private const string ROUTE_EDIT          = 'inv/edit';
    private const string FILTER_CLASS        = 'native-reset inv-filter';
    private const string AMOUNT_FILTER_CLASS = 'native-reset inv-amount-filter';
    private const string AMOUNT_FILTER_STYLE =
        'text-align: right; display: block; width: 100%;';
    private const string BDG_TXT_DARK = 'badge bg-warning text-dark';
    private const string BDG_BG_SCS  = 'badge bg-success';

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly GridComponents $gridComponents,
        private readonly InvsFilterOptions $filterOptions,
        private readonly bool $visible,
        private readonly bool $visibleInvSentLogColumn,
    ) {}

    /**
     * @return ColumnInterface[]
     */
    public function buildColumns(InvsColumnParams $p): array
    {
        $iR           = $p->iR;
        $irR          = $p->irR;
        $islR         = $p->islR;
        $sR           = $p->sR;
        $dp           = $p->dp;
        $totalAmount  = $p->totalAmount;
        $totalPaid    = $p->totalPaid;
        $totalBalance = $p->totalBalance;
        $t   = $this->translator;
        $ug  = $this->urlGenerator;
        $vis = $this->visible;

        $columns = [
            $this->buildCheckboxColumn(),
            $this->buildWorkflowTypeColumn(),
            $this->buildEditColumn($sR),
            $this->buildPdfEmailColumn(),
            $this->buildInvNumberColumn(),
            $this->buildFamilyNameColumn(),
            $this->buildYearMonthColumn(),
            $this->buildStatusColumn($iR, $irR, $sR),
            $this->buildClientActiveColumn(),
            $this->buildCreditNoteColumn($iR),
            ...$this->buildSentLogColumns($islR),
            $this->buildClientFullNameColumn(),

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

            ...$this->buildDateColumns(),
            ...$this->buildAmountColumns($sR, $dp, $totalAmount, $totalPaid, $totalBalance),

            new DataColumn(
                header: '🚚',
                content: static fn(Inv $model): A =>
                    Html::a(Html::tag('i', '', ['class' => 'bi-plus']),
                        $ug->generate('del/add',
                            ['client_id' => $model->reqClientId()],
                            ['origin' => 'inv', 'origin_id' => $model->reqId(),
                                'action' => 'index'])),
                encodeContent: false,
                visible: $vis,
                withSorting: false,
            ),

            $this->buildDeleteColumn($sR),
        ];

        array_push($columns, ...$this->buildOptionalLinkColumns($p));

        return $columns;
    }

    // ── Grouped array-returning helpers ───────────────────────────────────────

    /**
     * Sent-log toggle, count, and inline-table columns.
     *
     * @return DataColumn[]
     */
    private function buildSentLogColumns(ISLR $islR): array
    {
        $ug             = $this->urlGenerator;
        $gridComponents = $this->gridComponents;
        $t              = $this->translator;

        $toggleAnchor = (new A())
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                'title' => $t->translate('hide.or.unhide.columns')])
            ->addClass('btn btn-info me-1 ajax-loader')
            ->content('↔️')
            ->href($ug->generate('setting/toggleinvsentlogcolumn'))
            ->id('btn-all-visible');

        return [
            new DataColumn(
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
            ),
            new DataColumn(
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
            ),
            new DataColumn(
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
            ),
        ];
    }

    /**
     * Total, paid, and balance amount columns with footer totals.
     *
     * @return DataColumn[]
     */
    private function buildAmountColumns(
        SR $sR,
        int $dp,
        float $totalAmount,
        float $totalPaid,
        float $totalBalance,
    ): array {
        $t = $this->translator;
        return [
            new DataColumn(
                property: 'filterInvAmountTotal',
                header: $t->translate('total') . '➡️' . $sR->getSetting('currency_symbol'),
                content: static function (Inv $model) use ($dp): Label {
                    $total = $model->getInvAmount()->getTotal();
                    return (new Label())
                        ->attributes(['class' => $total > 0.00
                            ? self::BDG_BG_SCS : self::BDG_TXT_DARK])
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
            ),
            new DataColumn(
                property: 'filterInvAmountPaid',
                header: $t->translate('paid') . '➡️' . $sR->getSetting('currency_symbol'),
                content: static function (Inv $model) use ($dp): Label {
                    $paid  = $model->getInvAmount()->getPaid();
                    $value = (null !== $paid && $paid > 0.00) ? $paid : 0.00;
                    $class = ($model->getInvAmount()->getPaid()
                        < $model->getInvAmount()->getTotal())
                        ? 'badge bg-danger' : self::BDG_BG_SCS;
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
            ),
            new DataColumn(
                property: 'filterInvAmountBalance',
                header: $t->translate('balance') . '➡️' . $sR->getSetting('currency_symbol'),
                content: static function (Inv $model) use ($dp): Label {
                    $bal   = $model->getInvAmount()->getBalance();
                    $value = (null !== $bal && $bal > 0.00) ? $bal : 0.00;
                    return (new Label())
                        ->attributes(['class' => $bal > 0.00
                            ? self::BDG_BG_SCS : self::BDG_TXT_DARK])
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
            ),
        ];
    }

    /**
     * Date-modified and date-due columns.
     *
     * @return DataColumn[]
     */
    private function buildDateColumns(): array
    {
        $t = $this->translator;
        return [
            new DataColumn('date_modified',
                header: $t->translate('datetime.immutable.date.modified'),
                content: static function (Inv $m): Label {
                    $cls = $m->getDateModified() <> $m->getDateCreated()
                        ? 'badge bg-danger' : self::BDG_BG_SCS;
                    return (new Label())
                        ->attributes(['class' => $cls])
                        ->content(Html::encode($m->getDateModified()->format('Y-m-d')));
                },
                encodeContent: false,
                visible: $this->visible),
            new DataColumn('date_due',
                header: $t->translate('due.date'),
                content: static function (Inv $m): Label {
                    $now = new \DateTimeImmutable('now');
                    $due = $m->getDateDue();
                    return (new Label())
                        ->attributes(['class' => $due > $now
                            ? self::BDG_BG_SCS : self::BDG_TXT_DARK])
                        ->content(Html::encode($due->format('Y-m-d')));
                },
                encodeContent: false,
                withSorting: true,
                visible: $this->visible),
        ];
    }

    /**
     * Quote-link, sales-order-link, and delivery-location columns — only added
     * when the matching repository is present in InvsColumnParams.
     *
     * @return DataColumn[]
     */
    private function buildOptionalLinkColumns(InvsColumnParams $p): array
    {
        $ug   = $this->urlGenerator;
        $t    = $this->translator;
        $cols = [];

        $qR = $p->qR;
        if ($qR !== null) {
            $cols[] = new DataColumn(
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

        $soR = $p->soR;
        if ($soR !== null) {
            $cols[] = new DataColumn(
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

        $dlR = $p->dlR;
        if ($dlR !== null) {
            $cols[] = new DataColumn(
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

        return $cols;
    }

    // ── Individual column builders ────────────────────────────────────────────

    private function buildWorkflowTypeColumn(): DataColumn
    {
        $t = $this->translator;
        return new DataColumn(
            header: (new Label())->content('🔀')
                ->addAttributes(['data-bs-toggle' => 'tooltip',
                    'title' => $t->translate('invoice') . ' / '
                        . $t->translate('quote') . ' → '
                        . $t->translate('salesorder') . ' → '
                        . $t->translate('invoice')])
                ->render(),
            encodeHeader: false,
            content: static function (Inv $model) use ($t): string {
                if (($model->getSoId() ?? 0) > 0) {
                    return '<span class="badge bg-primary" data-bs-toggle="tooltip" title="'
                        . Html::encode(
                            $t->translate('quote') . ' → '
                            . $t->translate('salesorder') . ' → '
                            . $t->translate('invoice')
                            . ' (' . $t->translate('peppol') . ')')
                        . '">🔀</span>';
                }
                if (($model->getQuoteId() ?? 0) > 0) {
                    return '<span class="badge bg-info text-dark" data-bs-toggle="tooltip" title="'
                        . Html::encode(
                            $t->translate('quote') . ' → '
                            . $t->translate('invoice'))
                        . '">💬→📄</span>';
                }
                return '<span class="badge bg-secondary" data-bs-toggle="tooltip" title="'
                    . Html::encode($t->translate('invoice'))
                    . '">📄</span>';
            },
            encodeContent: false,
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
                ->optionsData($this->filterOptions->familyName),
            withSorting: false,
        );
    }

    private function buildYearMonthColumn(): DataColumn
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
                ->optionsData($this->filterOptions->yearMonth),
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

    private function buildClientFullNameColumn(): DataColumn
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
                ->optionsData($this->filterOptions->clients),
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
                ->optionsData($this->filterOptions->clientGroup),
            withSorting: false,
        );
    }

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
                ->optionsData($this->filterOptions->invNumber),
            withSorting: false,
        );
    }

    private function buildStatusColumn(IR $iR, IRR $irR, SR $sR): DataColumn
    {
        $t      = $this->translator;
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
                ->optionsData($this->filterOptions->status),
            encodeContent: false,
            withSorting: false,
            visible: $this->visible,
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
                ->optionsData($this->filterOptions->creditInvNumber),
            withSorting: false,
            visible: $this->visible,
        );
    }
}
