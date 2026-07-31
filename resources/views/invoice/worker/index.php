<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Worker\Worker;
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
 * @var App\Infrastructure\Persistence\Worker\Worker $worker
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

$toolbarReset =  new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content( new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'worker/index'))
    ->id('btn-reset')
    ->render();

$columns = [
    new DataColumn(
        property: 'worker_id',
        header: $translator->translate('id'),
        content: static fn (Worker $model) => Html::encode($model->reqId()),
    ),
    new DataColumn(
        property: 'firstname',
        header: $translator->translate('worker.firstname'),
        content: static fn (Worker $model) => Html::encode($model->getFirstname()),
    ),
    new DataColumn(
        property: 'lastname',
        header: $translator->translate('worker.lastname'),
        content: static fn (Worker $model) => Html::encode($model->getLastname()),
    ),
    new DataColumn(
        property: 'active',
        header: $translator->translate('active'),
        content: static fn (Worker $model) => $model->getActive()
            ? Html::tag('span', $translator->translate('general.yes'), ['class' => 'badge text-bg-success'])
            : Html::tag('span', $translator->translate('general.no'), ['class' => 'badge text-bg-secondary']),
        encodeContent: false,
    ),
    new DataColumn(
        property: 'user_id',
        header: $translator->translate('worker.linked.login'),
        content: static function (Worker $model) use ($translator): \Yiisoft\Html\Tag\CustomTag {
            $linkedUser = $model->getUser();
            return $linkedUser !== null
                ? Html::tag('span', '🔗 ' . Html::encode($linkedUser->getLogin()), ['class' => 'badge text-bg-success'])
                : Html::tag('span', $translator->translate('general.no'), ['class' => 'badge text-bg-secondary']);
        },
        encodeContent: false,
    ),

    new ActionColumn(
        before: Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
        after: Html::closeTag('div'),
        buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (Worker $model) use ($urlGenerator): string {
                return $urlGenerator->generate('worker/view', ['worker_id' => $model->reqId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
                'class' => 'btn btn-outline-primary btn-sm',
            ],
        ),
        new ActionButton(
            content: '✎',
            url: static function (Worker $model) use ($urlGenerator): string {
                return $urlGenerator->generate('worker/edit', ['worker_id' => $model->reqId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
                'class' => 'btn btn-outline-warning btn-sm',
            ],
        ),
        new ActionButton(
            content: '❌',
            url: static function (Worker $model) use ($urlGenerator): string {
                return $urlGenerator->generate('worker/delete', ['worker_id' => $model->reqId()]);
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
    $translator->translate('workers'),
    '',
);

$toolbarString =  new Form()->post($urlGenerator->generate('worker/index'))->csrf($csrf)->open()
    .  new A()
    ->href($urlGenerator->generate('worker/add'))
    ->addClass('btn btn-info')
    ->content('➕')
    ->render()
    .  new Div()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    .  new Form()->close();

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-worker'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('workers'))
->id('w176-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($gridSummary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);
