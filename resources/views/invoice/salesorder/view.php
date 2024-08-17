<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;

/**
 * @var App\Invoice\Group\GroupRepository $gR
 * @var App\Invoice\Product\ProductRepository $pR
 * @var App\Invoice\SalesOrder\SalesOrderForm $form
 * @var App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository $soiaR
 * @var App\Invoice\TaxRate\TaxRateRepository $trR
 * @var App\Invoice\Unit\UnitRepository $uR
 * @var App\Invoice\Entity\SalesOrder $so
 * @var App\Invoice\Entity\SalesOrderAmount $so_amount
 * @var App\Invoice\Entity\SalesOrderTaxRate $soTaxRates
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\View\WebView $this
 * @var array $soItems
 * @var array $soStatuses
 * @var array $body
 * @var array $customFields
 * @var array $customValues
 * @var array $salesOrderCustomValues
 * @var string $alert
 * @var string $csrf
 * @var string $invNumber
 * @var string $modal_salesorder_to_pdf
 * @var string $modal_so_to_invoice
 * @var string $partial_item_table
 * @var string $view_custom_fields
 * @var string $title 
 * @var bool $invEdit
 * @var bool $invView
 * */

$this->setTitle($translator->translate('invoice.salesorder'));

$vat = $s->get_setting('enable_vat_registration');
?>
<div class="panel panel-default">
<div class="panel-heading">
    <?= Html::encode($this->getTitle()); ?>
</div>
    <?php
        $clienthelper = new ClientHelper($s);
        $countryhelper = new CountryHelper();  
        $datehelper = new DateHelper($s);  
        $numberhelper = new NumberHelper($s);
        echo $modal_salesorder_to_pdf;
        echo $modal_so_to_invoice;
    ?>
