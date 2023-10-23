<?php

declare(strict_types=1);

use App\Invoice\Entity\Inv;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var \App\Invoice\Entity\Contract $contract
 * @var string $csrf
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var TranslatorInterface $translator
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
                            ->content(' ' . Html::encode($translator->translate('invoice.invoice.contract')))
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


<div>
    <h5><?= $translator->translate('invoice.invoice.contract.contracts'); ?></h5>
</div>
<br>    
    <?= GridView::widget()
        ->columns(
            DataColumn::create()
                ->attribute('id')
                ->label($s->trans('id'))
                ->value(static fn (object $model) => Html::encode($model->getId())
            ),
            DataColumn::create()
                ->attribute('id')
                ->label($translator->translate('invoice.invoice.contract.index.button.list'))
                ->value(static function ($model) use ($urlGenerator, $iR) : string {
                    $invoices = $iR->findAllWithContract($model->getId());
                    $buttons = '';
                    $button = '';
                    /**
                     * @var Inv $invoice
                     */
                    foreach ($invoices as $invoice) {
                       $button = (string)Html::a($invoice->getNumber(), $urlGenerator->generate('inv/view',['id'=>$invoice->getId()]),
                         ['class'=>'btn btn-primary btn-sm',
                          'data-bs-toggle' => 'tooltip',
                          'title' => $model->getReference() 
                         ]);
                       $buttons .= $button . str_repeat("&nbsp;", 1);
                    }
                    return $buttons;
                }
            ),
            DataColumn::create()
                ->label($s->trans('client'))                
                ->attribute('client_id')     
                ->value(static function ($model) use ($cR) : string {
                    $client = ($cR->repoClientCount($model->getClient_id()) > 0 ? ($cR->repoClientquery($model->getClient_id()))->getClient_name() : '');
                    return (string)$client;
                } 
            ),
            DataColumn::create()
                ->label($translator->translate('invoice.invoice.contract.name'))                
                ->attribute('name')     
                ->value(static fn ($model): string => Html::encode($model->getName())                        
            ),
            DataColumn::create()
                ->label($translator->translate('invoice.invoice.contract.reference'))                
                ->attribute('reference')     
                ->value(static fn ($model): string => Html::encode($model->getReference())                        
            ),
            DataColumn::create()
                ->label($translator->translate('invoice.invoice.contract.period.start'))                
                ->attribute('period_start')     
                ->value(static fn ($model): string => ($model->getPeriod_start())->format($datehelper->style())                        
            ),
            DataColumn::create()
                ->label($translator->translate('invoice.invoice.contract.period.end'))                
                ->attribute('period_end')     
                ->value(static fn ($model): string => ($model->getPeriod_end())->format($datehelper->style())                        
            ),
            DataColumn::create()
                ->label($s->trans('view'))    
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('contract/view',['id'=>$model->getId()]),[])->render();
                }
            ),
            DataColumn::create()
                ->label($s->trans('edit'))    
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('contract/edit',['id'=>$model->getId()]),[])->render();
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
                            $urlGenerator->generate('contract/delete',['id'=>$model->getId()]),[]                                         
                        )->render();
                }
            ),          
        )
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->filterPosition('header')
        ->filterModelName('contract')
        ->header($header)
        ->id('w11-grid')
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
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-contract'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('contract/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
    ?>
    <?php
        $pageSize = $paginator->getCurrentPageSize();
        if ($pageSize > 0) {
            echo Html::p(
                sprintf($translator->translate('invoice.index.footer.showing').' contracts', $pageSize, $paginator->getTotalItems()),
                ['class' => 'text-muted']
            );
        } else {
            echo Html::p($translator->translate('invoice.records.no'));
        }
    ?>
    </div>
</div>
