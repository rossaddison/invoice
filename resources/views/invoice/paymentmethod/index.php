<?php

declare(strict_types=1);

use App\Invoice\Entity\PaymentMethod;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $payment_methods
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var int $page
 * @var string $alert
 * @var string $csrf
 * @var string $sortString
 * @psalm-var positive-int $page
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset = new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'paymentmethod/index'))
    ->id('btn-reset')
    ->render();

$columns = [
    new DataColumn(
        property: 'id',
        header: $translator->translate('id'),
        content: static fn (PaymentMethod $model) => Html::encode($model->getId()),
        withSorting: true,
    ),
    new DataColumn(
        property: 'name',
        header: $translator->translate('payment.method'),
        content: static fn (PaymentMethod $model) => Html::encode($model->getName() ?? ''),
        withSorting: true,
    ),
    new DataColumn(
        property: 'active',
        header: $translator->translate('active'),
        content: static fn (PaymentMethod $model) => $model->getActive() ? '✅' : '❌',
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (PaymentMethod $model) use ($urlGenerator): string {
                return $urlGenerator->generate('paymentmethod/view',
                    ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ],
        ),
        new ActionButton(
            content: '✎',
            url: static function (PaymentMethod $model) use ($urlGenerator): string {
                return $urlGenerator->generate('paymentmethod/edit',
                    ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ],
        ),
        new ActionButton(
            content: '❌',
            url: static function (PaymentMethod $model) use ($urlGenerator): string {
                return $urlGenerator->generate('paymentmethod/delete',
                    ['id' => $model->getId()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm('"
                    . $translator->translate('delete.record.warning')
                    . "');",
            ],
        ),
    ]),
];

$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));
$sort = Sort::only(['id', 'name'])
    ->withOrderString($sortString);

$toolbarString = new Form()->post($urlGenerator->generate('paymentmethod/index'))->csrf($csrf)->open()
    . new A()
        ->href($urlGenerator->generate('paymentmethod/add'))
        ->addClass('btn btn-primary me-1')
        ->content('➕ ' . $translator->translate('new'))
        ->render()
    . new Div()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    . new Form()->close();

$sortedAndPagedPaginator = (new OffsetPaginator($payment_methods))
    ->withPageSize($s->positiveListLimit())
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

$gridSummary = $s->gridSummary(
    $sortedAndPagedPaginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('payment.methods'),
    '',
);

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75'])
->columns(...$columns)
->dataReader($sortedAndPagedPaginator)
->urlCreator($urlCreator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('payment.methods'))
->multiSort(true)
->id('w4-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($sortedAndPagedPaginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($gridSummary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);

