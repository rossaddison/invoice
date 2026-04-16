<?php

declare(strict_types=1);

use App\Widget\Button;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Option;

/**
 * @var App\Invoice\Entity\Inv $inv
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
 * @var string $buttonsToolbarFull
 * @var string $modal_add_inv_tax
 * @var string $modal_add_allowance_charge
 * @var string $modal_change_client
 * @var string $modal_choose_items
 * @var string $modal_choose_tasks
 * @var string $modal_copy_inv
 * @var string $modal_create_credit
 * @var string $modal_delete_inv
 * @var string $modal_delete_items
 * @var string $modal_inv_to_modal_pdf
 * @var string $modal_inv_to_pdf
 * @var string $modal_inv_to_html
 * @var string $modal_message_no_payment_method
 * @var string $modal_pdf
 * @var string $partial_inv_delivery_location
 * @var string $partial_inv_attachments
 * @var string $partial_item_table
 * @var string $peppol_doc_currency_toggle
 * @var string $peppol_stream_toggle
 * @var string $sales_order_number
 * @var string $title
 * @var string $view_custom_fields
 */

$vat = $s->getSetting('enable_vat_registration');
echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';
echo $modal_delete_inv;
if ($vat === '0') {
    echo $modal_add_inv_tax;
}
echo $modal_change_client;
// modal_product_lookups is performed using below $modal_choose_items
echo $modal_choose_items;
// modal_task_lookups is performed using below $modal_choose_tasks
echo $modal_choose_tasks;
// custom fields or no custom fields choices for non-download, modal showing pdf
// ... $modal_pdf
echo $modal_inv_to_modal_pdf;
echo $modal_inv_to_pdf;
echo $modal_inv_to_html;
echo $modal_copy_inv;
echo $modal_delete_items;
echo $modal_create_credit;
echo $modal_message_no_payment_method;
echo $modal_pdf;

if (!empty($payments)) {
    echo '<br>';
    echo '<br>';
    echo H::openTag('div', ['class' => 'panel-heading']);
     echo H::openTag('b');
      echo H::openTag('h2');
       echo H::encode($translator->translate('payments'));
      echo H::closeTag('h2');
     echo H::closeTag('b');
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'table-responsive']);
     echo H::openTag('table', ['class' => 'table table-hover']);
      echo H::openTag('thead');
       echo H::openTag('tr');
        echo H::openTag('th');
         echo H::encode($translator->translate('date'));
        echo H::closeTag('th');
        echo H::openTag('th');
         echo H::encode($translator->translate('amount'));
        echo H::closeTag('th');
        echo H::openTag('th');
         echo H::encode($translator->translate('note'));
        echo H::closeTag('th');
       echo H::closeTag('tr');
      echo H::closeTag('thead');
      echo H::closeTag('tbody');
      /**
       * @var App\Invoice\Entity\Payment $payment
       */
      foreach ($payments as $payment) {
        echo H::openTag('tr');
         echo H::openTag('td');
          echo H::encode(!is_string($paymentDate = $payment->getPaymentDate()) ?
              $paymentDate->format('Y-m-d') : '');
         echo H::closeTag('td');
         echo H::openTag('td');
          echo H::encode($s->formatCurrency($payment->getAmount() >= 0.00 ?
           $payment->getAmount() : 0.00));
         echo H::closeTag('td');
         echo H::openTag('td');
          echo H::encode($payment->getNote());
         echo H::closeTag('td');
        echo H::closeTag('tr');
      }
      echo H::closeTag('tbody');
     echo H::closeTag('table');
    echo H::closeTag('div');
}
if ($readOnly === false && $invEdit && $inv->getStatusId() === 1) {
    echo '<br>';
    echo '<br>';
    echo H::openTag('ul', ['id' => 'product-tabs',
        'class' => 'nav nav-tabs nav-tabs-noborder']);
        echo H::openTag('li', ['class' => 'active']);
        echo  new A()
            ->addAttributes([
                'data-bs-toggle' => 'tab',
                'style' => 'text-decoration:none',
            ])
            ->addClass('btn btn-info me-1')
            ->content(H::b($translator->translate('add.product')))
            ->href('#add-product-tab')
            ->id('btn-reset')
            ->render();
        echo H::closeTag('li');
        echo H::openTag('li');
        echo  new A()
            ->addAttributes([
                'data-bs-toggle' => 'tab',
                'style' => 'text-decoration:none',
            ])
            ->addClass('btn btn-info me-1')
            ->content(H::b($translator->translate('add.task')))
            ->href('#add-task-tab')
            ->id('btn-reset')
            ->render();
        echo H::closeTag('li');
        echo H::openTag('li', ['id' => 'back', 'class' => 'tab-pane']);
        echo  new A()
            ->addAttributes([
                'type' => 'reset',
                'onclick' => 'window.history.back()',
                'value' => '1',
                'data-bs-toggle' => 'tab',
                'style' => 'text-decoration:none',
            ])
            ->addClass('btn btn-danger bi bi-arrow-left')
            ->id('back')
            ->render();
        echo H::closeTag('li');
    echo H::closeTag('ul');
    echo H::openTag('div', ['class' => 'tabbable tabs-below']);
     echo H::openTag('div', ['class' => 'tab-content']);
      echo H::openTag('div', ['id' => 'add-product-tab', 'class' => 'tab-pane']);
       echo H::openTag('div', ['class' => 'panel-heading']);
        echo H::openTag('div');
         echo H::openTag('button',
            [
                'class' => 'btn btn-primary',
                'href' => '#modal-choose-items',
                'id' => '#modal-choose-items',
                'data-bs-toggle' => 'modal',
            ],
         );
         echo  new I()
            ->addClass('bi bi-list-ul')
            ->addAttributes([
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('add.product'),
            ]);
            $translator->translate('add.product');
         echo H::closeTag('button');
        echo H::closeTag('div');
        echo $add_inv_item_product;
       echo H::closeTag('div');
      echo H::closeTag('div');
      echo H::openTag('div', ['id' => 'add-task-tab', 'class' => 'tab-pane']);
       echo H::openTag('div', ['class' => 'panel-heading']);
        echo H::openTag('div');
         echo H::openTag('button', [
            'class' => 'btn btn-primary bi bi-ui-checks w-100',
            'data-bs-target' => '#modal-choose-tasks-inv',
            'data-bs-toggle' => 'modal']);
            $translator->translate('add.task');
         echo H::closeTag('button');
        echo H::closeTag('div');
        echo $add_inv_item_task;
       echo H::closeTag('div');
      echo H::closeTag('div');
     echo H::closeTag('div');
    echo H::closeTag('div');
}
echo H::tag('input', '', [
    'type' => 'hidden',
    'id' => '_csrf',
    'name' => '_csrf',
    'value' => $csrf]);
 echo H::openTag('div', ['id' => 'headerbar']);
  echo H::openTag('h1', ['class' => 'headerbar-title']);
   echo H::encode($translator->translate('invoice')) . ' ';
   echo H::encode(strlen($inv->getNumber() ?? '') > 0 ?
        ' #' . ($inv->getNumber() ?? ' #') : $inv->getId());
  echo H::closeTag('h1');
 echo H::closeTag('div');
