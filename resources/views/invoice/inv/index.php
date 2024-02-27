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
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\Column\ColumnInterface;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var \App\Invoice\Entity\Inv $inv
 * @var string $csrf
 * @var CurrentRoute $currentRoute
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator
 * @var WebView $this
 */
?>
<?= $alert; ?>
<?php
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
    <div class="btn-group">
        <?php if ($client_count === 0) { ?>
            <a href="#modal-add-inv" class="btn btn-success" data-toggle="modal" disabled data-bs-toggle = "tooltip" title="<?= $translator->translate('i.add_client'); ?>">
                <i class="fa fa-plus"></i><?= $translator->translate('i.new'); ?>
            </a>
        <?php } else { ?>
            <a href="#modal-add-inv" class="btn btn-success" data-toggle="modal">
                <i class="fa fa-plus"></i><?= $translator->translate('i.new'); ?>
            </a>
        <?php } ?>
    </div>
    <br>
    <br>
    <div class="submenu-row">
        <div class="btn-group index-options">
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 0]); ?>"
               class="btn <?= $status == 0 ? 'btn-primary' : 'btn-default' ?>">
                   <?= $translator->translate('i.all'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 1]); ?>" style="text-decoration:none"
               class="btn  <?= $status == 1 ? 'btn-primary' : 'btn-default' ?>">
                   <?= $translator->translate('i.draft'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 2]); ?>" style="text-decoration:none"
               class="btn  <?= $status == 2 ? 'btn-primary' : 'btn-default' ?>">
                   <?= $translator->translate('i.sent'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 3]); ?>" style="text-decoration:none"
               class="btn  <?= $status == 3 ? 'btn-primary' : 'btn-default' ?>">
                   <?= $translator->translate('i.viewed'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 4]); ?>" style="text-decoration:none"
               class="btn  <?= $status == 4 ? 'btn-primary' : 'btn-default' ?>">
                   <?= $translator->translate('i.paid'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 5]); ?>" style="text-decoration:none"
               class="btn  <?= $status == 5 ? 'btn-primary' : 'btn-default' ?>">
                <?= $translator->translate('i.overdue'); ?>
            </a>
        </div>
    </div>
    <br>
</div>

