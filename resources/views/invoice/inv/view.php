<?php

declare(strict_types=1);

use App\Widget\Button;
use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Option;

/**
 * @var App\Infrastructure\Persistence\Inv\Inv $inv
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
 * @var array $email_templates_invoice
 * @var array $invoice_groups
 */

$settingTabIndex = 'setting/tabIndex';
$peppolEnabled = $s->getSetting('enable_peppol') == '1';

// Resolve invoice group name from ID
$defaultInvGroupName = $translator->translate('not.set');
/** @var App\Infrastructure\Persistence\Group\Group $group */
foreach ($invoice_groups as $group) {
    if ((string) $group->reqId() === $s->getSetting('default_invoice_group')) {
        $defaultInvGroupName = $group->getName() ?? $translator->translate('not.set');
        break;
    }
}

// Resolve payment method name from ID
$defaultPmName = $translator->translate('not.set');
/** @var App\Infrastructure\Persistence\PaymentMethod\PaymentMethod $pm */
foreach ($payment_methods as $pm) {
    if ((string) $pm->reqId() === $s->getSetting('invoice_default_payment_method')) {
        $defaultPmName = $pm->getName() ?? $translator->translate('not.set');
        break;
    }
}

// Resolve email template titles from IDs
$emailInvTemplateName = $translator->translate('not.set');
$emailInvTemplatePaidName = $translator->translate('not.set');
$emailInvTemplateOverdueName = $translator->translate('not.set');
/** @var App\Infrastructure\Persistence\EmailTemplate\EmailTemplate $et */
foreach ($email_templates_invoice as $et) {
    $etId = (string) $et->reqEmailTemplateId();
    $etTitle = $et->getEmailTemplateTitle() ?? $translator->translate('not.set');
    if ($etId === $s->getSetting('email_invoice_template')) {
        $emailInvTemplateName = $etTitle;
    }
    if ($etId === $s->getSetting('email_invoice_template_paid')) {
        $emailInvTemplatePaidName = $etTitle;
    }
    if ($etId === $s->getSetting('email_invoice_template_overdue')) {
        $emailInvTemplateOverdueName = $etTitle;
    }
}

// Map read_only_toggle numeric value to label
$readOnlyLabel = match($s->getSetting('read_only_toggle')) {
    '2' => $translator->translate('sent'),
    '3' => $translator->translate('viewed'),
    '4' => $translator->translate('paid'),
    default => $translator->translate('not.set'),
};