// Toolbar
echo $buttonsToolbarFull;
 echo H::openTag('div', ['class' => 'headerbar-item pull-left' .
    ($inv->getIsReadOnly() === false || $inv->getStatusId() !== 4 ? ' btn-group' : '')]);
  echo H::openTag('div', ['class' => 'dropdown']);
   echo H::openTag('button', [
       'class' => 'btn btn-primary dropdown-toggle',
       'type' => 'button',
       'data-bs-toggle' => 'dropdown',
       'aria-expanded' => 'false'
   ]);
    echo $translator->translate('options');
   echo H::closeTag('button');
   echo H::openTag('ul', ['class' => 'dropdown-menu dropdown-menu']);
// Options...Edit
if ($showButtons
        && $invEdit
        && strlen($inv->getQuoteId()) === 0
// Only allow the editing of the invoice if not connected to a salesorder
        && strlen($inv->getSoId()) === 0) {
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/edit', ['id' => $inv->getId()]),
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'bi-pencil-square']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('edit'));
     echo H::closeTag('a');
    echo H::closeTag('li');
// Options...Add Invoice Tax
    if ($vat === '0') {
        echo H::openTag('li');
         echo H::openTag('a', [
             'href' => '#add-inv-tax',
             'data-bs-toggle' => 'modal',
             'style' => 'text-decoration:none'
         ]);
          echo H::openTag('i', ['class' => 'bi-plus']);
          echo H::closeTag('i');
          echo ' ' . H::encode($translator->translate('add.invoice.tax'));
         echo H::closeTag('a');
        echo H::closeTag('li');
    }
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => '#add-inv-allowance-charge',
         'data-bs-toggle' => 'modal',
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'bi-plus']);
      echo H::closeTag('i');
      echo ' ' . $translator->translate('allowance.or.charge.inv.add');
     echo H::closeTag('a');
    echo H::closeTag('li');
}
// Options ... Peppol UBL 2.1 Invoice
//if ($showButtons && $invEdit) {
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/peppol', ['id' => $inv->getId()]),
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'bi bi-window-stack']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('peppol'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/peppolStreamToggle',
                 ['id' => $inv->getId()]),
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', [
          'class' => 'fa ' . ($peppol_stream_toggle === '1' ?
              'fa-toggle-on' : 'fa-toggle-off') . ' fa-margin',
          'aria-hidden' => 'true'
      ]);
      echo H::closeTag('i');
