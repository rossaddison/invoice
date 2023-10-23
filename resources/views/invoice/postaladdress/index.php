<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var \App\Invoice\Entity\PostalAddress $postaladdress
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $canEdit
 * @var string $id
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
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('invoice.client.postaladdress'))
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
<h1><?= $translator->translate('invoice.client.postaladdress'); ?></h1>
<?= GridView::widget()
        ->columns(
            DataColumn::create()
            ->attribute('id')
            ->label($s->trans('id'))
            ->value(static fn ($model) => $model->getId()),        
            DataColumn::create()
                ->label($s->trans('client'))                
                ->attribute('client_id')     
                ->value(static function ($model) use ($cR) : string {
                    $client = ($cR->repoClientCount($model->getClient_id()) > 0 ? ($cR->repoClientquery($model->getClient_id()))->getClient_name() : '');
                    return (string)$client;
                } 
            ),
            DataColumn::create()
                ->label($s->trans('view')) 
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('postaladdress/view',['id'=>$model->getId()]),[])->render();
                }                        
            ),
            DataColumn::create()
                ->label($s->trans('edit')) 
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('postaladdress/edit',['id'=>$model->getId()]),[])->render();
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
                            $urlGenerator->generate('postaladdress/delete',['id'=>$model->getId()]),[]                                         
                    )->render();
                }                        
            ),                       
        )
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->filterPosition('header')
        ->filterModelName('postaladdress')
        ->header($header)
        ->id('w3-grid')
        ->paginator($paginator)
        ->pagination(
        OffsetPagination::widget()
             ->menuClass('pagination justify-content-center')
             ->paginator($paginator)
             ->render(),
        )
        ->rowAttributes(['class' => 'align-middle'])
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summary('')
        ->tableAttributes(['class' => 'table table-striped text-center h-85','id'=>'table-postaladdress'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('postaladdress/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
            
    $pageSize = $paginator->getCurrentPageSize();
    if ($pageSize > 0) {
      echo Html::p(
        sprintf($translator->translate('invoice.index.footer.showing').' invoices: Max '. $max . ' invoices per page: Total Invs '.$paginator->getTotalItems() , $pageSize, $paginator->getTotalItems()),
        ['class' => 'text-muted']
    );
    } else {
      echo Html::p($translator->translate('invoice.records.no'));
    }