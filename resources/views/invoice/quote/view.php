<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
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
 * Related logic: see $quoteForm is necessary for customValuesHelper viewing custom fields and is not used for input
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
 * @var string $add_quote_product
 * @var string $add_quote_task
 * @var string $alert
 * @var string $csrf
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
 * @var string $sales_order_number
 * @var string $view_custom_fields
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
?>
<div>
<br>
<br>
</div>
<div>
<!-- comment --> 
<?php if ($invEdit && $quote->getStatus_id() === 1) { ?>
<?= Html::openTag('ul', ['id' => 'product-tabs', 'class' => 'nav nav-tabs nav-tabs-noborder']); ?>
    <?= Html::openTag('li', ['class' => 'active']); ?>
        <?= A::tag()
        ->addAttributes([
            'data-bs-toggle' => 'tab',
            'style' => 'text-decoration:none',
        ])
        ->addClass('btn btn-info me-1')
        ->content(Html::b($translator->translate('add.product')))
        ->href('#add-product-tab')
        ->id('btn-reset')
        ->render();
    ?>
    <?= Html::closeTag('li'); ?>
    <?= Html::openTag('li'); ?>
        <?= A::tag()
        ->addAttributes([
            'data-bs-toggle' => 'tab',
            'style' => 'text-decoration:none',
        ])
        ->addClass('btn btn-info me-1')
        ->content(Html::b($translator->translate('add.task')))
        ->href('#add-task-tab')
        ->id('btn-reset')
        ->render();
    ?>
    <?= Html::closeTag('li'); ?> 
    <?= Html::openTag('li', ['id' => 'back', 'class' => 'tab-pane']); ?>
        <?= A::tag()
        ->addAttributes([
            'type' => 'reset',
            'onclick' => 'window.history.back()',
            'value' => '1',
            'data-bs-toggle' => 'tab',
            'style' => 'text-decoration:none',
        ])
        ->addClass('btn btn-danger bi bi-arrow-left')
        ->id('back')
        ->render(); ?>
    <?= Html::closeTag('li'); ?>    
<?= Html::closeTag('ul'); ?>
    
<?= Html::openTag('div', ['class' => 'tabbable tabs-below']); ?>
    <?= Html::openTag('div', ['class' => 'tab-content']); ?>
        <?= Html::openTag('div', ['id' => 'add-product-tab', 'class' => 'tab-pane']); ?>
            <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
                <?= Html::openTag('div'); ?>
                    <?= Html::openTag(
                        'button',
                        [
                            'class' => 'btn btn-primary',
                            'href' => '#modal-choose-items',
                            'id' => '#modal-choose-items',
                            'data-bs-toggle' => 'modal',
                        ],
                    );
    ?>
                    <?= I::tag()
        ->addClass('fa fa-list')
        ->addAttributes([
            'data-bs-toggle' => 'tooltip',
            'title' => $translator->translate('add.product'),
        ]);
    ?>
                    <?= $translator->translate('add.product'); ?>
                    <?= Html::closeTag('button'); ?>
                <?= Html::closeTag('div'); ?>
                <?= $add_quote_product; ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['id' => 'add-task-tab', 'class' => 'tab-pane']); ?>
            <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
                <?= Html::openTag('div'); ?>
                    <?= Html::openTag('button', [
                        'class' => 'btn btn-primary bi bi-ui-checks w-100',
                        'data-bs-target' => '#modal-choose-tasks-quote',
                        'id' => 'btn-choose-tasks-quote',
                        'data-bs-toggle' => 'modal']);
    ?>
                    <?= $translator->translate('add.task'); ?>
                    <?= Html::closeTag('button'); ?>
                <?= Html::closeTag('div'); ?>           
                <?= $add_quote_task; ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<!-- comment -->
<?php } ?>
<input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">   
<div id="headerbar">
    <h1 class="headerbar-title">
    <?php
        echo $translator->translate('quote') . ' ';
