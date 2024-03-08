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
use Yiisoft\Html\Tag\Label;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;
use Yiisoft\Yii\DataView\UrlConfig;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;
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
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('i.quote'))
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
    <h5><?= $translator->translate('i.quote'); ?></h5>
    <div class="btn-group">
        <?php if ($client_count === 0) { ?>
        <a href="#modal-add-quote" class="btn btn-success" data-toggle="modal" disabled data-bs-toggle = "tooltip" title="<?= $translator->translate('i.add_client'); ?>">
            <i class="fa fa-plus"></i><?= $translator->translate('i.new'); ?>
        </a>
        <?php } else { ?>
        <a href="#modal-add-quote" class="btn btn-success" data-toggle="modal">
            <i class="fa fa-plus"></i><?= $translator->translate('i.new'); ?>
        </a>
        <?php } ?>
    </div>
    <br>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>0]); ?>"
                   class="btn <?php echo $status == 0 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.all'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>1]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 1 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.draft'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>2]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 2 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.sent'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>3]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 3 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $translator->translate('i.viewed'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>4]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 4 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.approved'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>5]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 5 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $translator->translate('i.rejected'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index',['page'=>1,'status'=>6]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 6 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $translator->translate('i.canceled'); ?>
                </a>
            </div>
    </div>
</div>
<br>

<?php 
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn (object $model) => $model->getId()
        ),        
        new DataColumn(
            'status_id',
            header: $translator->translate('i.status'),
            content: static function ($model) use ($quote_statuses): Yiisoft\Html\Tag\CustomTag { 
                $span = $quote_statuses[(string)$model->getStatus_id()]['label'];
                return Html::tag('span', $span,['class'=>'label '. $quote_statuses[(string)$model->getStatus_id()]['class']]);
            }       
        ),
        new DataColumn(
            'so_id',
            header: $translator->translate('invoice.salesorder.number.status'),               
            content: static function ($model) use ($s, $urlGenerator, $soR, $quote_statuses) : string {
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
        new DataColumn(
            'number',
            queryProperty: 'filterQuoteNumber',
            header: $translator->translate('invoice.quote.number'),        
            content: static function ($model) use ($urlGenerator): string {
               return Html::a($model->getNumber(), $urlGenerator->generate('quote/view',['id'=>$model->getId()]),['style'=>'text-decoration:none'])->render();
            }, 
            filter: true
        ),
        new DataColumn(
            'date_created',    
            header: $translator->translate('i.date_created'),
            content: static fn ($model): string => ($model->getDate_created())->format($datehelper->style())                        
        ),
        new DataColumn(
            'date_expires',
            content: static fn ($model): string => ($model->getDate_expires())->format($datehelper->style())                        
        ),
        new DataColumn(
            'date_required',
            content: static fn ($model): string => ($model->getDate_required())->format($datehelper->style())
        ),
        new DataColumn(
            'client_id',
            queryProperty: 'filterClient',
            header: $translator->translate('i.client'),
            content: static fn($model): string => $model->getClient()->getClient_name() . str_repeat(' ', 2).$model->getClient()->getClient_surname(),
            filter: $optionsDataClientsDropdownFilter    
        ),
        new DataColumn(
        'id',
            queryProperty: 'filterQuoteAmountTotal',
            header: $translator->translate('i.total') . ' ( '. $s->get_setting('currency_symbol'). ' ) ',
            content: static function ($model) : string|null {
               return  
                    Label::tag()
                        ->attributes(['class' => $model->getQuoteAmount()->getTotal() > 0.00 ? 'label label-success' : 'label label-warning'])
                        ->content(Html::encode(null!==$model->getQuoteAmount()->getTotal() ? $model->getQuoteAmount()->getTotal() : 0.00))
                        ->render();
            },
            filter: true
        ),        
        new DataColumn(
            header: $translator->translate('i.view'),
            content: static function ($model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('quote/view',['id'=>$model->getId()]),[])->render();
            }
        ),
        new DataColumn(
            header: $translator->translate('i.edit'),
            content: static function ($model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('quote/edit',['id'=>$model->getId()]),[])->render();
            }
        ),
        new DataColumn(
            header: $translator->translate('i.delete'), 
            content: static function ($model) use ($translator, $urlGenerator): string {
                if ($model->getStatus_id() == '1') {
                    return Html::a( Html::tag('button',
                        Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                        [
                            'type'=>'submit', 
                            'class'=>'dropdown-button',
                            'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                        ]
                        ),
                        $urlGenerator->generate('quote/delete',['id'=>$model->getId()]),[]                                         
                    )->render();
                } else { return ''; }
            }
        )
    ];
?>
<?=     
    GridView::widget()
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->header($header)
    ->id('w2-grid')
    ->pagination(
    OffsetPagination::widget()
        ->listTag('ul')    
        ->listAttributes(['class' => 'pagination'])
        ->itemTag('li')
        ->itemAttributes(['class' => 'page-item'])
        ->linkAttributes(['class' => 'page-link'])
        ->currentItemClass('active')
        ->currentLinkClass('page-link')
        ->disabledItemClass('disabled')
        ->disabledLinkClass('disabled')
        ->defaultPageSize($defaultPageSizeOffsetPaginator)
        ->urlConfig(new UrlConfig()) 
        ->urlCreator(new UrlCreator($urlGenerator))    
        ->paginator($paginator)
        ->render()
    )
    ->rowAttributes(['class' => 'align-middle'])
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    /**
     * @see config/common/params.php `yiisoft/view` => ['parameters' => ['pageSizeLimiter' ... No need to be in inv/index
     */        
    ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $urlGenerator, 'quote').' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText((string)$translator->translate('invoice.invoice.no.records'))
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-quote'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('quote/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );
?>

<?php echo $modal_add_quote ?> 
