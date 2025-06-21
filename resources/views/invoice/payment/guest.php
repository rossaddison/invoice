<?php

declare(strict_types=1);

use App\Invoice\Entity\Payment;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $alert
 * @var string $csrf
 */

echo $alert;

?>
<?php

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'payment/guest'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>

<br>
<?php
    $columns = [
        new DataColumn(
            'id',
            header:  $translator->translate('id'),
            content: static fn (Payment $model): string => Html::encode($model->getId())
        ),
        new DataColumn(
            field: 'payment_date',
            property: 'paymentDateFilter',
            header:  $translator->translate('payment.date'),
            content: static fn (Payment $model): string|DateTimeImmutable => !is_string($date = $model->getPayment_date())
                                                                            ? $date->format('Y-m-d') : '',
            filter: true
        ),
        new DataColumn(
            field: 'amount',
            property: 'paymentAmountFilter',
            header:  $translator->translate('amount'),
            content: static function (Payment $model) use ($s): string {
                return $s->format_currency($model->getAmount() >= 0.00 ?
                                           $model->getAmount() : 0.00);
            },
            filter: true
        ),
        new DataColumn(
            'note',
            header:  $translator->translate('note'),
            content: static fn (Payment $model): string => Html::encode($model->getNote())
        ),
        new DataColumn(
            'inv_id',
            header:  $translator->translate('invoice'),
            content: static function (Payment $model) use ($urlGenerator): A {
                return Html::a($model->getInv()?->getNumber() ?? '', $urlGenerator->generate('inv/view', ['id' => $model->getInv_id()]), ['style' => 'text-decoration:none']);
            }
        ),
        new DataColumn(
            'inv_id',
            header:  $translator->translate('total'),
            content: static function (Payment $model) use ($s, $iaR): string {
                $inv_amount = (($iaR->repoInvAmountCount((int)$model->getInv_id()) > 0) ? $iaR->repoInvquery((int)$model->getInv_id()) : null);
                return $s->format_currency(null !== $inv_amount ? $inv_amount->getTotal() : 0.00);
            }
        ),
        new DataColumn(
            header:  $translator->translate('paid'),
            content: static function (Payment $model) use ($s, $iaR): string {
                $inv_amount = (($iaR->repoInvAmountCount((int)$model->getInv_id()) > 0) ? $iaR->repoInvquery((int)$model->getInv_id()) : null);
                return $s->format_currency(null !== $inv_amount ? $inv_amount->getPaid() : 0.00);
            }
        ),
        new DataColumn(
            'id',
            header:  $translator->translate('balance'),
            content: static function (Payment $model) use ($s, $iaR): string {
                $inv_amount = (($iaR->repoInvAmountCount((int)$model->getInv_id()) > 0) ? $iaR->repoInvquery((int)$model->getInv_id()) : null);
                return $s->format_currency(null !== $inv_amount ? $inv_amount->getBalance() : 0.00);
            }
        ),
        new DataColumn(
            'payment_method_id',
            header: $translator->translate('payment.method'),
            content: static function (Payment $model): string {
                return $model->getPaymentMethod()?->getName() ?? '';
            }
        ),
    ]
?>
<?php
$toolbarString = Form::tag()->post($urlGenerator->generate('payment/guest'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-payment-guest'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->header($gridComponents->header(' ' . $translator->translate('payment')))
    ->id('w148-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('no.records'))
    ->toolbar($toolbarString);