<?php
    /**
     * @var ColumnInterface[] $columns
     */
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn(object $any) => $any->getId()    
        ),
        new DataColumn(
            'status_id',
            header: $translator->translate('i.status'),
            content: static function ($model) use ($s, $irR, $inv_statuses, $translator): Yiisoft\Html\Tag\CustomTag {
                $span = $inv_statuses[(string) $model->getStatus_id()]['label'];
                if ($model->getCreditinvoice_parent_id() > 0) {
                    $span = Html::tag('i', str_repeat(' ', 2) . $translator->translate('i.credit_invoice'), ['class' => 'fa fa-credit-invoice']);
                }
                if (($model->getIs_read_only()) && $s->get_setting('disable_read_only') === (string) 0) {
                    $span = Html::tag('i', str_repeat(' ', 2) . $translator->translate('i.paid'), ['class' => 'fa fa-read-only']);
                }
                if ($irR->repoCount((string) $model->getId()) > 0) {
                    $span = Html::tag('i', str_repeat(' ', 2) . $translator->translate('i.recurring'), ['class' => 'fa fa-refresh']);
                }
                return Html::tag('span', $span, ['class' => 'label ' . $inv_statuses[(string) $model->getStatus_id()]['class']]);
            }    
        ),
        new DataColumn(
            'number',
            content: static function ($model) use ($urlGenerator): string {
                return Html::a($model->getNumber(), $urlGenerator->generate('inv/view', ['id' => $model->getId()]), ['style' => 'text-decoration:none'])->render();
            }     
        ),        
        new DataColumn(
            'quote_id',
            header: $translator->translate('invoice.quote.number.status'),
            content: static function ($model) use ($translator, $urlGenerator, $qR): string {
                $quote_id = $model->getQuote_id();
                $quote = $qR->repoQuoteUnloadedquery($quote_id);
                if ($quote) {
                    return (string) Html::a($quote->getNumber() . ' ' . (string) $qR->getStatuses($translator)[$quote->getStatus_id()]['label'], $urlGenerator->generate('quote/view', ['id' => $quote_id]), ['style' => 'text-decoration:none', 'class' => 'label ' . (string) $qR->getStatuses($translator)[$quote->getStatus_id()]['class']]);
                } else {
                    return '';
                }
            }    
        ),
        new DataColumn(
            'so_id',
            header: $translator->translate('invoice.salesorder.number.status'),
            content: static function ($model) use ($s, $urlGenerator, $soR): string {
                $so_id = $model->getSo_id();
                $so = $soR->repoSalesOrderUnloadedquery($so_id);
                if ($so) {
                    return (string) Html::a($so->getNumber() . ' ' . (string) $soR->getStatuses($s)[$so->getStatus_id()]['label'], $urlGenerator->generate('salesorder/view', ['id' => $so_id]), ['style' => 'text-decoration:none', 'class' => 'label ' . (string) $soR->getStatuses($s)[$so->getStatus_id()]['class']]);
                } else {
                    return '';
                }
            }     
        ),        
        new DataColumn(
            'client_id',
            header: $translator->translate('i.client'),
            content: static fn($model): string => $model->getClient()->getClient_name() . str_repeat(' ', 2).$model->getClient()->getClient_surname()
        ),
        new DataColumn(
            'delivery_location_id',
            header: $translator->translate('invoice.delivery.location.global.location.number'),
            content: static function ($model) use ($dlR): string|null {
                $delivery_location_id = $model->getDelivery_location_id();
                $delivery_location = (($dlR->repoCount($delivery_location_id) > 0) ? $dlR->repoDeliveryLocationquery($delivery_location_id) : null);
                return null !== $delivery_location ? $delivery_location->getGlobal_location_number() : '';
            } 
        ),        
        new DataColumn(
            'date_created',
            header: $translator->translate('i.date_created'),
            content: static fn($model): string => ($model->getDate_created())->format($datehelper->style())
        ),
        new DataColumn(
            'date_due',
            content: static fn($model): string => ($model->getDate_due())->format($datehelper->style())
        ),        
        new DataColumn(
            'id',
            header: $translator->translate('i.total'),
            content: static function ($model) use ($s, $iaR): string|null {
                $inv_id = $model->getId();
                $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ? $iaR->repoInvquery((int) $inv_id) : null);
                return $s->format_currency(null !== $inv_amount ? $inv_amount->getTotal() : 0.00);
            }     
        ),        
        new DataColumn(
            'id',
            header: $translator->translate('i.paid'),
            content: static function ($model) use ($s, $iaR): string|null {
                $inv_id = $model->getId();
                $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ? $iaR->repoInvquery((int) $inv_id) : null);
                return $s->format_currency(null !== $inv_amount ? $inv_amount->getPaid() : 0.00);
            }     
        ),        
        new DataColumn(
            'id',
            header: $translator->translate('i.balance'),
            content: static function ($model) use ($s, $iaR): string|null {
                $inv_id = $model->getId();
                $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ? $iaR->repoInvquery((int) $inv_id) : null);
                return $s->format_currency(null !== $inv_amount ? $inv_amount->getBalance() : 0.00);
            }     
        ),
        new DataColumn(
            header: $translator->translate('invoice.delivery.location.add'),
            content: static function ($model) use ($urlGenerator): string {
                return Html::a(Html::tag('i', '', ['class' => 'fa fa-plus fa-margin']), $urlGenerator->generate('del/add', [
                    /**
                     * 
                     * @see DeliveryLocation add function getRedirectResponse
                     * @see config/common/routes/routes.php Route::methods([Method::GET, Method::POST], '/del/add/{client_id}[/{origin}/{origin_id}/{action}]')
                     */
                    'client_id' => $model->getClient_id()
                    
                ],
                [
                    'origin' => 'inv',
                    'origin_id' => $model->getId(),
                    'action' => 'index'
                ]))->render();
            }     
        ),        
        new DataColumn(
            header: $translator->translate('i.view'), 
            content: static function ($model) use ($urlGenerator): string {
                return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('inv/view', ['id' => $model->getId()]), [])->render();
            }  
        ),        
        new DataColumn(
            header: $translator->translate('i.edit'),
            content: static function ($model) use ($s, $urlGenerator): string {
                return $model->getIs_read_only() === false && $s->get_setting('disable_read_only') === (string) 0 ? Html::a(Html::tag('i', '', ['class' => 'fa fa-edit fa-margin']), $urlGenerator->generate('inv/edit', ['id' => $model->getId()]), [])->render() : '';
            }     
        ),
        new DataColumn(
            header: $translator->translate('i.delete'),
            content: static function ($model) use ($s, $translator, $urlGenerator): string {
                return $model->getIs_read_only() === false && $s->get_setting('disable_read_only') === (string) 0 && $model->getSo_id() === '0' && $model->getQuote_id() === '0' ? Html::a(Html::tag('button',
                        Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                        [
                            'type' => 'submit',
                            'class' => 'dropdown-button',
                            'onclick' => "return confirm(" . "'" . $translator->translate('i.delete_record_warning') . "');"
                        ]
                        ),
                        $urlGenerator->generate('inv/delete', ['id' => $model->getId()]), []
                )->render() : '';
            }     
        )   
    ];
?>
<?php  echo GridView::widget()
    // unpack the contents within the array using the three dot splat operator    
    ->columns(...$columns)
    ->dataReader($paginator)
    //->filterModelName('invoice')
    //->filterPosition('header')
    ->header($header)
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])    
    ->id('w3-grid') 
    ->pagination(
        OffsetPagination::widget()
        ->paginator($paginator)
        //->urlArguments(['status'=>$status])    
        ->render()
    )    
    ->rowAttributes(['class' => 'align-middle'])
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText((string) $translator->translate('invoice.invoice.no.records'))
    ->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-invoice'])
    ->toolbar(
          Form::tag()->post($urlGenerator->generate('quote/index'))->csrf($csrf)->open() .
          Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
          Form::tag()->close()    
    )    
?>

<?php echo $modal_add_inv; ?>