// Options ...  Peppol Stream Toggle
      echo ' ' . H::encode($translator->translate('peppol.stream.toggle'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/peppolDocCurrencyToggle',
                 ['id' => $inv->getId()]),
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', [
          'class' => 'fa ' . ($peppol_doc_currency_toggle === '1' ?
              'fa-toggle-on' : 'fa-toggle-off') . ' fa-margin',
          'aria-hidden' => 'true'
      ]);
      echo H::closeTag('i');
// Options ...  Peppol Doc Currency Toggle
      echo ' '
      . H::encode($translator->translate('peppol.doc.currency.toggle')
      . '➡️' . $s->getSetting('peppol_document_currency'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => 'https',
         'onclick' =>
 "window.open('https://ecosio.com/en/peppol-e-invoice-xml-document-validator/')",
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', [
          'class' => 'bi bi-check-lg',
          'aria-hidden' => 'true']);
      echo H::closeTag('i');
// Options ...  Ecosio Validator
      echo ' ' . H::encode($translator->translate('peppol.ecosio.validator'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/storecove', ['id' => $inv->getId()]),
         'style' => 'text-decoration:none',
         'target' => '_blank'
     ]);
      echo H::openTag('i', ['class' => 'bi-eye']);
      echo H::closeTag('i');
// Options ...  Store Cove Json Encoded Invoice
      echo ' ' . H::encode($translator->translate('storecove.invoice.json.encoded'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('del/add',
            ['client_id' => $inv->getClientId()],
                 ['origin' => 'inv',
                     'origin_id' => $inv->getId(), 'action' => 'view'],''),
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'bi-plus']);
      echo H::closeTag('i');
// Options ... Delivery Location Add
      echo ' ' . H::encode($translator->translate('delivery.location.add'));
     echo H::closeTag('a');
    echo H::closeTag('li');
//Options...Create Credit Invoice
                    // Show the create credit invoice button if the invoice is
                    // read-only or if it is paid + the user is allowed to edit.
                    /**
                     * Related logic: see Modal string activated with
                     * #create-credit-inv. Modal string from InvController/index
                     * output to $modal_create_credit
                     * Related logic: see InvController/create_credit_confirm
                     * run from src\Invoice\Asset\rebuild-1.1.3\inv.js
                     * create-credit-confirm
                     */
                    if (($readOnly === true || $inv->getStatusId() === 4)
                        && $invEdit
                        && !(int) $inv->getCreditinvoiceParentId() > 0) {
        echo H::openTag('li');
         echo H::openTag('a', [
             'href' => '#create-credit-inv',
             'data-bs-toggle' => 'modal',
             'data-invoice-id' => $inv->getId(),
             'style' => 'text-decoration:none'
         ]);
          echo H::openTag('i', ['class' => 'bi bi-dash-lg']);
          echo H::closeTag('i');
          echo ' ' . H::encode($translator->translate('create.credit.invoice'));
         echo H::closeTag('a');
        echo H::closeTag('li');
    }
// Options ... Enter Payment
/**
 * @var App\Invoice\Entity\InvAmount $inv_amount
 */
$inv_amount = ($iaR->repoInvAmountcount((int) $inv->getId()) > 0 ?
        $iaR->repoInvquery((int) $inv->getId()) : '');
// If there is a balance outstanding and the invoice is not a draft ie. at
// least sent, allow a payment to be allocated against it.
$invAmountBalance = $inv_amount->getBalance();
if ($invAmountBalance >= 0.00 && $inv->getStatusId() !== 1 && $invEdit) :
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('payment/add'),
         'style' => 'text-decoration:none',
         'class' => 'invoice-add-payment',
         'data-invoice-id' => H::encode($inv->getId()),
         'data-invoice-balance' => H::encode($invAmountBalance),
         'data-invoice-payment-method' => H::encode($inv->getPaymentMethod()),
         'data-payment-cf-exisst' => H::encode($paymentCfExist)
     ]);
      echo H::openTag('i', ['class' => 'bi bi-credit-card']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('enter.payment'));
     echo H::closeTag('a');
    echo H::closeTag('li');
