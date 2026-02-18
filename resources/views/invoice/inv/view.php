<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\A;

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
 * @var string $modal_create_recurring
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
          echo H::encode(!is_string($paymentDate = $payment->getPayment_date()) ?
              $paymentDate->format('Y-m-d') : '');
         echo H::closeTag('td');
         echo H::openTag('td');
          echo H::encode($s->format_currency($payment->getAmount() >= 0.00 ?
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
if ($readOnly === false && $invEdit) {
    echo '<br>';
    echo '<br>';
    echo H::openTag('ul', ['id' => 'product-tabs',
        'class' => 'nav nav-tabs nav-tabs-noborder']);
        echo H::openTag('li', ['class' => 'active']);
        echo A::tag()
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
        echo A::tag()
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
        echo A::tag()
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
         echo I::tag()
            ->addClass('fa fa-list')
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
echo H::openTag('input', [
    'type' => 'hidden',
    'id' => '_csrf',
    'name' => '_csrf',
    'value' => $csrf]);
 echo H::openTag('div', ['id' => 'headerbar']);
  echo H::openTag('h1', ['class' => 'headerbar-title']);
   echo H::encode($translator->translate('invoice')) . ' ';
   echo(H::encode(strlen($inv->getNumber() ?? '') > 0 ?
        ' #' . ($inv->getNumber() ?? ' #') : $inv->getId()));
  echo H::closeTag('h1');
 echo H::closeTag('div');
// Toolbar
echo $buttonsToolbarFull;
 echo H::openTag('div', ['class' => 'headerbar-item pull-left' . 
    ($inv->getIs_read_only() === false || $inv->getStatus_id() !== 4 ? ' btn-group' : '')]);
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
if ($showButtons && $invEdit) {
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/edit', ['id' => $inv->getId()]),
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'fa fa-edit fa-margin']);
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
          echo H::openTag('i', ['class' => 'fa fa-plus fa-margin']);
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
      echo H::openTag('i', ['class' => 'fa fa-plus fa-margin']);
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
      echo H::openTag('i', ['class' => 'fa fa-window-restore']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('peppol'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/peppol_stream_toggle',
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
         'href' => $urlGenerator->generate('inv/peppol_doc_currency_toggle',
                 ['id' => $inv->getId()]),
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', [
          'class' => 'fa ' . ($peppol_doc_currency_toggle === '1' ?
              'fa-toggle-on' : 'fa-toggle-off') . ' fa-margin',
          'aria-hidden' => 'true'
      ]);
      echo H::closeTag('i');
// Options ...  Peppol Stream Toggle
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
          'class' => 'fa fa-check fa-margin',
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
      echo H::openTag('i', ['class' => 'fa fa-eye fa-margin']);
      echo H::closeTag('i');
// Options ...  Store Cove Json Encoded Invoice
      echo ' ' . H::encode($translator->translate('storecove.invoice.json.encoded'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('del/add',
            ['client_id' => $inv->getClient_id()],
                 ['origin' => 'inv',
                     'origin_id' => $inv->getId(), 'action' => 'view'],''),
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'fa fa-plus fa-margin']);
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
                    if (($readOnly === true || $inv->getStatus_id() === 4)
                        && $invEdit
                        && !(int) $inv->getCreditinvoice_parent_id() > 0) {
        echo H::openTag('li');
         echo H::openTag('a', [
             'href' => '#create-credit-inv',
             'data-bs-toggle' => 'modal',
             'data-invoice-id' => $inv->getId(),
             'style' => 'text-decoration:none'
         ]);
          echo H::openTag('i', ['class' => 'fa fa-minus fa-margin']);
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
if ($invAmountBalance >= 0.00 && $inv->getStatus_id() !== 1 && $invEdit) :
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('payment/add'),
         'style' => 'text-decoration:none',
         'class' => 'invoice-add-payment',
         'data-invoice-id' => H::encode($inv->getId()),
         'data-invoice-balance' => H::encode($invAmountBalance),
         'data-invoice-payment-method' => H::encode($inv->getPayment_method()),
         'data-payment-cf-exisst' => H::encode($paymentCfExist)
     ]);
      echo H::openTag('i', ['class' => 'fa fa-credit-card fa-margin']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('enter.payment'));
     echo H::closeTag('a');
    echo H::closeTag('li');
endif;
// Options ... Pay Now
// Show the pay now button if not a draft and the user has viewPayment
// permission ie. not editPayment permission
if ((in_array($inv->getStatus_id(), [2, 3])
        && $invAmountBalance > 0)
        && $paymentView) {
    /**
     * @var array $enabled_gateways
     * @var string $gateway
     */
    foreach ($enabled_gateways as $gateway) {
        echo H::openTag('li');
         if ($inv->getPayment_method() !== 0) {
            // Because there is a payment method
            // there is no need to show a message modal
            echo H::openTag('a', [
                'href' => $urlGenerator->generate('inv/url_key',
                        ['url_key' => $inv->getUrl_key(),
                            'gateway' => $gateway]),
                'style' => 'text-decoration:none'
            ]);
             echo H::openTag('i', ['class' => 'fa fa-minus fa-margin']);
             echo H::closeTag('i');
             echo ' '
                . H::encode($translator->translate('pay.now')
                . '-' . ucfirst($gateway));
            echo H::closeTag('a');
        }
        // show a message modal if there is no payment method
        // resources/views/invoice/inv/modal_message_layout has
        // the ... 'id' => 'modal-message-'.$type which matches the
        // #modal-message-inv below
        if ($inv->getPayment_method() === 0) {
            echo H::openTag('a', [
                'href' => '#modal-message-inv',
                'data-bs-toggle' => 'modal',
                'style' => 'text-decoration:none'
            ]);
             echo H::openTag('i', ['class' => 'fa fa-minus fa-margin']);
             echo H::closeTag('i');
             echo ' ' . H::encode($translator->translate('pay.now') . '-'
                     . ucfirst($gateway));
            echo H::closeTag('a');
        }
        echo H::closeTag('li');
    }
}
echo H::openTag('li');
?>
<!-- null!==$sumex There is a sumex extension record linked to the current
    invoice_id and the sumex setting under View...Settings...Invoice...Sumex
    Settings is set at Yes. -->
<?php
// Options ... Download PDF
 echo H::openTag('a', [
     'href' => '#inv-to-pdf',
     'data-bs-toggle' => 'modal',
     'style' => 'text-decoration:none'
 ]);
  echo H::openTag('i', ['class' => 'fa fa-print fa-margin']);
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
     echo H::openTag('i', ['class' => 'fa fa-desktop fa-margin']);
     echo H::closeTag('i');
     echo ' ' . H::encode($translator->translate('pdf.modal') . ' ✅');
    echo H::closeTag('a');
} else {
    echo H::openTag('a', [
        'href' => $urlGenerator->generate('setting/tab_index',
                [],
                ['active' => 'invoices'], 'settings[pdf_stream_inv]'),
        'style' => 'text-decoration:none'
    ]);
     echo H::openTag('i', ['class' => 'fa fa-desktop fa-margin']);
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
      echo H::openTag('i', ['class' => 'fa fa-refresh fa-margin']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('create.recurring'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/email_stage_0',
                 ['id' => $inv->getId()]),
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'fa fa-send fa-margin']);
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
      echo H::openTag('i', ['class' => 'fa fa-copy fa-margin']);
      echo H::closeTag('i');