$breadcrumbLinks = [
     // ── Invoice settings ──────────────────────────────────────────────
     BreadcrumbLink::to(
         label: $translator->translate('default.invoice.group'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[default_invoice_group]',
         ),
         active: true,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $defaultInvGroupName,
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('default.terms'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[default_invoice_terms]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('default_invoice_terms')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('default.payment.method'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[invoice_default_payment_method]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $defaultPmName,
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('invoices.due.after'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[invoices_due_after]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('invoices_due_after')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('generate.invoice.number.for.draft'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[generate_invoice_number_for_draft]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('generate_invoice_number_for_draft') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('mark.invoices.sent.pdf'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[mark_invoices_sent_pdf]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('mark_invoices_sent_pdf') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('pre.password'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[invoice_pre_password]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('invoice_pre_password')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('pdf.include.zugferd'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[include_zugferd]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('include_zugferd') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('pdf.watermark'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[pdf_watermark]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('pdf_watermark') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('stream'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[pdf_stream_inv]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('pdf_stream_inv') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('archive'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[pdf_archive_inv]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('pdf_archive_inv') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: 'Preview Invoice Pdf as Webpage',
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[pdf_html_inv]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('pdf_html_inv') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('default.pdf.template'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[pdf_invoice_template]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('pdf_invoice_template')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('pdf.template.paid'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[pdf_invoice_template_paid]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('pdf_invoice_template_paid')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('pdf.template.overdue'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[pdf_invoice_template_overdue]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('pdf_invoice_template_overdue')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('default.public.template'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[public_invoice_template]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('public_invoice_template')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('default.email.template'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[email_invoice_template]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $emailInvTemplateName,
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('email.template.paid'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[email_invoice_template_paid]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $emailInvTemplatePaidName,
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('email.template.overdue'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[email_invoice_template_overdue]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $emailInvTemplateOverdueName,
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('automatic.email.on.recur'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[automatic_email_on_recur]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('automatic_email_on_recur') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('set.to.read.only'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[read_only_toggle]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $readOnlyLabel,
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('mark.invoices.sent.copy'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[mark_invoices_sent_copy]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('mark_invoices_sent_copy') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
];
if ($peppolEnabled) {
    $breadcrumbLinks = array_merge($breadcrumbLinks, [
     // ── Peppol settings ───────────────────────────────────────────────
     BreadcrumbLink::to(
         label: $translator->translate('peppol.enable'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'peppol'],
             'settings[enable_peppol]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('enable_peppol') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('peppol.document.currency'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'peppol'],
             'settings[peppol_document_currency]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('peppol_document_currency')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('peppol.currency.code.from'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'peppol'],
             'settings[currency_code_from]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('currency_code_from')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('peppol.currency.code.to'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'peppol'],
             'settings[currency_code_to]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('currency_code_to')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('peppol.stand.in.code'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'peppol'],
             'settings[stand_in_code]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('stand_in_code')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('peppol.include.delivery.period'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'peppol'],
             'settings[include_delivery_period]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('include_delivery_period') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('peppol.xml.stream'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'peppol'],
             'settings[peppol_xml_stream]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('peppol_xml_stream') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('peppol.debug.with.emojis'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'peppol'],
             'settings[peppol_debug_with_emojis]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('peppol_debug_with_emojis') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('peppol.debug.with.internal.validator'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'peppol'],
             'settings[peppol_debug_with_internal_validator]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('peppol_debug_with_internal_validator') == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
    ]);
}
echo Breadcrumbs::widget()
 ->links(...$breadcrumbLinks)
 ->listId(false)
 ->render();

$vat          = $s->getSetting('enable_vat_registration');
$biBiPlus     = 'bi bi-plus';
$col          = 'col-12 col-md-6';
$fc           = 'form-control form-control-lg';
$invProp      = 'invoice-properties';
$dropdownItem = 'dropdown-item';
$biDash       = 'bi bi-dash-lg';
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
    echo H::openTag('div', ['class' => 'card-header']);
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
       * @var App\Infrastructure\Persistence\Payment\Payment  $payment
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
if ($readOnly === false && $invEdit && $inv->reqStatusId() === 1) {
    echo '<br>';
    echo '<br>';
    echo H::openTag('ul', ['id' => 'product-tabs',
        'class' => 'nav nav-tabs nav-tabs-noborder']);
        echo H::openTag('li', ['class' => 'active']);
        echo  new A()
            ->addAttributes([
                'data-bs-toggle' => 'tab',
                'class' => 'text-decoration-none',
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
                'class' => 'text-decoration-none',
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
                'class' => 'text-decoration-none',
            ])
            ->addClass('btn btn-danger bi bi-arrow-left')
            ->id('back')
            ->render();
        echo H::closeTag('li');
    echo H::closeTag('ul');
    echo H::openTag('div', ['class' => 'tabs-below']);
     echo H::openTag('div', ['class' => 'tab-content']);
      echo H::openTag('div', ['id' => 'add-product-tab', 'class' => 'tab-pane']);
       echo H::openTag('div', ['class' => 'card-header']);
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
       echo H::openTag('div', ['class' => 'card-header']);
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
        ' #' . ($inv->getNumber() ?? ' #') : $inv->reqId());
  echo H::closeTag('h1');
 echo H::closeTag('div');
// Toolbar
echo $buttonsToolbarFull;
 echo H::openTag('div', ['class' => 'headerbar-item float-start' .
    ($inv->getIsReadOnly() === false || $inv->reqStatusId() !== 4 ? ' btn-group' : '')]);
  echo H::openTag('div', ['class' => 'dropdown']);
   echo H::openTag('button', [
       'class' => 'btn btn-primary dropdown-toggle',
       'type' => 'button',
       'data-bs-toggle' => 'dropdown',
       'aria-expanded' => 'false'
   ]);
    echo $translator->translate('options');
   echo H::closeTag('button');
   echo H::openTag('ul', ['class' => 'dropdown-menu']);
// Options...Edit
if ($showButtons
        && $invEdit
        && null === $inv->getQuoteId()
// Only allow the editing of the invoice if not connected to a salesorder
        && null === $inv->getSoId()) {
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/edit', ['id' => $inv->reqId()]),
         'class' => $dropdownItem
     ]);
      echo H::openTag('i', ['class' => 'bi bi-pencil-square']);
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
             'class' => $dropdownItem
         ]);
          echo H::openTag('i', ['class' => $biBiPlus]);
          echo H::closeTag('i');
          echo ' ' . H::encode($translator->translate('add.invoice.tax'));
         echo H::closeTag('a');
        echo H::closeTag('li');
    }
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => '#add-inv-allowance-charge',
         'data-bs-toggle' => 'modal',
         'class' => $dropdownItem
     ]);
      echo H::openTag('i', ['class' => $biBiPlus]);
      echo H::closeTag('i');
      echo ' ' . $translator->translate('allowance.or.charge.inv.add');
     echo H::closeTag('a');
    echo H::closeTag('li');
}
// Options ... Peppol UBL 2.4 Invoice
if ($peppolEnabled) {
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/peppol', ['id' => $inv->reqId()]),
         'class' => $dropdownItem
     ]);
      echo H::openTag('i', ['class' => 'bi bi-window-stack']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('peppol'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/peppolStreamToggle',
                 ['id' => $inv->reqId()]),
         'class' => $dropdownItem
     ]);
      echo H::openTag('i', [
          'class' => 'bi ' . ($peppol_stream_toggle === '1' ?
              'bi-toggle-on' : 'bi-toggle-off'),
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
                 ['id' => $inv->reqId()]),
         'class' => $dropdownItem
     ]);
      echo H::openTag('i', [
          'class' => 'bi ' . ($peppol_doc_currency_toggle === '1' ?
              'bi-toggle-on' : 'bi-toggle-off'),
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
         'class' => $dropdownItem
     ]);
      echo H::openTag('i', [
          'class' => 'bi bi-check-lg',
          'aria-hidden' => 'true']);
      echo H::closeTag('i');
// Options ...  Ecosio Validator
      echo ' ' . H::encode($translator->translate('peppol.ecosio.validator'));
     echo H::closeTag('a');
    echo H::closeTag('li');
}
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/storecove', ['id' => $inv->reqId()]),
         'class' => $dropdownItem,
         'target' => '_blank'
     ]);
      echo H::openTag('i', ['class' => 'bi bi-eye']);
      echo H::closeTag('i');
// Options ...  Store Cove Json Encoded Invoice
      echo ' ' . H::encode($translator->translate('storecove.invoice.json.encoded'));
     echo H::closeTag('a');
    echo H::closeTag('li');
// Options ... Send via Peppol (Oxalis) — only available for non-draft invoices
if ($peppolEnabled && $inv->reqStatusId() !== 1) {
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('inv/peppolSend', ['id' => $inv->reqId()]),
         'class' => $dropdownItem,
     ]);
      echo H::openTag('i', ['class' => 'bi bi-send-fill']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('peppol.send.via.oxalis'));
     echo H::closeTag('a');
    echo H::closeTag('li');
}
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('del/add',
            ['client_id' => $inv->reqClientId()],
                 ['origin' => 'inv',
                     'origin_id' => $inv->reqId(), 'action' => 'view'],''),
         'class' => $dropdownItem
     ]);
      echo H::openTag('i', ['class' => $biBiPlus]);
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
                    if (($readOnly === true || $inv->reqStatusId() === 4)
                        && $invEdit
                        && null === $inv->getCreditinvoiceParentId()) {
        echo H::openTag('li');
         echo H::openTag('a', [
             'href' => '#create-credit-inv',
             'data-bs-toggle' => 'modal',
             'data-invoice-id' => $inv->reqId(),
             'class' => $dropdownItem
         ]);
          echo H::openTag('i', ['class' => $biDash]);
          echo H::closeTag('i');
          echo ' ' . H::encode($translator->translate('create.credit.invoice'));
         echo H::closeTag('a');
        echo H::closeTag('li');
    }
// Options ... Enter Payment
/**
 * @var App\Infrastructure\Persistence\InvAmount\InvAmount $inv_amount
 */
$inv_amount = ($iaR->repoInvAmountcount($inv->reqId()) > 0 ?
        $iaR->repoInvquery($inv->reqId()) : '');
// If there is a balance outstanding and the invoice is not a draft ie. at
// least sent, allow a payment to be allocated against it.
$invAmountBalance = $inv_amount->getBalance();
if ($invAmountBalance >= 0.00 && $inv->reqStatusId() !== 1 && $invEdit) :
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => $urlGenerator->generate('payment/add'),
         'class' => 'dropdown-item invoice-add-payment',
         'data-invoice-id' => H::encode($inv->reqId()),
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
if ((in_array($inv->reqStatusId(), [2, 3])
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
                'class' => $dropdownItem
            ]);
             echo H::openTag('i', ['class' => $biDash]);
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
                'class' => $dropdownItem
            ]);
             echo H::openTag('i', ['class' => $biDash]);
             echo H::closeTag('i');
             echo ' ' . H::encode($translator->translate('pay.now') . '-'
                     . ucfirst($gateway));
            echo H::closeTag('a');
        }
        echo H::closeTag('li');
    }
}
if ((in_array($inv->reqStatusId(), [1]))) {
    echo H::openTag('li');
        echo H::openTag('a', [
                'href' => '#modal-message-inv',
                'data-bs-toggle' => 'modal',
                'class' => $dropdownItem
            ]);
             echo H::openTag('i', ['class' => $biDash]);
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
     'class' => $dropdownItem
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
        'class' => $dropdownItem
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
        'class' => $dropdownItem
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
                 ['inv_id' => $inv->reqId()]),
         'class' => $dropdownItem
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
                 ['id' => $inv->reqId()]),
         'class' => $dropdownItem
     ]);
      echo H::openTag('i', ['class' => 'bi bi-send']);
      echo H::closeTag('i');
