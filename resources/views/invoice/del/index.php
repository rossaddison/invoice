<?php
declare(strict_types=1);

use App\Invoice\Helpers\DateHelper;
use Yiisoft\Html\Html;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var \App\Invoice\Entity\DeliveryLocation $del
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator
 * @var OffsetPaginator $paginator
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
      I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('invoice.delivery.location'))
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
<h1><?= $translator->translate('invoice.delivery.location'); ?></h1>
<?php 
    $columns = [
        new DataColumn(
            'id',
            header:  $translator->translate('i.id'),
            content: static fn($model) => $model->getId()
        ),
        new DataColumn(
            'client_id',              
            header:  $translator->translate('i.client'),
            content: static function ($model) use ($cR): string {
                $client = $cR->repoClientCount($model->getClient_id()) > 0 ? $cR->repoClientquery($model->getClient_id()) : '';
                return (string) $client->getClient_name();
            }
        ),
        new DataColumn(
            'id',
            header:  $translator->translate('invoice.invoice.delivery.location.index.button.list'),
            content: static function ($model) use ($urlGenerator, $iR, $s) : string {
                $datehelper = new DateHelper($s);
                $invoices = $iR->findAllWithDeliveryLocation($model->getId());
                $buttons = '';
                $button = '';
                /**
                 * @var App\Invoice\Entity\Inv $invoice
                 */
                foreach ($invoices as $invoice) {
                   $button = (string)Html::a($invoice->getNumber().' '.($invoice->getDate_created())->format($datehelper->style()), $urlGenerator->generate('inv/view',['id'=>$invoice->getId()]),
                     ['class'=>'btn btn-primary btn-sm',
                      'data-bs-toggle' => 'tooltip',
                      'title' => $invoice->getId() 
                     ]);
                   $buttons .= $button . str_repeat("&nbsp;", 1);
                }
                return $buttons;
            }
        ),  
        new DataColumn(
            'global_location_number',    
            header:  $translator->translate('invoice.delivery.location.global.location.number'),
            content: static function ($model): string {
                return (string) $model->getGlobal_location_number();
            }
        ),
        new DataColumn(
            'global_location_number',    
            header:  $translator->translate('invoice.delivery.location.global.location.number'),
            content: static function ($model): string {
                return (string) $model->getGlobal_location_number();
            }
        ),  
        new DataColumn(
            'date_created',    
            header:  $translator->translate('i.date_created'),
                content: static fn($model): string => ($model->getDate_created())->format($datehelper->style(),
            ),
        ),
        new DataColumn(
            header:  $translator->translate('i.view'),
            content: static function ($model) use ($urlGenerator): string {
                return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('del/view', ['id' => $model->getId()]), [])->render();
            }
        ),
        new DataColumn(
            header:  $translator->translate('i.edit'),
            content: static function ($model) use ($urlGenerator): string {
                return Html::a(Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']), $urlGenerator->generate('del/edit', ['id' => $model->getId()]), [])->render();
            }
        ),
        new DataColumn(
            header:  $translator->translate('i.delete'),
            content: static function ($model) use ($translator, $urlGenerator): string {
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
<?=
    GridView::widget()
    ->columns(...$columns)
    ->dataReader($paginator)          
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->filterPosition('header')
    ->filterModelName('del')
    ->header($header)
    ->id('w341-grid')
    ->pagination(
      OffsetPagination::widget()
      ->menuClass('pagination justify-content-center')
      ->paginator($paginator)
      // No need to use page argument since built-in. Use status bar value passed from urlGenerator to inv/guest
      //->urlArguments(['status'=>$status])
      ->render(),
    )
    ->rowAttributes(['class' => 'align-middle'])
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summary($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText((string) $translator->translate('invoice.invoice.no.records'))
    ->tableAttributes(['class' => 'table table-striped text-center h-191', 'id' => 'table-delivery'])
    ->toolbar(
      Form::tag()->post($urlGenerator->generate('del/index'))->csrf($csrf)->open() .
      Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
      Form::tag()->close()
  );