$number = $quote->getNumber();
$id = $quote->getId();
if (null !== ($number) && null !== $id) {
    echo($number ? '#' . $number : $id);
}
?>
    </h1>
        <div class="headerbar-item pull-right">

        <?php
    // Purpose: To remind the user that VAT is enabled
    $s->getSetting('display_vat_enabled_message') === '1' ?
    LabelSwitch::checkbox(
        'quote-view-label-switch',
        $s->getSetting('enable_vat_registration'),
        $translator->translate('quote.label.switch.on'),
        $translator->translate('quote.label.switch.off'),
        'quote-view-label-switch-id',
        '16',
    ) : '';
?>    
        <?= $quoteToolbar; ?>        
    </div>
</div>

<div id="content">    
    <?= $alert; ?>  
    <div id="quote_form">
        <div class="quote">
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <div class="col-xs-12 col-sm-6 col-md-5">
                    <h3>
                        <a href="<?= $urlGenerator->generate('client/view', ['_language' => $_language, 'id' => (int) $quote->getClient()?->getClient_id()]); ?>">
                            <?= Html::encode($clientHelper->format_client($quote->getClient())); ?>
                        </a>
                    </h3>
                    <br>
                    <div id="pre_save_client_id" value="<?php echo $quote->getClient()?->getClient_id(); ?>" hidden></div>
                    <div class="client-address">
                        <span class="client-address-street-line-1">
                            <?php echo(null !== $quote->getClient()?->getClient_address_1() ? Html::encode($quote->getClient()?->getClient_address_1()) . '<br>' : ''); ?>
                        </span>
                        <span class="client-address-street-line-2">
                            <?php echo(null !== $quote->getClient()?->getClient_address_2() ? Html::encode($quote->getClient()?->getClient_address_2()) . '<br>' : ''); ?>
                        </span>
                        <span class="client-address-town-line">
                            <?php echo(null !== $quote->getClient()?->getClient_city() ? Html::encode($quote->getClient()?->getClient_city()) . '<br>' : ''); ?>
                            <?php echo(null !== $quote->getClient()?->getClient_state() ? Html::encode($quote->getClient()?->getClient_state()) . '<br>' : ''); ?>
                            <?php echo(null !== $quote->getClient()?->getClient_zip() ? Html::encode($quote->getClient()?->getClient_zip()) : ''); ?>
                        </span>
                        <span class="client-address-country-line">
                            <?php
                                $countryName = $quote->getClient()?->getClient_country();
