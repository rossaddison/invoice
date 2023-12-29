<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var \App\Invoice\Entity\FromDropDown $from
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator
 * @var OffsetPaginator $paginator
 * @var string $id
 */
 
 echo $alert;

?>
<h1><?= $translator->translate('invoice.from.email.address'); ?></h1>
<?= Html::a(Html::tag('i','',['class'=>'btn btn-primary fa fa-plus fa-margin']),$urlGenerator->generate('from/add')); ?>
<br>
<br>
<?php
    $header = Div::tag()
      ->addClass('row')
      ->content(
        H5::tag()
        ->addClass('bg-primary text-white p-3 rounded-top')
        ->content(
          I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('invoice.from.email.address'))
        )
      )
      ->render();

    $toolbarReset = A::tag()
      ->addAttributes(['type' => 'reset'])
      ->addClass('btn btn-danger me-1 ajax-loader')
      ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
      ->href($urlGenerator->generate($currentRoute->getName()))
      ->id('btn-reset')
      ->render();

    $toolbar = Div::tag();
       
    $columns = [
        new DataColumn(
            'id',
            header:  $translator->translate('i.id'),
            content: static fn($model) => $model->getId()
        ),
        new DataColumn(
            'default_email',
            header:  $translator->translate('invoice.email.default'),
            content: static fn($model) => $model->getDefault_email() == 'true' ? $translator->translate('i.yes') : $translator->translate('i.no')
        ),
        new DataColumn(
            header:  $translator->translate('i.view'),
            content: static function ($model) use ($urlGenerator): string {
                return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('from/view', ['id' => $model->getId()]), [])->render();
            }
        ),
        new DataColumn(
            header:  $translator->translate('i.edit'),
            content: static function ($model) use ($urlGenerator): string {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']), $urlGenerator->generate('from/edit', ['id' => $model->getId()]), [])->render();
        }
        ),
        new DataColumn(
            header:  $translator->translate('i.delete'),
            content: static function ($model) use ($translator, $urlGenerator): string {
            return Html::a(Html::tag('button',
                  Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                  [
                    'type' => 'submit',
                    'class' => 'dropdown-button',
                    'onclick' => "return confirm(" . "'" . $translator->translate('i.delete_record_warning') . "');"
                  ]
                ),
                $urlGenerator->generate('from/delete', ['id' => $model->getId()]), []
              )->render();
            }
        ),
    ];
    
    echo GridView::widget()
      ->columns(...$columns)
      ->dataReader($paginator)          
      ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
      ->filterPosition('header')
      ->filterModelName('from')
      ->header($header)
      ->id('w99999999999999999-grid')
      ->pagination(
        OffsetPagination::widget()
        ->menuClass('pagination justify-content-center')
        ->paginator($paginator)
        // No need to use page argument since built-in. Use status bar value passed from urlGenerator to inv/guest
        //->urlArguments(['status'=>$status])
        ->render(),
      )
      ->rowAttributes(['class' => 'align-middle'])
      ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
      ->summary($grid_summary)
      ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
      ->emptyText((string) $translator->translate('invoice.invoice.no.records'))
      ->tableAttributes(['class' => 'table table-striped text-center h-99999999999999999', 'id' => 'table-from'])
      ->toolbar(
        Form::tag()->post($urlGenerator->generate('from/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );