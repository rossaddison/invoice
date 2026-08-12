<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Dwelling\Dwelling;
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
 * @var App\Infrastructure\Persistence\Dwelling\Dwelling $dwelling
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

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset =  new A()
  ->addAttributes(['type' => 'reset'])
  ->addClass('btn btn-danger me-1 ajax-loader')
  ->content( new I()->addClass('bi bi-bootstrap-reboot'))
  ->href($urlGenerator->generate($currentRoute->getName() ?? 'dwelling/index'))
  ->id('btn-reset')
  ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (Dwelling $model) => Html::encode($model->reqId()),
    ),
    new DataColumn(
        'house_number_numeric',
        header: $translator->translate('dwelling.house.number'),
        content: static fn (Dwelling $model) => Html::encode($model->getHouseNumberDisplay()),
    ),
    new DataColumn(
        'flat_unit',
        header: $translator->translate('dwelling.flat.unit'),
        content: static fn (Dwelling $model) => Html::encode($model->getFlatUnit() ?? ''),
    ),
    new DataColumn(
        'postcode',
        header: $translator->translate('dwelling.postcode'),
        content: static fn (Dwelling $model) => Html::encode($model->getPostcode()),
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: function (Dwelling $model) use ($urlGenerator): string {
                /** @psalm-suppress InvalidArgument */
                return $urlGenerator->generate('dwelling/view', ['id' => $model->reqId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ],
        ),
        new ActionButton(
            content: '✎',
            url: function (Dwelling $model) use ($urlGenerator): string {
                /** @psalm-suppress InvalidArgument */
                return $urlGenerator->generate('dwelling/edit', ['id' => $model->reqId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ],
        ),
        new ActionButton(
            content: '❌',
            url: function (Dwelling $model) use ($urlGenerator): string {
                /** @psalm-suppress InvalidArgument */
                return $urlGenerator->generate('dwelling/delete', ['id' => $model->reqId()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
            ],
        ),
    ]),
];

$toolbarString =  new Form()->post($urlGenerator->generate('dwelling/index'))->csrf($csrf)->open()
    .  new A()
        ->href($urlGenerator->generate('dwelling/add'))
        ->addClass('text-decoration-none')
        ->content('➕')
        ->render()
    .  new Div()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    .  new Form()->close();

$gridSummary = $s->gridSummary($paginator, $translator, (int) $s->getSetting('default_list_limit'), $translator->translate('dwelling.plural'), '');

echo GridView::widget()
  ->bodyRowAttributes(['class' => 'align-middle'])
  ->tableAttributes(['class' => 'table table-striped text-center', 'id' => 'table-dwelling'])
  ->columns(...$columns)
  ->dataReader($paginator)
  ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
  ->header($translator->translate('dwelling.plural'))
  ->id('w372-grid')
  ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
  ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
  ->summaryTemplate($gridSummary)
  ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
  ->noResultsText($translator->translate('no.records'))
  ->toolbar($toolbarString);
