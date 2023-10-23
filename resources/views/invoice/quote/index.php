<?php

declare(strict_types=1);

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;
use Yiisoft\Router\CurrentRoute;

/**
 * @var \App\Invoice\Entity\Quote $quote 
 * @var string $csrf
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var TranslatorInterface $translator
 * @var WebView $this
 */

echo $alert;

$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')->content(' ' . $s->trans('quote'))
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
    <h5><?= $s->trans('quote'); ?></h5>
    <div class="btn-group">
        <?php
            echo $modal_create_quote;
        ?>
        <?php if ($client_count === 0) { ?>
        <a href="#create-quote" class="btn btn-success" data-toggle="modal" disabled data-bs-toggle = "tooltip" title="<?= $s->trans('add_client'); ?>">
            <i class="fa fa-plus"></i><?= $s->trans('new'); ?>
        </a>
        <?php } else { ?>
        <a href="#create-quote" class="btn btn-success" data-toggle="modal">
            <i class="fa fa-plus"></i><?= $s->trans('new'); ?>
        </a>
        <?php } ?>
    </div>
    <br>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>0]); ?>"
                   class="btn <?php echo $status == 0 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $s->trans('all'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>1]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 1 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $s->trans('draft'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>2]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 2 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $s->trans('sent'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>3]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 3 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $s->trans('viewed'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>4]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 4 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $s->trans('approved'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>5]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 5 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $s->trans('rejected'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>6]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 6 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $s->trans('canceled'); ?>
                </a>
            </div>
    </div>
</div>
<br>
<?= GridView::widget()
    ->columns(
        DataColumn::create()
        ->attribute('id')
        ->label($s->trans('id'))
        ->value(static fn (object $model) => $model->getId()),        
        DataColumn::create()
            ->attribute('status_id')
            ->label($s->trans('status'))
            ->value(static function ($model) use ($quote_statuses): Yiisoft\Html\Tag\CustomTag { 
                $span = $quote_statuses[(string)$model->getStatus_id()]['label'];
                return Html::tag('span', $span,['class'=>'label '. $quote_statuses[(string)$model->getStatus_id()]['class']]);
            }       
        ),
        DataColumn::create()
            ->label($translator->translate('invoice.salesorder.number.status'))                
            ->attribute('so_id')     
            ->value(static function ($model) use ($s, $urlGenerator, $soR, $quote_statuses) : string {
                $so_id = $model->getSo_id();
                $so = $soR->repoSalesOrderUnloadedquery($so_id);
                if ($so) {
                    return (string)Html::a($so->getNumber(). ' '. (string)$soR->getStatuses($s)[$so->getStatus_id()]['label'],$urlGenerator->generate('salesorder/view',['id'=>$so_id]),['style'=>'text-decoration:none','class'=> 'label '. (string)$soR->getStatuses($s)[$so->getStatus_id()]['class']]);
                }
                if ($model->getSo_id() === '0' && $model->getStatus_id() === 7 ) {
                    return (string)Html::a($quote_statuses[$model->getStatus_id()]['label'],'',['class'=>'btn btn-warning']);
                }
                return '';
            }            
        ),
        DataColumn::create()
            ->attribute('number')
            ->value(static function ($model) use ($urlGenerator): string {
               return Html::a($model->getNumber(), $urlGenerator->generate('quote/view',['id'=>$model->getId()]),['style'=>'text-decoration:none'])->render();
            }                       
        ),
        DataColumn::create()
            ->label($s->trans('date_created'))
            ->attribute('date_created')
            ->value(static fn ($model): string => ($model->getDate_created())->format($datehelper->style())                        
        ),
        DataColumn::create()
            ->attribute('date_expires')   
            ->value(static fn ($model): string => ($model->getDate_expires())->format($datehelper->style())                        
        ),        
        DataColumn::create()
            ->attribute('date_required')
            ->value(static fn ($model): string => ($model->getDate_required())->format($datehelper->style())
        ), 
        DataColumn::create()
            ->label($s->trans('client'))                
            ->attribute('client_id')     
            ->value(static fn ($model): string => $model->getClient()->getClient_name()                        
        ),
        DataColumn::create()
            ->label($s->trans('total'))    
            ->attribute('id')
            ->value(function ($model) use ($s, $qaR) : string|null {
               $quote_id = $model->getId(); 
               $quote_amount = (($qaR->repoQuoteAmountCount((string)$quote_id) > 0) ? $qaR->repoQuotequery((string)$quote_id) : null);
               return $s->format_currency(null!==$quote_amount ? $quote_amount->getTotal() : 0.00);
            }
        ),
        DataColumn::create()
            ->label($s->trans('view'))    
            ->value(static function ($model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('quote/view',['id'=>$model->getId()]),[])->render();
            }
        ),
        DataColumn::create()
            ->label($s->trans('edit'))    
            ->value(static function ($model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('quote/edit',['id'=>$model->getId()]),[])->render();
            }
        ),
        DataColumn::create()
            ->label($s->trans('delete'))    
            ->value(static function ($model) use ($s, $urlGenerator): string {
                if ($model->getStatus_id() == '1') {
                    return Html::a( Html::tag('button',
                                                        Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                                                        [
                                                            'type'=>'submit', 
                                                            'class'=>'dropdown-button',
                                                            'onclick'=>"return confirm("."'".$s->trans('delete_record_warning')."');"
                                                        ]
                                                        ),
                        $urlGenerator->generate('quote/delete',['id'=>$model->getId()]),[]                                         
                    )->render();
                } else { return ''; }
            }
        ),          
    )
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->filterPosition('header')
    ->filterModelName('quote')
    ->header($header)
    ->id('w2-grid')
    ->paginator($paginator)
    ->pagination(
    OffsetPagination::widget()
         ->menuClass('pagination justify-content-center')
         ->paginator($paginator)         
         // No need to use page argument since built-in. Use status bar value passed from urlGenerator to quote/guest
         ->urlArguments(['status'=>$status])
         ->render(),
    )
    ->rowAttributes(['class' => 'align-middle'])
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summary($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText((string)$translator->translate('invoice.invoice.no.records'))
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-quote'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('quote/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );
?>

 