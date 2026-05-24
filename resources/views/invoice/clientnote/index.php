<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\ClientNote\ClientNote;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;
use Yiisoft\Router\CurrentRoute;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var CurrentRoute $currentRoute
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $clientNotes
 * @var Yiisoft\Data\Paginator\OffsetPaginator $sortedAndPagedPaginator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var int $defaultPageSizeOffsetPaginator
 * @var int $page
 * @var string $alert
 * @var string $csrf
 * @var string $sortString
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset = new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'clientnote/index'))
    ->id('btn-reset')
    ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (ClientNote $model): int => $model->reqId(),
        withSorting: true,
    ),
    new DataColumn(
        'client_id',
        header: $translator->translate('client'),
        content: static fn (ClientNote $model): string => Html::encode(
            ($model->getClient()?->getClientName() ?? '#') . ' ' .
            ($model->getClient()?->getClientSurname() ?? '#')
        ),
    ),
    new DataColumn(
        'note',
        header: $translator->translate('client.note'),
        content: static fn (ClientNote $model): string => Html::encode(ucfirst($model->getNote())),
    ),
    new DataColumn(
        'date_note',
        header: $translator->translate('client.note.date'),
        content: static function (ClientNote $model): string {
            $dateNote = $model->getDateNote();
            if (!is_string($dateNote) && null !== $dateNote) {
                return Html::encode($dateNote->format('Y-m-d'));
            }
            return '';
        },
        withSorting: true,
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (ClientNote $model) use ($urlGenerator): string {
                return $urlGenerator->generate('clientnote/view',
                    ['id' => $model->reqId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ],
        ),
        new ActionButton(
            content: '✎',
            url: static function (ClientNote $model) use ($urlGenerator): string {
                return $urlGenerator->generate('clientnote/edit',
                    ['id' => $model->reqId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ],
        ),
        new ActionButton(
            content: '❌',
            url: static function (ClientNote $model) use ($urlGenerator): string {
                return $urlGenerator->generate('clientnote/delete',
                    ['id' => $model->reqId()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm("
                    . "'"
                    . $translator->translate('delete.record.warning')
                    . "');",
            ],
        ),
    ]),
];

$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));

$sort = Sort::only(['id', 'date_note'])
    ->withOrderString($sortString);

$toolbarString =
    new Form()->post($urlGenerator->generate('clientnote/index'))->csrf($csrf)->open()
    . new A()
        ->href($urlGenerator->generate('clientnote/add'))
        ->addClass('text-decoration-none')
        ->content('➕')
        ->render()
    . new Div()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    . new Form()->close();

$sortedAndPagedPaginator = (new OffsetPaginator($clientNotes))
    ->withPageSize($defaultPageSizeOffsetPaginator > 0 ? $defaultPageSizeOffsetPaginator : 1)
    ->withCurrentPage(max(1, $page))
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

$gridSummary = $s->gridSummary(
    $sortedAndPagedPaginator,
    $translator,
    $defaultPageSizeOffsetPaginator,
    $translator->translate('client.notes'),
    '',
);

echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-clientnote'])
    ->columns(...$columns)
    ->dataReader($sortedAndPagedPaginator)
    ->urlCreator($urlCreator)
    ->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50">⭥</div>')
    ->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
    ->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->header($translator->translate('client.note'))
    ->id('w44-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($sortedAndPagedPaginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($gridSummary)
    ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
    ->noResultsText($translator->translate('no.records'))
    ->toolbar($toolbarString);
