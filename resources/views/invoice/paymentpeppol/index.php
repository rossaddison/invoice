<?php

declare(strict_types=1);

use App\Invoice\Entity\PaymentPeppol;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * @var App\Invoice\Entity\PaymentPeppol $paymentpeppol
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $routeCurrent
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var string $alert
 * @var string $csrf
 * @var string $id
 */

echo $alert;

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn(PaymentPeppol $model) => $model->getId(),
    ),
    new DataColumn(
        header: $translator->translate('view'),
        content: static function (PaymentPeppol $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('paymentpeppol/view', ['id' => $model->getId()]), []);
        },
    ),
    new DataColumn(
        header: $translator->translate('edit'),
        content: static function (PaymentPeppol $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']), $urlGenerator->generate('paymentpeppol/edit', ['id' => $model->getId()]), []);
        },
    ),
    new DataColumn(
        header: $translator->translate('delete'),
        content: static function (PaymentPeppol $model) use ($translator, $urlGenerator): A {
            return Html::a(
                Html::tag(
                    'button',
                    Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                    [
                        'type' => 'submit',
                        'class' => 'dropdown-button',
                        'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
                    ],
                ),
                $urlGenerator->generate('paymentpeppol/delete', ['id' => $model->getId()]),
                [],
            );
        },
    ),
];

$toolbarReset = A::tag()
  ->addAttributes(['type' => 'reset'])
  ->addClass('btn btn-danger me-1 ajax-loader')
  ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
  ->href($urlGenerator->generate($routeCurrent->getName() ?? 'paymentpeppol/index'))
  ->id('btn-reset')
  ->render();

$toolbarString = Form::tag()->post($urlGenerator->generate('paymentpeppol/index'))->csrf($csrf)->open() .

    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Form::tag()->close();
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('paymentpeppol.reference.plural'),
    '',
);

echo GridView::widget()
  ->bodyRowAttributes(['class' => 'align-middle'])
  ->tableAttributes(['class' => 'table table-striped text-center h-99999999999999999', 'id' => 'table-delivery'])
  ->columns(...$columns)
  ->dataReader($paginator)
  ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
  ->header($translator->translate('paymentpeppol'))
  ->id('w137-grid')
  ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
  ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
  ->summaryTemplate($grid_summary)
  ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
  ->noResultsText($translator->translate('no.records'))
  ->toolbar($toolbarString);
