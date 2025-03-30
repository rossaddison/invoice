<?php

declare(strict_types=1);

use App\Invoice\Entity\ProductProperty;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var string $alert
 * @var string $csrf
 */

echo $alert;

?>
<h1><?= $translator->translate('invoice.product.property'); ?></h1>
<?php
    $header = Div::tag()
      ->addClass('row')
      ->content(
          H5::tag()
        ->addClass('bg-primary text-white p-3 rounded-top')
        ->content(
            I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('invoice.product.property'))
        )
      )
      ->render();

$toolbarReset = A::tag()
  ->addAttributes(['type' => 'reset'])
  ->addClass('btn btn-danger me-1 ajax-loader')
  ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
  ->href($urlGenerator->generate($currentRoute->getName() ?? 'productproperty/index '))
  ->id('btn-reset')
  ->render();

$toolbar = Div::tag();

$columns = [
    new DataColumn(
        'id',
        header:  $translator->translate('i.id'),
        content: static fn (ProductProperty $model) => $model->getProperty_id()
    ),
    new DataColumn(
        'name',
        header:  $translator->translate('invoice.product.property.name'),
        content: static fn (ProductProperty $model) => Html::encode($model->getName())
    ),
    new DataColumn(
        'value',
        header:  $translator->translate('invoice.product.property.value'),
        content: static fn (ProductProperty $model) => Html::encode($model->getValue())
    ),
    new DataColumn(
        header:  $translator->translate('i.view'),
        content: static function (ProductProperty $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('productproperty/view', ['id' => $model->getProperty_id()]), []);
        }
    ),
    new DataColumn(
        header:  $translator->translate('i.edit'),
        content: static function (ProductProperty $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']), $urlGenerator->generate('productproperty/edit', ['id' => $model->getProperty_id()]), []);
        }
    ),
    new DataColumn(
        header:  $translator->translate('i.delete'),
        content: static function (ProductProperty $model) use ($translator, $urlGenerator): A {
            return Html::a(
                Html::tag(
                    'button',
                    Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                    [
                    'type' => 'submit',
                    'class' => 'dropdown-button',
                    'onclick' => "return confirm(" . "'" . $translator->translate('i.delete_record_warning') . "');"
                ]
                ),
                $urlGenerator->generate('productproperty/delete', ['id' => $model->getProperty_id()]),
                []
            );
        }
    ),
];
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int)$s->getSetting('default_list_limit'),
    $translator->translate('invoice.product.property'),
    ''
);
$toolbarString = Form::tag()->post($urlGenerator->generate('productproperty/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
echo GridView::widget()
  ->bodyRowAttributes(['class' => 'align-middle'])
  ->tableAttributes(['class' => 'table table-striped text-center h-99999999999999999', 'id' => 'table-productproperty'])
  ->columns(...$columns)
  ->dataReader($paginator)
  ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
  ->header($header)
  ->id('w28-grid')
  ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
  ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
  ->summaryTemplate($grid_summary)
  ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
  ->emptyText($translator->translate('invoice.invoice.no.records'))
  ->toolbar($toolbarString);
