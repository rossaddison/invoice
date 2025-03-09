<?php

declare(strict_types=1);

use App\Invoice\Entity\Unit;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
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
 * @var App\Invoice\Entity\Unit $unit
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
                        ->content(' ' . Html::encode($translator->translate('i.unit')))
            )
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'unit/index'))
    ->id('btn-reset')
    ->render();
$toolbar = Div::tag();
?>
<?= Html::openTag('div'); ?>
    <?= Html::openTag('h5'); ?>
        <?= $translator->translate('i.unit'); ?>
    <?= Html::closeTag('h5'); ?>    
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'btn-group']); ?>
        <?= A::tag()
            ->addClass('btn btn-success')
            ->content(I::tag()
                      ->addClass('fa fa-plus'))
            ->href($urlGenerator->generate('unit/add')); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<br>
    <?php
        $columns = [
            new DataColumn(
                'unit_id',
                header: $translator->translate('i.id'),
                content: static fn (Unit $model) => Html::encode($model->getUnit_id())
            ),
            new DataColumn(
                'unit_name',
                header: $translator->translate('i.unit_name'),
                content: static fn (Unit $model) => Html::encode($model->getUnit_name())
            ),
            new DataColumn(
                'unit_name_plrl',
                header: $translator->translate('i.unit_name_plrl'),
                content: static fn (Unit $model) => Html::encode($model->getUnit_name_plrl())
            ),

            new ActionColumn(buttons: [
                new ActionButton(
                    content: '🔎',
                    url: static function (Unit $model) use ($urlGenerator): string {
                        return $urlGenerator->generate('unit/view', ['id' => $model->getUnit_id()]);
                    },
                    attributes: [
                        'data-bs-toggle' => 'tooltip',
                        'title' => $translator->translate('i.view'),
                    ]
                ),
                new ActionButton(
                    content: '✎',
                    url: static function (Unit $model) use ($urlGenerator): string {
                        return $urlGenerator->generate('unit/edit', ['id' => $model->getUnit_id()]);
                    },
                    attributes: [
                        'data-bs-toggle' => 'tooltip',
                        'title' => $translator->translate('i.edit'),
                    ]
                ),
                new ActionButton(
                    content: '❌',
                    url: static function (Unit $model) use ($urlGenerator): string {
                        return $urlGenerator->generate('unit/delete', ['id' => $model->getUnit_id()]);
                    },
                    attributes: [
                        'title' => $translator->translate('i.delete'),
                        'onclick' => "return confirm("."'".$translator->translate('i.delete_record_warning')."');"
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
        $translator->translate('i.units'),
        ''
    );
$toolbarString = Form::tag()->post($urlGenerator->generate('unit/index'))->csrf($csrf)->open() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Form::tag()->close();
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-unit'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($header)
->id('w175-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('invoice.invoice.no.records'))
->toolbar($toolbarString);
?>