endif;
// Options ... Pay Now
// Show the pay now button if not a draft and the user has viewPayment
// permission ie. not editPayment permission
if ((in_array($inv->getStatusId(), [2, 3])
        && $invAmountBalance > 0)
        && $paymentView) {
    /**
     * @var string $gateway
     */
    foreach ($enabled_gateways as $gateway) {
        echo H::openTag('li');
         if ($inv->getPaymentMethod() !== 0) {
            // Because there is a payment method
            // there is no need to show a message modal
            echo H::openTag('a', [
                'href' => $urlGenerator->generate('inv/urlKey',
                        ['url_key' => $inv->getUrlKey(),
                            'gateway' => $gateway]),
                'style' => 'text-decoration:none'
            ]);
             echo H::openTag('i', ['class' => 'bi bi-dash-lg']);
             echo H::closeTag('i');
             echo $translator->translate('pay.now')
                . '➡️'
                . (ucfirst($gateway) == 'Braintree' ? Button::braintree() : '')
                . (ucfirst($gateway) == 'Stripe' ? Button::stripe() : '')
                . (ucfirst($gateway) == 'Amazon_Pay' ? Button::amazon() : '')
                . (ucfirst($gateway) == 'Mollie' ? Button::mollie() : '');

            echo H::closeTag('a');
        }
        // show a message modal if there is no payment method
        // resources/views/invoice/inv/modal_message_layout has
        // the ... 'id' => 'modal-message-'.$type which matches the
        // #modal-message-inv below
        if ($inv->getPaymentMethod() === 0) {
            echo H::openTag('a', [
                'href' => '#modal-message-inv',
                'data-bs-toggle' => 'modal',
                'style' => 'text-decoration:none'
            ]);
             echo H::openTag('i', ['class' => 'bi bi-dash-lg']);
             echo H::closeTag('i');
             echo ' ' . H::encode($translator->translate('pay.now') . '-'
                     . ucfirst($gateway));
            echo H::closeTag('a');
        }
        echo H::closeTag('li');
    }
}
if ((in_array($inv->getStatusId(), [1]))) {
    echo H::openTag('li');
        echo H::openTag('a', [
                'href' => '#modal-message-inv',
                'data-bs-toggle' => 'modal',
                'style' => 'text-decoration:none'
            ]);
             echo H::openTag('i', ['class' => 'bi bi-dash-lg']);
             echo H::closeTag('i');
             echo ' ' . H::encode($translator->translate('pay.now') . '-'
                     . $translator->translate('invoice.needs.to.be.sent'));
            echo H::closeTag('a');
    echo H::closeTag('li');
}
echo H::openTag('li');
// Options ... Download PDF
 echo H::openTag('a', [
     'href' => '#inv-to-pdf',
     'data-bs-toggle' => 'modal',
     'style' => 'text-decoration:none'
 ]);
  echo H::openTag('i', ['class' => 'bi bi-printer']);
  echo H::closeTag('i');
  echo ' ' . H::encode($translator->translate('download.pdf'));
 echo H::closeTag('a');
// Options ... Modal PDF
if ($s->getSetting('pdf_stream_inv') == '1') {
    echo H::openTag('a', [
        'href' => '#inv-to-modal-pdf',
        'data-bs-toggle' => 'modal',
        'style' => 'text-decoration:none'
    ]);
     echo H::openTag('i', ['class' => 'bi bi-display']);
     echo H::closeTag('i');
     echo ' ' . H::encode($translator->translate('pdf.modal') . ' ✅');
    echo H::closeTag('a');
} else {
    echo H::openTag('a', [
        'href' => $urlGenerator->generate('setting/tabIndex',
                [],
                ['active' => 'invoices'], 'settings[pdf_stream_inv]'),
        'style' => 'text-decoration:none'
    ]);
     echo H::openTag('i', ['class' => 'bi bi-display']);
     echo H::closeTag('i');
     echo ' ' . H::encode($translator->translate('pdf.modal') . ' ❌');
    echo H::closeTag('a');
}


/**
    views/invoice/inv/modal_inv_to_pdf   ... include custom fields or not on pdf
    src/Invoice/Inv/InvController/pdf ... calls the src/Invoice/Helpers/PdfHelper
        ->generate_inv_pdf
    src/Invoice/Helpers/PdfHelper ... calls the src/Invoice/Helpers/MpdfHelper
        ->pdf_create
    src/Invoice/Helpers/MpdfHelper ... saves folder in
        src/Invoice/Uploads/Archive
    using 'pdf_invoice_template' setting or
        'default' views/invoice/template/invoice/invoice.pdf
*/

echo H::closeTag('li');
// Options ... Create Recurring Invoice
if ($invEdit) {
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('invrecurring/add',
                 ['inv_id' => $inv->getId()]),
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'bi bi-arrow-clockwise']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('create.recurring'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/emailStage0',
                 ['id' => $inv->getId()]),
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'bi bi-send']);
      echo H::closeTag('i');
// Options ... Send Email
      echo ' ' . H::encode($translator->translate('send.email'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => '#inv-to-inv',
         'data-bs-toggle' => 'modal',
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'bi bi-copy']);
      echo H::closeTag('i');
