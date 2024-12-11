<?php
declare(strict_types=1);

use App\Invoice\Entity\SalesOrder;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Entity\SalesOrder $so
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\SalesOrderAmount\SalesOrderAmountRepository $soaR
 * @var App\Invoice\SalesOrder\SalesOrderRepository $soR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button 
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $status
 * @var array $so_statuses
 * @var array $so_statuses[$status]
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 */

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
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'salesorder/guest'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>

<div>
    <!-- see SalesOrder/SalesOrderRepository getStatuses function 
     && Invoice\Asset\invoice\css\style.css & yii3i.css -->
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('salesorder/guest',['page'=>1,'status'=>0]); ?>"
                   class="btn <?php echo $status == 0 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.all'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/guest',['page'=>1,'status'=>2]); ?>" style="text-decoration:none"
                   data-bs-toggle = "tooltip" title="<?= $s->getSetting('debug_mode') === '1' ? $translator->translate('invoice.payment.term.add.additional.terms.at.setting.repository') : ''; ?>"
                   class="btn  <?php echo $status == 2 ? 'btn-primary' : 'label '. $soR->getSpecificStatusArrayClass(2); ?>">
                    <?= $soR->getSpecificStatusArrayLabel('2'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/guest',['page'=>1,'status'=>3]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 3 ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(3);  ?>">
                    <?= $soR->getSpecificStatusArrayLabel('3'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/guest',['page'=>1,'status'=>4]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 4 ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(4); ?>">
                    <?= $soR->getSpecificStatusArrayLabel('4'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/guest',['page'=>1,'status'=>5]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 5 ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(5);  ?>">
                    <?= $soR->getSpecificStatusArrayLabel('5'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/guest',['page'=>1,'status'=>6]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 6 ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(6);  ?>">
                    <?= $soR->getSpecificStatusArrayLabel('6'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/guest',['page'=>1,'status'=>7]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 7 ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(7);  ?>">
                    <?= $soR->getSpecificStatusArrayLabel('7'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/guest',['page'=>1,'status'=>8]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 8 ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(8);  ?>">
                    <?= $soR->getSpecificStatusArrayLabel('8'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('salesorder/guest',['page'=>1,'status'=>9]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 9 ? 'btn-primary' : 'label '.$soR->getSpecificStatusArrayClass(9);  ?>">
                    <?= $soR->getSpecificStatusArrayLabel('9'); ?>
                </a>
            </div>
    </div>
</div>
<div>
<br>
<?= $alert; ?>   
</div>
<?php $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content:static fn (SalesOrder $model) => $model->getId()
        ),        
        new DataColumn(
            'status_id',
            header: $translator->translate('i.status'),
            content: static function (SalesOrder $model) use ($soR): Yiisoft\Html\Tag\CustomTag|string {
                if (null!==$model->getStatus_id()) {
                    $span = $soR->getSpecificStatusArrayLabel((string)$model->getStatus_id());
                    $class = $soR->getSpecificStatusArrayClass((int)$model->getStatus_id());
                    return (string)Html::tag('span', $span,['id'=>'#so-to-invoice','class'=>'label '. $class]);
                }
                return '';
            }       
        ),
        new DataColumn(
            'quote_id',
            header: $translator->translate('invoice.quote.number'),
            content:static function (SalesOrder $model) use ($urlGenerator): string {
               return Html::a($model->getNumber() ?? '#', $urlGenerator->generate('quote/view',['id'=>$model->getQuote_id()]),['style'=>'text-decoration:none'])->render();
            }        
        ),
        new DataColumn(
            'date_created',
            header: $translator->translate('i.date_created'),
            content: static function (SalesOrder $model) use ($dateHelper): string {
                /**
                 * @psalm-suppress PossiblyInvalidMethodCall $model->getDate_created()->format($dateHelper->style())
                 */    
                return Html::encode($model->getDate_created() instanceof \DateTimeImmutable ? $model->getDate_created()->format($dateHelper->style()) : ''); 
            }            
        ),
        new DataColumn(
            'client_id',
            header: $translator->translate('i.client'),                
            content:static function (SalesOrder $model) : string {
                $clientName = $model->getClient()?->getClient_name();
                if (null!==$clientName) {
                   return $clientName;
                } else {
                    return '';
                }   
            },                          
        ),
        new DataColumn(
            'id',
            header: $translator->translate('i.total'),    
            content: function (SalesOrder $model) use ($s, $soaR) : string {
               $so_id = $model->getId(); 
               $so_amount = (($soaR->repoSalesOrderAmountCount((string)$so_id) > 0) ? $soaR->repoSalesOrderquery((string)$so_id) : null);
               return $s->format_currency(null!==$so_amount ? $so_amount->getTotal() : 0.00);
            }
        ),
        new DataColumn(
            header: $translator->translate('i.view'), 
            content:static function (SalesOrder $model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('salesorder/view',['id'=>$model->getId()]),[])->render();
            }                        
        ),    
];
?>
<?php
    $grid_summary =
        $s->grid_summary($paginator, 
        $translator, 
        (int)$s->getSetting('default_list_limit'), 
        $translator->translate('invoice.salesorders'),
        (string)$so_statuses[$status]['label']
    );
    $toolbarString = 
        Form::tag()->post($urlGenerator->generate('salesorder/guest'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
    echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-quote'])
    ->dataReader($paginator)    
    ->columns(...$columns)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->header($header)
    ->id('w12-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'salesorder').' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('invoice.invoice.no.records'))
    ->toolbar($toolbarString);
?>

 