/**
 * Related logic: see resources/views/invoice/inv/modal_copy_inv.php
 * Options ... Copy Invoice
 */
      echo ' ' . H::encode($translator->translate('copy.invoice'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
// Options ... Invoice to HTML with Sumex
     if ($s->getSetting('sumex') === '1') {
         echo H::openTag('a', [
             'href' => '#inv-to-html',
             'data-bs-toggle' => 'modal',
             'style' => 'text-decoration:none'
         ]);
          echo H::openTag('i', ['class' => 'fa fa-print fa-margin']);
          echo H::closeTag('i');
          echo ' ' . H::encode($translator->translate('html.sumex.yes'));
         echo H::closeTag('a');
// Options ... Invoice to HTML without Sumex
     } else {
         echo H::openTag('a', [
             'href' => '#inv-to-html',
             'data-bs-toggle' => 'modal',
             'style' => 'text-decoration:none'
         ]);
          echo H::openTag('i', ['class' => 'fa fa-print fa-margin']);
          echo H::closeTag('i');
          echo ' ' . H::encode($translator->translate('html.sumex.no'));
         echo H::closeTag('a');
     }

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
if (($inv->getStatus_id() === 1 ||
        ($s->getSetting('enable_invoice_deletion') === '1'
        && $inv->getIs_read_only() === false)) && !$inv->getSo_id() && $invEdit) {
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => '#delete-inv',
         'data-bs-toggle' => 'modal',
         'style' => 'text-decoration:none'
     ]);
      echo H::openTag('i', ['class' => 'fa fa-trash fa-margin']);
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
      echo H::openTag('i', ['class' => 'fa fa-trash fa-margin']);
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
       echo H::openTag('span', ['class' => 'label label-info']);
        echo H::openTag('i', ['class' => 'fa fa-refresh']);
        echo H::closeTag('i');
        echo ' ' . H::encode($translator->translate('recurring'));
       echo H::closeTag('span');
   }
   if ($inv->getIs_read_only() === true) {
       echo H::openTag('span', ['class' => 'label label-danger']);
        echo H::openTag('i', ['class' => 'fa fa-read-only']);
        echo H::closeTag('i');
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
              ['id' => $inv->getClient()?->getClient_id()])]);
       echo H::encode($clientHelper->format_client($inv->getClient()));
      echo H::closeTag('a');
     echo H::closeTag('h3');
     echo '<br>';
     echo H::openTag('div', ['id' => 'pre_save_client_id',
         'value' => $inv->getClient()?->getClient_id(), 'hidden' => true]);
     echo H::closeTag('div');
     echo H::openTag('div', ['class' => 'client-address']);
      echo H::openTag('span', ['class' => 'client-address-street-line-1']);
       if (strlen($inv->getClient()?->getClient_address_1() ?? '') > 0) {
           echo H::encode($inv->getClient()?->getClient_address_1()) . '<br>';
       }
      echo H::closeTag('span');
      echo H::openTag('span', ['class' => 'client-address-street-line-2']);
       if (strlen($inv->getClient()?->getClient_address_2() ?? '') > 0) {
           echo H::encode($inv->getClient()?->getClient_address_2()) . '<br>';
       }
      echo H::closeTag('span');
      echo H::openTag('span', ['class' => 'client-address-town-line']);
       if (strlen($inv->getClient()?->getClient_city() ?? '') > 0) {
           echo H::encode($inv->getClient()?->getClient_city()) . '<br>';
       }
       if (strlen($inv->getClient()?->getClient_state() ?? '') > 0) {
           echo H::encode($inv->getClient()?->getClient_state()) . '<br>';
       }
       if (strlen($inv->getClient()?->getClient_zip() ?? '') > 0) {
           echo H::encode($inv->getClient()?->getClient_zip());
       }
      echo H::closeTag('span');
      echo H::openTag('span', ['class' => 'client-address-country-line']);
       if (strlen($inv->getClient()?->getClient_country() ?? '') > 0) {
           echo '<br>'
           . $countryHelper->get_country_name($translator->translate('cldr'),
                   ($inv->getClient()?->getClient_country() ?? ''));
       }
      echo H::closeTag('span');
     echo H::closeTag('div');
     echo '<hr>';
     if (strlen($inv->getClient()?->getClient_phone() ?? '') > 0) {
         echo H::openTag('div', ['class' => 'client-phone']);
          echo $translator->translate('phone')
                  . ':&nbsp;'
                  . H::encode($inv->getClient()?->getClient_phone() ?? '');
         echo H::closeTag('div');
     }
     if ($inv->getClient()?->getClient_mobile() ?? '') {
         echo H::openTag('div', ['class' => 'client-mobile']);
          echo $translator->translate('mobile')
                  . ':&nbsp;'
                  . H::encode($inv->getClient()?->getClient_mobile());
         echo H::closeTag('div');
     }
     if (null !== $inv->getClient()?->getClient_email()) {
         echo H::openTag('div', ['class' => 'client-email']);
          echo $translator->translate('email')
                  . ':&nbsp;'
                  . ($inv->getClient()?->getClient_email() ?? '');
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
         echo H::openTag('input', [
             'type' => 'text',
             'id' => 'inv_number',
             'class' => 'form-control',
             'readonly' => true,
             'value' => (strlen($inv->getNumber() ?? '') > 0 ?
                $inv->getNumber() : null),
             'placeholder' => (strlen($inv->getNumber() ?? '') > 0 ?
                null : H::encode($translator->translate('not.set')))
         ]);
         echo H::closeTag('input');
        echo H::closeTag('div');
        echo H::openTag('div', ['class' => 'invoice-properties has-feedback']);
         echo H::openTag('label', ['for' => 'date_created']);
          echo H::openTag('b');
           echo $translator->translate('date.issued');
          echo H::closeTag('b');
         echo H::closeTag('label');
         echo H::openTag('div', ['class' => 'input-group']);
          echo H::openTag('input', [
              'id' => 'date_created',
              'disabled' => true,
              'class' => 'form-control',
              'value' => $inv->getDate_created()->format('Y-m-d')
          ]);
          echo H::closeTag('input');
          echo H::openTag('span', ['class' => 'input-group-text']);
           echo H::openTag('i', ['class' => 'fa fa-calendar fa-fw']);
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
          echo H::openTag('input', [
              'id' => 'date_supplied',
              'disabled' => true,
              'class' => 'form-control',
              'value' => $inv->getDate_supplied()->format('Y-m-d')
          ]);
          echo H::closeTag('input');
          echo H::openTag('span', ['class' => 'input-group-text']);
           echo H::openTag('i', ['class' => 'fa fa-calendar fa-fw']);
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
      echo H::openTag('input', [
          'id' => 'date_tax_point',
          'disabled' => true,
          'class' => 'form-control',
          'value' => $inv->getDate_tax_point()->format('Y-m-d')
      ]);
      echo H::closeTag('input');
      echo H::openTag('span', ['class' => 'input-group-text']);
       echo H::openTag('i', ['class' => 'fa fa-calendar fa-fw']);
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
          echo H::openTag('input', [
              'name' => 'inv_date_due',
              'id' => 'inv_date_due',
              'disabled' => true,
              'class' => 'form-control',
              'value' => !is_string($dateDue = $inv->getDate_due()) ?
              $dateDue->format('Y-m-d') : ''
          ]);
          echo H::closeTag('input');
          echo H::openTag('span', ['class' => 'input-group-text']);
           echo H::openTag('i', ['class' => 'fa fa-calendar fa-fw']);
           echo H::closeTag('i');
          echo H::closeTag('span');
         echo H::closeTag('div');
        echo H::closeTag('div');
        echo H::openTag('div');
?>
<?php
    /**
     * @var App\Invoice\Entity\CustomField $custom_field
     */
    foreach ($custom_fields as $custom_field): ?>
        <?php if ($custom_field->getLocation() !== 1) {
            continue;
        } ?>
        <?php $cvH->print_field_for_view($custom_field, $form,
                $inv_custom_values, $custom_values); ?>
 <?php endforeach; ?>
<?php
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
             'class' => 'form-control'
         ]);