/**
 * Related logic: see resources/views/invoice/inv/modal_copy_inv.php
 * Options ... Copy Invoice
 */
      echo ' ' . H::encode($translator->translate('copy.invoice'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
    echo H::openTag('a', [
        'href' => '#inv-to-html',
        'data-bs-toggle' => 'modal',
        'style' => 'text-decoration:none'
    ]);
     echo H::openTag('i', ['class' => 'bi bi-printer']);
     echo H::closeTag('i');
     echo ' ' . H::encode($translator->translate('invoice.to.html'));
    echo H::closeTag('a');


/**
    views/invoice/inv/modal_inv_to_pdf   ... include custom fields or not on pdf
    src/Invoice/Inv/InvController/pdf ... calls the src/Invoice/Helpers/PdfHelper
        ->generate_inv_pdf
    src/Invoice/Helpers/PdfHelper ... calls the src/Invoice/Helpers/MpdfHelper
        ->pdf_create
    src/Invoice/Helpers/MpdfHelper ... saves folder in src/Invoice/Uploads/Archive
    using 'pdf_invoice_template' setting or 'default'
    views/invoice/template/invoice/invoice.pdf
*/

    echo H::closeTag('li');
}

// Invoices can be deleted if:
// the user has invEdit permission
// it is a draft ie. status => 1, or
// the system has been overridden and the invoices read only status has been set
// to false and a sales order has not been generated ie. invoice not based on
// sales order

// Options ... Delete Invoice
if ($inv->getStatusId() === 1
        && $s->getSetting('enable_invoice_deletion') === '1'
        && $inv->getIsReadOnly() === false
        && strlen($inv->getSoId()) === 0
        && strlen($inv->getQuoteId()) === 0
        && $invEdit) {
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => '#delete-inv',
         'data-bs-toggle' => 'modal',
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'bi-trash']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('delete'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => '#delete-items',
         'data-bs-toggle' => 'modal',
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'bi-trash']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('delete')
              . " " . $translator->translate('item'));
     echo H::closeTag('a');
    echo H::closeTag('li');
}
   echo H::closeTag('ul');
  echo H::closeTag('div');
  echo H::openTag('div', ['class' => 'headerbar-item invoice-labels pull-right']);
   if ($isRecurring) {
       echo H::openTag('span', ['class' => 'badge text-bg-info']);
        echo H::openTag('i', ['class' => 'bi bi-arrow-clockwise']);
        echo H::closeTag('i');
        echo ' ' . H::encode($translator->translate('recurring'));
       echo H::closeTag('span');
   }
   if ($inv->getIsReadOnly() === true) {
       echo H::openTag('span', ['class' => 'badge text-bg-danger']);
        echo ' ' . H::encode($translator->translate('read.only'));
       echo H::closeTag('span');
   }
  echo H::closeTag('div');
 echo H::closeTag('div');

 echo H::openTag('div', ['id' => 'content']);

 echo H::openTag('div', ['id' => 'inv_form']);
  echo H::openTag('div', ['class' => 'inv']);
   echo H::openTag('div', ['class' => 'row']);
    echo H::openTag('div', ['class' => 'col-xs-12 col-sm-6 col-md-5']);
     echo H::openTag('h3');
      echo H::openTag('a', ['href' => $urlGenerator->generate('client/view',
              ['id' => $inv->getClient()?->reqId()])]);
       echo H::encode($clientHelper->formatClient($inv->getClient()));
      echo H::closeTag('a');
     echo H::closeTag('h3');
     echo '<br>';
     echo H::openTag('div', ['id' => 'pre_save_client_id',
         'value' => $inv->getClient()?->reqId(), 'hidden' => true]);
     echo H::closeTag('div');
     echo H::openTag('div', ['class' => 'client-address']);
      echo H::openTag('span', ['class' => 'client-address-street-line-1']);
       if (strlen($inv->getClient()?->getClientAddress1() ?? '') > 0) {
           echo H::encode($inv->getClient()?->getClientAddress1()) . '<br>';
       }
      echo H::closeTag('span');
      echo H::openTag('span', ['class' => 'client-address-street-line-2']);
       if (strlen($inv->getClient()?->getClientAddress2() ?? '') > 0) {
           echo H::encode($inv->getClient()?->getClientAddress2()) . '<br>';
       }
      echo H::closeTag('span');
      echo H::openTag('span', ['class' => 'client-address-town-line']);
       if (strlen($inv->getClient()?->getClientCity() ?? '') > 0) {
           echo H::encode($inv->getClient()?->getClientCity()) . '<br>';
       }
       if (strlen($inv->getClient()?->getClientState() ?? '') > 0) {
           echo H::encode($inv->getClient()?->getClientState()) . '<br>';
       }
       if (strlen($inv->getClient()?->getClientZip() ?? '') > 0) {
           echo H::encode($inv->getClient()?->getClientZip());
       }
      echo H::closeTag('span');
      echo H::openTag('span', ['class' => 'client-address-country-line']);
       if (strlen($inv->getClient()?->getClientCountry() ?? '') > 0) {
           echo '<br>'
           . $countryHelper->getCountryName($translator->translate('cldr'),
                   ($inv->getClient()?->getClientCountry() ?? ''));
       }
      echo H::closeTag('span');
     echo H::closeTag('div');
     echo '<hr>';
     if (strlen($inv->getClient()?->getClientPhone() ?? '') > 0) {
         echo H::openTag('div', ['class' => 'client-phone']);
          echo H::encode($translator->translate('phone'))
                  . ":\u{00A0}"
                  . H::encode($inv->getClient()?->getClientPhone() ?? '');
         echo H::closeTag('div');
     }
     if ($inv->getClient()?->getClientMobile() ?? '') {
         echo H::openTag('div', ['class' => 'client-mobile']);
          echo H::encode($translator->translate('mobile'))
                  . ":\u{00A0}"
                  . H::encode($inv->getClient()?->getClientMobile());
         echo H::closeTag('div');
     }
     if (null !== $inv->getClient()?->getClientEmail()) {
         echo H::openTag('div', ['class' => 'client-email']);
          echo H::encode($translator->translate('email'))
                  . ":\u{00A0}"
                  . H::encode($inv->getClient()?->getClientEmail() ?? '');
         echo H::closeTag('div');
     }
     echo '<br>';
    echo H::closeTag('div');

    echo H::openTag('div', ['class' => 'col-xs-12 visible-xs']);
     echo '<br>';
    echo H::closeTag('div');

    echo H::openTag('div', ['class' => 'col-xs-12 col-sm-6 col-md-7']);
     echo H::openTag('div', ['class' => 'details-box']);
      echo H::openTag('div', ['class' => 'row']);

       echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']);

        echo H::openTag('div', ['class' => 'invoice-properties']);
         echo H::openTag('label', ['for' => 'inv_number']);
          echo H::openTag('b');
           echo $translator->translate('invoice') . ' #';
          echo H::closeTag('b');
         echo H::closeTag('label');
         echo H::tag('input', '', [
             'type' => 'text',
             'id' => 'inv_number',
             'class' => 'form-control form-control-lg',
             'readonly' => true,
             'value' => (strlen($inv->getNumber() ?? '') > 0 ?
                $inv->getNumber() : null),
             'placeholder' => (strlen($inv->getNumber() ?? '') > 0 ?
                null : H::encode($translator->translate('not.set')))
         ]);
        echo H::closeTag('div');
        echo H::openTag('div', ['class' => 'invoice-properties has-feedback']);
         echo H::openTag('label', ['for' => 'date_created']);
          echo H::openTag('b');
           echo $translator->translate('date.issued');
          echo H::closeTag('b');
         echo H::closeTag('label');
         echo H::openTag('div', ['class' => 'input-group']);
          echo H::tag('input', '', [
              'id' => 'date_created',
              'disabled' => true,
              'class' => 'form-control form-control-lg',
              'value' => $inv->getDateCreated()->format('Y-m-d')
          ]);
          echo H::openTag('span', ['class' => 'input-group-text']);
           $biCalender = 'bi bi-calendar';
           echo H::openTag('i', ['class' => $biCalender]);
           echo H::closeTag('i');
          echo H::closeTag('span');
         echo H::closeTag('div');
        echo H::closeTag('div');
        echo H::openTag('div', ['class' => 'invoice-properties has-feedback']);
         echo H::openTag('label', ['for' => 'date_supplied']);
          echo H::openTag('b');
           echo $translator->translate('date.supplied');
          echo H::closeTag('b');
         echo H::closeTag('label');
         echo H::openTag('div', ['class' => 'input-group']);
          echo H::tag('input', '', [
              'id' => 'date_supplied',
              'disabled' => true,
              'class' => 'form-control form-control-lg',
              'value' => $inv->getDateSupplied()->format('Y-m-d')
          ]);
          echo H::openTag('span', ['class' => 'input-group-text']);
           echo H::openTag('i', ['class' => 'bi bi-calendar']);
           echo H::closeTag('i');
          echo H::closeTag('span');
         echo H::closeTag('div');
        echo H::closeTag('div');
