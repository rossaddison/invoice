<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\I;

/**
 * @var App\Invoice\Entity\Inv                    $inv
 * @var App\Invoice\Entity\Sumex|null             $sumex
 * @var App\Invoice\Inv\InvForm                   $form
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\Helpers\ClientHelper          $clientHelper
 * @var App\Invoice\Helpers\DateHelper            $dateHelper
 * @var App\Invoice\Helpers\CountryHelper         $countryHelper
 * @var App\Invoice\Helpers\CustomValuesHelper    $cvH
 * @var App\Invoice\Helpers\NumberHelper          $numberHelper
 * @var App\Invoice\Setting\SettingRepository     $s
 * @var App\Widget\Button                         $button
 * @var Yiisoft\Translator\TranslatorInterface    $translator
 * @var Yiisoft\Router\UrlGeneratorInterface      $urlGenerator
 * @var array                                     $custom_fields
 * @var array                                     $custom_values
 * @var array                                     $enabled_gateways
 * @var array                                     $inv_custom_values
 * @var array                                     $inv_items
 * @var array                                     $inv_statuses
 * @var array                                     $payments
 * @var array                                     $payment_methods
 * @var bool                                      $invEdit
 * @var bool                                      $isRecurring
 * @var bool                                      $paymentView
 * @var bool                                      $paymentCfExist
 * @var bool                                      $readOnly
 * @var bool                                      $showButtons
 * @var string                                    $alert
 * @var string                                    $csrf
 * @var string                                    $add_inv_item_product
 * @var string                                    $add_inv_item_task
 * @var string                                    $modal_add_inv_tax
 * @var string                                    $modal_add_allowance_charge
 * @var string                                    $modal_change_client
 * @var string                                    $modal_choose_items
 * @var string                                    $modal_choose_tasks
 * @var string                                    $modal_copy_inv
 * @var string                                    $modal_create_recurring
 * @var string                                    $modal_create_credit
 * @var string                                    $modal_delete_inv
 * @var string                                    $modal_delete_items
 * @var string                                    $modal_inv_to_modal_pdf
 * @var string                                    $modal_inv_to_pdf
 * @var string                                    $modal_inv_to_html
 * @var string                                    $modal_message_no_payment_method
 * @var string                                    $modal_pdf
 * @var string                                    $partial_inv_delivery_location
 * @var string                                    $partial_inv_attachments
 * @var string                                    $partial_item_table
 * @var string                                    $peppol_stream_toggle
 * @var string                                    $sales_order_number
 * @var string                                    $title
 * @var string                                    $view_custom_fields
 */
$vat = $s->getSetting('enable_vat_registration');

?>
    <?php
        echo $alert;
