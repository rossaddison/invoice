<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\I;
use App\Widget\LabelSwitch;

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\CountryHelper $countryHelper
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * 
 * @see $quoteForm is necessary for customValuesHelper viewing custom fields and is not used for input
 * @var App\Invoice\Quote\QuoteForm $quoteForm
 * 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\View\WebView $this
 * @var array $body
 * @var array $customFields
 * @var array $customValues
 * @var array $quoteCustomValues
 * @var array $quoteStatuses
 * @var bool $invEdit
 * @var int $quote_amount_total
 * @var string $add_quote_item
 * @var string $alert
 * @var string $csrf
 * @var string $modal_add_quote_tax
 * @var string $modal_choose_items
 * @var string $modal_delete_quote
 * @var string $modal_quote_to_invoice
 * @var string $modal_quote_to_so
 * @var string $modal_quote_to_pdf
 * @var string $modal_copy_quote
 * @var string $modal_delete_items
 * @var string $partial_item_table
 * @var string $sales_order_number
 * @var string $view_custom_fields
 */

$this->setTitle($translator->translate('i.quote'));

$vat = $s->get_setting('enable_vat_registration');
?>
<div class="panel panel-default">
<div class="panel-heading">
    <?= Html::encode($this->getTitle()); ?>
</div>
    <?php
        echo $modal_delete_quote;
        if ($vat === '0') {
            echo $modal_add_quote_tax;
        }  
        // modal_product_lookups is performed using below $modal_choose_items
        echo $modal_choose_items;
        echo $modal_quote_to_invoice;
        echo $modal_quote_to_so;
        echo $modal_quote_to_pdf;
        echo $modal_copy_quote;
        echo $modal_delete_items;
    ?>
<div>
<br>
<br>
</div>
<div>
    <?php if ($invEdit && $quote->getStatus_id() === 1) { ?>
        <br>
        <br>
        <div class="panel-heading">
            <?= Html::openTag('div'); ?>
                <?= Html::openTag('button', 
                    [
                        'class' => 'btn btn-primary', 
                        'href' => '#modal-choose-items', 
                        'id' => 'modal-choose-items', 
                        'data-bs-toggle' => 'modal'
                    ]); 
                ?>
                <?= I::tag()
                    ->addClass('fa fa-list')
                    ->addAttributes([
                        'data-bs-toggle' => 'tooltip', 
                        'title' => $translator->translate('i.add_product')
                    ]);
                ?>
                <?= $translator->translate('i.add_product'); ?>
                <?= Html::closeTag('button'); ?>
            <?= Html::closeTag('div'); ?>
            <?= $add_quote_item; ?>
        </div>
    <?php } ?>
