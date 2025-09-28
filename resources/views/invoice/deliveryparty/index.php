<?php

declare(strict_types=1);

use App\Invoice\Entity\DeliveryParty;
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
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var int $defaultPageSizeOffsetPaginator
 * @var bool $canEdit
 * @var string $alert
 * @var string $csrf
 */

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'deliveryparty/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn(DeliveryParty $model) => Html::encode($model->getId()),
    ),
    new DataColumn(
        'party_name',
        header: $translator->translate('name'),
        content: static fn(DeliveryParty $model) => Html::encode($model->getPartyName()),
    ),
    new DataColumn(
        header: $translator->translate('view'),
        content: static function (DeliveryParty $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('deliveryparty/view', ['id' => $model->getId()]), []);
        },
    ),
    new DataColumn(
        'id',
        header: $translator->translate('delivery.party.edit'),
        content: static function (DeliveryParty $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-edit fa-margin']), $urlGenerator->generate('deliveryparty/edit', ['id' => $model->getId()]), []);
        },
    ),
    new DataColumn(
        header: $translator->translate('delete'),
        content: static function (DeliveryParty $model) use ($translator, $urlGenerator): A {
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
                $urlGenerator->generate('deliveryparty/delete', ['id' => $model->getId()]),
                [],
            );
        },
        encodeContent: false,
    ),
];

echo $alert;

$toolbarString =
    Form::tag()->post($urlGenerator->generate('deliveryparty/index'))->csrf($csrf)->open() .
    A::tag()
        ->href($urlGenerator->generate('deliveryparty/add'))
        ->addClass('btn btn-info')
        ->content('➕')
        ->render() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Form::tag()->close();

$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('delivery.party'),
    '',
);

echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-deliveryparty'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->header($translator->translate('delivery.party'))
    ->id('w15-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
    ->noResultsText($translator->translate('no.records'))
    ->toolbar($toolbarString);