/**
 * @var array $inv_statuses
 * @var string $key
 * @var array $status
 */
foreach ($inv_statuses as $key => $status) {
    echo H::openTag('option', [
        'value' => $key,
        'selected' => ($key == (string)$form->getStatus_id() ?
            $s->check_select((string)$form->getStatus_id(), $key) : null)
    ]);
     echo H::encode($status['label']);
    echo H::closeTag('option');
}
?>
<?php
         echo H::closeTag('select');
        echo H::closeTag('div');
        echo H::openTag('div', ['class' => 'invoice-properties']);
         echo H::openTag('label', ['for' => 'payment_method']);
          echo H::openTag('b');
           echo $translator->translate('payment.method');
          echo H::closeTag('b');
         echo H::closeTag('label');
if ($inv->getPayment_method() !== 0) {
    echo H::openTag('select', [
        'name' => 'payment_method',
        'id' => 'payment_method_1',
        'class' => 'form-control',
        'disabled' => 'disabled'
    ]);
     echo H::openTag('option', ['value' => '0']);
      echo H::encode($translator->translate('select.payment.method'));
     echo H::closeTag('option');
    /**
     * @var App\Invoice\Entity\PaymentMethod $payment_method
     */
    foreach ($payment_methods as $payment_method) {
        $s->check_select((string) $inv->getPayment_method(),
                $payment_method->getId());
        echo H::openTag('option', ['value' => $payment_method->getId()]);
         echo $payment_method->getName() ?? '';
        echo H::closeTag('option');
    }
    echo H::closeTag('select');
} else {
    echo H::openTag('select', [
        'name' => 'payment_method_2',
        'id' => 'payment_method',
        'class' => 'form-control',
        'disabled' => true
    ]);
     echo H::openTag('option', ['value' => '0']);
      echo H::encode($translator->translate('none'));
     echo H::closeTag('option');
    echo H::closeTag('select');
}
        echo H::closeTag('div');
