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
                I::tag()->addClass('bi bi-receipt')->content(' ' . $s->trans('invoice'))
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
    <h5><?= $s->trans('invoice'); ?></h5>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('inv/guest',['page'=>1,'status'=>0]); ?>"
                   class="btn <?= $status == 0 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $s->trans('all'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest',['page'=>1,'status'=>2]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 2 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $s->trans('sent'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest',['page'=>1,'status'=>3]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 3 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $s->trans('viewed'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest',['page'=>1,'status'=>4]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 4 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $s->trans('paid'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest',['page'=>1,'status'=>5]); ?>" style="text-decoration:none"
                   class="btn  <?= $status == 5 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $s->trans('overdue'); ?>
                </a>
            </div>
    </div>
</div>
<?= GridView::widget()
        ->columns(
            DataColumn::create()
            ->attribute('id')
            ->label($s->trans('id'))
            ->value(static fn (object $model) => $model->getId()),        
            DataColumn::create()
                ->attribute('status_id')
                ->label($s->trans('status'))
                ->value(static function ($model) use ($s, $irR, $inv_statuses): Yiisoft\Html\Tag\CustomTag { 
                    $span = $inv_statuses[(string)$model->getStatus_id()]['label'];
                    if ($model->getCreditinvoice_parent_id()>0) { 
                        $span = Html::tag('i', str_repeat(' ',2).$s->trans('credit_invoice'),['class'=>'fa fa-credit-invoice']);
                    }
                    if (($model->getIs_read_only()) && $s->get_setting('disable_read_only') === (string)0){ 
                        $span = Html::tag('i', str_repeat(' ',2).$s->trans('paid'), ['class'=>'fa fa-read-only']);
                    }
                    if ($irR->repoCount((string)$model->getId())>0) { 
                        $span = Html::tag('i',str_repeat(' ',2).$s->trans('recurring'),['class'=>'fa fa-refresh']);
                    }
                    return Html::tag('span', $span, ['class'=>'label '. $inv_statuses[(string)$model->getStatus_id()]['class']]);
                }       
            ),
            DataColumn::create()
                ->attribute('number')
                ->label('#')
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a($model->getNumber(), $urlGenerator->generate('inv/view',['id'=>$model->getId()]),['style'=>'text-decoration:none'])->render();
               }                       
            ),
            DataColumn::create()
                ->label($s->trans('client'))                
                ->attribute('client_id')     
                ->value(static fn ($model): string => $model->getClient()->getClient_name()                        
            ),
            DataColumn::create()
                ->label($s->trans('date_created'))                
                ->attribute('date_created')     
                ->value(static fn ($model): string => ($model->getDate_created())->format($datehelper->style())                        
            ),
            DataColumn::create()              
                ->attribute('date_due')     
                ->value(static fn ($model): string => ($model->getDate_due())->format($datehelper->style())                        
            ),
            DataColumn::create()
                ->label($s->trans('total'))                
                ->attribute('id')     
                ->value(static function ($model) use ($s, $iaR) : string|null {
                   $inv_id = $model->getId(); 
                   $inv_amount = (($iaR->repoInvAmountCount((int)$inv_id) > 0) ? $iaR->repoInvquery((int)$inv_id) : null);
                   return $s->format_currency(null!==$inv_amount ? $inv_amount->getTotal() : 0.00);
                }                        
            ),
            DataColumn::create()
                ->label($s->trans('paid'))                
                ->attribute('id')     
                ->value(static function ($model) use ($s, $iaR) : string|null {
                   $inv_id = $model->getId(); 
                   $inv_amount = (($iaR->repoInvAmountCount((int)$inv_id) > 0) ? $iaR->repoInvquery((int)$inv_id) : null);
                   return $s->format_currency(null!==$inv_amount ? $inv_amount->getPaid() : 0.00);
                }                        
            ),
            DataColumn::create()
                ->label($s->trans('balance'))                
                ->attribute('id')     
                ->value(static function ($model) use ($s, $iaR) : string|null {
                   $inv_id = $model->getId(); 
                   $inv_amount = (($iaR->repoInvAmountCount((int)$inv_id) > 0) ? $iaR->repoInvquery((int)$inv_id) : null);
                   return $s->format_currency(null!==$inv_amount ? $inv_amount->getBalance() : 0.00);
                }                        
            ),            
        )
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->filterPosition('header')
        ->filterModelName('invoice_guest')
        ->header($header)
        ->id('w8-grid')
        ->paginator($paginator)
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
        ->summary($grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText((string)$translator->translate('invoice.invoice.no.records')) 
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-invoice-guest'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('inv/guest'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
?>
