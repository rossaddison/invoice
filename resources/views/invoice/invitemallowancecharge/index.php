<?php

declare(strict_types=1);

use App\Invoice\Entity\InvItemAllowanceCharge;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $alert
 * @var string $csrf
 * @var string $inv_item_id
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'invitemallowancecharge/index'))
    ->id('btn-reset')
    ->render();

$backButton = A::tag()
    ->addAttributes([
        'type' => 'reset',
        'onclick' => 'window.history.back()',
        'class' => 'btn btn-primary me-1',
        'id' => 'btn-cancel',
    ])
    ->content('⬅ ️' . $translator->translate('back'))
    ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (InvItemAllowanceCharge $model) => $model->getId(),
    ),
    new DataColumn(
        header: $translator->translate('allowance.or.charge.reason.code'),
        content: static fn (InvItemAllowanceCharge $model) => $model->getAllowanceCharge()?->getReasonCode() ?? '',
    ),
    new DataColumn(
        content: static function (InvItemAllowanceCharge $model) use ($translator): string {
            if ($model->getAllowanceCharge()?->getIdentifier() == 1) {
                return  $translator->translate('allowance.or.charge.charge');
            } else {
                return $translator->translate('allowance.or.charge.allowance');
            }
        },
    ),
    new DataColumn(
        header: $translator->translate('allowance.or.charge.reason'),
        content: static fn (InvItemAllowanceCharge $model) => $model->getAllowanceCharge()?->getReason() ?? '',
    ),
    new DataColumn(
        header: $translator->translate('allowance.or.charge.amount'),
        content: static function (InvItemAllowanceCharge $model) use ($numberHelper): string {
            // show the charge in brackets
            if ($model->getAllowanceCharge()?->getIdentifier() == 0) {
                return '(' . $numberHelper->format_currency($model->getAmount()) . ')';
            } else {
                return $numberHelper->format_currency($model->getAmount());
            }
        },
    ),
    new DataColumn(
        header: $translator->translate('vat'),
        content: static function (InvItemAllowanceCharge $model) use ($numberHelper): string {
            // show the charge in brackets
            if ($model->getAllowanceCharge()?->getIdentifier() == 0) {
                return '(' . $numberHelper->format_currency($model->getVatOrTax()) . ')';
            } else {
                return $numberHelper->format_currency($model->getVatOrTax());
            }
        },
    ),
    new DataColumn(
        header: $translator->translate('edit'),
        content: static function (InvItemAllowanceCharge $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']), $urlGenerator->generate('invitemallowancecharge/edit', ['id' => $model->getId()]), []);
        },
    ),
    new DataColumn(
        header: $translator->translate('view'),
        content: static function (InvItemAllowanceCharge $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('invitemallowancecharge/view', ['id' => $model->getId()]), []);
        },
    ),
    new DataColumn(
        header: $translator->translate('delete'),
        content: static function (InvItemAllowanceCharge $model) use ($translator, $urlGenerator): A {
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
                $urlGenerator->generate('invitemallowancecharge/delete', ['id' => $model->getId()]),
                [],
            );
        },
        encodeContent: false,
    ),
];

$grid_summary =  $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('allowance.or.charge.item'),
    '',
);

$toolbarString
    = Form::tag()->post($urlGenerator->generate('invitemallowancecharge/index'))->csrf($csrf)->open()
    . A::tag()
    ->href($urlGenerator->generate('invitemallowancecharge/add', ['inv_item_id' => $inv_item_id]))
    ->addAttributes(['style' => 'text-decoration:none'])
    ->content('➕ ' . $translator->translate('allowance.or.charge.item.add'))
    ->render()
    . Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    . Div::tag()->addClass('float-end m-3')->content($backButton)->encode(false)->render()
    . Form::tag()->close();

echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-invitemallowancecharge'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->header($translator->translate('allowance.or.charge.item'))
    ->id('w18-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
    ->noResultsText($translator->translate('no.records'))
    ->toolbar($toolbarString);
