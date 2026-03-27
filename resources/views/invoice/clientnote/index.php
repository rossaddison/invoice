<?php

declare(strict_types=1);

use App\Invoice\Entity\ClientNote;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
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

$toolbarReset =  new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content( new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'clientnote/index'))
    ->id('btn-reset')
    ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (ClientNote $model) => Html::encode($model->getId()),
    ),
    new DataColumn(
        'client_id',
        header: $translator->translate('client'),
        content: static fn (ClientNote $model): string => Html::encode(($model->getClient()?->getClientName() ?? '#') . ' ' . ($model->getClient()?->getClientSurname() ?? '#')),
    ),
    new DataColumn(
        'note',
        header: $translator->translate('client.note'),
        content: static fn (ClientNote $model): string => Html::encode(ucfirst($model->getNote())),
    ),
    new DataColumn(
        'date_note',
        header: $translator->translate('client.note.date'),
        content: static fn (ClientNote $model): string => Html::encode((!is_string($dateNote = $model->getDateNote()) ? $dateNote->format('Y-m-d') : '')),
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (ClientNote $model) use ($urlGenerator): string {
                return $urlGenerator->generate('clientnote/view', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ],
        ),
        new ActionButton(
            content: '✎',
            url: static function (ClientNote $model) use ($urlGenerator): string {
                return $urlGenerator->generate('clientnote/edit', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ],
        ),
        new ActionButton(
            content: '❌',
            url: static function (ClientNote $model) use ($urlGenerator): string {
                return $urlGenerator->generate('clientnote/delete', ['id' => $model->getId()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
            ],
        ),
    ]),
];

$gridSummary = $s->gridSummary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('client.notes'),
    '',
);

$toolbarString
    =  new Form()->post($urlGenerator->generate('clientnote/index'))->csrf($csrf)->open()
    .  new A()
        ->href($urlGenerator->generate('clientnote/add'))
        ->addAttributes(['style' => 'text-decoration:none'])
        ->content('➕')
        ->render()
    .  new Div()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    .  new Form()->close();

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-clientnote'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('client.note'))
->id('w44-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($gridSummary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);