if ($vat === '1') {
    echo H::openTag('div', ['class' => 'invoice-properties has-feedback']);
     echo H::openTag('label', ['for' => 'date_tax_point']);
      echo H::openTag('b');
       echo $translator->translate('tax.point');
      echo H::closeTag('b');
     echo H::closeTag('label');
     echo H::openTag('div', ['class' => 'input-group']);
      echo H::tag('input', '', [
          'id' => 'date_tax_point',
          'disabled' => true,
          'class' => 'form-control form-control-lg',
          'value' => $inv->getDateTaxPoint()->format('Y-m-d')
      ]);
      echo H::openTag('span', ['class' => 'input-group-text']);
       echo H::openTag('i', ['class' => 'bi bi-calendar']);
       echo H::closeTag('i');
      echo H::closeTag('span');
     echo H::closeTag('div');
    echo H::closeTag('div');
}
        echo H::openTag('div', ['class' => 'invoice-properties has-feedback']);
         echo H::openTag('label', ['for' => 'inv_date_due']);
          echo H::openTag('b');
           echo $translator->translate('expires');
          echo H::closeTag('b');
         echo H::closeTag('label');
         echo H::openTag('div', ['class' => 'input-group']);
          echo H::tag('input', '', [
              'name' => 'inv_date_due',
              'id' => 'inv_date_due',
              'disabled' => true,
              'class' => 'form-control form-control-lg',
              'value' => !is_string($dateDue = $inv->getDateDue()) ?
              $dateDue->format('Y-m-d') : ''
          ]);
          echo H::openTag('span', ['class' => 'input-group-text']);
           echo H::openTag('i', ['class' => 'bi bi-calendar']);
           echo H::closeTag('i');
          echo H::closeTag('span');
         echo H::closeTag('div');
        echo H::closeTag('div');
        echo H::openTag('div');
        /**
         * @var App\Invoice\Entity\CustomField $custom_field
         */
        foreach ($custom_fields as $custom_field) {
            if ($custom_field->getLocation() !== 1) {
                continue;
            }
            $cvH->printFieldForView($custom_field, $form, $inv_custom_values);
        }
        echo H::closeTag('div');
       echo H::closeTag('div');
       echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']);
        echo H::openTag('div', ['class' => 'invoice-properties']);
         echo H::openTag('label', ['for' => 'inv_status_id']);
          echo H::openTag('b');
           echo $translator->translate('status');
          echo H::closeTag('b');
         echo H::closeTag('label');
         echo H::openTag('select', [
             'name' => 'inv_status_id',
             'id' => 'inv_status_id',
             'disabled' => true,
             'class' => 'form-control form-control-lg',
         ]);
         /**
          * @var string $key
          * @var array $status
          */
         foreach ($inv_statuses as $key => $status) {
            echo  new Option()
            ->value($key)
            ->selected($key == (string) $form->getStatusId())
            ->content(H::encode((string) $status['label']));
         }
         echo H::closeTag('select');
        echo H::closeTag('div');
        echo H::openTag('div', ['class' => 'invoice-properties']);
         echo H::openTag('label', ['for' => 'payment_method']);
          echo H::openTag('b');
           echo $translator->translate('payment.method');
          echo H::closeTag('b');
         echo H::closeTag('label');
