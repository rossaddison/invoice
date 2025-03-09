<?php

declare(strict_types=1);

use App\Widget\Button;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\A;

/**
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Entity\Sumex|null $sumex
 * @var App\Invoice\Inv\InvForm $form
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\CountryHelper $countryHelper
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $custom_fields
 * @var array $custom_values
 * @var array $enabled_gateways
 * @var array $inv_custom_values
 * @var array $inv_items
 * @var array $inv_statuses
 * @var array $payments
 * @var array $payment_methods
 * @var bool $invEdit
 * @var bool $isRecurring
 * @var bool $paymentView
 * @var bool $paymentCfExist
 * @var bool $readOnly
 * @var bool $showButtons
 * @var string $alert
 * @var string $csrf
 * @var string $add_inv_item_product
 * @var string $add_inv_item_task
 * @var string $modal_add_inv_tax
 * @var string $modal_add_allowance_charge
 * @var string $modal_change_client
 * @var string $modal_choose_items
 * @var string $modal_choose_tasks
 * @var string $modal_copy_inv
 * @var string $modal_create_recurring
 * @var string $modal_create_credit
 * @var string $modal_delete_inv
 * @var string $modal_delete_items
 * @var string $modal_inv_to_pdf
 * @var string $modal_inv_to_html
 * @var string $modal_message_no_payment_method
 * @var string $partial_inv_delivery_location
 * @var string $partial_inv_attachments
 * @var string $partial_item_table
 * @var string $peppol_stream_toggle
 * @var string $sales_order_number
 * @var string $title
 * @var string $view_custom_fields
 */

$vat = $s->getSetting('enable_vat_registration');

?>
    <?php
        echo $alert;
echo $modal_delete_inv;
if ($vat === '0') {
    echo $modal_add_inv_tax;
}
echo $modal_change_client;
// modal_product_lookups is performed using below $modal_choose_items
echo $modal_choose_items;
// modal_task_lookups is performed using below $modal_choose_tasks
echo $modal_choose_tasks;
echo $modal_inv_to_pdf;
echo $modal_inv_to_html;
echo $modal_copy_inv;
echo $modal_delete_items;
echo $modal_create_credit;
echo $modal_message_no_payment_method;
?>   

   
<?php if (!empty($payments)) { ?>
        <br>
        <br>
        <div class="panel-heading">
            <b><h2><?= Html::encode($translator->translate('i.payments')); ?></h2></b>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?= Html::encode($translator->translate('i.date')); ?></th>
                        <th><?= Html::encode($translator->translate('i.amount')); ?></th>
                        <th><?= Html::encode($translator->translate('i.note')); ?></th>
                    </tr>
                </thead>
                <tbody>
    <?php
        /**
         * @var App\Invoice\Entity\Payment $payment
         */
        foreach ($payments as $payment) { ?>
                <tr>
                    <td><?= Html::encode(!is_string($paymentDate = $payment->getPayment_date()) ? $paymentDate->format('Y-m-d') : ''); ?></td>
                    <td><?= Html::encode($s->format_currency($payment->getAmount() >= 0.00 ? $payment->getAmount() : 0.00)); ?></td>
                    <td><?= Html::encode($payment->getNote()); ?></td>
                </tr>
    <?php } ?>
                </tbody>
            </table>
        </div>
<?php } ?>
<?php if ($readOnly === false && $invEdit) { ?>
        <br>
        <br>
        
    <?= Html::openTag('ul', ['id' => 'product-tabs', 'class' => 'nav nav-tabs nav-tabs-noborder']); ?>
    <?= Html::openTag('li', ['class' => 'active']); ?>
        <?= A::tag()
        ->addAttributes([
            'data-bs-toggle' => 'tab',
            'style' => 'text-decoration:none'
        ])
        ->addClass('btn btn-info me-1')
        ->content(Html::b($translator->translate('i.add_product')))
        ->href('#add-product-tab')
        ->id('btn-reset')
        ->render();
    ?>
    <?= Html::closeTag('li'); ?>
    <?= Html::openTag('li'); ?>
        <?= A::tag()
        ->addAttributes([
            'data-bs-toggle' => 'tab',
            'style' => 'text-decoration:none'
        ])
        ->addClass('btn btn-info me-1')
        ->content(Html::b($translator->translate('i.add_task')))
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
            'style' => 'text-decoration:none'
        ])
        ->addClass('btn btn-danger bi bi-arrow-left')
        ->id('back')
        ->render(); ?>
    <?= Html::closeTag('li'); ?>    