if (null !== $countryName) {
    echo '<br>' . $countryHelper->get_country_name($translator->translate('cldr'), $countryName);
} ?>
                        </span>
                    </div>
                    <hr>
                    <?php if (null !== $quote->getClient()?->getClient_phone()): ?>
                        <div class="client-phone">
                            <?= $translator->translate('phone'); ?>:&nbsp;
                            <?= Html::encode($quote->getClient()?->getClient_phone()); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (null !== $quote->getClient()?->getClient_mobile()): ?>
                        <div class="client-mobile">
                            <?= $translator->translate('mobile'); ?>:&nbsp;
                            <?= Html::encode($quote->getClient()?->getClient_mobile()); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (null !== $quote->getClient()?->getClient_email()): ?>
                        <div class='client-email'>
                            <?= $translator->translate('email'); ?>:&nbsp;
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
                                        <?= $translator->translate('quote'); ?> #
                                    </label>
                                    <input type="text" id="quote_number" class="form-control" readonly
                                        <?php if (null !== ($quote->getNumber())) : ?> value="<?= $quote->getNumber(); ?>"
                                        <?php else : ?> placeholder="<?= $translator->translate('not.set'); ?>"
                                        <?php endif; ?>>
                                </div>
                                <div class="quote-properties has-feedback">
                                    <label for="quote_date_created">
                                        <?= $vat == '0' ? $translator->translate('date.issued') : $translator->translate('quote.date'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input name="quote_date_created" id="quote_date_created" disabled
                                               class="form-control"
                                               value="<?= Html::encode($quote->getDate_created()->format('Y-m-d')); ?>"/>
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar fa-fw"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="quote-properties has-feedback">
                                    <label for="quote_date_expires">
                                        <?= $translator->translate('expires'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input name="quote_date_expires" id="quote_date_expires" readonly
                                               class="form-control"
                                               value="<?= Html::encode($quote->getDate_expires()->format('Y-m-d')); ?>">
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
                                        <?php if ($customField->getLocation() !== 1) {
                                            continue;
                                        } ?>
                                        <?php  $cvH->print_field_for_view($customField, $quoteForm, $quoteCustomValues, $customValues); ?>                                   
                                    <?php endforeach; ?>
                                </div>    
                            </div>
                            <div class="col-xs-12 col-md-6">

                                <div class="quote-properties">
                                    <label for="status_id">
                                        <?= $translator->translate('status'); ?>
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
                                            <option value="<?php echo $key; ?>" <?php if ($key === $body['status_id']) {
                                                $s->check_select(Html::encode($body['status_id'] ?? ''), $key);
                                            } ?>>
                                                <?= Html::encode($status['label']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="quote-properties">
                                    <label for="quote_password" hidden>
                                        <?= $translator->translate('quote.password'); ?>
                                    </label>
                                    <input type="text" id="quote_password" class="form-control" disabled value="<?= Html::encode($body['password'] ?? ''); ?>" hidden>
                                </div>

                                <?php
                                    if ($quote->getStatus_id() == 1) { ?>
                                    <div class="quote-properties">
                                        <label for="quote_guest_url" hidden><?php echo $translator->translate('guest.url'); ?></label>
                                        <div class="input-group" hidden>
                                            <input type="text" id="quote_guest_url" disabled class="form-control" value="<?= $quote->getUrl_key(); ?>">
                                            <span class="input-group-text to-clipboard cursor-pointer"
                                                  data-clipboard-target="#quote_guest_url">
                                                <i class="fa fa-clipboard fa-fw"></i>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php
                                        if (($quote->getStatus_id() === 2 || $quote->getStatus_id() === 3 || $quote->getStatus_id() === 5)  && !$invEdit && ($quote->getSo_id() === '0' || empty($quote->getSo_id()))) { ?>
                                    <div>
                                        <br>
                                        <a href="<?= $urlGenerator->generate('quote/url_key', ['url_key' => $quote->getUrl_key()]); ?>" class="btn btn-success">  
                                            <?= $translator->translate('approve.this.quote') ; ?></i>    
                                        </a>
                                    </div>
                                    <?php } ?>
                                    <?php
                                        if (($quote->getStatus_id() === 2 || $quote->getStatus_id() === 3 || $quote->getStatus_id() === 4)  && !$invEdit && ($quote->getSo_id() === '0' || empty($quote->getSo_id()))) { ?>
                                    <div>
                                        <br>
                                        <a href="<?= $urlGenerator->generate('quote/url_key', ['url_key' => $quote->getUrl_key()]); ?>" class="btn btn-danger">  
                                            <?= $translator->translate('reject.this.quote') ; ?></i>    
                                        </a>
                                    </div>
                                    <?php } ?>
                                <?php } else {?>
                                    <div class="quote-properties">
                                        <label for="quote_guest_url"><?php echo $translator->translate('guest.url'); ?></label>
                                        <div class="input-group">
                                            <input type="text" id="quote_guest_url" readonly  class="form-control" value="<?= $translator->translate('approve.this.quote'); ?>">                                            
                                        </div>
                                    </div>
                                <?php } ?>
                                <input type="text" id="dropzone_client_id" readonly  hidden class="form-control" value="<?= $quote->getClient()?->getClient_id(); ?>">
                                <?php if ($quote->getSo_id()) { ?>  
                                <div has-feedback">
                                    <label for="salesorder_to_url"><?= $translator->translate('salesorder'); ?></label>
                                    <div class="input-group">
                                        <?= Html::a($sales_order_number, $urlGenerator->generate('salesorder/view', ['id' => $quote->getSo_id()]), ['class' => 'btn btn-success']); ?>
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
                        <?= $translator->translate('notes'); ?>
                    </div>
                    <div class="panel-body">
                        <textarea name="notes" id="notes" rows="3" disabled
                                  class="input-sm form-control"><?= Html::encode($body['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="col-xs-12 visible-xs visible-sm"><br></div>

            </div> <div id="view_partial_inv_delivery_location" class="col-xs-12 col-md-6">
                <?= $partial_quote_delivery_location; ?>
            </div> 
            <div id="view_custom_fields" class="col-xs-12 col-md-6">
                <?php //echo $dropzone_quote_html;?>
                <?php echo $view_custom_fields; ?>
            </div>
    </div>
</div>
</div>    
