<?php

declare(strict_types=1);

use App\Invoice\Entity\Setting;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\Filter\Widget\DropdownFilter;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $settings
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlFastRouteGenerator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator $urlCreator
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $sortString
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataSettingsKeyDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataSettingsValueDropDownFilter
 * @psalm-var positive-int $page
 */

echo $alert;

?>
<?php
$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')
                        ->content(' ' . Html::encode($translator->translate('setting'))),
            ),
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'setting/debug_index'))
    ->id('btn-reset')
    ->render();

$toolbarFilter = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('setting_filters_submit')
    ->addClass('btn btn-info me-1')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href('#setting_filters_submit')
    ->id('setting_filters_submit')
    ->render();

$toolbar = Div::tag();
?>

<div>
    <h5><?= $translator->translate('settings'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('setting/add'); ?>">
            <i class="fa fa-plus"></i> <?= Html::encode($translator->translate('new')); ?>
        </a>
    </div>
</div>
<br>
<div>

</div>
<div>
<?php
    $columns = [
        new DataColumn(
            property: 'id',
            header: $translator->translate('id'),
            content: static fn(Setting $model) => Html::encode($model->getSetting_id()),
            withSorting: true,
        ),
        new DataColumn(
            property: 'setting_key',
            header: $translator->translate('setting.key'),
            content: static fn(Setting $model) => Html::encode($model->getSetting_key()),
            withSorting: true,
            filter: (new DropdownFilter())->optionsData($optionsDataSettingsKeyDropDownFilter),
        ),
        new DataColumn(
            property: 'setting_value',    
            header: $translator->translate('setting.value'),
            content: static fn(Setting $model) => Html::encode($model->getSetting_value()),
            withSorting: true,
            filter: (new DropdownFilter())->optionsData($optionsDataSettingsValueDropDownFilter),
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: static function (Setting $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('setting/view', ['setting_id' => $model->getSetting_id()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('view'),
                ],
            ),
            new ActionButton(
                content: '✎',
                url: static function (Setting $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('setting/edit', ['setting_id' => $model->getSetting_id()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('edit'),
                ],
            ),
            new ActionButton(
                content: '❌',
                url: static function (Setting $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('setting/delete', ['setting_id' => $model->getSetting_id()]);
                },
                attributes: [
                    'title' => $translator->translate('delete'),
                    'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
                ],
            ),
        ]),
    ];
?>
<?php
$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));
$sort = Sort::only(['id', 'setting_key', 'setting_value'])
        ->withOrderString($sortString);

$sortedAndPagedPaginator = (new OffsetPaginator($settings))
    ->withPageSize($s->positiveListLimit())
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

$grid_summary = $s->grid_summary(
    $sortedAndPagedPaginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('settings'),
    '',
);

$toolbarString = Form::tag()->post($urlGenerator->generate('setting/debug_index'))->csrf($csrf)->open() .
    Div::tag()->addClass('float-end m-3')->content($toolbarFilter)->encode(false)->render() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Form::tag()->close();

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-left h-75','id' => 'table-setting'])
/** @psalm-suppress InvalidArgument */
->columns(...$columns)
->dataReader($sortedAndPagedPaginator)
->urlCreator($urlCreator)
// the up and down symbol will appear at first indicating that the column can be sorted
// Ir also appears in this state if another column has been sorted
->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50">⭥</div>')
// the up arrow will appear if column values are ascending
->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
// the down arrow will appear if column values are descending
->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->urlQueryParameters(['filter_setting_key', 'filter_setting_value'])
->emptyCell($translator->translate('not.set'))
->emptyCellAttributes(['style' => 'color:red'])
->header($header)
->id('w439-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($sortedAndPagedPaginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlFastRouteGenerator, 'setting') . ' ' . $grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('no.records'))
->toolbar($toolbarString);
?>
</div>