<?= Html::closeTag('ul'); ?>
    
    <?= Html::openTag('div', ['class' => 'tabbable tabs-below']); ?>
        <?= Html::openTag('div', ['class' => 'tab-content']); ?>
            <?= Html::openTag('div', ['id' => 'add-product-tab', 'class' => 'tab-pane']); ?>
                    <div class="panel-heading">
                        <?= Html::openTag('div'); ?>
                            <?= Html::openTag(
                                'button',
                                [
                                                    'class' => 'btn btn-primary',
                                                    'href' => '#modal-choose-items',
                                                    'id' => '#modal-choose-items',
                                                    'data-bs-toggle' => 'modal'
                                                ]
                            );
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
                <?= $add_inv_item_product; ?>
                    </div>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['id' => 'add-task-tab', 'class' => 'tab-pane']); ?>
                    <div class="panel-heading">
                        <?= Html::openTag('td'); ?>
                            <?= Html::openTag('button', [
        'class' => 'btn btn-primary bi bi-ui-checks',
        'href' => '#modal-choose-tasks',
        'id' => 'modal-choose-tasks',
        'data-bs-toggle' => 'modal']);
    ?>
                            <?= $translator->translate('i.add_task'); ?>
                            <?= Html::closeTag('button'); ?>
                        <?= Html::closeTag('td'); ?>           
                <?= $add_inv_item_task; ?>
                    </div>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>    
<?php } ?>
    <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">
    <div id="headerbar">
        <h1 class="headerbar-title">
<?php
echo Html::encode($translator->translate('i.invoice')) . ' ';
echo(Html::encode(strlen($inv->getNumber() ?? '') > 0 ? ' #' . ($inv->getNumber() ?? ' #') : $inv->getId()));
?>
        </h1>        
        <div class="headerbar-item pull-right <?php if ($inv->getIs_read_only() === false || $inv->getStatus_id() !== 4) { ?>btn-group<?php } ?>">
            <div class="options btn-group">
                <a class="btn btn-default" data-bs-toggle="dropdown" href="#">
                    <i class="fa fa-chevron-down"></i><?= $translator->translate('i.options'); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-right">
<?php
// Options...Edit
if ($showButtons && $invEdit) {
    ?>
                        <li>
                            <a href="<?= $urlGenerator->generate('inv/edit', ['id' => $inv->getId()]) ?>" style="text-decoration:none">
                                <i class="fa fa-edit fa-margin"></i>
    <?= Html::encode($translator->translate('i.edit')); ?>
                            </a>
                        </li>
    <?php
// Options...Add Invoice Tax
    if ($vat === '0') {
        ?>
                        <li>
                            <a href="#add-inv-tax" data-bs-toggle="modal"  style="text-decoration:none">
                                <i class="fa fa-plus fa-margin"></i>
                                <?= Html::encode($translator->translate('i.add_invoice_tax')); ?>
                            </a>
                        </li>
                        <?php } ?>
                        <li>
                            <a href="#add-inv-allowance-charge" data-bs-toggle="modal"  style="text-decoration:none">
                                <i class="fa fa-plus fa-margin"></i>
                                <?= $translator->translate('invoice.invoice.allowance.or.charge.add'); ?>
                            </a>
                        </li>
                    <?php } ?>
                    <?php
