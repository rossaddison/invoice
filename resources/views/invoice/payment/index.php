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
 * @var App\Widget\Button $button
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var int $defaultPageSizeOffsetPaginator
 * @var bool $canEdit
 * @var bool $canView
 * @var string $alert
 * @var string $csrf
 */

echo $alert;

?>
<?php
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int)$s->getSetting('default_list_limit'),
    $translator->translate('invoice.payments'),
    ''
);
$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'payment/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>

<?php if ($canEdit && $canView) { ?>
    <div>
     <h5><?= $translator->translate('i.payment'); ?></h5>
     <a class="btn btn-success" href="<?= $urlGenerator->generate('payment/add'); ?>">
          <i class="fa fa-plus"></i> <?= $translator->translate('i.new'); ?> </a>
    </div>
<?php } ?>
<br>
<?php
    $columns = [
        new DataColumn(
            'id',
            header:  $translator->translate('i.id'),
            content: static fn (Payment $model): string => $model->getId()
        ),
        new DataColumn(
            field: 'payment_date',
            property: 'paymentDateFilter',
            header:  $translator->translate('i.payment_date'),
            content: static fn (Payment $model): string|DateTimeImmutable => !is_string($date = $model->getPayment_date())
                                                                             ? $date->format('Y-m-d')
                                                                             : '',
            filter: true
        ),
        new DataColumn(
            field: 'amount',
            property: 'paymentAmountFilter',
            header:  $translator->translate('i.amount'),
            content: static function (Payment $model) use ($s): string {
                return $s->format_currency($model->getAmount() >= 0.00 ?
                                           $model->getAmount() : 0.00);
            },
            filter: true
        ),
        new DataColumn(
            'note',
            header:  $translator->translate('i.note'),
            content: static fn (Payment $model): string => Html::encode($model->getNote())
        ),
        new DataColumn(
            'inv_id',
            header:  $translator->translate('i.invoice'),
            content: static function (Payment $model) use ($urlGenerator): A {
                return Html::a($model->getInv()?->getNumber() ?? '', $urlGenerator->generate('inv/view', ['id' => $model->getInv_id()]), ['style' => 'text-decoration:none']);
            }
        ),
        new DataColumn(
            'inv_id',
            header:  $translator->translate('i.total'),
            content: static function (Payment $model) use ($s, $iaR): string {
                $inv_amount = (($iaR->repoInvAmountCount((int)$model->getInv_id()) > 0) ? $iaR->repoInvquery((int)$model->getInv_id()) : null);
                return $s->format_currency(null !== $inv_amount ? $inv_amount->getTotal() : 0.00);
            }
        ),
        new DataColumn(
            header:  $translator->translate('i.paid'),
            content: static function (Payment $model) use ($s, $iaR): string {
                $inv_amount = (($iaR->repoInvAmountCount((int)$model->getInv_id()) > 0) ? $iaR->repoInvquery((int)$model->getInv_id()) : null);
                return $s->format_currency(null !== $inv_amount ? $inv_amount->getPaid() : 0.00);
            }
        ),
        new DataColumn(
            'id',
            header:  $translator->translate('i.balance'),
            content: static function (Payment $model) use ($s, $iaR): string {
                $inv_amount = (($iaR->repoInvAmountCount((int)$model->getInv_id()) > 0) ? $iaR->repoInvquery((int)$model->getInv_id()) : null);
                return $s->format_currency(null !== $inv_amount ? $inv_amount->getBalance() : 0.00);
            }
        ),
        new DataColumn(
            'payment_method_id',
            header:  $translator->translate('i.payment_method'),
            content: static function (Payment $model): string|null {
                return null !== $model->getPaymentMethod()?->getId() ? $model->getPaymentMethod()?->getName() : '';
            }
        ),
        new DataColumn(
            header:  $translator->translate('i.view'),
            visible: $canView,
            content: static function (Payment $model) use ($urlGenerator): A {
                return Html::a(
                    Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']),
                    $urlGenerator->generate(
                        'payment/view',
                        ['id' => $model->getId()]
                    ),
                    []
                );
            }
        ),
        new DataColumn(
            header:  $translator->translate('i.edit'),
            visible: $canEdit,
            content: static function (Payment $model) use ($s, $urlGenerator): A|string {
                return $model->getInv()?->getIs_read_only() === false
                       && $s->getSetting('disable_read_only') === (string)0
                       ? Html::a(
                           Html::tag(
                               'i',
                               '',
                               ['class' => 'fa fa-edit fa-margin']
                           ),
                           $urlGenerator->generate(
                               'payment/edit',
                               ['id' => $model->getId()]
                           ),
                           []
                       ) : '';
            },
            encodeContent: false
        ),
        new DataColumn(
            header:  $translator->translate('i.delete'),
            visible: $canEdit,
            content: static function (Payment $model) use ($translator, $s, $urlGenerator): string|A {
                return $model->getInv()?->getIs_read_only() === false && $s->getSetting('disable_read_only') === (string)0 ? Html::a(
                    Html::tag(
                        'button',
                        Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                        [
                        'type' => 'submit',
                        'class' => 'dropdown-button',
                        'onclick' => "return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                    ]
                    ),
                    $urlGenerator->generate('payment/delete', ['id' => $model->getId()]),
                    []
                ) : '';
            }
        ),
    ]
?>
<?php
$toolbarString = Form::tag()->post($urlGenerator->generate('payment/index'))->csrf($csrf)->open() .
Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
Form::tag()->close();
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-payment-index'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($gridComponents->header(' ' . $translator->translate('i.payment')))
->id('w147-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'payment') .' '.$grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('invoice.invoice.no.records'))
->toolbar($toolbarString);
?>
