<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder\Widget;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\Inv\InvRepository as InvRepo;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Input\Checkbox;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\DataView\Filter\Widget\DropdownFilter;
use Yiisoft\Yii\DataView\GridView\Column\Base\DataContext;
use Yiisoft\Yii\DataView\GridView\Column\CheckboxColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

final class SalesOrdersColumnBuilder
{
    /**
     * @psalm-param array<array-key, array<array-key, string>|string> $optionsDataClientsDropdownFilter
     */
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly bool $visible,
        private readonly ?InvRepo $iR,
        private readonly SR $sR,
        private readonly SoR $soR,
        private readonly SoAR $soaR,
        private readonly array $optionsDataClientsDropdownFilter,
    ) {
    }

    public function buildCheckboxColumn(): CheckboxColumn
    {
        return new CheckboxColumn(
            content: static function (Checkbox $input, DataContext $context): string {
                $so = $context->data;
                if (!$so instanceof SalesOrder) {
                    return '';
                }
                return $input
                    ->addAttributes([
                        'id'             => (string) $so->reqId(),
                        'name'           => 'checkbox[]',
                        'data-bs-toggle' => 'tooltip',
                        'title'          => '',
                    ])
                    ->value((string) $so->reqId())
                    ->render();
            },
            multiple: true,
        );
    }

    public function buildStatusColumn(): DataColumn
    {
        $soR = $this->soR;
        $ug  = $this->urlGenerator;
        return new DataColumn(
            'status_id',
            content: static function (SalesOrder $m) use ($soR, $ug): string {
                $statusId = $m->getStatusId();
                if (null === $statusId) {
                    return Html::tag('span')->render();
                }
                $label   = $soR->getSpecificStatusArrayLabel((string) $statusId);
                $class   = $soR->getSpecificStatusArrayClass($statusId);
                $spanTag = Html::tag('span', $label, ['class' => 'badge text-bg-' . $class])->render();
                if (7 === $statusId) {
                    return Html::tag('a', $spanTag, [
                        'href'     => $ug->generate('salesorder/soToInvoice',
                            ['id' => $m->reqId()]),
                        'class'    => 'text-decoration-none',
                        'hx-boost' => 'false',
                    ])->render();
                }
                return $spanTag;
            },
            encodeContent: false,
        );
    }

    public function buildNumberColumn(): DataColumn
    {
        $ug = $this->urlGenerator;
        return new DataColumn(
            'number',
            content: static fn(SalesOrder $m): A =>
                Html::a(
                    $m->getNumber() ?? '#',
                    $ug->generate('salesorder/view', ['id' => $m->reqId()]),
                    ['class' => 'text-decoration-none', 'hx-boost' => 'false'],
                ),
        );
    }

    public function buildQuoteColumn(): DataColumn
    {
        $ug  = $this->urlGenerator;
        $t   = $this->translator;
        $vis = $this->visible;
        return new DataColumn(
            'quote_id',
            header: $t->translate('quote'),
            content: static function (SalesOrder $m) use ($ug): string|A {
                $quote = $m->getQuote();
                return $quote
                    ? Html::a(
                        $quote->getNumber() ?? '#',
                        $ug->generate('quote/view', ['id' => $quote->reqId()]),
                        ['class' => 'text-decoration-none', 'hx-boost' => 'false'],
                    )
                    : '';
            },
            visible: $vis,
        );
    }

    public function buildInvoiceColumn(): DataColumn
    {
        $ug  = $this->urlGenerator;
        $t   = $this->translator;
        $iR  = $this->iR;
        $vis = $this->visible;
        return new DataColumn(
            'inv_id',
            header: $t->translate('invoice'),
            content: static function (SalesOrder $m) use ($ug, $iR): string|A {
                if (!$m->hasLinkedInvoice() || $iR === null) {
                    return '';
                }
                $invId = $m->reqInvId();
                $inv   = $iR->repoInvUnloadedquery($invId);
                return $inv
                    ? Html::a(
                        $inv->getNumber() ?? '#',
                        $ug->generate('inv/view', ['id' => $invId]),
                        ['class' => 'text-decoration-none', 'hx-boost' => 'false'],
                    )
                    : '';
            },
            visible: $vis,
        );
    }

    public function buildDateCreatedColumn(): DataColumn
    {
        $t   = $this->translator;
        $vis = $this->visible;
        return new DataColumn(
            'date_created',
            header: $t->translate('date.created'),
            content: static fn(SalesOrder $m): string =>
                $m->getDateCreated() instanceof \DateTimeImmutable
                    ? $m->getDateCreated()->format('Y-m-d')
                    : '',
            encodeContent: true,
            visible: $vis,
        );
    }

    public function buildClientColumn(): DataColumn
    {
        $t    = $this->translator;
        $opts = $this->optionsDataClientsDropdownFilter;
        return new DataColumn(
            property: 'filterClient',
            header: $t->translate('client'),
            content: static fn(SalesOrder $m): string =>
                Html::encode($m->getClient()?->getClientFullName() ?? ''),
            encodeContent: false,
            filter: DropdownFilter::widget()
                ->addAttributes(['name' => 'client_id', 'class' => 'native-reset'])
                ->optionsData($opts),
            withSorting: false,
        );
    }

    public function buildTotalColumn(): DataColumn
    {
        $sR   = $this->sR;
        $soaR = $this->soaR;
        $t    = $this->translator;
        $vis  = $this->visible;
        return new DataColumn(
            'id',
            header: $t->translate('total'),
            content: static function (SalesOrder $m) use ($sR, $soaR): string {
                $soId     = $m->reqId();
                $soAmount = $soaR->repoSalesOrderAmountCount($soId) > 0
                    ? $soaR->repoSalesOrderquery($soId)
                    : null;
                return $sR->formatCurrency(
                    null !== $soAmount ? ($soAmount->getTotal() ?? 0.00) : 0.00
                );
            },
            visible: $vis,
        );
    }
}