echo $modal_delete_inv;
if ('0' === $vat) {
    echo $modal_add_inv_tax;
}
echo $modal_change_client;
// modal_product_lookups is performed using below $modal_choose_items
echo $modal_choose_items;
// modal_task_lookups is performed using below $modal_choose_tasks
echo $modal_choose_tasks;
// custom fields or no custom fields choices for non-download, modal showing pdf ... $modal_pdf
echo $modal_inv_to_modal_pdf;
echo $modal_inv_to_pdf;
echo $modal_inv_to_html;
echo $modal_copy_inv;
echo $modal_delete_items;
echo $modal_create_credit;
echo $modal_message_no_payment_method;
echo $modal_pdf;
?>   

   
<?php if (!empty($payments)) { ?>
        <br>
        <br>
        <div class="panel-heading">
            <b><h2><?php echo Html::encode($translator->translate('payments')); ?></h2></b>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?php echo Html::encode($translator->translate('date')); ?></th>
                        <th><?php echo Html::encode($translator->translate('amount')); ?></th>
                        <th><?php echo Html::encode($translator->translate('note')); ?></th>
                    </tr>
                </thead>
                <tbody>
    <?php
        /**
         * @var App\Invoice\Entity\Payment $payment
         */
        foreach ($payments as $payment) { ?>
                <tr>
                    <td><?php echo Html::encode(!is_string($paymentDate = $payment->getPayment_date()) ? $paymentDate->format('Y-m-d') : ''); ?></td>
                    <td><?php echo Html::encode($s->format_currency($payment->getAmount() >= 0.00 ? $payment->getAmount() : 0.00)); ?></td>
                    <td><?php echo Html::encode($payment->getNote()); ?></td>
                </tr>
    <?php } ?>
                </tbody>
            </table>
        </div>
<?php } ?>
<?php if (false === $readOnly && $invEdit) { ?>
        <br>
        <br>
        
    <?php echo Html::openTag('ul', ['id' => 'product-tabs', 'class' => 'nav nav-tabs nav-tabs-noborder']); ?>
    <?php echo Html::openTag('li', ['class' => 'active']); ?>
        <?php echo A::tag()
        ->addAttributes([
            'data-bs-toggle' => 'tab',
            'style'          => 'text-decoration:none',
        ])
        ->addClass('btn btn-info me-1')
        ->content(Html::b($translator->translate('add.product')))
        ->href('#add-product-tab')
        ->id('btn-reset')
        ->render();
    ?>
    <?php echo Html::closeTag('li'); ?>
    <?php echo Html::openTag('li'); ?>
        <?php echo A::tag()
        ->addAttributes([
            'data-bs-toggle' => 'tab',
            'style'          => 'text-decoration:none',
        ])
        ->addClass('btn btn-info me-1')
        ->content(Html::b($translator->translate('add.task')))
        ->href('#add-task-tab')
        ->id('btn-reset')
        ->render();
    ?>
    <?php echo Html::closeTag('li'); ?> 
    <?php echo Html::openTag('li', ['id' => 'back', 'class' => 'tab-pane']); ?>
        <?php echo A::tag()
        ->addAttributes([
            'type'           => 'reset',
            'onclick'        => 'window.history.back()',
            'value'          => '1',
            'data-bs-toggle' => 'tab',
            'style'          => 'text-decoration:none',
        ])
        ->addClass('btn btn-danger bi bi-arrow-left')
        ->id('back')
        ->render(); ?>
    <?php echo Html::closeTag('li'); ?>    
<?php echo Html::closeTag('ul'); ?>
    
    <?php echo Html::openTag('div', ['class' => 'tabbable tabs-below']); ?>
        <?php echo Html::openTag('div', ['class' => 'tab-content']); ?>
            <?php echo Html::openTag('div', ['id' => 'add-product-tab', 'class' => 'tab-pane']); ?>
                    <div class="panel-heading">
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Html::openTag(
                                'button',
                                [
                                    'class'          => 'btn btn-primary',
                                    'href'           => '#modal-choose-items',
                                    'id'             => '#modal-choose-items',
                                    'data-bs-toggle' => 'modal',
                                ],
                            );
    ?>
                            <?php echo I::tag()
        ->addClass('fa fa-list')
        ->addAttributes([
            'data-bs-toggle' => 'tooltip',
            'title'          => $translator->translate('add.product'),
        ]);
    ?>
                            <?php echo $translator->translate('add.product'); ?>
                            <?php echo Html::closeTag('button'); ?>
                        <?php echo Html::closeTag('div'); ?>
                <?php echo $add_inv_item_product; ?>
                    </div>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['id' => 'add-task-tab', 'class' => 'tab-pane']); ?>
                    <div class="panel-heading">
                        <?php echo Html::openTag('td'); ?>
                            <?php echo Html::openTag('button', [
                                'class'          => 'btn btn-primary bi bi-ui-checks',
                                'href'           => '#modal-choose-tasks',
                                'id'             => 'modal-choose-tasks',
                                'data-bs-toggle' => 'modal']);
    ?>
                            <?php echo $translator->translate('add.task'); ?>
                            <?php echo Html::closeTag('button'); ?>
                        <?php echo Html::closeTag('td'); ?>           
                <?php echo $add_inv_item_task; ?>
                    </div>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>    
<?php } ?>
    <input type="hidden" id="_csrf" name="_csrf" value="<?php echo $csrf; ?>">
    <div id="headerbar">
        <h1 class="headerbar-title">