// Options ... Send Email
      echo ' ' . H::encode($translator->translate('send.email'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    if ($s->getSetting('enable_telegram') == '1') {
        echo H::openTag('li');
         echo H::openTag('a', [
             'href' => $urlGenerator->generate('telegram/sendInvoice',
                     ['inv_id' => $inv->reqId()]),
             'class' => $dropdownItem
         ]);
          echo H::openTag('i', ['class' => 'bi bi-telegram']);
          echo H::closeTag('i');
// Options ... Send Telegram Invoice
          echo ' ' . H::encode($translator->translate('send.telegram.invoice'));
         echo H::closeTag('a');
        echo H::closeTag('li');
        echo H::openTag('li');
         echo H::openTag('a', [
             'href' => $urlGenerator->generate('telegram/invoiceLink',
                     ['inv_id' => $inv->reqId()]),
             'class' => $dropdownItem
         ]);
          echo H::openTag('i', ['class' => 'bi bi-link-45deg']);
          echo H::closeTag('i');
// Options ... Create Telegram Payment Link
          echo ' ' . H::encode($translator->translate('telegram.invoice.link.created.menu'));
         echo H::closeTag('a');
        echo H::closeTag('li');
        echo H::openTag('li');
         echo H::openTag('a', [
             'href' => $urlGenerator->generate('telegram/sendPdf',
                     ['inv_id' => $inv->reqId()]),
             'class' => $dropdownItem
         ]);
          echo H::openTag('i', ['class' => 'bi bi-file-earmark-pdf']);
          echo H::closeTag('i');
// Options ... Send PDF via Telegram
          echo ' ' . H::encode($translator->translate('telegram.pdf.send.menu'));
         echo H::closeTag('a');
        echo H::closeTag('li');
    }
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => '#inv-to-inv',
         'data-bs-toggle' => 'modal',
         'class' => $dropdownItem
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
        'class' => $dropdownItem
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
if ($inv->reqStatusId() === 1
        && $s->getSetting('enable_invoice_deletion') === '1'
        && $inv->getIsReadOnly() === false
        && null === $inv->getSoId()
        && null === $inv->getQuoteId()
        && $invEdit) {
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => '#delete-inv',
         'data-bs-toggle' => 'modal',
         'class' => $dropdownItem
     ]);
      echo H::openTag('i', ['class' => 'bi bi-trash']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('delete'));
     echo H::closeTag('a');
    echo H::closeTag('li');
    echo H::openTag('li');
     echo H::openTag('a', [
         'href' => '#delete-items',
         'data-bs-toggle' => 'modal',
         'class' => $dropdownItem
     ]);
      echo H::openTag('i', ['class' => 'bi bi-trash']);
      echo H::closeTag('i');
      echo ' ' . H::encode($translator->translate('delete')
              . " " . $translator->translate('item'));
     echo H::closeTag('a');
    echo H::closeTag('li');
}
   echo H::closeTag('ul');
  echo H::closeTag('div');
  echo H::openTag('div', ['class' => 'headerbar-item invoice-labels float-end']);
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
    echo H::openTag('div', ['class' => 'col-12 col-sm-6 col-md-5']);
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

    echo H::openTag('div', ['class' => 'col-12 d-block d-sm-none']);
     echo '<br>';
    echo H::closeTag('div');

    echo H::openTag('div', ['class' => 'col-12 col-sm-6 col-md-7']);
     echo H::openTag('div', ['class' => 'details-box']);
      echo H::openTag('div', ['class' => 'row']);

       echo H::openTag('div', ['class' => $col]);

        echo H::openTag('div', ['class' => $invProp]);
         echo H::openTag('label', ['for' => 'inv_number']);
          echo H::openTag('b');
           echo $translator->translate('invoice') . ' #';
          echo H::closeTag('b');
         echo H::closeTag('label');
         echo H::tag('input', '', [
             'type' => 'text',
             'id' => 'inv_number',
             'class' => $fc,
             'readonly' => true,
             'value' => (strlen($inv->getNumber() ?? '') > 0 ?
                $inv->getNumber() : null),
             'placeholder' => (strlen($inv->getNumber() ?? '') > 0 ?
                null : H::encode($translator->translate('not.set')))
         ]);
        echo H::closeTag('div');
        echo H::openTag('div');
         echo H::openTag('label', ['for' => 'date_created']);
          echo H::openTag('b');
           echo $translator->translate('date.issued');
          echo H::closeTag('b');
         echo H::closeTag('label');
         echo H::openTag('div');
          echo H::tag('input', '', [
              'id' => 'date_created',
              'disabled' => true,
              'class' => $fc,
              'value' => $inv->getDateCreated()->format('Y-m-d')
          ]);
         echo H::closeTag('div');
        echo H::closeTag('div');
        echo H::openTag('div', ['class' => $invProp]);
         echo H::openTag('label', ['for' => 'date_supplied']);
          echo H::openTag('b');
           echo $translator->translate('date.supplied');
          echo H::closeTag('b');
         echo H::closeTag('label');
         echo H::openTag('div');
          echo H::tag('input', '', [
              'id' => 'date_supplied',
              'disabled' => true,
              'class' => $fc,
              'value' => $inv->getDateSupplied()->format('Y-m-d')
          ]);
         echo H::closeTag('div');
        echo H::closeTag('div');