// Options ... Peppol UBL 2.1 Invoice
                    if ($showButtons && $invEdit && $inv->getSo_id()) {
                        ?>
                        <li>
                            <a href="" style="text-decoration:none" onclick="window.open('<?= $urlGenerator->generate('inv/peppol', ['id' => $inv->getId()]) ?>')">
                                <i class="fa fa-window-restore"></i>
                                <?= Html::encode($translator->translate('invoice.peppol')); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= $urlGenerator->generate('inv/peppol_stream_toggle', ['id' => $inv->getId()]) ?>" style="text-decoration:none">
                                <i class="fa <?= $peppol_stream_toggle === '1' ? 'fa-toggle-on' : 'fa-toggle-off'; ?> fa-margin" aria-hidden="true"></i>
                                <?=
// Options ...  Peppol Stream Toggle
                                  Html::encode($translator->translate('invoice.peppol.stream.toggle')); ?>
                            </a>
                        </li>
                        <li>
                            <a href="" onclick="window.open('https://ecosio.com/en/peppol-and-xml-document-validator-button/?pk_abe=EN_Peppol_XML_Validator_Page&pk_abv=With_CTA')" style="text-decoration:none">
                                <i class="fa fa-check fa-margin" aria-hidden="true"></i>
                                <?=
// Options ...  Ecosio Validator
                                     Html::encode($translator->translate('invoice.peppol.ecosio.validator')); ?>
                            </a>
                        </li>                        
                        <li>
                             <a href="" style="text-decoration:none" onclick="window.open('<?= $urlGenerator->generate('inv/storecove', ['id' => $inv->getId()]) ?>')">
                                <i class="fa fa-eye fa-margin"></i>
                                <?=
// Options ...  Store Cove Json Encoded Invoice
                                  Html::encode($translator->translate('invoice.storecove.invoice.json.encoded')); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= $urlGenerator->generate('del/add', ['client_id' => $inv->getClient_id()]) ?>" style="text-decoration:none">
                                <i class="fa fa-plus fa-margin"></i>
                                <?= Html::encode($translator->translate('invoice.delivery.location.add')); ?>
                            </a>
                        </li>
                    <?php } ?>
                    <?php
//Options...Create Credit Invoice
                    // Show the create credit invoice button if the invoice is read-only or if it is paid
                    // and the user is allowed to edit.
                    /**
                     * @see Modal string activated with #create-credit-inv. Modal string from InvController/index output to $modal_create_credit
                     * @see InvController/create_credit_confirm run from src\Invoice\Asset\rebuild-1.1.3\inv.js create-credit-confirm
                     */
                    if (($readOnly === true || $inv->getStatus_id() === 4) && $invEdit) {
                        ?>
                        <li>
                            <a href="#create-credit-inv" data-bs-toggle="modal" data-invoice-id="<?= $inv->getId(); ?>" style="text-decoration:none">
                                <i class="fa fa-minus fa-margin"></i> <?= Html::encode($translator->translate('i.create_credit_invoice')); ?>
                            </a>
                        </li>
                    <?php } ?>
                    <?php
// Options ... Enter Payment
                    /**
                     * @var App\Invoice\Entity\InvAmount $inv_amount
                     */
                    $inv_amount = ($iaR->repoInvAmountcount((int) $inv->getId()) > 0 ? $iaR->repoInvquery((int) $inv->getId()) : '');
// If there is a balance outstanding and the invoice is not a draft ie. at least sent, allow a payment to be allocated against it.
$invAmountBalance = $inv_amount->getBalance();
if ($invAmountBalance >= 0.00 && $inv->getStatus_id() !== 1 && $invEdit) :
    ?>
                        <li>
                            <a href="<?= $urlGenerator->generate('payment/add'); ?>" style="text-decoration:none" class="invoice-add-payment"
                               data-invoice-id="<?= Html::encode($inv->getId()); ?>"
                               data-invoice-balance="<?= Html::encode($invAmountBalance); ?>"
                               data-invoice-payment-method="<?= Html::encode($inv->getPayment_method()); ?>"
                               data-payment-cf-exisst="<?= Html::encode($paymentCfExist); ?>">
                                <i class="fa fa-credit-card fa-margin"></i>
                        <?= Html::encode($translator->translate('i.enter_payment')); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php
