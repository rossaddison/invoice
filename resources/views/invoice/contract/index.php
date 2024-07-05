<?php
declare(strict_types=1);

use App\Invoice\Entity\Inv;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Br;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var \App\Invoice\Entity\Contract $contract
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
                    I::tag()->addClass('bi bi-receipt')
                            ->content(' ' . Html::encode($translator->translate('invoice.invoice.contract')))
                            ->encode(true)
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
<?= Html::openTag('div'); ?>
    <?= Html::openTag('h5'); ?>
        <?= $translator->translate('invoice.invoice.contract.contracts'); ?>
    <?= Html::closeTag('h5'); ?>    
<?= Html::closeTag('div'); ?>

<?= Br::tag(); ?>
    <?php
        $columns = [
            new DataColumn(
                'id',
                header: $translator->translate('i.id'),
                content: static fn (object $model) => Html::encode($model->getId())
            ),
            new DataColumn(
                'id',
                header: $translator->translate('invoice.invoice.contract.index.button.list'),
                content: static function ($model) use ($urlGenerator, $iR) : string {
                    $invoices = $iR->findAllWithContract($model->getId());
                    $buttons = '';
                    $button = '';
                    /**
                     * @var Inv $invoice
                     */
                    foreach ($invoices as $invoice) {
                       $button = (string)Html::a($invoice->getNumber(), $urlGenerator->generate('inv/view',['id'=>$invoice->getId()]),
                         ['class'=>'btn btn-primary btn-sm',
                          'data-bs-toggle' => 'tooltip',
                          'title' => $model->getReference() 
                         ]);
                       $buttons .= $button . str_repeat("&nbsp;", 1);
                    }
                    return $buttons;
                }
            ),
            new DataColumn(
                'client_id',    
                header: $translator->translate('i.client'),                
                content: static function ($model) use ($cR) : string {
                    $client = ($cR->repoClientCount($model->getClient_id()) > 0 ? ($cR->repoClientquery($model->getClient_id()))->getClient_name() : '');
                    return (string)$client;
                } 
            ),
            new DataColumn(
                'name',    
                header: $translator->translate('invoice.invoice.contract.name'),                
                content: static fn ($model): string => Html::encode($model->getName())                        
            ),
            new DataColumn(
                'reference',    
                header: $translator->translate('invoice.invoice.contract.reference'),                
                content: static fn ($model): string => Html::encode($model->getReference())                        
            ),
            new DataColumn(
                'period_start',
                header: $translator->translate('invoice.invoice.contract.period.start'),                
                content: static fn ($model): string => ($model->getPeriod_start())->format($datehelper->style())                        
            ),
            new DataColumn(
                'period_end',    
                header: $translator->translate('invoice.invoice.contract.period.end'),                
                content: static fn ($model): string => ($model->getPeriod_end())->format($datehelper->style())                        
            ),
            new ActionColumn(
                content: static fn($model): string => Html::openTag('div', ['class' => 'btn-group']) .
                Html::a()
                ->addAttributes([
                    'class' => 'dropdown-button text-decoration-none', 
                    'title' => $translator->translate('i.view')
                ])
                ->content('🔎')
                ->encode(false)
                ->href('contract/view/'. $model->getId())
                ->render() .
                Html::a()
                ->addAttributes([
                    'class' => 'dropdown-button text-decoration-none', 
                    'title' => $translator->translate('i.edit')
                ])
                ->content('✎')
                ->encode(false)
                ->href('contract/edit/'. $model->getId())
                ->render() .
                Html::a()
                ->addAttributes([
                    'class'=>'dropdown-button text-decoration-none', 
                    'title' => $translator->translate('i.delete'),
                    'type'=>'submit', 
                    'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                ])
                ->content('❌')
                ->encode(false)
                ->href('contract/delete/'. $model->getId())
                ->render() . Html::closeTag('div')
            ),        
        ];
    ?>
    <?= GridView::widget()
        ->rowAttributes(['class' => 'align-middle'])
        ->columns(...$columns)
        ->dataReader($paginator)    
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        //->filterPosition('header')
        //->filterModelName('contract')
        ->header($header)
        ->id('w11-grid')
        ->pagination(
        OffsetPagination::widget()
             ->paginator($paginator)
             ->render(),
        )
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summaryTemplate($grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText((string)$translator->translate('invoice.invoice.no.records'))
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-contract'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('contract/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
    ?>
