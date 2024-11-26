<?php

declare(strict_types=1);

use App\Invoice\Entity\Contract;
use App\Invoice\Entity\Inv;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Br;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;

/**
 * @var App\Invoice\Client\ClientRepository $cR
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var string $alert
 * @var string $csrf 
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
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'contract/index'))
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
                content: static fn (Contract $model) => Html::encode($model->getId())
            ),
            new DataColumn(
                'id',
                header: $translator->translate('invoice.invoice.contract.index.button.list'),
                content: static function (Contract $model) use ($urlGenerator, $iR) : string {
                    $modelId = $model->getId();
                    if (null!==$modelId) {
                        $invoices = $iR->findAllWithContract($modelId);
                        $buttons = '';
                        $button = '';
                        /**
                         * @var Inv $invoice
                         */
                        foreach ($invoices as $invoice) {
                           $button = (string)Html::a($invoice->getNumber() ?? '#', $urlGenerator->generate('inv/view',['id'=>$invoice->getId()]),
                             ['class'=>'btn btn-primary btn-sm',
                              'data-bs-toggle' => 'tooltip',
                              'title' => $model->getReference() 
                             ]);
                           $buttons .= $button . str_repeat("&nbsp;", 1);
                        }
                        return $buttons;
                    } else {
                        return '';
                    }
                }
            ),
            new DataColumn(
                'client_id',    
                header: $translator->translate('i.client'),                
                content: static function (Contract $model) use ($cR) : string {
                    $client = ($cR->repoClientCount($model->getClient_id()) > 0 ? ($cR->repoClientquery($model->getClient_id()))->getClient_name() : '');
                    return $client;
                } 
            ),
            new DataColumn(
                'name',    
                header: $translator->translate('invoice.invoice.contract.name'),                
                content: static fn (Contract $model): string => Html::encode($model->getName())                        
            ),
            new DataColumn(
                'reference',    
                header: $translator->translate('invoice.invoice.contract.reference'),                
                content: static fn (Contract $model): string => Html::encode($model->getReference())                        
            ),
            new DataColumn(
                'period_start',
                header: $translator->translate('invoice.invoice.contract.period.start'),                
                content: static fn (Contract $model): string => ($model->getPeriod_start())->format($dateHelper->style())                        
            ),
            new DataColumn(
                'period_end',    
                header: $translator->translate('invoice.invoice.contract.period.end'),                
                content: static fn (Contract $model): string => ($model->getPeriod_end())->format($dateHelper->style())                        
            ),
            new ActionColumn(
                content: static fn(Contract $model): string => null!==($modelId = $model->getId()) ? 
               (
                Html::openTag('div', ['class' => 'btn-group']) .
                    Html::a()
                    ->addAttributes([
                        'class' => 'dropdown-button text-decoration-none', 
                        'title' => $translator->translate('i.view')
                    ])
                    ->content('🔎')
                    ->encode(false)
                    ->href('contract/view/'. $modelId)
                    ->render() .
                    Html::a()
                    ->addAttributes([
                        'class' => 'dropdown-button text-decoration-none', 
                        'title' => $translator->translate('i.edit')
                    ])
                    ->content('✎')
                    ->encode(false)
                    ->href('contract/edit/'. $modelId)
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
                    ->href('contract/delete/'. $modelId)
                    ->render() . Html::closeTag('div')
                )   : ''
            ),        
        ];
    ?>
    <?php
        $toolbarString = 
            Form::tag()->post($urlGenerator->generate('contract/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close();
        $grid_summary = $s->grid_summary(
            $paginator, 
            $translator, 
            (int)$s->getSetting('default_list_limit'), 
            $translator->translate('invoice.invoice.contracts'), ''
        ); 
        echo GridView::widget()
        ->bodyRowAttributes(['class' => 'align-middle'])
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-contract'])
        ->columns(...$columns)
        ->dataReader($paginator)    
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
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
        ->emptyText($translator->translate('invoice.invoice.no.records'))
        ->toolbar($toolbarString);
    ?>