// Options ... Pay Now
                    // Show the pay now button if not a draft and the user has viewPayment permission ie. not editPayment permission
                    if (($readOnly === false && in_array($inv->getStatus_id(), [2, 3]) && $invAmountBalance > 0) && $paymentView) {
                        ?>
    <?php
        /**
         * @var array $enabled_gateways
         * @var string $gateway
         */
        foreach ($enabled_gateways as $gateway) { ?>                        
        <li>
            <?php if ($inv->getPayment_method() !== 0) {
                // Because there is a payment method there is no need to show a message modal
                ?>
            <a href="<?= $urlGenerator->generate('inv/url_key', ['url_key' => $inv->getUrl_key(), 'gateway' => $gateway]); ?>"
                style="text-decoration:none">
                <i class="fa fa-minus fa-margin"></i> <?= Html::encode($translator->translate('i.pay_now') . '-' . ucfirst($gateway)); ?>
            </a>
            <?php } ?>
            <?php
                 // show a message modal if there is no payment method
                 // resources/views/invoice/inv/modal_message_layout has the ... 'id' => 'modal-message-'.$type which matches the #modal-message-inv below?>
            <?php if ($inv->getPayment_method() === 0) { ?>
            <a href="#modal-message-inv" data-bs-toggle="modal" style="text-decoration:none">
                <i class="fa fa-minus fa-margin"></i> <?= Html::encode($translator->translate('i.pay_now') . '-' . ucfirst($gateway)); ?>
            </a>
            <?php } ?>
        </li>
    <?php } ?>
<?php } ?>
                    <li>
                        <!-- null!==$sumex There is a sumex extension record linked to the current invoice_id
                             and the sumex setting under View...Settings...Invoice...Sumex Settings is set at Yes.
                        -->
                            <?php if ($s->getSetting('sumex') === '1') { ?>
                            <a href="#inv-to-pdf"  data-bs-toggle="modal" style="text-decoration:none">
                                <i class="fa fa-print fa-margin"></i>
                            <?= Html::encode($translator->translate('i.generate_sumex')); ?>
                            </a>
    <?php
// Options ... Download PDF
                            } else {
                                ?>
                            <a href="#inv-to-pdf"  data-bs-toggle="modal" style="text-decoration:none">
                                <i class="fa fa-print fa-margin"></i>
    <?= Html::encode($translator->translate('i.download_pdf')); ?>
                            </a>
<?php } ?>
                        <!--
                            views/invoice/inv/modal_inv_to_pdf   ... include custom fields or not on pdf
                            src/Invoice/Inv/InvController/pdf ... calls the src/Invoice/Helpers/PdfHelper->generate_inv_pdf
                            src/Invoice/Helpers/PdfHelper ... calls the src/Invoice/Helpers/MpdfHelper->pdf_create
                            src/Invoice/Helpers/MpdfHelper ... saves folder in src/Invoice/Uploads/Archive
                            using 'pdf_invoice_template' setting or 'default' views/invoice/template/invoice/invoice.pdf
                        -->
                        </a>
                    </li>
