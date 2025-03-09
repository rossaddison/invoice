<?php

declare(strict_types=1);

use App\Invoice\Entity\Group;
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
use Yiisoft\Router\CurrentRoute;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var CurrentRoute $currentRoute
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 */

echo $alert;

$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')
                        ->content(' ' . Html::encode($translator->translate('invoice.group')))
            )
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'group/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>
<?= Html::openTag('div'); ?>
    <?= Html::openTag('h5'); ?><?= $translator->translate('invoice.group'); ?><?= Html::closeTag('h5'); ?>
        <?= Html::openTag('div'); ?>
            <?= Html::a(I::tag()->addClass('bi bi-plus')->content(' '.Html::encode($translator->translate('i.new'))), $urlGenerator->generate('group/add'), ['class' => 'btn btn-success']); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::br(); ?>
    <?= Html::openTag('div'); ?>

<?php
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn (Group $model) => Html::encode($model->getId())
        ),
        new DataColumn(
            'name',
            header: $translator->translate('i.name'),
            content: static fn (Group $model) => Html::encode($model->getName())
        ),
        new DataColumn(
            'identifier_format',
            header: $translator->translate('i.identifier_format'),
            content: static fn (Group $model) => Html::encode($model->getIdentifier_format())
        ),
        new DataColumn(
            'left_pad',
            header: $translator->translate('i.left_pad'),
            content: static fn (Group $model) => Html::encode($model->getLeft_pad())
        ),
        new DataColumn(
            'next_id',
            header: $translator->translate('i.next_id'),
            content: static fn (Group $model) => Html::encode($model->getNext_id())
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: static function (Group $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('group/view', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('i.view'),
                ]
            ),
            new ActionButton(
                content: '✎',
                url: static function (Group $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('group/edit', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('i.edit'),
                ]
            ),
            new ActionButton(
                content: '❌',
                url: static function (Group $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('group/delete', ['id' => $model->getId()]);
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
$toolbarString =
    Form::tag()->post($urlGenerator->generate('group/index'))->csrf($csrf)->open() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Form::tag()->close();
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int)$s->getSetting('default_list_limit'),
    $translator->translate('invoice.groups'),
    ''
);
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-group'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($header)
->id('w75-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'group').' '.$grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('invoice.invoice.no.records'))
->toolbar($toolbarString);
?>