if ($vat === '1') {
    echo H::openTag('div');
     echo H::openTag('label', ['for' => 'date_tax_point']);
      echo H::openTag('b');
       echo $translator->translate('tax.point');
      echo H::closeTag('b');
     echo H::closeTag('label');
     echo H::openTag('div');
      echo H::tag('input', '', [
          'id' => 'date_tax_point',
          'disabled' => true,
          'class' => $fc,
          'value' => $inv->getDateTaxPoint()->format('Y-m-d')
      ]);
     echo H::closeTag('div');
    echo H::closeTag('div');
}
        echo H::openTag('div');
         echo H::openTag('label', ['for' => 'inv_date_due']);
          echo H::openTag('b');
           echo $translator->translate('expires');
          echo H::closeTag('b');
         echo H::closeTag('label');
         echo H::openTag('div');
          echo H::tag('input', '', [
              'name' => 'inv_date_due',
              'id' => 'inv_date_due',
              'disabled' => true,
              'class' => $fc,
              'value' => !is_string($dateDue = $inv->getDateDue()) ?
              $dateDue->format('Y-m-d') : ''
          ]);
         echo H::closeTag('div');
        echo H::closeTag('div');
        echo H::openTag('div');
        /**
         * @var App\Infrastructure\Persistence\CustomField\CustomField $custom_field
         */
        foreach ($custom_fields as $custom_field) {
            if ($custom_field->getLocation() !== 1) {
                continue;
            }
            $cvH->printFieldForView($custom_field, $form, $inv_custom_values);
        }
        echo H::closeTag('div');
       echo H::closeTag('div');
       echo H::openTag('div', ['class' => $col]);
        echo H::openTag('div', ['class' => $invProp]);
         echo H::openTag('label', ['for' => 'inv_status_id']);
          echo H::openTag('b');
           echo $translator->translate('status');
          echo H::closeTag('b');
         echo H::closeTag('label');
         echo H::openTag('select', [
             'name' => 'inv_status_id',
             'id' => 'inv_status_id',
             'disabled' => true,
             'class' => $fc,
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
        echo H::openTag('div', ['class' => $invProp]);
         echo H::openTag('label', ['for' => 'payment_method']);
          echo H::openTag('b');
           echo $translator->translate('payment.method');
          echo H::closeTag('b');
         echo H::closeTag('label');
if ($inv->getPaymentMethod() !== 0) {
    echo H::openTag('select', [
        'name' => 'payment_method',
        'id' => 'payment_method_1',
        'class' => $fc,
        'disabled' => 'disabled'
    ]);
     echo new Option()
      ->value('0')
      ->content(H::encode($translator->translate('select.payment.method')));
    /**
     * @var App\Infrastructure\Persistence\PaymentMethod\PaymentMethod $payment_method
     */
    foreach ($payment_methods as $payment_method) {
        echo new Option()
         ->value($payment_method->reqId())
         ->selected($inv->getPaymentMethod() === $payment_method->reqId())
         ->content($payment_method->getName() ?? '');
    }
    echo H::closeTag('select');
} else {
    echo H::openTag('select', [
        'name' => 'payment_method_2',
        'id' => 'payment_method',
        'class' => $fc,
        'disabled' => true
    ]);
     echo new Option()
      ->value('0')
      ->content(H::encode($translator->translate('none')));
    echo H::closeTag('select');
}
        echo H::closeTag('div');
// Show originating quote button if invoice was created from a quote
if (null !== $inv->getQuoteId()) {
    echo H::openTag('div', ['class' => $invProp]);
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
            . ' #' . (string) $inv->getQuoteId();
      echo H::closeTag('a');
     echo H::closeTag('div');
    echo H::closeTag('div');
}
if (($inv->reqStatusId() !== 1) && ($invEdit)) {
    echo H::openTag('div', ['class' => $invProp]);
     echo H::openTag('label', ['for' => 'inv_password']);
      echo H::openTag('b');
       echo H::encode($translator->translate('password'));
      echo H::closeTag('b');
     echo H::closeTag('label');
     echo H::tag('input', '', [
         'type' => 'text',
         'id' => 'inv_password',
         'class' => $fc,
         'disabled' => true,
         'value' => H::encode($form->getPassword() ?? '')
     ]);
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => $invProp]);
     echo H::openTag('div', ['class' => 'mb-3']);
      echo H::openTag('label', ['for' => 'guest-url']);
       echo H::openTag('b');
        echo H::encode($translator->translate('guest.url'));
       echo H::closeTag('b');
      echo H::closeTag('label');
      echo H::openTag('div');
       echo H::tag('input', '', [
           'type' => 'text',
           'id' => 'guest-url',
           'name' => 'guest-url',
           'disabled' => true,
           'readonly' => true,
           'class' => $fc,
           'value' => 'inv/url_key/' . $inv->getUrlKey()
       ]);
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
$statusId = $inv->reqStatusId();
if (isset($statusImages[$statusId])) {
    $statusInfo = $statusImages[$statusId];
    echo H::tag('img', '', [
        'src' => $statusInfo[0],
        'alt' => $translator->translate($statusInfo[1])
    ]);
}
    echo H::closeTag('div');
if (null !== $inv->getSoId()) {
    echo H::openTag('div');
     echo $translator->translate('salesorder');
    echo H::closeTag('div');
    echo H::openTag('div');
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
            'class' => $fc,
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
   echo H::openTag('div', ['class' => $col]);
    echo H::openTag('div', ['class' => 'card m-0']);
     echo H::openTag('div', ['class' => 'card-header']);
      echo H::openTag('b');
       echo H::encode($translator->translate('terms'));
        $paymentTermArray = $s->getPaymentTermArray($translator);
        $termsKey = (int) $inv->getTerms() ?: 0;
        $terms = (string) $paymentTermArray[$termsKey];
      echo H::closeTag('b');
     echo H::closeTag('div');
     echo H::openTag('div', ['class' => 'card-body']);
      echo H::openTag('textarea', [
          'name' => 'terms',
          'id' => 'terms',
          'rows' => '3',
          'disabled' => true,
          'class' => 'form-control form-control-sm'
      ]);
       echo H::encode($terms);
      echo H::closeTag('textarea');
     echo H::closeTag('div');
    echo H::closeTag('div');

    echo H::openTag('div', ['class' => 'col-12 d-block d-sm-none']);
     echo '<br>';
    echo H::closeTag('div');

   echo H::closeTag('div');
   echo H::openTag('div', ['id' => 'view_custom_fields',
       'class' => $col]);
    echo $view_custom_fields;
   echo H::closeTag('div');
   echo H::openTag('div', ['id' => 'view_partial_inv_delivery_location',
       'class' => $col]);
    echo $partial_inv_delivery_location;
   echo H::closeTag('div');
   echo H::openTag('div', ['id' => 'view_partial_inv_attachments']);
    echo $partial_inv_attachments;
   echo H::closeTag('div');
   echo $modal_add_allowance_charge;
  echo H::closeTag('div');
