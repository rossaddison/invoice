<?php

declare(strict_types=1);

use App\Invoice\Entity\Delivery;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var int $max
 * @var string $alert
 * @var string $csrf
 * @var string $label
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset = A::tag()
  ->addAttributes(['type' => 'reset'])
  ->addClass('btn btn-danger me-1 ajax-loader')
  ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
  ->href($urlGenerator->generate($currentRoute->getName() ?? 'delivery/index'))
  ->id('btn-reset')
  ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (Delivery $model) => Html::encode($model->getId()),
    ),
    new DataColumn(
        'start_date',
        header: $translator->translate('start.date'),
        content: static fn (Delivery $model) => Html::encode(
                ($model->getStart_date())?->format('Y-m-d') ?? ''),
    ),
    new DataColumn(
        'actual_delivery_date',
        header: $translator->translate('delivery.actual.delivery.date'),
        content: static fn (Delivery $model) => Html::encode(
                ($model->getActual_delivery_date())?->format('Y-m-d') ?? ''),
    ),
    new DataColumn(
        'end_date',
        header: $translator->translate('end.date'),
        content: static fn (Delivery $model) => Html::encode(
                ($model->getEnd_date())?->format('Y-m-d') ?? ''),
    ),
    new DataColumn(
        content: static function (Delivery $model) use ($urlGenerator,
                                                        $translator): string {
            return Html::a($translator->translate('back'),
                    $urlGenerator->generate('inv/edit',
                            ['id' => $model->getInv_id()]),
                    ['style' => 'text-decoration:none'])->render();
        },
        encodeContent: false
    ),
    new DataColumn(
        'delivery_location_id',
        header: $translator->translate('delivery.location.global.location.number'),
        content: static fn (Delivery $model):
        string => Html::encode(
                $model->getDelivery_location()?->getGlobal_location_number()),
    ),
];

$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('deliveries'),
    '',
);

$toolbarString
    = Form::tag()->post($urlGenerator->generate(
            'delivery/index'))->csrf($csrf)->open()
    . Div::tag()->addClass('float-end m-3')->content(
            $toolbarReset)->encode(false)->render()
    . Form::tag()->close();

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes([
    'class' => 'table table-striped text-center h-191',
    'id' => 'table-delivery'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('delivery'))
->id('w14-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);

$pageSize = $paginator->getCurrentPageSize();
if ($pageSize > 0) {
    echo Html::p(
        sprintf($translator->translate('index.footer.showing')
                . ' deliveries: Max '
                . (string) $max
                . ' deliveries per page: Total Deliveries '
                . (string) $paginator->getTotalItems(),
                $pageSize, $paginator->getTotalItems()),
        ['class' => 'text-muted'],
    );
} else {
    echo Html::p($translator->translate('records.no'));
}
