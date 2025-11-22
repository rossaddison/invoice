<?php

declare(strict_types=1);

use App\Invoice\Entity\InvSentLog;
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
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataInvNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientsDropDownFilter
 */

echo $alert;

$toolbarReset = A::tag()
  ->addAttributes(['type' => 'reset'])
  ->addClass('btn btn-danger me-1 ajax-loader')
  ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
  ->href($urlGenerator->generate($currentRoute->getName() ?? 'invsentlog/index'))
  ->id('btn-reset')
  ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static function (InvSentLog $model): string {
            return (string) $model->getId();
        },
    ),
    new DataColumn(
        property: 'filterInvNumber',
        header: $translator->translate('number'),
        content: static function (InvSentLog $model) use ($urlGenerator): A {
            return Html::a(($model->getInv()?->getNumber() ?? '#') . ' 🔍', $urlGenerator->generate(
                'inv/view',
                ['id' => $model->getId()],
            ), ['style' => 'text-decoration:none']);
        },
        filter: $optionsDataInvNumberDropDownFilter,
        withSorting: false,
    ),
    new DataColumn(
        property: 'filterClient',
        header: $translator->translate('client'),
        content: static fn (InvSentLog $model): string => Html::encode($model->getClient()?->getClient_full_name() ?? ''),
        filter: $optionsDataClientsDropDownFilter,
        withSorting: false,
    ),
    new DataColumn(
        'inv_id',
        header: $translator->translate('setup.db.username.info'),
        content: static fn (InvSentLog $model) => $model->getInv()?->getUser()->getLogin(),
    ),
    new DataColumn(
        'date_sent',
        header: $translator->translate('email.date'),
        content: static fn (InvSentLog $model): string => ($model->getDate_sent())->format('l, d-M-Y H:i:s T'),
    ),
];
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('email.logs'),
    '',
);

$toolbarString =  Form::tag()->post($urlGenerator->generate('invsentlog/index'))->csrf($csrf)->open()
                  . Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
                  . Form::tag()->close();

echo GridView::widget()
  ->bodyRowAttributes(['class' => 'align-middle'])
  ->tableAttributes(['class' => 'table table-striped text-center h-10463', 'id' => 'table-invsentlog'])
  ->columns(...$columns)
  ->dataReader($paginator)
  ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
  ->header('📨')
  ->id('w10463-grid')
  ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
  ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
  ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'invsentlog') . ' ' . $grid_summary)
  ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
  ->noResultsText($translator->translate('no.records'))
  ->toolbar($toolbarString);