if ($inv->getPaymentMethod() !== 0) {
    echo H::openTag('select', [
        'name' => 'payment_method',
        'id' => 'payment_method_1',
        'class' => 'form-control form-control-lg',
        'disabled' => 'disabled'
    ]);
     echo new Option()
      ->value('0')
      ->content(H::encode($translator->translate('select.payment.method')));
    /**
     * @var App\Invoice\Entity\PaymentMethod $payment_method
     */
    foreach ($payment_methods as $payment_method) {
        echo new Option()
         ->value($payment_method->getId())
         ->selected((string) $inv->getPaymentMethod() === $payment_method->getId())
         ->content($payment_method->getName() ?? '');
    }
    echo H::closeTag('select');
} else {
    echo H::openTag('select', [
        'name' => 'payment_method_2',
        'id' => 'payment_method',
        'class' => 'form-control form-control-lg',
        'disabled' => true
    ]);
     echo new Option()
      ->value('0')
      ->content(H::encode($translator->translate('none')));
    echo H::closeTag('select');
}
        echo H::closeTag('div');
// Show originating quote button if invoice was created from a quote
if ($inv->getQuoteId() !== '' && $inv->getQuoteId() !== '0') {
    echo H::openTag('div', ['class' => 'invoice-properties']);
     echo H::openTag('label', ['for' => 'quote-view-url']);
      echo H::openTag('b');
       echo $translator->translate('invoice.origin');
      echo H::closeTag('b');
     echo H::closeTag('label');
     echo H::openTag('div');
      echo H::openTag('a', [
          'href' => $urlGenerator->generate('quote/view',
                  ['id' => $inv->getQuoteId()]),
          'class' => 'btn btn-info btn-sm',
          'id' => 'quote-view-url'
      ]);
       echo H::openTag('i', ['class' => 'bi bi-file-text']);
       echo H::closeTag('i');
       echo ' '
            . $translator->translate('invoice.created.from.quote')
            . ' #' . $inv->getQuoteId();
      echo H::closeTag('a');
     echo H::closeTag('div');
    echo H::closeTag('div');
}
if (($inv->getStatusId() !== 1) && ($invEdit)) {
    echo H::openTag('div', ['class' => 'invoice-properties']);
     echo H::openTag('label', ['for' => 'inv_password']);
      echo H::openTag('b');
       echo H::encode($translator->translate('password'));
      echo H::closeTag('b');
     echo H::closeTag('label');
     echo H::tag('input', '', [
         'type' => 'text',
         'id' => 'inv_password',
         'class' => 'form-control form-control-lg',
         'disabled' => true,
         'value' => H::encode($form->getPassword() ?? '')
     ]);
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'invoice-properties']);
     echo H::openTag('div', ['class' => 'form-group']);
      echo H::openTag('label', ['for' => 'guest-url']);
       echo H::openTag('b');
        echo H::encode($translator->translate('guest.url'));
       echo H::closeTag('b');
      echo H::closeTag('label');
      echo H::openTag('div', ['class' => 'input-group']);
       echo H::tag('input', '', [
           'type' => 'text',
           'id' => 'guest-url',
           'name' => 'guest-url',
           'readonly' => true,
           'class' => 'form-control form-control-lg',
           'value' => 'inv/url_key/' . $inv->getUrlKey()
       ]);
       echo H::openTag('span', [
           'class' => 'input-group-text to-clipboard cursor-pointer',
           'data-clipboard-target' => '#guest-url'
       ]);
        echo H::openTag('i', ['class' => 'bi bi-clipboard']);
        echo H::closeTag('i');
       echo H::closeTag('span');
      echo H::closeTag('div');
     echo H::closeTag('div');
    echo H::closeTag('div');
}
    echo H::openTag('div');
     echo H::tag('br', '');
