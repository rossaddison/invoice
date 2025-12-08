<?php

declare(strict_types=1);

use App\Invoice\Entity\ProductImage;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

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

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'productimage/index'))
    ->id('btn-reset')
    ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (ProductImage $model) => Html::encode($model->getId()),
    ),
    new DataColumn(
        'product_id',
        header: $translator->translate('product'),
        content: static fn (ProductImage $model): string => Html::encode($model->getProduct()?->getProduct_name() ?? ''),
    ),
    new DataColumn(
        'file_name_original',
        header: $translator->translate('upload.filename.original'),
        content: static fn (ProductImage $model): string => Html::encode($model->getFile_name_original()),
    ),
    new DataColumn(
        'file_name_new',
        header: $translator->translate('upload.filename.new'),
        content: static fn (ProductImage $model): string => Html::encode($model->getFile_name_new()),
    ),
    new DataColumn(
        'description',
        header: $translator->translate('upload.filename.description'),
        content: static fn (ProductImage $model): string => Html::encode($model->getDescription()),
    ),
    new DataColumn(
        header: $translator->translate('view'),
        content: static function (ProductImage $model) use ($urlGenerator): string {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('productimage/view', ['id' => $model->getId()]), [])->render();
        },
    ),
    new DataColumn(
        header: $translator->translate('edit'),
        content: static function (ProductImage $model) use ($urlGenerator): string {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-edit fa-margin']), $urlGenerator->generate('productimage/edit', ['id' => $model->getId()]), [])->render();
        },
    ),
    new DataColumn(
        header: $translator->translate('delete'),
        content: static function (ProductImage $model) use ($translator, $urlGenerator): A {
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
                $urlGenerator->generate('productimage/delete', ['id' => $model->getId()]),
                [],
            );
        },
    ),
];

$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('product.image.plural'),
    '',
);

$toolbarString = Form::tag()->post($urlGenerator->generate('upload/index'))->csrf($csrf)->open()
        . Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
        . Form::tag()->close();

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-125','id' => 'table-upload'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header('productimage.index')
->id('w44-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);
