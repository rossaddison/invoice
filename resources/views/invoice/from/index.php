<?php

declare(strict_types=1);

use App\Invoice\Entity\FromDropDown;
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
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $alert
 * @var string $csrf
 */

echo $alert;

$toolbarReset = A::tag()
  ->addAttributes(['type' => 'reset'])
  ->addClass('btn btn-danger me-1 ajax-loader')
  ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
  ->href($urlGenerator->generate($currentRoute->getName() ?? 'from/index'))
  ->id('btn-reset')
  ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn(FromDropDown $model) => $model->getId(),
    ),
    new DataColumn(
        'default_email',
        header: $translator->translate('email.default'),
        content: static fn(FromDropDown $model) => $model->getDefault_email() == 'true' ? $translator->translate('yes') : $translator->translate('no'),
    ),
    new DataColumn(
        header: $translator->translate('view'),
        content: static function (FromDropDown $model) use ($urlGenerator): string {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('from/view', ['id' => $model->getId()]), [])->render();
        },
        encodeContent: false,
    ),
    new DataColumn(
        header: $translator->translate('edit'),
        content: static function (FromDropDown $model) use ($urlGenerator): string {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']), $urlGenerator->generate('from/edit', ['id' => $model->getId()]), [])->render();
        },
        encodeContent: false,
    ),
    new DataColumn(
        header: $translator->translate('delete'),
        content: static function (FromDropDown $model) use ($translator, $urlGenerator): A {
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
                $urlGenerator->generate('from/delete', ['id' => $model->getId()]),
                [],
            );
        },
        encodeContent: false,
    ),
];

$toolbarString
    = Form::tag()->post($urlGenerator->generate('from/index'))->csrf($csrf)->open()
    . A::tag()
    ->href($urlGenerator->generate('from/add'))
    ->addClass('btn btn-info')
    ->content('➕')
    ->render()
    . Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    . Form::tag()->close();

$grid_summary = $s->grid_summary($paginator, $translator, (int) $s->getSetting('default_list_limit'), $translator->translate('plural'), '');

echo GridView::widget()
  ->bodyRowAttributes(['class' => 'align-middle'])
  ->tableAttributes(['class' => 'table table-striped text-center h-99999999999999999', 'id' => 'table-from'])
  ->columns(...$columns)
  ->dataReader($paginator)
  ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
  ->header($translator->translate('from.email.address'))
  ->id('w3197-grid')
  ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
  ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
  ->summaryTemplate($grid_summary)
  ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
  ->noResultsText($translator->translate('no.records'))
  ->toolbar($toolbarString);