<?php
echo Html::encode($translator->translate('invoice')).' ';
echo Html::encode(strlen($inv->getNumber() ?? '') > 0 ? ' #'.($inv->getNumber() ?? ' #') : $inv->getId());
?>
        </h1>        
        <div class="headerbar-item pull-right <?php if (false === $inv->getIs_read_only() || 4 !== $inv->getStatus_id()) { ?>btn-group<?php } ?>">
            <div class="options btn-group">
                <a class="btn btn-default" data-bs-toggle="dropdown" href="#">
                    <i class="fa fa-chevron-down"></i><?php echo $translator->translate('options'); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-right">
<?php
// Options...Edit
if ($showButtons && $invEdit) {
    ?>
                        <li>
                            <a href="<?php echo $urlGenerator->generate('inv/edit', ['id' => $inv->getId()]); ?>" style="text-decoration:none">
                                <i class="fa fa-edit fa-margin"></i>
    <?php echo Html::encode($translator->translate('edit')); ?>
                            </a>
                        </li>
    <?php
// Options...Add Invoice Tax
    if ('0' === $vat) {
        ?>
                        <li>
                            <a href="#add-inv-tax" data-bs-toggle="modal"  style="text-decoration:none">
                                <i class="fa fa-plus fa-margin"></i>
                                <?php echo Html::encode($translator->translate('add.invoice.tax')); ?>
                            </a>
                        </li>
                        <?php } ?>
                        <li>
                            <a href="#add-inv-allowance-charge" data-bs-toggle="modal"  style="text-decoration:none">
                                <i class="fa fa-plus fa-margin"></i>
                                <?php echo $translator->translate('allowance.or.charge.add'); ?>
                            </a>
                        </li>
                    <?php } ?>
                    <?php
// Options ... Peppol UBL 2.1 Invoice
                    if ($showButtons && $invEdit && $inv->getSo_id()) {
                        ?>
                        <li>
                            <a href="" style="text-decoration:none" onclick="window.open('<?php echo $urlGenerator->generate('inv/peppol', ['id' => $inv->getId()]); ?>')">
                                <i class="fa fa-window-restore"></i>
                                <?php echo Html::encode($translator->translate('peppol')); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $urlGenerator->generate('inv/peppol_stream_toggle', ['id' => $inv->getId()]); ?>" style="text-decoration:none">
                                <i class="fa <?php echo '1' === $peppol_stream_toggle ? 'fa-toggle-on' : 'fa-toggle-off'; ?> fa-margin" aria-hidden="true"></i>
                                <?php echo // Options ...  Peppol Stream Toggle
                                  Html::encode($translator->translate('peppol.stream.toggle')); ?>
                            </a>
                        </li>
                        <li>
                            <a href="" onclick="window.open('https://ecosio.com/en/peppol-and-xml-document-validator-button/?pk_abe=EN_Peppol_XML_Validator_Page&pk_abv=With_CTA')" style="text-decoration:none">
                                <i class="fa fa-check fa-margin" aria-hidden="true"></i>
                                <?php echo // Options ...  Ecosio Validator
                                     Html::encode($translator->translate('peppol.ecosio.validator')); ?>
                            </a>
                        </li>                        
                        <li>
                             <a href="" style="text-decoration:none" onclick="window.open('<?php echo $urlGenerator->generate('inv/storecove', ['id' => $inv->getId()]); ?>')">
                                <i class="fa fa-eye fa-margin"></i>
                                <?php echo // Options ...  Store Cove Json Encoded Invoice
                                  Html::encode($translator->translate('storecove.invoice.json.encoded')); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $urlGenerator->generate('del/add', ['client_id' => $inv->getClient_id()]); ?>" style="text-decoration:none">
                                <i class="fa fa-plus fa-margin"></i>
                                <?php echo Html::encode($translator->translate('delivery.location.add')); ?>
                            </a>
                        </li>
                    <?php } ?>
                    <?php
