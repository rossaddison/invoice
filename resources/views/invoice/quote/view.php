<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\I;

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 *
 * Related logic: see $quoteForm is necessary for customValuesHelper viewing
 * custom fields and is not used for input
 * @var App\Invoice\Quote\QuoteForm $quoteForm
 *
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\View\WebView $this
 * @var string $alert
 * @var string $csrf
 * @var string $modal_add_allowance_charge
 * @var string $modal_add_quote_tax
 * @var string $modal_choose_products
 * @var string $modal_choose_tasks
 * @var string $modal_delete_quote
 * @var string $modal_quote_to_invoice
 * @var string $modal_quote_to_so
 * @var string $modal_quote_to_pdf
 * @var string $modal_copy_quote
 * @var string $modal_delete_items
 * @var string $partial_item_table
 * @var string $partial_quote_delivery_location
 * @var string $quoteToolbar
 * @var string $view_custom_fields
 * @var string $view_details_box_with_custom_field
 * @var string $view_product_task_tabs
 * @var string $view_quote_number
 * @var string $view_quote_client_details
 * @var string $view_quote_vat_enabled_switch
 * @var string $view_quote_approve_reject 
 * @var string $_language
 */

$this->setTitle($translator->translate('quote'));

$vat = $s->getSetting('enable_vat_registration');
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
    // modal_product_lookups is performed using below $modal_choose_products
    echo $modal_choose_products;
    echo $modal_choose_tasks;
    echo $modal_quote_to_invoice;
    echo $modal_quote_to_so;
    echo $modal_quote_to_pdf;
    echo $modal_copy_quote;
    echo $modal_delete_items;
    echo $view_product_task_tabs;
?>
<input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">   
<div id="headerbar">
    <h1 class="headerbar-title">
    <?php echo $view_quote_number; ?>
    </h1>
        <div class="headerbar-item pull-right">
        <?php
            echo $view_quote_vat_enabled_switch;
        ?>    
        <?= $quoteToolbar; ?>        
    </div>
</div>

<div id="content">    
    <?= $alert; ?>  
    <div id="quote_form">
        <div class="quote">
            <div class = "row">
                <?= $view_quote_client_details; ?>
                <div class="col-xs-12 visible-xs"><br></div>
                <div class="col-xs-12 col-sm-6 col-md-7">
                    <div class="details-box">
                        <div class = "row">
                        	<?= $view_details_box_with_custom_field; ?>                            
                            <div class="col-xs-12 col-md-6">
                                <?= $view_quote_approve_reject; ?>
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
                        <?= $translator->translate('notes'); ?>
                    </div>
                    <div class="panel-body">
                        <textarea name="notes" 
                                  id="notes" 
                                  rows="3" disabled
                            	  class="input-sm form-control">
                                    <?= Html::encode($body['notes'] ?? ''); ?>
                        </textarea>
                    </div>
                </div>
                <div class="col-xs-12 visible-xs visible-sm"><br></div>
            </div> 
            <div id="view_partial_inv_delivery_location"
                 class="col-xs-12 col-md-6">
                <?= $partial_quote_delivery_location; ?>
            </div> 
            <div id="view_custom_fields" class="col-xs-12 col-md-6">
                <?= $view_custom_fields; ?>
            </div>
    </div>
</div>
<?php echo $modal_add_allowance_charge; ?>
