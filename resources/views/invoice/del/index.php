<?php

declare(strict_types=1);

use App\Invoice\Entity\DeliveryLocation;
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
 * @see App\Invoice\DeliveryLocation\DeliveryLocationController function index
 * @var App\Invoice\Client\ClientRepository $cR
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\Quote\QuoteRepository $qR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $alert
 * @var string $csrf
 * @var string $title
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
      I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('invoice.delivery.location'))
    )
  )
  ->render();

$toolbarReset = A::tag()
  ->addAttributes(['type' => 'reset'])
  ->addClass('btn btn-danger me-1 ajax-loader')
  ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
  ->href($urlGenerator->generate($currentRoute->getName() ?? 'deliverylocation/index'))
  ->id('btn-reset')
  ->render();

$toolbar = Div::tag();
?>
<h1><?= $translator->translate('invoice.delivery.location'); ?></h1>
<?php 
    $columns = [
        new DataColumn(
            'id',
            header:  $translator->translate('i.id'),
            content: static fn(DeliveryLocation $model) => $model->getId()
        ),
        new DataColumn(
            'client_id',              
            header:  $translator->translate('i.client'),
            content: static function (DeliveryLocation $model) use ($cR): string {
                if ($cR->repoClientCount($model->getClient_id()) > 0) {
                    return $cR->repoClientquery($model->getClient_id())->getClient_name();
                }
                return '#';
            }
        ),
        new DataColumn(
            'id',
            header:  $translator->translate('invoice.quote.delivery.location.index.button.list'),
            content: static function (DeliveryLocation $model) use ($urlGenerator, $qR, $dateHelper) : string {
                $deliveryLocationId = $model->getId();
                if (null!==$deliveryLocationId) {
                    $quotes = $qR->findAllWithDeliveryLocation($deliveryLocationId);
                    $buttons = '';
                    $button = '';
                    /**
                     * @var App\Invoice\Entity\Quote $quote
                     */
                    foreach ($quotes as $quote) {
                        $quoteId = $quote->getId();
                        if (null!==$quoteId) {
                            $button = (string)Html::a(($quote->getNumber() ?? '#').
                                       ' '.
                                       ($quote->getDate_created())->format($dateHelper->style()), 
                                             $urlGenerator->generate('quote/view', ['id'=>$quoteId]),
                              ['class'=>'btn btn-primary btn-sm',
                               'data-bs-toggle' => 'tooltip',
                               'title' => $quoteId 
                              ]);
                            $buttons .= $button . str_repeat("&nbsp;", 1);
                        }
                    }
                    return $buttons;
                }
                return '';
            }    
        ),          
        new DataColumn(
            'id',
            header:  $translator->translate('invoice.invoice.delivery.location.index.button.list'),
            content: static function (DeliveryLocation $model) use ($urlGenerator, $iR, $dateHelper) : string {
                $deliveryLocationId = $model->getId();
                if (null!==$deliveryLocationId) {
                    $invoices = $iR->findAllWithDeliveryLocation($deliveryLocationId);
                    $buttons = '';
                    $button = '';
                    /**
                     * @var App\Invoice\Entity\Inv $invoice
                     */
                    foreach ($invoices as $invoice) {
                        $invoiceId = $invoice->getId(); 
                        if (null!==$invoiceId) {
                            $button = (string)Html::a(($invoice->getNumber() ?? '#').
                                    ' '.
                                    ($invoice->getDate_created())->format(
                                        $dateHelper->style()), 
                                        $urlGenerator->generate('inv/view',
                                        ['id'=>$invoiceId]
                                    ),
                            ['class'=>'btn btn-primary btn-sm',
                             'data-bs-toggle' => 'tooltip',
                             'title' => $invoiceId 
                            ]);
                            $buttons .= $button . str_repeat("&nbsp;", 1);
                        }
                    }
                    return $buttons;
                }
                return '';
            }
        ),  
        new DataColumn(
            'global_location_number',    
            header:  $translator->translate('invoice.delivery.location.global.location.number'),
            content: static function (DeliveryLocation $model): string {
                return (string) $model->getGlobal_location_number();
            }
        ),
        new DataColumn(
            'global_location_number',    
            header:  $translator->translate('invoice.delivery.location.global.location.number'),
            content: static function (DeliveryLocation $model): string {
                return (string) $model->getGlobal_location_number();
            }
        ),  
        new DataColumn(
            'date_created',    
            header:  $translator->translate('i.date_created'),
                content: static fn(DeliveryLocation $model): string => ($model->getDate_created())->format($dateHelper->style(),
            ),
        ),
        new DataColumn(
            header:  $translator->translate('i.view'),
            content: static function (DeliveryLocation $model) use ($urlGenerator): string {
                return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), 
                        $urlGenerator->generate('del/view', ['id' => $model->getId()]), [                            
                        ])->render();
            }
        ),
        new DataColumn(
            header:  $translator->translate('i.edit'),
            content: static function (DeliveryLocation $model) use ($urlGenerator): string {
                return Html::a(Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']), 
                        $urlGenerator->generate('del/edit', 
                                ['id' => $model->getId()],
                                ['origin' => 'del', 'origin_id' => '', 'action' => 'index']
                        ), [])->render();
            }
        ),
        new DataColumn(
            header:  $translator->translate('i.delete'),
            content: static function (DeliveryLocation $model) use ($translator, $urlGenerator): string {
            return Html::a(Html::tag('button',
                        Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                        [
                            'type' => 'submit',
                            'class' => 'dropdown-button',
                            'onclick' => "return confirm(" . "'" . $translator->translate('i.delete_record_warning') . "');"
                        ]
                ),
                $urlGenerator->generate('del/delete', ['id' => $model->getId()]), []
            )->render();
            }
        )        
    ];        
?>
<?php 
    $grid_summary = $s->grid_summary(
        $paginator, 
        $translator, 
        (int) $s->get_setting('default_list_limit'), 
        $translator->translate('invoice.delivery.location.plural'),
        ''
    );
    $toolbarString = 
        Form::tag()->post($urlGenerator->generate('del/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
    echo GridView::widget()
    ->rowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-191', 'id' => 'table-delivery'])
    ->columns(...$columns)
    ->dataReader($paginator)          
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->header($header)
    ->id('w341-grid')
    ->pagination(
      OffsetPagination::widget()
      ->paginator($paginator)
      ->render(),
    )
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('invoice.invoice.no.records'))
    ->toolbar($toolbarString);