// Options...Create Credit Invoice
                    // Show the create credit invoice button if the invoice is read-only or if it is paid
                    // and the user is allowed to edit.
                    /*
                     * @see Modal string activated with #create-credit-inv. Modal string from InvController/index output to $modal_create_credit
                     * @see InvController/create_credit_confirm run from src\Invoice\Asset\rebuild-1.1.3\inv.js create-credit-confirm
                     */
                    if ((true === $readOnly || 4 === $inv->getStatus_id()) && $invEdit && !(int) $inv->getCreditinvoice_parent_id() > 0) {
                        ?>
                        <li>
                            <a href="#create-credit-inv" data-bs-toggle="modal" data-invoice-id="<?php echo $inv->getId(); ?>" style="text-decoration:none">
                                <i class="fa fa-minus fa-margin"></i> <?php echo Html::encode($translator->translate('create.credit.invoice')); ?>
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
if ($invAmountBalance >= 0.00 && 1 !== $inv->getStatus_id() && $invEdit) {
    ?>
                        <li>
                            <a href="<?php echo $urlGenerator->generate('payment/add'); ?>" style="text-decoration:none" class="invoice-add-payment"
                               data-invoice-id="<?php echo Html::encode($inv->getId()); ?>"
                               data-invoice-balance="<?php echo Html::encode($invAmountBalance); ?>"
                               data-invoice-payment-method="<?php echo Html::encode($inv->getPayment_method()); ?>"
                               data-payment-cf-exisst="<?php echo Html::encode($paymentCfExist); ?>">
                                <i class="fa fa-credit-card fa-margin"></i>
                        <?php echo Html::encode($translator->translate('enter.payment')); ?>
                            </a>
                        </li>
                    <?php } ?>
                    <?php
// Options ... Pay Now
                    // Show the pay now button if not a draft and the user has viewPayment permission ie. not editPayment permission
                    if ((false === $readOnly && in_array($inv->getStatus_id(), [2, 3]) && $invAmountBalance > 0) && $paymentView) {
                        ?>
    <?php
        /**
         * @var array  $enabled_gateways
         * @var string $gateway
         */
        foreach ($enabled_gateways as $gateway) { ?>                        
        <li>
            <?php if (0 !== $inv->getPayment_method()) {
                // Because there is a payment method there is no need to show a message modal
                ?>
            <a href="<?php echo $urlGenerator->generate('inv/url_key', ['url_key' => $inv->getUrl_key(), 'gateway' => $gateway]); ?>"
                style="text-decoration:none">
                <i class="fa fa-minus fa-margin"></i> <?php echo Html::encode($translator->translate('pay.now').'-'.ucfirst($gateway)); ?>
            </a>
            <?php } ?>
            <?php
                 // show a message modal if there is no payment method
                 // resources/views/invoice/inv/modal_message_layout has the ... 'id' => 'modal-message-'.$type which matches the #modal-message-inv below?>
            <?php if (0 === $inv->getPayment_method()) { ?>
            <a href="#modal-message-inv" data-bs-toggle="modal" style="text-decoration:none">
                <i class="fa fa-minus fa-margin"></i> <?php echo Html::encode($translator->translate('pay.now').'-'.ucfirst($gateway)); ?>
            </a>
            <?php } ?>
        </li>
    <?php } ?>
<?php } ?>
                    <li>
                        <!-- null!==$sumex There is a sumex extension record linked to the current invoice_id
                             and the sumex setting under View...Settings...Invoice...Sumex Settings is set at Yes.
                        -->
                            <?php if ('1' === $s->getSetting('sumex')) { ?>
                            <a href="#inv-to-pdf"  data-bs-toggle="modal" style="text-decoration:none">
                                <i class="fa fa-print fa-margin"></i>
                            <?php echo Html::encode($translator->translate('generate.sumex')); ?>
                            </a>
    <?php
// Options ... Download PDF
                            } else {
                                ?>
                            <a href="#inv-to-pdf"  data-bs-toggle="modal" style="text-decoration:none">
                                <i class="fa fa-print fa-margin"></i>
                                <?php echo Html::encode($translator->translate('download.pdf')); ?>
                            </a>
                            <?php
// Options ... Modal PDF
                            if ('1' == $s->getSetting('pdf_stream_inv')) { ?>
                            <a href="#inv-to-modal-pdf" data-bs-toggle="modal"  style="text-decoration:none">
                                <i class="fa fa-desktop fa-margin"></i>
                                <?php echo Html::encode($translator->translate('pdf.modal').' ✅'); ?>
                            </a>
                            <?php } else { ?>
                            <a href="<?php echo $urlGenerator->generate(
                                'setting/tab_index',
                                [],
                                ['active' => 'invoices'],
                                'settings[pdf_stream_inv]'); ?>" style="text-decoration:none">
                                <i class="fa fa-desktop fa-margin"></i>
                                <?php echo Html::encode($translator->translate('pdf.modal').' ❌'); ?>
                            </a>
                            <?php } ?>
                        
<?php } ?>
                        <!--
                            views/invoice/inv/modal_inv_to_pdf   ... include custom fields or not on pdf
                            src/Invoice/Inv/InvController/pdf ... calls the src/Invoice/Helpers/PdfHelper->generate_inv_pdf
                            src/Invoice/Helpers/PdfHelper ... calls the src/Invoice/Helpers/MpdfHelper->pdf_create
                            src/Invoice/Helpers/MpdfHelper ... saves folder in src/Invoice/Uploads/Archive
                            using 'pdf_invoice_template' setting or 'default' views/invoice/template/invoice/invoice.pdf
                        -->
                    </li>