</div> 
<input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">   
<div id="headerbar">
    <h1 class="headerbar-title">
    <?php
        echo $translator->translate('i.quote') . ' ';
        $number = $quote->getNumber();
        $id = $quote->getId();
        if (null!==($number) && null!==$id) {
            echo($number ? '#' . $number :  $id);
        }
    ?>
    </h1>
        <div class="headerbar-item pull-right">

        <?php
            // Purpose: To remind the user that VAT is enabled
            $s->get_setting('display_vat_enabled_message') === '1' ?
            LabelSwitch::checkbox(
                    'quote-view-label-switch',
                    $s->get_setting('enable_vat_registration'),
                    $translator->translate('invoice.quote.label.switch.on'),
                    $translator->translate('invoice.quote.label.switch.off'),
                    'quote-view-label-switch-id',
                    '16'
            ) : '';
        ?>    
        <div class="options btn-group">
            <a class="btn btn-default" data-toggle="dropdown" href="#">
                <i class="fa fa-chevron-down"></i><?= $translator->translate('i.options'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                <?php
                if ($invEdit) { ?> 
                <li>
                    <a href="<?= $urlGenerator->generate('quote/edit',['id'=>$quote->getId()]) ?>" style="text-decoration:none">
                        <i class="fa fa-edit fa-margin"></i>
                        <?= $translator->translate('i.edit'); ?>
                    </a>
                </li>
                <li>
                    <?php if ($vat === '0') { ?>
                    <a href="#add-quote-tax" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-plus fa-margin"></i>
                        <?= $translator->translate('i.add_quote_tax'); ?>
                    </a>
                    <?php }?>
                </li>
                <?php } ?>
                <li>
                    <a href="#quote-to-pdf"  data-toggle="modal" style="text-decoration:none">
                        <i class="fa fa-print fa-margin"></i>
                        <!-- 
                            views/invoice/quote/modal_quote_to_pdf   ... include custom fields or not on pdf
                            src/Invoice/Quote/QuoteController/pdf ... calls the src/Invoice/Helpers/PdfHelper->generate_quote_pdf
                            src/Invoice/Helpers/PdfHelper ... calls the src/Invoice/Helpers/MpdfHelper
                            src/Invoice/Helpers/MpdfHelper ... saves folder in src/Invoice/Uploads/Archive
                            using 'pdf_quote_template' setting or 'default' views/invoice/template/quote/quote.pdf
                        -->
                        <?= $translator->translate('i.download_pdf'); ?>
                    </a>
                </li>
                <?php if ($invEdit  && $quote->getStatus_id() === 1 && ($quote_amount_total > 0)) { ?>
                <li>
                    <a href="<?= $urlGenerator->generate('quote/email_stage_0',['id'=> $quote->getId()]); ?>" style="text-decoration:none">
                        <i class="fa fa-send fa-margin"></i>
                        <?= $translator->translate('i.send_email'); ?>
                    </a>
                </li>
                <?php // if quote has been approved (ie status 4) by the client without po number do not show quote to sales order again   
                     if ($quote->getSo_id() === '0' && $quote->getStatus_id() === 4) { ?>
                <li>
                    <a href="#quote-to-so" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-refresh fa-margin"></i>
                        <?= $translator->translate('invoice.quote.to.so'); ?>
                    </a>
                </li>
                <?php } ?>
                <li>
                    <a href="#quote-to-invoice" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-refresh fa-margin"></i>
                        <?= $translator->translate('i.quote_to_invoice'); ?>
                    </a>
                </li>
                <li>                    
                    <a href="#quote-to-quote" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-copy fa-margin"></i>
                         <?= $translator->translate('i.copy_quote'); ?>
                    </a>
                </li>
                <li>
                    <a href="#delete-quote" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-trash fa-margin"></i> <?= $translator->translate('i.delete_quote'); ?>
                    </a>
                </li>
                <li>      
                    <a href="#delete-items"  data-toggle="modal" style="text-decoration:none">
                        <i class="fa fa-trash fa-margin"></i>
                        <?= $translator->translate('i.delete')." ".$translator->translate('i.item'); ?>
                    </a>
                </li>
                <?php } ?>
            </ul>
        </div>        
    </div>
</div>

<div id="content">    
    <?= $alert; ?>  
    <div id="quote_form">
        <div class="quote">
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <div class="col-xs-12 col-sm-6 col-md-5">
                    <h3>
                        <a href="<?= $urlGenerator->generate('client/view',['id' => $quote->getClient()?->getClient_id()]); ?>">
                            <?= Html::encode($clientHelper->format_client($quote->getClient())); ?>
                        </a>
                    </h3>
                    <br>
                    <div id="pre_save_client_id" value="<?php echo $quote->getClient()?->getClient_id(); ?>" hidden></div>
                    <div class="client-address">
                        <span class="client-address-street-line-1">
                            <?php echo(null!==$quote->getClient()?->getClient_address_1() ? Html::encode($quote->getClient()?->getClient_address_1()) . '<br>' : ''); ?>
                        </span>
                        <span class="client-address-street-line-2">
                            <?php echo(null!==$quote->getClient()?->getClient_address_2() ? Html::encode($quote->getClient()?->getClient_address_2()) . '<br>' : ''); ?>
                        </span>
                        <span class="client-address-town-line">
                            <?php echo(null!==$quote->getClient()?->getClient_city() ? Html::encode($quote->getClient()?->getClient_city()) . '<br>' : ''); ?>
                            <?php echo(null!==$quote->getClient()?->getClient_state() ? Html::encode($quote->getClient()?->getClient_state()) . '<br>' : ''); ?>
                            <?php echo(null!==$quote->getClient()?->getClient_zip() ? Html::encode($quote->getClient()?->getClient_zip()) : ''); ?>
                        </span>
                        <span class="client-address-country-line">
                            <?php 
                                $countryName = $quote->getClient()?->getClient_country();
                                if (null!==$countryName) {
                                    echo '<br>' . $countryHelper->get_country_name($translator->translate('i.cldr'), $countryName); 
                                } ?>
                        </span>
                    </div>
                    <hr>
                    <?php if (null!==$quote->getClient()?->getClient_phone()): ?>
                        <div class="client-phone">
                            <?= $translator->translate('i.phone'); ?>:&nbsp;
                            <?= Html::encode($quote->getClient()?->getClient_phone()); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (null!==$quote->getClient()?->getClient_mobile()): ?>
                        <div class="client-mobile">
                            <?= $translator->translate('i.mobile'); ?>:&nbsp;
                            <?= Html::encode($quote->getClient()?->getClient_mobile()); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (null!==$quote->getClient()?->getClient_email()): ?>
                        <div class='client-email'>
                            <?= $translator->translate('i.email'); ?>:&nbsp;
                            <?php echo $quote->getClient()?->getClient_email(); ?>
                        </div>
                    <?php endif; ?>
                    <br>
                </div>

                <div class="col-xs-12 visible-xs"><br></div>

                <div class="col-xs-12 col-sm-6 col-md-7">
                    <div class="details-box">
                        <?= Html::openTag('div', ['class' => 'row']); ?>

                            <div class="col-xs-12 col-md-6">

                                <div class="quote-properties">
                                    <label for="quote_number">
                                        <?= $translator->translate('i.quote'); ?> #
                                    </label>
                                    <input type="text" id="quote_number" class="form-control input-sm" readonly
                                        <?php if (null!==($quote->getNumber())) : ?> value="<?= $quote->getNumber(); ?>"
                                        <?php else : ?> placeholder="<?= $translator->translate('i.not_set'); ?>"
                                        <?php endif; ?>>
                                </div>
                                <div class="quote-properties has-feedback">
                                    <label for="quote_date_created">
                                        <?= $vat == '0' ? $translator->translate('invoice.invoice.date.issued') : $translator->translate('i.quote_date'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input name="quote_date_created" id="quote_date_created" disabled
                                               class="form-control input-sm datepicker"
                                               value="<?= Html::encode($quote->getDate_created()->format($dateHelper->style())); ?>"/>
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar fa-fw"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="quote-properties has-feedback">
                                    <label for="quote_date_expires">
                                        <?= $translator->translate('i.expires'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input name="quote_date_expires" id="quote_date_expires" readonly
                                               class="form-control input-sm datepicker"
                                               value="<?= Html::encode($quote->getDate_expires()->format($dateHelper->style())); ?>">
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar fa-fw"></i>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <?php
                                        /**
                                         * @var App\Invoice\Entity\CustomField $customField
                                         */
                                        foreach ($customFields as $customField): ?>
                                        <?php if ($customField->getLocation() !== 1) {continue;} ?>
                                        <?php  $cvH->print_field_for_view($customField, $quoteForm, $quoteCustomValues, $customValues); ?>                                   
                                    <?php endforeach; ?>
                                </div>    
                            </div>
                            <div class="col-xs-12 col-md-6">

                                <div class="quote-properties">
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
                                            foreach ($quoteStatuses as $key => $status) { ?>
                                            <option value="<?php echo $key; ?>" <?php if ($key === $body['status_id']) {  $s->check_select(Html::encode($body['status_id'] ?? ''), $key);} ?>>
                                                <?= Html::encode($status['label']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="quote-properties">
                                    <label for="quote_password" hidden>
                                        <?= $translator->translate('i.quote_password'); ?>
                                    </label>
                                    <input type="text" id="quote_password" class="form-control input-sm" disabled value="<?= Html::encode($body['password'] ?? ''); ?>" hidden>
                                </div>

                                <?php
                                    // show the guest url which the customer will click on to gain access to the site and this quote
                                    // there is no need to show it if it has not been sent yet ie. 1 => draft, 2 => sent
                                    // Update: This button has been replaced with the below button
                                    if ($quote->getStatus_id() !== 1) { ?>
                                    <div class="quote-properties">
                                        <label for="quote_guest_url" hidden><?php echo $translator->translate('i.guest_url'); ?></label>
                                        <div class="input-group" hidden>
                                            <input type="text" id="quote_guest_url" readonly class="form-control" value="<?=  'quote/url_key/'.$quote->getUrl_key(); ?>" hidden>
                                            <span class="input-group-text to-clipboard cursor-pointer"
                                                  data-clipboard-target="#quote_guest_url">
                                                <i class="fa fa-clipboard fa-fw"></i>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php
                                        if (($quote->getStatus_id() === 2 || $quote->getStatus_id() === 3 || $quote->getStatus_id() === 5)  && !$invEdit && ($quote->getSo_id() === '0' || empty($quote->getSo_id()))) 
                                    { ?>
                                    <div>
                                        <br>
                                        <a href="<?= $urlGenerator->generate('quote/url_key',['url_key' => $quote->getUrl_key()]); ?>" class="btn btn-success">  
                                            <?= $translator->translate('i.approve_this_quote') ; ?></i>    
                                        </a>
                                    </div>
                                    <?php } ?>
                                    <?php                                        
                                        if (($quote->getStatus_id() === 2 || $quote->getStatus_id() === 3 || $quote->getStatus_id() === 4 )  && !$invEdit && ($quote->getSo_id() === '0' || empty($quote->getSo_id()))) 
                                    { ?>
                                    <div>
                                        <br>
                                        <a href="<?= $urlGenerator->generate('quote/url_key',['url_key' => $quote->getUrl_key()]); ?>" class="btn btn-danger">  
                                            <?= $translator->translate('i.reject_this_quote') ; ?></i>    
                                        </a>
                                    </div>
                                    <?php } ?>
                                <?php } else {?>
                                    <div class="quote-properties">
                                        <label for="quote_guest_url"><?php echo $translator->translate('i.guest_url'); ?></label>
                                        <div class="input-group">
                                            <input type="text" id="quote_guest_url" readonly  class="form-control" value="">                                            
                                        </div>
                                    </div>
                                <?php } ?>
                                <input type="text" id="dropzone_client_id" readonly  hidden class="form-control" value="<?= $quote->getClient()?->getClient_id(); ?>">
                                <?php if ($quote->getSo_id()) { ?>  
                                <div has-feedback">
                                    <label for="salesorder_to_url"><?= $translator->translate('invoice.salesorder'); ?></label>
                                    <div class="input-group">
                                        <?= Html::a($sales_order_number, $urlGenerator->generate('salesorder/view',['id'=>$quote->getSo_id()]), ['class'=>'btn btn-success']); ?>
                                    </div>
                                </div>
                                <?php } ?>
                            
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
   <div id="partial_item_table_parameters" disabled>
    <?=
       $partial_item_table;
    ?>     
   </div>
    
   <?= Html::openTag('div', ['class' => 'row']); ?>
            <div class="col-xs-12 col-md-6">
                <div class="panel panel-default no-margin">
                    <div class="panel-heading">
                        <?= $translator->translate('i.notes'); ?>
                    </div>
                    <div class="panel-body">
                        <textarea name="notes" id="notes" rows="3" disabled
                                  class="input-sm form-control"><?= Html::encode($body['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="col-xs-12 visible-xs visible-sm"><br></div>

            </div>
            <div id="view_custom_fields" class="col-xs-12 col-md-6">
                <?php //echo $dropzone_quote_html; ?>
                <?php echo $view_custom_fields; ?>
            </div>
    </div>
</div>
</div>    
