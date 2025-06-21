<?php

declare(strict_types=1);

use App\Invoice\Entity\CategoryPrimary;
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
 * @var App\Invoice\Entity\CategoryPrimary $categoryprimary
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator 
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf 
 */
 
 echo $alert;

?>

<?= Html::tag('br'); ?>
<?= Html::a(Html::tag('i','',['class'=>'fa fa-plus btn btn-primary fa-margin']), $urlGenerator->generate('categoryprimary/add'),[]); ?>
<?php
    $header = Div::tag()
      ->addClass('row')
      ->content(
        H5::tag()
        ->addClass('bg-primary text-white p-3 rounded-top')
        ->content(
          I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('category.primary'))
        )
      )
      ->render();

    $toolbarReset = A::tag()
      ->addAttributes(['type' => 'reset'])
      ->addClass('btn btn-danger me-1 ajax-loader')
      ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
      ->href($urlGenerator->generate($currentRoute->getName() ?? 'categoryprimary/index'))
      ->id('btn-reset')
      ->render();

    $toolbar = Div::tag();
    
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('id'),
            content: static fn(CategoryPrimary $model) => (string)$model->getId()
        ),
        new DataColumn(
            'name',
            header: $translator->translate('name'),
            content: static fn (CategoryPrimary $model) => Html::encode($model->getName() ?? '')
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: static function (CategoryPrimary $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('categoryprimary/view', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('view'),
                ]
            ),
            new ActionButton(
                content: '✎',
                url: static function (CategoryPrimary $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('categoryprimary/edit', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('edit'),
                ]
            ),
            new ActionButton(
                content: '❌',
                url: static function (CategoryPrimary $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('categoryprimary/delete', ['id' => $model->getId()]);
                },
                attributes: [
                    'title' => $translator->translate('delete'),
                    'onclick' => "return confirm("."'".$translator->translate('delete.record.warning')."');"
                ]
            ),
        ]),
    ];
    $toolbarString = Form::tag()->post($urlGenerator->generate('categoryprimary/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
    $grid_summary = $s->grid_summary($paginator, $translator, (int) $s->getSetting('default.list.limit'), $translator->translate('plural'), '');    
    echo GridView::widget()
      ->bodyRowAttributes(['class' => 'align-middle'])
      ->tableAttributes(['class' => 'table table-striped text-center', 'id' => 'table-categoryprimary'])
      ->columns(...$columns)
      ->dataReader($paginator)
      ->headerRowAttributes(['class' => 'card-header bg-info text-black']) 
      ->id('w194-grid')
      ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
      ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
      ->summaryTemplate($grid_summary)
      ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
      ->emptyText($translator->translate('no.records'))
      ->toolbar($toolbarString);
?>    