<?php
// Options ... Create Recurring Invoice
if ($invEdit) {
    ?>
                        <li>
                            <a href="<?php echo $urlGenerator->generate('invrecurring/add', ['inv_id' => $inv->getId()]); ?>" style="text-decoration:none">
                                <i class="fa fa-refresh fa-margin"></i>
                            <?php echo Html::encode($translator->translate('create.recurring')); ?>
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
                                <a href="<?php echo $urlGenerator->generate('sumex/edit', ['id' => $inv->getId()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-edit fa-margin"></i>
                                        <?php echo $translator->translate('sumex.edit'); ?>
                                </a>
                            <?php } ?>
                        <?php } ?>
                        </li>
                        <li>
                            <a href="<?php echo $urlGenerator->generate('inv/email_stage_0', ['id' => $inv->getId()]); ?>" style="text-decoration:none">
                                <i class="fa fa-send fa-margin"></i>
    <?php echo // Options ... Send Email
    Html::encode($translator->translate('send.email'));
    ?>
                            </a>
                        </li>
                        <li>
                            <a href="#inv-to-inv" data-bs-toggle="modal"  style="text-decoration:none">
                                <i class="fa fa-copy fa-margin"></i>
    <?php echo
 /*
  * @see resources/views/invoice/inv/modal_copy_inv.php
  * Options ... Copy Invoice
  */

    Html::encode($translator->translate('copy.invoice')); ?>
                            </a>
                        </li>
                        <li>
                            <?php