// draft=>1 sent=>2 viewed=>3 paid=>4 overdue=>5
$statusImages = [
    4 => ['/img/paid.png', 'paid'],
    5 => ['/img/overdue.png', 'overdue'],
    6 => ['/img/unpaid.png', 'unpaid'],
    7 => ['/img/reminder.png', 'reminder'],
    8 => ['/img/lba.png', 'letter'],
    9 => ['/img/legalclaim.png', 'claim'],
    10 => ['/img/judgement.png', 'judgement'],
    11 => ['/img/officer.png', 'enforcement'],
    12 => ['/img/creditnote.png', 'credit.invoice.for.invoice'],
    13 => ['/img/writtenoff.png', 'loss']
];
$statusId = $inv->getStatusId();
if ($statusId !== null && isset($statusImages[$statusId])) {
    $statusInfo = $statusImages[$statusId];
    echo H::tag('img', '', [
        'src' => $statusInfo[0],
        'alt' => $translator->translate($statusInfo[1])
    ]);
}
    echo H::closeTag('div');
if (!empty($inv->getSoId())) {
    echo H::openTag('div');
     echo $translator->translate('salesorder');
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'input-group']);
     echo H::a(
         $sales_order_number,
         $urlGenerator->generate('salesorder/view', ['id' => $inv->getSoId()]),
         ['class' => 'btn btn-success']
     );
    echo H::closeTag('div');
}
        echo H::tag('input', '', [
            'type' => 'text',
            'id' => 'dropzone_client_id',
            'readonly' => true,
            'class' => 'form-control form-control-lg',
            'value' => $inv->getClient()?->reqId(),
            'hidden' => true
        ]);
       echo H::closeTag('div');
      echo H::closeTag('div');
     echo H::closeTag('div');
    echo H::closeTag('div');
   echo H::closeTag('div');
  echo H::closeTag('div');
  echo H::openTag('div', ['id' => 'partial_item_table_parameters',
      'disabled' => true]);
   echo $partial_item_table;
  echo H::closeTag('div');

  echo H::openTag('div', ['class' => 'row']);
   echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']);
    echo H::openTag('div', ['class' => 'panel panel-default no-margin']);
     echo H::openTag('div', ['class' => 'panel-heading']);
      echo H::openTag('b');
       echo H::encode($translator->translate('terms'));
        $paymentTermArray = $s->getPaymentTermArray($translator);
        $termsKey = (int) $inv->getTerms() ?: 0;
        $terms = (string) $paymentTermArray[$termsKey];
      echo H::closeTag('b');
     echo H::closeTag('div');
     echo H::openTag('div', ['class' => 'panel-body']);
      echo H::openTag('textarea', [
          'name' => 'terms',
          'id' => 'terms',
          'rows' => '3',
          'disabled' => true,
          'class' => 'input-sm form-control'
      ]);
       echo H::encode($terms);
      echo H::closeTag('textarea');
     echo H::closeTag('div');
    echo H::closeTag('div');

    echo H::openTag('div', ['class' => 'col-xs-12 visible-xs visible-sm']);
     echo '<br>';
    echo H::closeTag('div');

   echo H::closeTag('div');
   echo H::openTag('div', ['id' => 'view_custom_fields',
       'class' => 'col-xs-12 col-md-6']);
    echo $view_custom_fields;
   echo H::closeTag('div');
   echo H::openTag('div', ['id' => 'view_partial_inv_delivery_location',
       'class' => 'col-xs-12 col-md-6']);
    echo $partial_inv_delivery_location;
   echo H::closeTag('div');
   echo H::openTag('div', ['id' => 'view_partial_inv_attachments']);
    echo $partial_inv_attachments;
   echo H::closeTag('div');
   echo $modal_add_allowance_charge;
  echo H::closeTag('div');
