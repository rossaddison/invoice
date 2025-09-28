<?php

declare(strict_types=1);

use App\Invoice\Entity\CategoryPrimary;
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
 * @var App\Invoice\Entity\CategoryPrimary $categoryprimary
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
  ->href($urlGenerator->generate($currentRoute->getName() ?? 'categoryprimary/index'))
  ->id('btn-reset')
  ->render();

$toolbar = Div::tag();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn(CategoryPrimary $model) => (string) $model->getId(),
    ),
    new DataColumn(
        'name',
        header: $translator->translate('name'),
        content: static fn(CategoryPrimary $model) => Html::encode($model->getName() ?? ''),
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (CategoryPrimary $model) use ($urlGenerator): string {
                return $urlGenerator->generate('categoryprimary/view', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ],
        ),
        new ActionButton(
            content: '✎',
            url: static function (CategoryPrimary $model) use ($urlGenerator): string {
                return $urlGenerator->generate('categoryprimary/edit', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ],
        ),
        new ActionButton(
            content: '❌',
            url: static function (CategoryPrimary $model) use ($urlGenerator): string {
                return $urlGenerator->generate('categoryprimary/delete', ['id' => $model->getId()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
            ],
        ),
    ]),
];

$toolbarString = Form::tag()->post($urlGenerator->generate('categoryprimary/index'))->csrf($csrf)->open() .
    A::tag()
        ->href($urlGenerator->generate('categoryprimary/add'))
        ->addAttributes(['style' => 'text-decoration:none'])
        ->content('➕')
        ->render() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Form::tag()->close();

$grid_summary = $s->grid_summary($paginator, $translator, (int) $s->getSetting('default.list.limit'), $translator->translate('plural'), '');

echo GridView::widget()
  ->bodyRowAttributes(['class' => 'align-middle'])
  ->tableAttributes(['class' => 'table table-striped text-center', 'id' => 'table-categoryprimary'])
  ->columns(...$columns)
  ->dataReader($paginator)
  ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
  ->header($translator->translate('category.primary'))
  ->id('w194-grid')
  ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
  ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
  ->summaryTemplate($grid_summary)
  ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
  ->noResultsText($translator->translate('no.records'))
  ->toolbar($toolbarString);