<div>
<br>
<br>
</div> 
<input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">   
<div id="headerbar">
    <h1 class="headerbar-title">
    <?php
        echo $translator->translate('invoice.salesorder');
        $soNumber = $so->getNumber();
        echo(null!==$soNumber ? '#' . $soNumber :  $so->getId());
    ?>
    </h1>
        <div class="headerbar-item pull-right">
        <div class="options btn-group">
            <a class="btn btn-default" data-bs-toggle="dropdown" href="#">
                <i class="fa fa-chevron-down"></i><?= $translator->translate('i.options'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                <?php
                if ($invEdit) { ?> 
                <li>
                    <a href="<?= $urlGenerator->generate('salesorder/edit',['id'=>$so->getId()]) ?>" style="text-decoration:none">
                        <i class="fa fa-edit fa-margin"></i>
                        <?= $translator->translate('i.edit'); ?>
                    </a>
                </li>
                <?php } ?>
                <li>
                    <a href="#so-to-pdf"  data-bs-toggle="modal" style="text-decoration:none">
                        <i class="fa fa-print fa-margin"></i>
                        <?= $translator->translate('i.download_pdf'); ?>
                    </a>
                </li>
                <?php 
                // if there is a sales order number do not show button
                // if the status is draft do not show button
                // only show the button if the sales order has reached invoice generate stage ie 6
                if (null!==$so->getInv_id() || (in_array($so->getStatus_id(),[1,2,3,4,5]))) {} else {?> 
                    <?php if ($invEdit) { ?> 
                        <li>
                            <a href="#so-to-invoice" data-bs-toggle="modal"  style="text-decoration:none">
                                <i class="fa fa-refresh fa-margin"></i>
                                <?= $translator->translate('invoice.salesorder.to.invoice'); ?>
                            </a>
                        </li>
                    <?php } ?>    
                <?php } ?>
            </ul>
        </div>        
    </div>
</div>

<div id="content">    
    <?= $alert; ?>  
    <div id="salesorder_form">
        <div class="salesorder">
            <div class = 'row'>
                <div class="col-xs-12 col-sm-6 col-md-5">
                    <h3>
                        <a href="<?= $urlGenerator->generate('client/view',['id' => $so->getClient()?->getClient_id()]); ?>">
                            <?= Html::encode($clienthelper->format_client($so->getClient())); ?>
                        </a>
                    </h3>
                    <br>
                    <div id="pre_save_client_id" value="<?php echo $so->getClient()?->getClient_id(); ?>" hidden></div>
                    <div class="client-address">
                        <span class="client-address-street-line-1">
                            <?php echo(null!==($so->getClient()?->getClient_address_1()) ? Html::encode($so->getClient()?->getClient_address_1()) . '<br>' : ''); ?>
                        </span>
                        <span class="client-address-street-line-2">
                            <?php echo(null!==$so->getClient()?->getClient_address_2() ? Html::encode($so->getClient()?->getClient_address_2()) . '<br>' : ''); ?>
                        </span>
                        <span class="client-address-town-line">
                            <?php echo(null!==$so->getClient()?->getClient_city() ? Html::encode($so->getClient()?->getClient_city()) . '<br>' : ''); ?>
                            <?php echo(null!==$so->getClient()?->getClient_state() ? Html::encode($so->getClient()?->getClient_state()) . '<br>' : ''); ?>
                            <?php echo(null!==$so->getClient()?->getClient_zip() ? Html::encode($so->getClient()?->getClient_zip()) : ''); ?>
                        </span>
                        <span class="client-address-country-line">
                            <?php
                                $soCountry = $so->getClient()?->getClient_country();
                                echo(null!==$soCountry ? '<br>' . $countryhelper->get_country_name($translator->translate('i.cldr'), $soCountry) : ''); ?>
                        </span>
                    </div>
                    <hr>
                    <?php if (null!==$so->getClient()?->getClient_phone()): ?>
                        <div class="client-phone">
                            <?= $translator->translate('i.phone'); ?>:&nbsp;
                            <?= Html::encode($so->getClient()?->getClient_phone()); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (null!==$so->getClient()?->getClient_mobile()): ?>
                        <div class="client-mobile">
                            <?= $translator->translate('i.mobile'); ?>:&nbsp;
                            <?= Html::encode($so->getClient()?->getClient_mobile()); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (null!==$so->getClient()?->getClient_email()): ?>
                        <div class='client-email'>
                            <?= $translator->translate('i.email'); ?>:&nbsp;
                            <?= Html::encode($so->getClient()?->getClient_email()); ?>
                        </div>
                    <?php endif; ?>
                    <br>
                </div>

                <div class="col-xs-12 visible-xs"><br></div>

                <div class="col-xs-12 col-sm-6 col-md-7">
                    <div class="details-box">
                        <div class = 'row'>

                            <div class="col-xs-12 col-md-6">

                                <div>
                                    <label for="salesorder_number">
                                        <?= $translator->translate('invoice.salesorder'); ?> #
                                    </label>
                                    <input type="text" id="salesorder_number" class="form-control input-sm" readonly
                                        <?php if (null!==$so->getNumber()) : ?> value="<?= $so->getNumber(); ?>"
                                        <?php else : ?> placeholder="<?= $translator->translate('i.not_set'); ?>"
                                        <?php endif; ?>>
                                </div>
                                <div has-feedback">
                                    <label for="salesorder_date_created">
                                        <?= $vat == '0' ? $translator->translate('invoice.invoice.date.issued') : $translator->translate('invoice.salesorder.date.created'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input name="salesorder_date_created" id="salesorder_date_created" disabled
                                               class="form-control input-sm datepicker"
                                               value="<?= Html::encode($so->getDate_created() instanceof \DateTimeImmutable ? 
                                                                       $so->getDate_created()->format('Y-m-d') : (is_string(
                                                                       $so->getDate_created()) ? 
                                                                       $so->getDate_created() : '')); ?>"/>
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar fa-fw"></i>
                                        </span>
                                    </div>
                                </div>
                                <?php if ($invNumber) { ?>  
                                <div has-feedback">
                                    <label for="salesorder_to_url"><?= $translator->translate('invoice.salesorder.invoice'); ?></label>
                                    <div class="input-group">
                                        <?= Html::a($invNumber, $urlGenerator->generate('inv/view',['id' => $so->getInv_id()]), ['class'=>'btn btn-success']); ?>
                                    </div>
                                </div>
                                <?php } ?>
                                <div>
                                    <?php
                                        /**
                                         * @var App\Invoice\Entity\CustomField $customField
                                         */
                                        foreach ($customFields as $customField): ?>
                                        <?php if ($customField->getLocation() !== 1) {continue;} ?>
                                        <?php  $cvH->print_field_for_view($customField, $form, $salesOrderCustomValues, $customValues); ?>                                   
                                    <?php endforeach; ?>
                                </div>    
                            </div>
                            <div class="col-xs-12 col-md-6">

                                <div>
                                    <label for="status_id">
                                        <?= $translator->translate('i.status'); ?>
                                    </label>
                                    <select name="status_id" id="status_id" disabled
                                            class="form-control">
                                        <?php 
                                            /**
                                             * @var string $key
                                             * @var array $status
                                             * @var string $status['label']
                                             */
                                            foreach ($soStatuses as $key => $status) { ?>
                                            <option value="<?php echo $key; ?>" <?php if ($key === $body['status_id']) {  $s->check_select(Html::encode($body['status_id'] ?? ''), $key);} ?>>
                                                <?= Html::encode($status['label']); ?> 
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="salesorder_password" hidden>
                                        <?= $translator->translate('invoice.salesorder.password'); ?>
                                    </label>
                                    <input type="text" id="salesorder_password" class="form-control input-sm" disabled value="<?= Html::encode($body['password'] ?? ''); ?>" hidden>
                                </div>
                                <div>
                                    <label for="salesorder_client_purchase_order_number">
                                        <?= $translator->translate('invoice.salesorder.clients.purchase.order.number'); ?>
                                    </label>
                                    <input type="text" id="salesorder_client_purchase_order_number" class="form-control input-sm" disabled value="<?= Html::encode($body['client_po_number'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label for="salesorder_client_purchase_order_person">
                                        <?= $translator->translate('invoice.salesorder.clients.purchase.order.person'); ?>
                                    </label>
                                    <input type="text" id="salesorder_client_purchase_order_number" class="form-control input-sm" disabled value="<?= Html::encode($body['client_po_person'] ?? ''); ?>">
                                </div>
                               
                                    <?php
                                        // 2 => Terms Agreement Required 8=> Rejected
                                        if (in_array($so->getStatus_id(),[2,8]) && !$invEdit) 
                                        { ?>
                                        <div>
                                            <br>
                                            <a href="<?= $urlGenerator->generate('salesorder/url_key',['key' => $so->getUrl_key()]); ?>" class="btn btn-success">  
                                                <?= $translator->translate('invoice.salesorder.agree.to.terms').'/'.$translator->translate('invoice.salesorder.reject'); ?>    
                                            </a>
                                        </div>
                                    <?php } ?>
                                <input type="text" id="dropzone_client_id" readonly  hidden class="form-control" value="<?= $so->getClient()?->getClient_id(); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
   <div id="partial_item_table_parameters" quote_items="<?php $soItems; ?>" disabled>
    <?=
       $partial_item_table;
    ?>     
   </div>
    
   <div class = 'row'>
            <div class="col-xs-12 col-md-6">
                <div class="panel panel-default no-margin">
                    <div class="panel-heading">
                        <?= $translator->translate('i.notes'); ?>
                    </div>
                    <div class="panel-body">
                        <textarea name="notes" id="notes" rows="3" disabled
                                  class="input-sm form-control"><?= Html::encode($so->getNotes() ?? ''); ?></textarea>
                    </div>
                </div>
                <br>
                <div class="col-xs-12 visible-xs visible-sm"><br></div>

            </div>
            <div id="view_custom_fields" class="col-xs-12 col-md-6">
                 <?php echo $view_custom_fields; ?>
            </div>
    </div>
</div>
</div>    
<div>  
</div>
       