<?php
// Options ... Create Recurring Invoice
if ($invEdit) {
    ?>
                        <li>
                            <a href="<?= $urlGenerator->generate('invrecurring/add', ['inv_id' => $inv->getId()]); ?>" style="text-decoration:none">
                                <i class="fa fa-refresh fa-margin"></i>
                            <?= Html::encode($translator->translate('i.create_recurring')); ?>
                            </a>
                        </li>
                        <li>
                            <?php
                            // If Settings...View...Invoices...Sumex Settings...Sumex is Yes
                            // the basic Sumex details will be available to edit
                            // since the basic details would have been added when the Invoice was added
                            // by means of the inv modal ie. inv/create_confirm
                            // The sumex->getInvoice function can return null since not all invoices will require
                            // Sumex details. If a Sumex Invoice has been been created due to 'Yes'
                            // it will be
// Options ... Generate Sumex

                            if (null !== $sumex) {
                                if (null !== $sumex->getInvoice()) {
                                    ?>
                                <a href="<?= $urlGenerator->generate('sumex/edit', ['id' => $inv->getId()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-edit fa-margin"></i>
                                        <?= $translator->translate('invoice.sumex.edit'); ?>
                                </a>
                            <?php } ?>
                        <?php } ?>
                        </li>
                        <li>
                            <a href="<?= $urlGenerator->generate('inv/email_stage_0', ['id' => $inv->getId()]); ?>" style="text-decoration:none">
                                <i class="fa fa-send fa-margin"></i>
    <?=
// Options ... Send Email
    Html::encode($translator->translate('i.send_email'));
    ?>
                            </a>
                        </li>
                        <li>
                            <a href="#inv-to-inv" data-bs-toggle="modal"  style="text-decoration:none">
                                <i class="fa fa-copy fa-margin"></i>
    <?=
 /**
  * @see resources/views/invoice/inv/modal_copy_inv.php
  * Options ... Copy Invoice
  */

    Html::encode($translator->translate('i.copy_invoice')); ?>
                            </a>
                        </li>
                        <li>
                            <?php
// Options ... Invoice to HTML with Sumex
                            if ($s->getSetting('sumex') === '1') {
                                ?>
                                <a href="#inv-to-html"  data-bs-toggle="modal" style="text-decoration:none">
                                    <i class="fa fa-print fa-margin"></i>
                                <?= Html::encode($translator->translate('invoice.invoice.html.sumex.yes')); ?>
                                </a>
        <?php
// Options ... Invoice to HTML without Sumex
                            } else {
                                ?>
                                <a href="#inv-to-html"  data-bs-toggle="modal" style="text-decoration:none">
                                    <i class="fa fa-print fa-margin"></i>
        <?= Html::encode($translator->translate('invoice.invoice.html.sumex.no')); ?>
                                </a>
                        <?php } ?>
                            <!--
                                views/invoice/inv/modal_inv_to_pdf   ... include custom fields or not on pdf
                                src/Invoice/Inv/InvController/pdf ... calls the src/Invoice/Helpers/PdfHelper->generate_inv_pdf
                                src/Invoice/Helpers/PdfHelper ... calls the src/Invoice/Helpers/MpdfHelper->pdf_create
                                src/Invoice/Helpers/MpdfHelper ... saves folder in src/Invoice/Uploads/Archive
                                using 'pdf_invoice_template' setting or 'default' views/invoice/template/invoice/invoice.pdf
                            -->
                            </a>
                        </li>
<?php } ?>
<?php
// Invoices can be deleted if:
// the user has invEdit permission
// it is a draft ie. status => 1, or
// the system has been overridden and the invoices read only status has been set to false
// and a sales order has not been generated ie. invoice not based on sales order
// Options ... Delete Invoice
if (($inv->getStatus_id() === 1 || ($s->getSetting('enable_invoice_deletion') === '1' && $inv->getIs_read_only() === false)) && !$inv->getSo_id() && $invEdit) {
    ?>
                        <li>
                            <a href="#delete-inv" data-bs-toggle="modal"  style="text-decoration:none">
                                <i class="fa fa-trash fa-margin"></i> <?= Html::encode($translator->translate('i.delete')); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#delete-items"  data-bs-toggle="modal" style="text-decoration:none">
                                <i class="fa fa-trash fa-margin"></i>
                        <?= Html::encode($translator->translate('i.delete') . " " . $translator->translate('i.item')); ?>
                            </a>
                        </li>
<?php } ?>
                </ul>
            </div>
            <div class="headerbar-item invoice-labels pull-right">
                <?php if ($isRecurring) { ?>
                    <span class="label label-info">
                        <i class="fa fa-refresh"></i>
    <?= Html::encode($translator->translate('i.recurring')); ?>
                    </span>
        <?php } ?>
        <?php if ($inv->getIs_read_only() === true) { ?>
                    <span class="label label-danger">
                        <i class="fa fa-read-only"></i><?= Html::encode($translator->translate('i.read_only')); ?>
                    </span>
        <?php } ?>
            </div>
        </div>
    </div>

    <div id="content">
        <div id="inv_form">
            <div class="inv">
                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-sm-6 col-md-5">
                        <h3>
                            <a href="<?= $urlGenerator->generate('client/view', ['id' => $inv->getClient()?->getClient_id()]); ?>">
                                <?= Html::encode($clientHelper->format_client($inv->getClient())) ?>
                            </a>
                        </h3>
                        <br>
                        <div id="pre_save_client_id" value="<?php echo $inv->getClient()?->getClient_id(); ?>" hidden></div>
                        <div class="client-address">
                            <span class="client-address-street-line-1">
                                <?php echo(strlen($inv->getClient()?->getClient_address_1() ?? '') > 0 ? Html::encode($inv->getClient()?->getClient_address_1()) . '<br>' : ''); ?>
                            </span>
                            <span class="client-address-street-line-2">
                            <?php echo(strlen($inv->getClient()?->getClient_address_2() ?? '') > 0 ? Html::encode($inv->getClient()?->getClient_address_2()) . '<br>' : ''); ?>
                            </span>
                            <span class="client-address-town-line">
                            <?php echo(strlen($inv->getClient()?->getClient_city() ?? '') > 0 ? Html::encode($inv->getClient()?->getClient_city()) . '<br>' : ''); ?>
                            <?php echo(strlen($inv->getClient()?->getClient_state() ?? '') > 0 ? Html::encode($inv->getClient()?->getClient_state()) . '<br>' : ''); ?>
                            <?php echo(strlen($inv->getClient()?->getClient_zip() ?? '') > 0 ? Html::encode($inv->getClient()?->getClient_zip()) : ''); ?>
                            </span>
                            <span class="client-address-country-line">
                        <?php echo(strlen($inv->getClient()?->getClient_country() ?? '') > 0 ? '<br>' . $countryHelper->get_country_name($translator->translate('i.cldr'), ($inv->getClient()?->getClient_country() ?? '')) : ''); ?>
                            </span>
                        </div>
                        <hr>
                        <?php if (strlen($inv->getClient()?->getClient_phone() ?? '') > 0): ?>
                            <div class="client-phone">
                            <?= $translator->translate('i.phone'); ?>:&nbsp;
                                <?= Html::encode($inv->getClient()?->getClient_phone() ?? ''); ?>
                            </div>
                            <?php endif; ?>
                        <?php if ($inv->getClient()?->getClient_mobile() ?? ''): ?>
                            <div class="client-mobile">
    <?= $translator->translate('i.mobile'); ?>:&nbsp;
    <?= Html::encode($inv->getClient()?->getClient_mobile()); ?>
                            </div>
