<?php

declare(strict_types=1);

use App\Invoice\Entity\ItemLookup;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var string $alert
 * @var string $csrf
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'itemlookup/index'))
    ->id('btn-reset')
    ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (ItemLookup $model) => Html::encode($model->getId()),
    ),
    new DataColumn(
        'name',
        header: $translator->translate('name'),
        content: static fn (ItemLookup $model): string => Html::encode($model->getName()),
    ),
    new DataColumn(
        'description',
        header: $translator->translate('description'),
        content: static fn (ItemLookup $model): string => Html::encode($model->getDescription()),
    ),
    new DataColumn(
        'price',
        header: $translator->translate('price'),
        content: static fn (ItemLookup $model): string => Html::encode($model->getPrice()),
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (ItemLookup $model) use ($urlGenerator): string {
                return $urlGenerator->generate('itemlookup/view', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ],
        ),
        new ActionButton(
            content: '✎',
            url: static function (ItemLookup $model) use ($urlGenerator): string {
                return $urlGenerator->generate('itemlookup/edit', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ],
        ),
        new ActionButton(
            content: '❌',
            url: static function (ItemLookup $model) use ($urlGenerator): string {
                return $urlGenerator->generate('itemlookup/delete', ['id' => $model->getId()]);
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
    $translator->translate('item.lookup'),
    '',
);

$toolbarString
    = Form::tag()->post($urlGenerator->generate('itemlookup/index'))->csrf($csrf)->open()
    . A::tag()
    ->href($urlGenerator->generate('itemlookup/add'))
    ->addClass('btn btn-info')
    ->content('➕')
    ->render()
    . Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    . Form::tag()->close();

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-itemlookup'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('item.lookup'))
->id('w31-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);
