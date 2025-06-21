<?php

declare(strict_types=1);

use App\Invoice\Entity\Merchant;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\Button $button
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var string $alert
 * @var string $csrf
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
                        ->content(' ' . Html::encode($translator->translate('merchant')))
            )
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'merchant/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>

<div>
    <h5><?= $translator->translate('merchant'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('merchant/add'); ?>">
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
            'id',
            header: $translator->translate('id'),
            content: static fn (Merchant $model) => Html::encode($model->getId())
        ),
        new DataColumn(
            'inv',
            header: $translator->translate('invoice'),
            content: static fn (Merchant $model): string => Html::encode($model->getInv()?->getNumber())
        ),
        new DataColumn(
            'date',
            header: $translator->translate('date'),
            content: static fn (Merchant $model): string => Html::encode(!is_string($date = $model->getDate()) ? $date->format('Y-m-d') : '')
        ),
        new DataColumn(
            'driver',
            header: $translator->translate('merchant.driver'),
            content: static fn (Merchant $model): string => Html::encode($model->getDriver())
        ),
        new DataColumn(
            'response',
            header: $translator->translate('merchant.response'),
            content: static fn (Merchant $model): string => Html::encode($model->getResponse())
        ),
        new DataColumn(
            'reference',
            header: $translator->translate('merchant.reference'),
            content: static fn (Merchant $model): string => Html::encode($model->getReference())
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: static function (Merchant $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('merchant/view', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('view'),
                ]
            ),
            new ActionButton(
                content: '✎',
                url: static function (Merchant $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('merchant/edit', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('edit'),
                ]
            ),
            new ActionButton(
                content: '❌',
                url: static function (Merchant $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('merchant/delete', ['id' => $model->getId()]);
                },
                attributes: [
                    'title' => $translator->translate('delete'),
                    'onclick' => "return confirm("."'".$translator->translate('delete.record.warning')."');"
                ]
            ),
        ]),
    ];
?>
<?php
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int)$s->getSetting('default_list_limit'),
    $translator->translate('merchant'),
    ''
);
$toolbarString = Form::tag()->post($urlGenerator->generate('merchant/index'))->csrf($csrf)->open() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Form::tag()->close();
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-merchant'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($header)
->id('w144-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('no.records'))
->toolbar($toolbarString); ?>
</div>