<?php endif; ?>
<?php if (null !== $inv->getClient()?->getClient_email()): ?>
                            <div class='client-email'>
    <?= $translator->translate('i.email'); ?>:&nbsp;
    <?php echo $inv->getClient()?->getClient_email(); ?>
                            </div>
<?php endif; ?>
                        <br>
                    </div>

                    <div class="col-xs-12 visible-xs"><br></div>

                    <div class="col-xs-12 col-sm-6 col-md-7">
                        <div class="details-box">
                            <?= Html::openTag('div', ['class' => 'row']); ?>

                                <div class="col-xs-12 col-md-6">

                                    <div class="invoice-properties">
                                        <label for="inv_number">
                                            <b><?= $translator->translate('i.invoice'); ?> #</b>
                                        </label>
                                        <input type="text" id="inv_number" class="form-control" readonly
                                                   <?php if (strlen($inv->getNumber() ?? '') > 0) : ?> value="<?= $inv->getNumber(); ?>"
                                                   <?php else : ?> placeholder="<?= Html::encode($translator->translate('i.not_set')); ?>"
<?php endif; ?>>
                                    </div>
                                    <div class="invoice-properties has-feedback">
                                        <label for="date_created">
                                            <b><?= $translator->translate('invoice.invoice.date.issued'); ?></b>
                                        </label>
                                        <div class="input-group">
                                            <input id="date_created" disabled
                                                   class="form-control"
                                                   value="<?=
                                                             $inv->getDate_created()->format('Y-m-d');
