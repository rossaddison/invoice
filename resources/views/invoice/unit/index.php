<?php

declare(strict_types=1);

use App\Invoice\Entity\Unit;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * @var App\Invoice\Entity\Unit $unit
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var string $alert
 * @var string $csrf
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\CurrentRoute $currentRoute
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var WebView $this
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'unit/index'))
    ->id('btn-reset')
    ->render();

$columns = [
    new DataColumn(
        property: 'unit_id',
        header: $translator->translate('id'),
        content: static fn (Unit $model) => Html::encode($model->getUnit_id()),
    ),
    new DataColumn(
        property: 'unit_name',
        header: $translator->translate('unit.name'),
        content: static fn (Unit $model) => Html::encode($model->getUnit_name()),
    ),
    new DataColumn(
        property: 'unit_name_plrl',
        header: $translator->translate('unit.name.plrl'),
        content: static fn (Unit $model) => Html::encode($model->getUnit_name_plrl()),
    ),

    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (Unit $model) use ($urlGenerator): string {
                return $urlGenerator->generate('unit/view', ['unit_id' => $model->getUnit_id()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ],
        ),
        new ActionButton(
            content: '✎',
            url: static function (Unit $model) use ($urlGenerator): string {
                return $urlGenerator->generate('unit/edit', ['unit_id' => $model->getUnit_id()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ],
        ),
        new ActionButton(
            content: '❌',
            url: static function (Unit $model) use ($urlGenerator): string {
                return $urlGenerator->generate('unit/delete', ['unit_id' => $model->getUnit_id()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
            ],
        ),
    ]),
];

$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('units'),
    '',
);

$toolbarString = Form::tag()->post($urlGenerator->generate('unit/index'))->csrf($csrf)->open()
    . A::tag()
    ->href($urlGenerator->generate('unit/add'))
    ->addClass('btn btn-info')
    ->content('➕')
    ->render()
    . Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    . Form::tag()->close();

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-unit'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('unit'))
->id('w175-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);
