<?php

declare(strict_types=1);

use App\Invoice\Entity\UnitPeppol;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Router\CurrentRoute;

/**
 * @var App\Invoice\Entity\UnitPeppol $unitpeppol
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var string $alert
 * @var string $csrf
 * @var CurrentRoute $currentRoute
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var TranslatorInterface $translator
 * @var WebView $this
 */

echo $alert;

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'unitpeppol/index'))
    ->id('btn-reset')
    ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn(UnitPeppol $model) => Html::encode($model->getId()),
    ),
    new DataColumn(
        'unit_id',
        header: $translator->translate('unit.name'),
        content: static fn(UnitPeppol $model) => Html::encode($model->getUnit()?->getUnit_name()),
    ),
    new DataColumn(
        'unit_id',
        header: $translator->translate('unit.name.plrl'),
        content: static fn(UnitPeppol $model) => Html::encode($model->getUnit()?->getUnit_name_plrl()),
    ),
    new DataColumn(
        'code',
        header: $translator->translate('code'),
        content: static fn(UnitPeppol $model) => Html::encode($model->getCode()),
    ),
    new DataColumn(
        'name',
        header: $translator->translate('name'),
        content: static fn(UnitPeppol $model) => Html::encode($model->getName()),
    ),
    new DataColumn(
        'description',
        header: $translator->translate('description'),
        content: static fn(UnitPeppol $model) => Html::encode($model->getDescription()),
    ),
    new DataColumn(
        header: $translator->translate('view'),
        content: static function (UnitPeppol $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('unitpeppol/view', ['id' => $model->getId()]), []);
        },
        encodeContent: false,
    ),
    new DataColumn(
        header: $translator->translate('edit'),
        content: static function (UnitPeppol $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-edit fa-margin']), $urlGenerator->generate('unitpeppol/edit', ['id' => $model->getId()]), []);
        },
        encodeContent: false,
    ),
    new DataColumn(
        header: $translator->translate('delete'),
        content: static function (UnitPeppol $model) use ($translator, $urlGenerator): A {
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
                $urlGenerator->generate('unitpeppol/delete', ['id' => $model->getId()]),
                [],
            );
        },
        encodeContent: false,
    ),
];

$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('unit.peppol'),
    '',
);

$toolbarString = Form::tag()->post($urlGenerator->generate('unitpeppol/index'))->csrf($csrf)->open()
    . A::tag()
    ->href($urlGenerator->generate('unitpeppol/add'))
    ->addClass('btn btn-info')
    ->content('➕')
    ->render()
    . Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    . Form::tag()->close();

echo GridView::widget()
->columns(...$columns)
->dataReader($paginator)
->tableAttributes(['class' => 'table table-striped text-center h-81','id' => 'table-unitpeppol'])
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('unit.peppol'))
->id('w44-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);
