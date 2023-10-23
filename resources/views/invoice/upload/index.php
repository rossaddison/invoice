<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var \App\Invoice\Entity\Upload $upload
 * @var CurrentRoute $currentRoute 
 */

echo $alert;

?>
<h1><?= $translator->translate('invoice.upload.index'); ?></h1>
<div>
<?php
    echo Html::a('Add', $urlGenerator->generate('upload/add'), 
            ['class' => 'btn btn-outline-secondary btn-md-12 mb-3']
    );
?>
</div>
<?php
    $header = Div::tag()
        ->addClass('row')
        ->content(
            H5::tag()
                ->addClass('bg-primary text-white p-3 rounded-top')
                ->content(
                    I::tag()->addClass('bi bi-receipt')
                            ->content(' ' . Html::encode($s->trans('client')))
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
?>

<?= GridView::widget()
    ->columns(
        DataColumn::create()
            ->attribute('id')
            ->label($s->trans('id'))
            ->value(static fn (object $model) => Html::encode($model->getId())
        ),
        DataColumn::create()
                ->label($s->trans('client'))                
                ->attribute('client_id')     
                ->value(static fn ($model): string => Html::encode($model->getClient()->getClient_name())                        
        ),
        DataColumn::create()
                ->label($translator->translate('invoice.upload.filename.original'))                
                ->attribute('file_name_original')     
                ->value(static fn ($model): string => Html::encode($model->getFile_name_original())                        
        ),
        DataColumn::create()
                ->label($translator->translate('invoice.upload.filename.new'))                
                ->attribute('file_name_new')     
                ->value(static fn ($model): string => Html::encode($model->getFile_name_new())                        
        ),
        DataColumn::create()
                ->label($translator->translate('invoice.upload.filename.description'))                
                ->attribute('description')     
                ->value(static fn ($model): string => Html::encode($model->getDescription())                        
        ),    
        DataColumn::create()
            ->label($s->trans('view'))    
            ->value(static function ($model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('upload/view',['id'=>$model->getId()]),[])->render();
            }
        ),
        DataColumn::create()
            ->label($s->trans('edit'))    
            ->value(static function ($model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('upload/edit',['id'=>$model->getId()]),[])->render();
            }
        ),
        DataColumn::create()
            ->label($s->trans('delete'))    
            ->value(static function ($model) use ($s, $urlGenerator): string {
               return Html::a( Html::tag('button',
                        Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                        [
                            'type'=>'submit', 
                            'class'=>'dropdown-button',
                            'onclick'=>"return confirm("."'".$s->trans('delete_record_warning')."');"
                        ]
                        ),
                        $urlGenerator->generate('upload/delete',['id'=>$model->getId()]),[]                                         
                    )->render();
            }
        ),    
            
    )
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->filterPosition('header')
    ->filterModelName('upload')
    ->id('w4-grid')
    ->paginator($paginator)
    ->pagination(
        OffsetPagination::widget()
             ->menuClass('pagination justify-content-center')
             ->paginator($paginator)
             ->urlArguments([])
             ->render(),
    )
    ->rowAttributes(['class' => 'align-middle'])
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summary($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText((string)$translator->translate('invoice.invoice.no.records'))
    ->tableAttributes(['class' => 'table table-striped text-center h-125','id'=>'table-upload'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('upload/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );            
?>