// Show originating quote button if invoice was created from a quote
if ($inv->getQuote_id() !== '' && $inv->getQuote_id() !== '0') {
    echo H::openTag('div', ['class' => 'invoice-properties']);
     echo H::openTag('label', ['for' => 'quote-view-url']);
      echo H::openTag('b');
       echo $translator->translate('invoice.origin');
      echo H::closeTag('b');
     echo H::closeTag('label');
     echo H::openTag('div');
      echo H::openTag('a', [
          'href' => $urlGenerator->generate('quote/view',
                  ['id' => $inv->getQuote_id()]),
          'class' => 'btn btn-info btn-sm',
          'id' => 'quote-view-url'
      ]);
       echo H::openTag('i', ['class' => 'fa fa-file-text']);
       echo H::closeTag('i');
       echo ' '
            . $translator->translate('invoice.created.from.quote')
            . ' #' . $inv->getQuote_id();
      echo H::closeTag('a');
     echo H::closeTag('div');
    echo H::closeTag('div');
}
if (($inv->getStatus_id() !== 1) && ($invEdit)) {
    echo H::openTag('div', ['class' => 'invoice-properties']);
     echo H::openTag('label', ['for' => 'inv_password']);
      echo H::openTag('b');
       echo H::encode($translator->translate('password'));
      echo H::closeTag('b');
     echo H::closeTag('label');
     echo H::openTag('input', [
         'type' => 'text',
         'id' => 'inv_password',
         'class' => 'form-control',
         'disabled' => true,
         'value' => H::encode($form->getPassword() ?? '')
     ]);
     echo H::closeTag('input');
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'invoice-properties']);
     echo H::openTag('div', ['class' => 'form-group']);
      echo H::openTag('label', ['for' => 'guest-url']);
       echo H::openTag('b');
        echo H::encode($translator->translate('guest.url'));
       echo H::closeTag('b');
      echo H::closeTag('label');
      echo H::openTag('div', ['class' => 'input-group']);
       echo H::openTag('input', [
           'type' => 'text',
           'id' => 'guest-url',
           'name' => 'guest-url',
           'readonly' => true,
           'class' => 'form-control',
           'value' => 'inv/url_key/' . $inv->getUrl_key()
       ]);
       echo H::closeTag('input');
       echo H::openTag('span', [
           'class' => 'input-group-text to-clipboard cursor-pointer',
           'data-clipboard-target' => '#guest-url'
       ]);
        echo H::openTag('i', ['class' => 'fa fa-clipboard fa-fw']);
        echo H::closeTag('i');
       echo H::closeTag('span');
      echo H::closeTag('div');
     echo H::closeTag('div');
    echo H::closeTag('div');
}
    echo H::openTag('div');
     echo '<br>';
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
$statusId = $inv->getStatus_id();
if ($statusId !== null && isset($statusImages[$statusId])) {
    $statusInfo = $statusImages[$statusId];
    echo H::openTag('img', [
        'src' => $statusInfo[0],
        'alt' => $translator->translate($statusInfo[1])
    ]);
    echo H::closeTag('img');
}
    echo H::closeTag('div');
if (!empty($inv->getSo_id())) {
    echo H::openTag('div');
     echo $translator->translate('salesorder');
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'input-group']);
     echo H::a(
         $sales_order_number,
         $urlGenerator->generate('salesorder/view', ['id' => $inv->getSo_id()]),
         ['class' => 'btn btn-success']
     );
    echo H::closeTag('div');
}
        echo H::openTag('input', [
            'type' => 'text',
            'id' => 'dropzone_client_id',
            'readonly' => true,
            'class' => 'form-control',
            'value' => $inv->getClient()?->getClient_id(),
            'hidden' => true
        ]);
        echo H::closeTag('input');
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
        $paymentTermArray = $s->get_payment_term_array($translator);
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
