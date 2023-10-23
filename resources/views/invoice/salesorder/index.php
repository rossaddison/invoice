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
 * @var \App\Invoice\Entity\SalesOrder $so 
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
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('invoice.salesorder'))
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
    <!-- see SalesOrder/SalesOrderRepository getStatuses function 
     && Invoice\Asset\invoice\css\style.css & yii3i.css -->
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('salesorder/index',['page'=>1,'status'=>0]); ?>"
                   class="btn <?php echo $status == 0 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $s->trans('all'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/index',['page'=>1,'status'=>1]); ?>" style="text-decoration:none"
                   class="btn <?php echo $status == 1 ? 'btn-primary' : 'label '.$so_statuses[(string)1]['class'] ?>">
                    <?= $so_statuses[(string)1]['label']; ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/index',['page'=>1,'status'=>2]); ?>" style="text-decoration:none"
                   data-bs-toggle = "tooltip" title="<?= $s->get_setting('debug_mode') === '1' ? $translator->translate('invoice.payment.term.add.additional.terms.at.setting.repository') : ''; ?>"
                   class="btn  <?php echo $status == 2 ? 'btn-primary' : 'label '.$so_statuses[(string)2]['class'] ?>">
                    <?= $so_statuses[(string)2]['label']; ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/index',['page'=>1,'status'=>3]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 3 ? 'btn-primary' : 'label '.$so_statuses[(string)3]['class']  ?>">
                    <?= $so_statuses[(string)3]['label']; ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/index',['page'=>1,'status'=>4]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 4 ? 'btn-primary' : 'label '.$so_statuses[(string)4]['class'] ?>">
                    <?= $so_statuses[(string)4]['label']; ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/index',['page'=>1,'status'=>5]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 5 ? 'btn-primary' : 'label '.$so_statuses[(string)5]['class']  ?>">
                    <?= $so_statuses[(string)5]['label']; ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/index',['page'=>1,'status'=>6]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 6 ? 'btn-primary' : 'label '.$so_statuses[(string)6]['class']  ?>">
                    <?= $so_statuses[(string)6]['label']; ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/index',['page'=>1,'status'=>7]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 7 ? 'btn-primary' : 'label '.$so_statuses[(string)7]['class']  ?>">
                    <?= $so_statuses[(string)7]['label']; ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/index',['page'=>1,'status'=>8]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 8 ? 'btn-primary' : 'label '.$so_statuses[(string)8]['class']  ?>">
                    <?= $so_statuses[(string)8]['label']; ?>
                </a>
                 <a href="<?= $urlGenerator->generate('salesorder/index',['page'=>1,'status'=>9]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 9 ? 'btn-primary' : 'label '.$so_statuses[(string)9]['class']  ?>">
                    <?= $so_statuses[(string)9]['label']; ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/index',['page'=>1,'status'=>10]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 10 ? 'btn-primary' : 'label '.$so_statuses[(string)10]['class']  ?>">
                    <?= $so_statuses[(string)10]['label']; ?>
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
            ->value(static function ($model) use ($so_statuses): Yiisoft\Html\Tag\CustomTag { 
                $span = $so_statuses[(string)$model->getStatus_id()]['label'];
                return Html::tag('span', $span,['class'=>'label '. $so_statuses[(string)$model->getStatus_id()]['class']]);
            }       
        ),
        DataColumn::create()
            ->attribute('number')
            ->value(static function ($model) use ($urlGenerator): string {
               return Html::a($model->getNumber(), $urlGenerator->generate('salesorder/view',['id'=>$model->getId()]),['style'=>'text-decoration:none'])->render();
            }                       
        ),
        DataColumn::create()
            ->attribute('quote_id')
            ->value(static function ($model) use ($urlGenerator): string {
                return (null!==$model->getQuote_id() ?
                Html::a($model->getQuote_id(), $urlGenerator->generate('quote/view',['id'=>$model->getQuote_id()]),['style'=>'text-decoration:none'])->render() : '');
            }        
        ),
        DataColumn::create()
            ->attribute('inv_id')
            ->value(static function ($model) use ($urlGenerator): string {
               return (null!==$model->getInv_id() ? 
               Html::a($model->getInv_id(), $urlGenerator->generate('inv/view',['id'=>$model->getInv_id()]),['style'=>'text-decoration:none'])->render() : '');
            }        
        ),
        DataColumn::create()
            ->label($s->trans('date_created'))
            ->attribute('date_created')
            ->value(static fn ($model): string => ($model->getDate_created())->format($datehelper->style())                        
        ),
        DataColumn::create()
            ->label($s->trans('client'))                
            ->attribute('client_id')     
            ->value(static fn ($model): string => $model->getClient()->getClient_name()                        
        ),
        DataColumn::create()
            ->label($s->trans('total'))    
            ->attribute('id')
            ->value(function ($model) use ($s, $soaR) : string|null {
               $so_id = $model->getId(); 
               $so_amount = (($soaR->repoSalesOrderAmountCount((string)$so_id) > 0) ? $soaR->repoSalesOrderquery((string)$so_id) : null);
               return $s->format_currency(null!==$so_amount ? $so_amount->getTotal() : 0.00);
            }
        ),
        DataColumn::create()
            ->label($s->trans('view')) 
            ->value(static function ($model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('salesorder/view',['id'=>$model->getId()]),[])->render();
            }                        
        ),
        DataColumn::create()
            ->label($s->trans('edit')) 
            ->value(static function ($model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('salesorder/edit',['id'=>$model->getId()]),[])->render();
            }                        
        ),      
    )
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->filterPosition('header')
    ->filterModelName('salesorder')
    ->header($header)
    ->id('w12-grid')
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
    // @see yiisoft\yii-dataview\src\BaseListView.php
    ->summary((string)$grid_summary ?: '')
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText((string)$translator->translate('invoice.invoice.no.records'))
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-quote'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('salesorder/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );
?>

 