?>"/>
                                            <span class="input-group-text">
                                                <i class="fa fa-calendar fa-fw"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="invoice-properties has-feedback">
                                        <label for="date_supplied">
                                            <b><?= $translator->translate('invoice.invoice.date.supplied'); ?></b>
                                        </label>
                                        <div class="input-group">
                                            <input id="date_supplied" disabled
                                                   class="form-control"
                                                   value="<?= $inv->getDate_supplied()->format('Y-m-d'); ?>"/>
                                            <span class="input-group-text">
                                                <i class="fa fa-calendar fa-fw"></i>
                                            </span>
                                        </div>
                                    </div>
<?php if ($vat === '1') { ?>
                                        <div class="invoice-properties has-feedback">
                                            <label for="date_tax_point">
                                                <b><?= $translator->translate('invoice.invoice.tax.point'); ?></b>
                                            </label>
                                            <div class="input-group">
                                                <input id="date_tax_point" disabled
                                                       class="form-control"
                                                       value="<?= $inv->getDate_tax_point()->format('Y-m-d'); ?>"/>
                                                <span class="input-group-text">
                                                    <i class="fa fa-calendar fa-fw"></i>
                                                </span>
                                            </div>
                                        </div>
<?php } ?>
                                    <div class="invoice-properties has-feedback">
                                        <label for="inv_date_due">
                                            <b><?= $translator->translate('i.expires'); ?></b>
                                        </label>
                                        <div class="input-group">
                                            <input name="inv_date_due" id="inv_date_due" disabled
                                                   class="form-control"
                                                   value="<?= !is_string($dateDue = $inv->getDate_due()) ? $dateDue->format('Y-m-d') : ''; ?>">
                                            <span class="input-group-text">
                                                <i class="fa fa-calendar fa-fw"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <?php
                                                /**
                                                 * @var App\Invoice\Entity\CustomField $custom_field
                                                 */
                                                foreach ($custom_fields as $custom_field): ?>
                                                     <?php if ($custom_field->getLocation() !== 1) {
                                                         continue;
                                                     } ?>
                                                     <?php $cvH->print_field_for_view($custom_field, $form, $inv_custom_values, $custom_values); ?>
                                         <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6">
                                    <div class="invoice-properties">
                                        <label for="inv_status_id">
                                            <b><?= $translator->translate('i.status'); ?></b>
                                        </label>
                                        <select name="inv_status_id" id="inv_status_id" disabled
                                                class="form-control">
                                            <?php
                                                /**
                                                 * @var array $inv_statuses
                                                 * @var string $key
                                                 * @var array $status
                                                 */
                                                foreach ($inv_statuses as $key => $status) { ?>
                                                <option value="<?php echo $key; ?>" <?php if ($key == (string)$form->getStatus_id()) {
                                                    $s->check_select((string)$form->getStatus_id(), $key);
                                                } ?>>
                                                        <?= Html::encode($status['label']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="invoice-properties">
                                        <label for="payment_method">
                                            <b><?= $translator->translate('i.payment_method'); ?></b>
                                        </label>
<?php if ($inv->getPayment_method() !== 0) { ?>
                                            <select name="payment_method" id="payment_method" class="form-control" disabled="disabled">
                                                <option value="0"><?= Html::encode($translator->translate('i.select_payment_method')); ?></option>
                                        <?php
                                                /**
                                                 * @var App\Invoice\Entity\PaymentMethod $payment_method
                                                 */
                                                foreach ($payment_methods as $payment_method) { ?>
                                                    <option <?php $s->check_select(
                                                        (string) $inv->getPayment_method(),
                                                        $payment_method->getId()
                                                    )
                                                    ?>
                                                        value="<?= $payment_method->getId(); ?>">
        <?= $payment_method->getName() ?? ''; ?>
                                                    </option>
    <?php } ?>
                                            </select>
<?php } else { ?>
                                            <select name="payment_method" id="payment_method" class="form-control"
    <?= 'disabled="disabled"'; ?>>
                                                <option "0" ><?= Html::encode($translator->translate('i.none')); ?></option>
                                            </select>
<?php } ?>
                                    </div>
<?php if (($inv->getStatus_id() !== 1) && ($invEdit)) { ?>
                                        <div class="invoice-properties">
                                            <label for="inv_password">
                                                <b><?= Html::encode($translator->translate('i.password')); ?></b>
                                            </label>
                                            <input type="text" id="inv_password" class="form-control" disabled value="<?= Html::encode($form->getPassword() ?? ''); ?>">
                                        </div>
                                        <div class="invoice-properties">
                                            <div class="form-group">
                                                <label for="guest-url">
                                                    <b><?= Html::encode($translator->translate('i.guest_url')); ?></b>
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" id="guest-url" name="guest-url" readonly class="form-control" value="<?= 'inv/url_key/' . $inv->getUrl_key(); ?>">
                                                    <span class="input-group-text to-clipboard cursor-pointer"
                                                          data-clipboard-target="#guest-url">
                                                        <i class="fa fa-clipboard fa-fw"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <div>
                                        <br>
                                        <!-- draft=>1 sent=>2 viewed=>3 paid=>4 overdue=>5 -->
<?php if ($inv->getStatus_id() === 4) { ?>
                                            <img src="/img/paid.png">
<?php } ?>
<?php if ($inv->getStatus_id() === 5) { ?>
    <img src="/img/overdue.png">
<?php } ?>
<?php if ($inv->getStatus_id() === 6) { ?>
         <img src="/img/unpaid.png">
<?php } ?>
<?php if ($inv->getStatus_id() === 7) { ?>
                                            <img src="/img/reminder.png">
<?php } ?>
<?php if ($inv->getStatus_id() === 8) { ?>
                                            <img src="/img/lba.png">
<?php } ?>
<?php if ($inv->getStatus_id() === 9) { ?>
                                            <img src="/img/legalclaim.png">
<?php } ?>
<?php if ($inv->getStatus_id() === 10) { ?>
                                            <img src="/img/judgement.png">
<?php } ?>                                            
<?php if ($inv->getStatus_id() === 11) { ?>
                                            <img src="/img/officer.png">
<?php } ?>                                              
<?php if ($inv->getStatus_id() === 12) { ?>
                                            <img src="/img/creditnote.png">
<?php } ?>
<?php if ($inv->getStatus_id() === 13) { ?>
                                            <img src="/img/writtenoff.png">
<?php } ?>
                                    </div>
<?php if (!empty($inv->getSo_id())) {
    Html::openTag('div');
    $translator->translate('invoice.salesorder');
    Html::closeTag('div');
    Html::openTag('div', ['class' => 'input-group']);
    Html::a(
        $sales_order_number,
        $urlGenerator->generate('salesorder/view', ['id' => $inv->getSo_id()]),
        ['class' => 'btn btn-success']
    );
    Html::closeTag('div');
} ?>
                                    <input type="text" id="dropzone_client_id" readonly class="form-control" value="<?= $inv->getClient()?->getClient_id(); ?>" hidden>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="partial_item_table_parameters" disabled>
<?= $partial_item_table; ?>
        </div>

        <?= Html::openTag('div', ['class' => 'row']); ?>
            <div class="col-xs-12 col-md-6">
                <div class="panel panel-default no-margin">
                    <div class="panel-heading">
                        <b>
                            <?= Html::encode($translator->translate('i.terms')); ?>
                            <?php
                               $paymentTermArray = $s->get_payment_term_array($translator);
?>         
                        </b>        
                    </div>
                    <div class="panel-body">
                        <textarea name="terms" id="terms" rows="3" disabled
                            class="input-sm form-control"><?= Html::encode($inv->getTerms() ?: $paymentTermArray[0]); ?></textarea>
                    </div>
                </div>

                <div class="col-xs-12 visible-xs visible-sm"><br></div>

            </div>
            <div id="view_custom_fields" class="col-xs-12 col-md-6">
<?= $view_custom_fields; ?>
            </div>
            <div id="view_partial_inv_delivery_location" class="col-xs-12 col-md-6">
<?= $partial_inv_delivery_location; ?>
            </div>     
            <div id="view_partial_inv_attachments">
<?= $partial_inv_attachments; ?>
            </div>
<?php  echo $modal_add_allowance_charge; ?>
        <?= Html::closeTag('div'); ?>