// Options ... Invoice to HTML with Sumex
                            if ('1' === $s->getSetting('sumex')) {
                                ?>
                                <a href="#inv-to-html"  data-bs-toggle="modal" style="text-decoration:none">
                                    <i class="fa fa-print fa-margin"></i>
                                <?php echo Html::encode($translator->translate('html.sumex.yes')); ?>
                                </a>
        <?php
// Options ... Invoice to HTML without Sumex
                            } else {
                                ?>
                                <a href="#inv-to-html"  data-bs-toggle="modal" style="text-decoration:none">
                                    <i class="fa fa-print fa-margin"></i>
        <?php echo Html::encode($translator->translate('html.sumex.no')); ?>
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
if ((1 === $inv->getStatus_id() || ('1' === $s->getSetting('enable_invoice_deletion') && false === $inv->getIs_read_only())) && !$inv->getSo_id() && $invEdit) {
    ?>
                        <li>
                            <a href="#delete-inv" data-bs-toggle="modal"  style="text-decoration:none">
                                <i class="fa fa-trash fa-margin"></i> <?php echo Html::encode($translator->translate('delete')); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#delete-items"  data-bs-toggle="modal" style="text-decoration:none">
                                <i class="fa fa-trash fa-margin"></i>
                        <?php echo Html::encode($translator->translate('delete').' '.$translator->translate('item')); ?>
                            </a>
                        </li>
<?php } ?>
                </ul>
            </div>
            <div class="headerbar-item invoice-labels pull-right">
                <?php if ($isRecurring) { ?>
                    <span class="label label-info">
                        <i class="fa fa-refresh"></i>
    <?php echo Html::encode($translator->translate('recurring')); ?>
                    </span>
        <?php } ?>
        <?php if (true === $inv->getIs_read_only()) { ?>
                    <span class="label label-danger">
                        <i class="fa fa-read-only"></i><?php echo Html::encode($translator->translate('read.only')); ?>
                    </span>
        <?php } ?>
            </div>
        </div>
    </div>

    <div id="content">
        <div id="inv_form">
            <div class="inv">
                <?php echo Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-sm-6 col-md-5">
                        <h3>
                            <a href="<?php echo $urlGenerator->generate('client/view', ['id' => $inv->getClient()?->getClient_id()]); ?>">
                                <?php echo Html::encode($clientHelper->format_client($inv->getClient())); ?>
                            </a>
                        </h3>
                        <br>
                        <div id="pre_save_client_id" value="<?php echo $inv->getClient()?->getClient_id(); ?>" hidden></div>
                        <div class="client-address">
                            <span class="client-address-street-line-1">
                                <?php echo strlen($inv->getClient()?->getClient_address_1() ?? '') > 0 ? Html::encode($inv->getClient()?->getClient_address_1()).'<br>' : ''; ?>
                            </span>
                            <span class="client-address-street-line-2">
                            <?php echo strlen($inv->getClient()?->getClient_address_2() ?? '') > 0 ? Html::encode($inv->getClient()?->getClient_address_2()).'<br>' : ''; ?>
                            </span>
                            <span class="client-address-town-line">
                            <?php echo strlen($inv->getClient()?->getClient_city() ?? '')  > 0 ? Html::encode($inv->getClient()?->getClient_city()).'<br>' : ''; ?>
                            <?php echo strlen($inv->getClient()?->getClient_state() ?? '') > 0 ? Html::encode($inv->getClient()?->getClient_state()).'<br>' : ''; ?>
                            <?php echo strlen($inv->getClient()?->getClient_zip() ?? '')   > 0 ? Html::encode($inv->getClient()?->getClient_zip()) : ''; ?>
                            </span>
                            <span class="client-address-country-line">
                        <?php echo strlen($inv->getClient()?->getClient_country() ?? '') > 0 ? '<br>'.$countryHelper->get_country_name($translator->translate('cldr'), $inv->getClient()?->getClient_country() ?? '') : ''; ?>
                            </span>
                        </div>
                        <hr>
                        <?php if (strlen($inv->getClient()?->getClient_phone() ?? '') > 0) { ?>
                            <div class="client-phone">
                            <?php echo $translator->translate('phone'); ?>:&nbsp;
                                <?php echo Html::encode($inv->getClient()?->getClient_phone() ?? ''); ?>
                            </div>
                            <?php } ?>
                        <?php if ($inv->getClient()?->getClient_mobile() ?? '') { ?>
                            <div class="client-mobile">
    <?php echo $translator->translate('mobile'); ?>:&nbsp;
    <?php echo Html::encode($inv->getClient()?->getClient_mobile()); ?>
                            </div>
<?php } ?>
<?php if (null !== $inv->getClient()?->getClient_email()) { ?>
                            <div class='client-email'>
    <?php echo $translator->translate('email'); ?>:&nbsp;
    <?php echo $inv->getClient()?->getClient_email(); ?>
                            </div>
<?php } ?>
                        <br>
                    </div>

                    <div class="col-xs-12 visible-xs"><br></div>

                    <div class="col-xs-12 col-sm-6 col-md-7">
                        <div class="details-box">
                            <?php echo Html::openTag('div', ['class' => 'row']); ?>

                                <div class="col-xs-12 col-md-6">

                                    <div class="invoice-properties">
                                        <label for="inv_number">
                                            <b><?php echo $translator->translate('invoice'); ?> #</b>
                                        </label>
                                        <input type="text" id="inv_number" class="form-control" readonly
                                                   <?php if (strlen($inv->getNumber() ?? '') > 0) { ?> value="<?php echo $inv->getNumber(); ?>"
                                                   <?php } else { ?> placeholder="<?php echo Html::encode($translator->translate('not.set')); ?>"
<?php } ?>>
                                    </div>
                                    <div class="invoice-properties has-feedback">
                                        <label for="date_created">
                                            <b><?php echo $translator->translate('date.issued'); ?></b>
                                        </label>
                                        <div class="input-group">
                                            <input id="date_created" disabled
                                                   class="form-control"
                                                   value="<?php echo $inv->getDate_created()->format('Y-m-d');
?>"/>
                                            <span class="input-group-text">
                                                <i class="fa fa-calendar fa-fw"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="invoice-properties has-feedback">
                                        <label for="date_supplied">
                                            <b><?php echo $translator->translate('date.supplied'); ?></b>
                                        </label>
                                        <div class="input-group">
                                            <input id="date_supplied" disabled
                                                   class="form-control"
                                                   value="<?php echo $inv->getDate_supplied()->format('Y-m-d'); ?>"/>
                                            <span class="input-group-text">
                                                <i class="fa fa-calendar fa-fw"></i>
                                            </span>
                                        </div>
                                    </div>
<?php if ('1' === $vat) { ?>
                                        <div class="invoice-properties has-feedback">
                                            <label for="date_tax_point">
                                                <b><?php echo $translator->translate('tax.point'); ?></b>
                                            </label>
                                            <div class="input-group">
                                                <input id="date_tax_point" disabled
                                                       class="form-control"
                                                       value="<?php echo $inv->getDate_tax_point()->format('Y-m-d'); ?>"/>
                                                <span class="input-group-text">
                                                    <i class="fa fa-calendar fa-fw"></i>
                                                </span>
                                            </div>
                                        </div>
<?php } ?>
                                    <div class="invoice-properties has-feedback">
                                        <label for="inv_date_due">
                                            <b><?php echo $translator->translate('expires'); ?></b>
                                        </label>
                                        <div class="input-group">
                                            <input name="inv_date_due" id="inv_date_due" disabled
                                                   class="form-control"
                                                   value="<?php echo !is_string($dateDue = $inv->getDate_due()) ? $dateDue->format('Y-m-d') : ''; ?>">
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
                                                foreach ($custom_fields as $custom_field) { ?>
                                                     <?php if (1 !== $custom_field->getLocation()) {
                                                         continue;
                                                     } ?>
                                                     <?php $cvH->print_field_for_view($custom_field, $form, $inv_custom_values, $custom_values); ?>
                                         <?php } ?>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6">
                                    <div class="invoice-properties">
                                        <label for="inv_status_id">
                                            <b><?php echo $translator->translate('status'); ?></b>
                                        </label>
                                        <select name="inv_status_id" id="inv_status_id" disabled
                                                class="form-control">
                                            <?php
                                                /**
                                                 * @var array  $inv_statuses
                                                 * @var string $key
                                                 * @var array  $status
                                                 */
                                                foreach ($inv_statuses as $key => $status) { ?>
                                                <option value="<?php echo $key; ?>" <?php if ($key == (string) $form->getStatus_id()) {
                                                    $s->check_select((string) $form->getStatus_id(), $key);
                                                } ?>>
                                                        <?php echo Html::encode($status['label']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="invoice-properties">
                                        <label for="payment_method">
                                            <b><?php echo $translator->translate('payment.method'); ?></b>
                                        </label>
<?php if (0 !== $inv->getPayment_method()) { ?>
                                            <select name="payment_method" id="payment_method" class="form-control" disabled="disabled">
                                                <option value="0"><?php echo Html::encode($translator->translate('select.payment.method')); ?></option>
                                        <?php
                                                /**
                                                 * @var App\Invoice\Entity\PaymentMethod $payment_method
                                                 */
                                                foreach ($payment_methods as $payment_method) { ?>
                                                    <option <?php $s->check_select(
                                                        (string) $inv->getPayment_method(),
                                                        $payment_method->getId(),
                                                    );
                                                    ?>
                                                        value="<?php echo $payment_method->getId(); ?>">
        <?php echo $payment_method->getName() ?? ''; ?>
                                                    </option>
    <?php } ?>
                                            </select>
<?php } else { ?>
                                            <select name="payment_method" id="payment_method" class="form-control"
    <?php echo 'disabled="disabled"'; ?>>
                                                <option "0" ><?php echo Html::encode($translator->translate('none')); ?></option>
                                            </select>
<?php } ?>
                                    </div>
<?php if ((1 !== $inv->getStatus_id()) && $invEdit) { ?>
                                        <div class="invoice-properties">
                                            <label for="inv_password">
                                                <b><?php echo Html::encode($translator->translate('password')); ?></b>
                                            </label>
                                            <input type="text" id="inv_password" class="form-control" disabled value="<?php echo Html::encode($form->getPassword() ?? ''); ?>">
                                        </div>
                                        <div class="invoice-properties">
                                            <div class="form-group">
                                                <label for="guest-url">
                                                    <b><?php echo Html::encode($translator->translate('guest.url')); ?></b>
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" id="guest-url" name="guest-url" readonly class="form-control" value="<?php echo 'inv/url_key/'.$inv->getUrl_key(); ?>">
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
<?php if (4 === $inv->getStatus_id()) { ?>
                                            <img src="/img/paid.png">
<?php } ?>
<?php if (5 === $inv->getStatus_id()) { ?>
    <img src="/img/overdue.png">
<?php } ?>
<?php if (6 === $inv->getStatus_id()) { ?>
         <img src="/img/unpaid.png">
<?php } ?>
<?php if (7 === $inv->getStatus_id()) { ?>
                                            <img src="/img/reminder.png">
<?php } ?>
<?php if (8 === $inv->getStatus_id()) { ?>
                                            <img src="/img/lba.png">
<?php } ?>
<?php if (9 === $inv->getStatus_id()) { ?>
                                            <img src="/img/legalclaim.png">
<?php } ?>
<?php if (10 === $inv->getStatus_id()) { ?>
                                            <img src="/img/judgement.png">
<?php } ?>                                            
<?php if (11 === $inv->getStatus_id()) { ?>
                                            <img src="/img/officer.png">
<?php } ?>                                              
<?php if (12 === $inv->getStatus_id()) { ?>
                                            <img src="/img/creditnote.png">
<?php } ?>
<?php if (13 === $inv->getStatus_id()) { ?>
                                            <img src="/img/writtenoff.png">
<?php } ?>
                                    </div>
<?php if (!empty($inv->getSo_id())) {
    Html::openTag('div');
    $translator->translate('salesorder');
    Html::closeTag('div');
    Html::openTag('div', ['class' => 'input-group']);
    Html::a(
        $sales_order_number,
        $urlGenerator->generate('salesorder/view', ['id' => $inv->getSo_id()]),
        ['class' => 'btn btn-success'],
    );
    Html::closeTag('div');
} ?>
                                    <input type="text" id="dropzone_client_id" readonly class="form-control" value="<?php echo $inv->getClient()?->getClient_id(); ?>" hidden>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="partial_item_table_parameters" disabled>
<?php echo $partial_item_table; ?>
        </div>

        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <div class="col-xs-12 col-md-6">
                <div class="panel panel-default no-margin">
                    <div class="panel-heading">
                        <b>
                            <?php echo Html::encode($translator->translate('terms')); ?>
                            <?php
                                $paymentTermArray = $s->get_payment_term_array($translator);
$termsKey                                         = (int) $inv->getTerms() ?: 0;
$terms                                            = (string) $paymentTermArray[$termsKey];
?>    
                        </b>        
                    </div>
                    <div class="panel-body">
                        <textarea name="terms" id="terms" rows="3" disabled
                            class="input-sm form-control"><?php echo Html::encode($terms); ?></textarea>
                    </div>
                </div>

                <div class="col-xs-12 visible-xs visible-sm"><br></div>

            </div>
            <div id="view_custom_fields" class="col-xs-12 col-md-6">
<?php echo $view_custom_fields; ?>
            </div>
            <div id="view_partial_inv_delivery_location" class="col-xs-12 col-md-6">
<?php echo $partial_inv_delivery_location; ?>
            </div>     
            <div id="view_partial_inv_attachments">
<?php echo $partial_inv_attachments; ?>
            </div>
<?php echo $modal_add_allowance_charge; ?>
        <?php echo Html::closeTag('div'); ?>