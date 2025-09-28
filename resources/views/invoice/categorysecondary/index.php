<?php

declare(strict_types=1);

use App\Invoice\Entity\CategorySecondary;
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
 * @var App\Invoice\Entity\CategorySecondary $categorysecondary
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 */

echo $alert;

$toolbarReset = A::tag()
  ->addAttributes(['type' => 'reset'])
  ->addClass('btn btn-danger me-1 ajax-loader')
  ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
  ->href($urlGenerator->generate($currentRoute->getName() ?? 'categorysecondary/index'))
  ->id('btn-reset')
  ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn(CategorySecondary $model) => Html::encode($model->getId()),
    ),
    new DataColumn(
        'name',
        header: $translator->translate('name'),
        content: static fn(CategorySecondary $model) => Html::encode($model->getName() ?? ''),
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: function (CategorySecondary $model) use ($urlGenerator): string {
                /** @psalm-suppress InvalidArgument */
                return $urlGenerator->generate('categorysecondary/view', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ],
        ),
        new ActionButton(
            content: '✎',
            url: function (CategorySecondary $model) use ($urlGenerator): string {
                /** @psalm-suppress InvalidArgument */
                return $urlGenerator->generate('categorysecondary/edit', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ],
        ),
        new ActionButton(
            content: '❌',
            url: function (CategorySecondary $model) use ($urlGenerator): string {
                /** @psalm-suppress InvalidArgument */
                return $urlGenerator->generate('categorysecondary/delete', ['id' => $model->getId()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
            ],
        ),
    ]),
];

$toolbarString = Form::tag()->post($urlGenerator->generate('categorysecondary/index'))->csrf($csrf)->open() .
    A::tag()
        ->href($urlGenerator->generate('categorysecondary/add'))
        ->addAttributes(['style' => 'text-decoration:none'])
        ->content('➕')
        ->render() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Form::tag()->close();

$grid_summary = $s->grid_summary($paginator, $translator, (int) $s->getSetting('default_list_limit'), $translator->translate('plural'), '');

echo GridView::widget()
  ->bodyRowAttributes(['class' => 'align-middle'])
  ->tableAttributes(['class' => 'table table-striped text-center', 'id' => 'table-categorysecondary'])
  ->columns(...$columns)
  ->dataReader($paginator)
  ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
  ->header($translator->translate('category.secondary'))
  ->id('w371-grid')
  ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
  ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
  ->summaryTemplate($grid_summary)
  ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
  ->noResultsText($translator->translate('no.records'))
  ->toolbar($toolbarString);
