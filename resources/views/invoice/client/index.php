<?php

declare(strict_types=1);

use App\Invoice\Entity\Client;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Column\DataColumn;

/**
 * @var App\Invoice\Entity\Client $client
 * @var App\Invoice\ClientPeppol\ClientPeppolRepository $cpR
 * @var App\Invoice\Helpers\DateHelper $dateHelper 
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\UserClient\UserClientRepository $ucR
 * @var App\Widget\Button $button
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter  
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $invoices
 * @var bool $canEdit
 * @var int $active
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $modal_create_client
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientNameDropdownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientSurnameDropdownFilter
 */

echo $alert;

?>

<div>
    <h5><?= Html::encode($translator->translate('i.clients')); ?></h5>
    <div class="btn-group">
        <a href="<?= $urlGenerator->generate('client/add', ['origin' => 'add']);?>" class="btn btn-success" style="text-decoration:none"><i class="fa fa-plus"></i> <?= $translator->translate('i.new'); ?></a>
    </div>
    <br>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('client/index',['page'=>1, 'active'=>2]); ?>"
                   class="btn <?= $active == 2 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.all'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('client/index',['page'=>1, 'active'=>1]); ?>" style="text-decoration:none"
                   class="btn  <?= $active == 1 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.active'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('client/index',['page'=>1, 'active'=>0]); ?>" style="text-decoration:none"
                   class="btn  <?= $active == 0 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.inactive'); ?>
                </a>    
            </div>
    </div>
    <br>
