<?php

declare(strict_types=1);

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

echo $alert;

/**
 * @var \App\Invoice\Entity\Inv $inv 
 * @var string $csrf
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var TranslatorInterface $translator
 * @var WebView $this
 */

$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('i.invoice'))
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
    <h5><?= $translator->translate('i.invoice'); ?></h5>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('inv/guest',['page'=>1,'status'=>0]); ?>"
                   class="btn <?= $status == 0 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.all'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest',['page'=>1,'status'=>2]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 2 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.sent'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest',['page'=>1,'status'=>3]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 3 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $translator->translate('i.viewed'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest',['page'=>1,'status'=>4]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 4 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.paid'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest',['page'=>1,'status'=>5]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 5 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $translator->translate('i.overdue'); ?>
                </a>
            </div>
    </div>
</div>
<?php
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn (object $model) => $model->getId(),
        ),
        new DataColumn(
            'status_id',
            $translator->translate('i.status'),
            content: static function ($model) use ($s, $irR, $inv_statuses): Yiisoft\Html\Tag\CustomTag { 
                $span = $inv_statuses[(string)$model->getStatus_id()]['label'];
                if ($model->getCreditinvoice_parent_id()>0) { 
                    $span = Html::tag('i', str_repeat(' ',2).$translator->translate('i.credit_invoice'),['class'=>'fa fa-credit-invoice']);
                }
                if (($model->getIs_read_only()) && $s->get_setting('disable_read_only') === (string)0){ 
                    $span = Html::tag('i', str_repeat(' ',2).$translator->translate('i.paid'), ['class'=>'fa fa-read-only']);
                }
                if ($irR->repoCount((string)$model->getId())>0) { 
                    $span = Html::tag('i',str_repeat(' ',2).$translator->translate('i.recurring'),['class'=>'fa fa-refresh']);
                }
                return Html::tag('span', $span, ['class'=>'label '. $inv_statuses[(string)$model->getStatus_id()]['class']]);
            }       
        ),
        new DataColumn(
            'number',
            header: '#',
            content: static function ($model) use ($urlGenerator): string {
               return Html::a($model->getNumber(), $urlGenerator->generate('inv/view',['id'=>$model->getId()]),['style'=>'text-decoration:none'])->render();
           }                       
        ),
        new DataColumn(
            'client_id',                
            content: static fn ($model): string => $model->getClient()->getClient_name()                        
        ),
        new DataColumn(                
            'date_created',
            header: $translator->translate('i.date_created'),    
            content: static fn ($model): string => ($model->getDate_created())->format($datehelper->style())                        
        ),
        new DataColumn(              
            'date_due',     
            content: static fn ($model): string => ($model->getDate_due())->format($datehelper->style())                        
        ),
        new DataColumn(
            'id',     
            header: $translator->translate('i.total'),                
            content: static function ($model) use ($s, $iaR) : string|null {
               $inv_id = $model->getId(); 
               $inv_amount = (($iaR->repoInvAmountCount((int)$inv_id) > 0) ? $iaR->repoInvquery((int)$inv_id) : null);
               return $s->format_currency(null!==$inv_amount ? $inv_amount->getTotal() : 0.00);
            }                        
        ),
        new DataColumn(
            'id',
            header: $translator->translate('i.paid'),                
            content: static function ($model) use ($s, $iaR) : string|null {
               $inv_id = $model->getId(); 
               $inv_amount = (($iaR->repoInvAmountCount((int)$inv_id) > 0) ? $iaR->repoInvquery((int)$inv_id) : null);
               return $s->format_currency(null!==$inv_amount ? $inv_amount->getPaid() : 0.00);
            }                        
        ),
        new DataColumn(
            'id',    
            header: $translator->translate('i.balance'),                
            content: static function ($model) use ($s, $iaR) : string|null {
               $inv_id = $model->getId(); 
               $inv_amount = (($iaR->repoInvAmountCount((int)$inv_id) > 0) ? $iaR->repoInvquery((int)$inv_id) : null);
               return $s->format_currency(null!==$inv_amount ? $inv_amount->getBalance() : 0.00);
            }                        
        ),
    ]            
?>
<?= GridView::widget()
        ->dataReader($paginator)                    
        ->columns(...$columns)
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        //->filterPosition('header')
        //->filterModelName('invoice_guest')
        ->header($header)
        ->id('w8-grid')
        ->pagination(
        OffsetPagination::widget()
             ->menuClass('pagination justify-content-center')
             ->paginator($paginator)
             // No need to use page argument since built-in. Use status bar value passed from urlGenerator to inv/guest   
             ->urlArguments(['status'=>$status])
             ->render(),
        )
        ->rowAttributes(['class' => 'align-middle'])
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summaryTemplate($grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText((string)$translator->translate('invoice.invoice.no.records')) 
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-invoice-guest'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('inv/guest'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
?>
