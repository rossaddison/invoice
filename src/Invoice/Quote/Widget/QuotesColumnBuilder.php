<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Widget;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
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
 * Column builders extracted from QuotesListWidget to stay within the S1448 limit.
 */
final class QuotesColumnBuilder
{
    /**
     * @psalm-param array<array-key, array<array-key, string>|string> $optionsDataClientsDropdownFilter
     * @psalm-param array<array-key, array<array-key, string>|string> $optionsDataStatusDropDownFilter
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly array $optionsDataClientsDropdownFilter,
        private readonly array $optionsDataStatusDropDownFilter,
    ) {}

    /** @return ColumnInterface[] */
    public function buildColumns(
        QR $qR,
        SOR $soR,
        SR $sR,
        int $decimalPlaces,
        float $totalAmount,
    ): array {
        return [
            $this->buildCheckboxColumn(),
            new DataColumn(
                'id',
                header: $this->translator->translate('id'),
                content: static fn(Quote $model): string => (string) $model->reqId(),
                withSorting: true,
            ),
            $this->buildActionColumn(),
            $this->buildStatusColumn($qR),
            $this->buildSoColumn($soR),
            $this->buildQuoteNumberColumn(),
            new DataColumn(
                'date_created',
                header: $this->translator->translate('date.created'),
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
            $this->buildClientColumn(),
            $this->buildTotalColumn($sR, $decimalPlaces, $totalAmount),
        ];
    }

    private function buildCheckboxColumn(): CheckboxColumn
    {
        $translator = $this->translator;
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

    private function buildActionColumn(): ActionColumn
    {
        $urlGenerator = $this->urlGenerator;
        $translator   = $this->translator;
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
                                'title'          => $translator->translate('delete.quote.single'),
                                'class'          => 'btn btn-outline-danger btn-sm',
                            ];
                        }
                        return [
                            'disabled'       => true,
                            'data-bs-toggle' => 'tooltip',
                            'title'          => $translator->translate('delete.quote.derived'),
                            'class'          => 'btn btn-secondary btn-sm disabled',
                        ];
                    },
                ),
            ],
        );
    }

    private function buildStatusColumn(QR $qR): DataColumn
    {
        $translator = $this->translator;
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

    private function buildSoColumn(SOR $soR): DataColumn
    {
        $urlGenerator = $this->urlGenerator;
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

    private function buildQuoteNumberColumn(): DataColumn
    {
        $urlGenerator = $this->urlGenerator;
        $translator   = $this->translator;
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

    private function buildClientColumn(): DataColumn
    {
        $translator = $this->translator;
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
        $translator = $this->translator;
        return new DataColumn(
            property: 'filterQuoteAmountTotal',
            header: $translator->translate('total')
                . ' ➡️ '
                . $sR->getSetting('currency_symbol'),
            content: static function (Quote $model) use ($decimalPlaces): Label {
                $quoteTotal = $model->getQuoteAmount()?->getTotal();
                return (new Label())
                    ->attributes([
                        'class' => ($model->getQuoteAmount()?->getTotal() ?? 0.0) > 0.0
                            ? 'badge bg-success'
                            : 'badge bg-warning text-dark',
                    ])
                    ->content(Html::encode(
                        null !== $quoteTotal
                            ? number_format($quoteTotal, $decimalPlaces)
                            : number_format(0, $decimalPlaces)
                    ));
            },
            encodeContent: false,
            filter: TextInputFilter::widget()
                ->addAttributes(['style' => 'max-width: 50px', 'class' => 'native-reset']),
            withSorting: false,
            footer: (new Span())
                ->addAttributes(['style' =>
                    'text-align: right; display: block; width: 100%;'])
                ->content(number_format($totalAmount, $decimalPlaces))
                ->render(),
        );
    }
}