</div>
<?php
    $gridComponents->header('i.client');
    $columns = [
        new DataColumn(
            'client_active',
            header: $translator->translate('i.active'),
            content: static function (Client $model) use ($button, $translator) : string {
                return $model->getClient_active() ? $button::activeLabel($translator) : $button::inactiveLabel($translator);
            }      
        ),
        new DataColumn(
            'client_id',
            header: 'Peppol',
            content: static function (Client $model) use ($cpR, $button, $translator) : string {
                return ($cpR->repoClientCount((string)$model->getClient_id()) !== 0 ) 
                        ? $button::activeLabel($translator) 
                        : $button::inactiveLabel($translator);
            }     
        ),        
        new DataColumn(
            'client_id',
            header: $translator->translate('invoice.client.has.user.account'),
            content: static function (Client $model) use ($canEdit, $ucR, $button, $translator, $urlGenerator) : string {
                return ($ucR->repoUserqueryCount((string)$model->getClient_id()) !== 0  && $canEdit) 
                       ? $button::activeLabel($translator) 
                       : $button::inactiveWithAddUserAccount($urlGenerator, $translator);
            } 
        ),
        new DataColumn(
            field: 'client_name',
            property: 'filter_client_name',    
            header: $translator->translate('i.client_name'),
            content: static function (Client $model) use ($urlGenerator) : string {
                return  A::tag()
                        ->content(Html::encode($model->getClient_name())) 
                        ->href($urlGenerator->generate('client/view', ['id' => $model->getClient_id()]))
                        ->addClass('btn btn-warning ms-2')
                        ->render();
            },
            filter: $optionsDataClientNameDropdownFilter        
        ),
        new DataColumn(
            field:  'client_surname',
            property: 'filter_client_surname',    
            header: $translator->translate('i.client_surname'),
            content: static function (Client $model) use ($urlGenerator) : string {
                return  A::tag()
                        ->content(Html::encode($model->getClient_surname() ?? '')) 
                        ->href($urlGenerator->generate('client/view', ['id' => $model->getClient_id()]))
                        ->addClass('btn btn-warning ms-2')
                        ->render();
            },
            filter: $optionsDataClientSurnameDropdownFilter        
            
        ),        
        new DataColumn(
            'client_birthdate',
            header: $translator->translate('i.birthdate'),
            content: static function (Client $model) use ($dateHelper) : string {
                $clientBirthDate = $model->getClient_birthdate();
                /**
                 * @see App\Invoice\Entity\Client function getClient_birthdate()
                 */
                if (null!==$clientBirthDate && !is_string($clientBirthDate)) {
                    return Html::encode($clientBirthDate->format($dateHelper->style()));
                }
                return '';
            }
        ),        
        new DataColumn(
            'client_phone',
            header: $translator->translate('i.phone'),
            content: static function (Client $model) : string {
                return Html::encode($model->getClient_phone() ?? '');
            }    
        ),
        new DataColumn(
            'client_mobile',
            header: $translator->translate('i.mobile_number'),
            content: static function (Client $model) : string {
                return Html::encode($model->getClient_mobile() ?? '');
            }    
        ),
        new DataColumn(
            'invs',
            content: static function (Client $model) use ($iR, $iaR) : int {
                if (null!==($clientId = $model->getClient_id())) {
                    $invoices = $iR->findAllWithClient($clientId);
                    /**
                     *  Initialize the ArrayCollection
                     *  @see Doctrine\Common\Collections\ArrayCollection
                     *  @see src\Invoice\Entity\Client function setInvs()
                     */
                    $model->setInvs();
                    /**
                     * @var App\Invoice\Entity\Inv $invoice
                     */
                    foreach ($invoices as $invoice) {
                        $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null); 
                        if (null!==$invoice_amount && null!==$invoice_amount->getBalance() && $invoice_amount->getBalance() > 0) {
                            // Load the ArrayCollection
                            $model->addInv($invoice);
                        }
                    }
                    /**
                     * Use the ArrayCollection count method to determine how many invoices there are for this client
                     * @see \vendor\doctrine\Common\Collections\ArrayCollection count method;
                     */
                    return $model->getInvs()->count();
                }
                return 0;
            }    
        ),
        new DataColumn(
            'invs',
            content: static function (Client $model) use ($iR, $iaR, $urlGenerator, $gridComponents) : string  {
                if (null!==($clientId = $model->getClient_id())) { 
                    $invoices = $iR->findAllWithClient($clientId);
                    // Initialize a new empty ArrayCollection without the need to create a new entity
                    $model->setInvs();
                    /**
                     * @var App\Invoice\Entity\Inv $invoice
                     */
                    foreach ($invoices as $invoice) {
                        $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId()) > 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null); 
                        if (null!==$invoice_amount && null!==$invoice_amount->getBalance() && $invoice_amount->getBalance() > 0) {
                            // Load into the ArrayCollection the invoices that make up this balance
                            $model->addInv($invoice);
                        }
                    }
                    // Iterate across $model->getInvs()->toArray() to generate a mini table 
                    // with invoice number, invoice amount, and date
                    return $gridComponents->gridMiniTableOfInvoicesForClient(
                        $model, 
                        $min_invoices_per_row = 4, 
                        $urlGenerator
                    );
                } else {
                    return '';
                }    
            }    
        ),        
        new DataColumn(
            'client_id',    
            header: $translator->translate('i.balance') . ' ('. $s->get_setting('currency_symbol') . ')',
            content: static function (Client $model) use ($iR, $iaR, $s) : string {
                if (null!==($clientId = $model->getClient_id())) { 
                    return Html::encode($s->format_currency($iR->with_total_balance($clientId, $iaR)));
                } else {
                    return '';
                }   
            }
        ),     
        new DataColumn(
            content: static function (Client $model) use ($urlGenerator, $translator, $cpR): string {
                $addUrl = $urlGenerator->generate('clientpeppol/add', ['client_id' => $model->getClient_id()]);
                $editUrl = $urlGenerator->generate('clientpeppol/edit', ['client_id' => $model->getClient_id(), 'origin' => 'edit']);
                $equal = ($cpR->repoClientCount((string)$model->getClient_id()) === 0 ? true : false);
                $heading = ($equal ? $translator->translate('invoice.client.peppol.add') : $translator->translate('invoice.client.peppol.edit'));
                return Html::a(Html::tag('i', $heading, ['class'=>'fa fa-'. ($equal ? 'plus' : 'edit') .'fa-margin']), ($equal ? $addUrl : $editUrl),[])->render();
            }
        ),        
        new DataColumn(
            header: $translator->translate('i.view'),
            content: static function (Client $model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('client/view',['id'=>$model->getClient_id()]),[])->render();
            }
        ),
        new DataColumn(
            header: $translator->translate('i.edit'),
            content: static function (Client $model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('client/edit',['id'=>$model->getClient_id(), 'origin' => 'edit']),[])->render();
            }
        ),
        new DataColumn(
            header: $translator->translate('i.delete'), 
            content: static function (Client $model) use ($translator, $urlGenerator): string {
                return Html::a( Html::tag('button',
                    Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                    [
                        'type'=>'submit', 
                        'class'=>'dropdown-button',
                        'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                    ]
                    ),
                    $urlGenerator->generate('client/delete', ['id' => $model->getClient_id()]),[]                                         
                )->render();
            }
        )        
    ];
        
 ?>
 <?php
    $grid_summary = $s->grid_summary(
        $paginator, 
        $translator, 
        (int)$s->get_setting('default_list_limit'), 
        $translator->translate('invoice.clients'), 
        ''
    );
    $toolbarString = 
        Form::tag()->post($urlGenerator->generate('client/index'))->csrf($csrf)->open() .  
        Div::tag()
            ->addClass('float-end m-3')
            ->content($gridComponents->toolbarReset($urlGenerator))
            ->encode(false)->render() .
        Form::tag()->close();
    echo GridView::widget()    
    ->rowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-client'])
    ->columns(...$columns)
    ->dataReader($paginator)        
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->enableMultisort(true)            
    ->header($gridComponents->header('i.client'))
    ->id('w34-grid')
    ->pagination(
        $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $paginator) 
    )
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $urlGenerator, 'client').' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('invoice.invoice.no.records'))
    ->toolbar($toolbarString);
?>

<div>
    <?php 
        echo $modal_create_client;
    ?>
</div>