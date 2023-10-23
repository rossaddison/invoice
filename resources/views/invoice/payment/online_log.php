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
 * @var \App\Invoice\Entity\Merchant $merchant 
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
                I::tag()->addClass('bi bi-receipt')->content(' ' . $s->trans('payment_logs'))
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

<?= GridView::widget()
        ->columns(
            DataColumn::create()
            ->attribute('id')
            ->label($s->trans('id'))
            ->value(static fn (object $model) => $model->getId()),        
            DataColumn::create()
                ->attribute('inv_id')
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a($model->getInv()->getNumber(), $urlGenerator->generate('inv/view',['id'=>$model->getInv_id()]),['style'=>'text-decoration:none'])->render();
               }                       
            ),
            DataColumn::create()
                ->label($s->trans('transaction_successful'))                
                ->attribute('successful')     
                ->value(static function ($model) use ($s) : Yiisoft\Html\Tag\CustomTag {
                    return $model->getSuccessful() ? Html::tag('Label',$s->trans('yes'),['class'=>'btn btn-success']) : Html::tag('Label',$s->trans('no'),['class'=>'btn btn-danger']);
                }
            ),            
            DataColumn::create()
                ->label($s->trans('payment_date'))                
                ->attribute('date')     
                ->value(static fn ($model): string => ($model->getDate())->format($datehelper->style())                        
            ),
            DataColumn::create()
                ->label($s->trans('payment_provider'))                
                ->attribute('driver')     
                ->value(static fn ($model): string => ($model->getDriver())                        
            ),
            DataColumn::create()
                ->label($s->trans('provider_response'))                
                ->attribute('response')     
                ->value(static fn ($model): string => ($model->getResponse())                        
            ),
            DataColumn::create()
                ->label($s->trans('transaction_reference'))                
                ->attribute('reference')     
                ->value(static fn ($model): string => ($model->getReference())                        
            ),                        
        )
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->filterPosition('header')
        ->filterModelName('payment_online_log')
        ->header($header)
        ->id('w3-grid')
        ->paginator($paginator)
        ->pagination(
        OffsetPagination::widget()
             ->menuClass('pagination justify-content-center')
             ->paginator($paginator)
             // eg. http://yii-invoice.myhost/invoice/online_log/page/3?pagesize=5 
             // ...  /page/3?pagesize=5 in the above derived with config/routes.php's payment/online_log
             // ie. Route::get('/online_log[/page/{page:\d+}]')  
             ->urlArguments([])  
             ->render(),
        )
        ->rowAttributes(['class' => 'align-middle'])
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summary('')
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-payment'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('payment/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
            
    $pageSize = $paginator->getCurrentPageSize();
    if ($pageSize > 0) {
      echo Html::p(
        sprintf($translator->translate('invoice.index.footer.showing').' payments: Max '. $max . ' payments per page: Total Payments '.$paginator->getTotalItems() , $pageSize, $paginator->getTotalItems()),
        ['class' => 'text-muted']
    );
    } else {
      echo Html::p($translator->translate('invoice.records.no'));
    }
?>