<?php

declare(strict_types=1);

use App\Invoice\ClientCustom\ClientCustomForm;
use App\Invoice\Entity\ClientCustom;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Input;

/**
 * @var App\Invoice\ClientPeppol\ClientPeppolRepository $cpR
 * @var App\Invoice\ClientCustom\ClientCustomForm $clientCustomForm
 * @var App\Infrastructure\Persistence\Client\Client $client
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $clientCustomValues
 * @var array $customValues
 * @var array $custom_fields
 * @var string $alert
 * @var string $partial_client_address
 * @var string $client_modal_layout_inv
 * @var string $client_modal_layout_quote
 * @var string $delivery_locations
 * @var string $quote_table
 * @var string $quote_draft_table
 * @var string $quote_sent_table
 * @var string $quote_viewed_table
 * @var string $quote_approved_table
 * @var string $quote_rejected_table
 * @var string $quote_cancelled_table
 * @var string $invoice_table
 * @var string $invoice_draft_table
 * @var string $invoice_sent_table
 * @var string $invoice_viewed_table
 * @var string $invoice_paid_table
 * @var string $invoice_overdue_table
 * @var string $invoice_unpaid_table
 * @var string $invoice_reminder_sent_table
 * @var string $invoice_seven_day_table
 * @var string $invoice_legal_claim_table
 * @var string $invoice_judgement_table
 * @var string $invoice_officer_table
 * @var string $invoice_credit_table
 * @var string $invoice_written_off_table
 * @var string $payment_table
 * @var string $partial_notes
 * @var string $title
 */

$clientId = (string) $client->reqId();

echo H::tag('h1', H::encode($title));

echo H::openTag('div', ['id' => 'headerbar']); //0
 echo H::tag('h1', H::encode($clientHelper->formatClient($client)), ['class' => 'headerbar-title']);
 echo H::openTag('div', ['class' => 'headerbar-item pull-right']); //1
  echo H::openTag('div', ['class' => 'btn-group btn-group-sm']); //2
   echo (new A())
    ->content(H::tag('i', '', ['class' => 'bi bi-file-earmark-text']) . $translator->translate('create.quote'))
    ->href('#modal-add-quote')
    ->encode(false)
    ->addAttributes(['class' => 'btn btn-outline-success', 'data-bs-toggle' => 'modal', 'style' => 'text-decoration:none'])
    ->render();
   echo (new A())
    ->content(H::tag('i', '', ['class' => 'bi bi-file-earmark-text']) . $translator->translate('create.invoice'))
    ->href('#modal-add-inv')
    ->encode(false)
    ->addAttributes(['class' => 'btn btn-outline-success', 'data-bs-toggle' => 'modal', 'style' => 'text-decoration:none'])
    ->render();
   if ($cpR->repoClientCount($clientId) === 0 && strlen($clientId) > 0) {
    echo (new A())
     ->content(H::tag('i', '', ['class' => 'bi bi-plus']) . ' ' . $translator->translate('client.peppol.add'))
     ->href($urlGenerator->generate('clientpeppol/add', ['_language' => 'en', 'client_id' => $client->reqId()]))
     ->encode(false)
     ->addAttributes(['class' => 'btn btn-outline-info', 'style' => 'text-decoration:none'])
     ->render();
   }
   if ($cpR->repoClientCount($clientId) > 0 && strlen($clientId) > 0) {
    echo (new A())
     ->content(H::tag('i', '', ['class' => 'bi bi-pencil-square']) . ' ' . $translator->translate('client.peppol.edit'))
     ->href($urlGenerator->generate('clientpeppol/edit', ['client_id' => $client->reqId()]))
     ->encode(false)
     ->addAttributes(['class' => 'btn btn-outline-warning', 'style' => 'text-decoration:none'])
     ->render();
   }
   $clientIdEdit = $client->reqId();
   echo (new A())
    ->content(H::tag('i', '', ['class' => 'bi bi-pencil-square']) . $translator->translate('edit'))
    ->href($urlGenerator->generate('client/edit', ['id' => $clientIdEdit, 'origin' => 'edit']))
    ->encode(false)
    ->addAttributes(['class' => 'btn btn-outline-warning', 'style' => 'text-decoration:none'])
    ->render();
   $clientIdPostalAdd = $client->reqId();
   echo (new A())
    ->content(H::tag('i', '', ['class' => 'bi bi-plus']) . $translator->translate('client.postaladdress.add'))
    ->href($urlGenerator->generate(
       'postaladdress/add',
       ['client_id' => $clientIdPostalAdd],
       [
        /**
         * Related logic: see Yiisoft\Router\UrlGeneratorInterface function generate $queryParameters
         * Purpose: Use origin and origin_id to generate return url to client view after user has
         * created the new postal address for the client
         * e.g  {origin}/view, ['client_id' => {origin_id}],
         */
        'origin' => 'client',
        'origin_id' => $clientIdPostalAdd,
        'action' => 'add',
       ]
      )
    )
    ->encode(false)
    ->addAttributes(['class' => 'btn btn-outline-primary', 'style' => 'text-decoration:none'])
    ->render();
   $clientIdDelAdd = $client->reqId();
   echo (new A())
    ->content(H::tag('i', '', ['class' => 'bi bi-plus']) . $translator->translate('delivery.location.add'))
    ->href($urlGenerator->generate(
       'del/add',
       ['client_id' => $clientIdDelAdd],
       ['origin' => 'client', 'origin_id' => $clientIdDelAdd, 'action' => 'view']
      )
    )
    ->encode(false)
    ->addAttributes(['class' => 'btn btn-outline-success', 'style' => 'text-decoration:none'])
    ->render();
   echo (new A())
    ->content(H::tag('i', '', ['class' => 'bi bi-trash']) . ' ' . $translator->translate('delete'))
    ->href($urlGenerator->generate('client/delete', ['id' => $client->reqId()]))
    ->encode(false)
    ->addAttributes([
     'class' => 'btn btn-outline-danger',
     'onclick' => 'return confirm(' . H::encode("'" . $translator->translate('delete.client.warning') . "'") . ')',
     'style' => 'text-decoration:none',
    ])
    ->render();
  echo H::closeTag('div'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0

// Nav tabs
echo H::openTag('ul', ['id' => 'submenu', 'class' => 'nav nav-tabs nav-tabs-noborder']); //0
$tabs = [
 ['id' => 'client-details-tab',           'target' => '#clientDetails',              'extra' => ' active',                   'label' => 'details'],
 ['id' => 'client-quotes-tab',            'target' => '#clientQuotes',               'extra' => ' bg-success bg-opacity-25', 'label' => 'quotes'],
 ['id' => 'client-quotes-draft-tab',      'target' => '#clientQuotesDraft',          'extra' => '',                          'label' => 'draft'],
 ['id' => 'client-quotes-sent-tab',       'target' => '#clientQuotesSent',           'extra' => '',                          'label' => 'sent'],
 ['id' => 'client-quotes-viewed-tab',     'target' => '#clientQuotesViewed',         'extra' => '',                          'label' => 'viewed'],
 ['id' => 'client-quotes-approved-tab',   'target' => '#clientQuotesApproved',       'extra' => '',                          'label' => 'approved'],
 ['id' => 'client-quotes-cancelled-tab',  'target' => '#clientQuotesCancelled',      'extra' => '',                          'label' => 'canceled'],
 ['id' => 'client-quotes-rejected-tab',   'target' => '#clientQuotesRejected',       'extra' => '',                          'label' => 'rejected'],
 ['id' => 'client-invoices-tab',          'target' => '#clientInvoices',             'extra' => ' bg-danger bg-opacity-25',  'label' => 'invoices'],
 ['id' => 'client-invoices-draft-tab',    'target' => '#clientInvoicesDraft',        'extra' => '',                          'label' => 'draft'],
 ['id' => 'client-invoices-sent-tab',     'target' => '#clientInvoicesSent',         'extra' => '',                          'label' => 'sent'],
 ['id' => 'client-invoices-viewed-tab',   'target' => '#clientInvoicesViewed',       'extra' => '',                          'label' => 'viewed'],
 ['id' => 'client-invoices-paid-tab',     'target' => '#clientInvoicesPaid',         'extra' => '',                          'label' => 'paid'],
 ['id' => 'client-invoices-overdue-tab',  'target' => '#clientInvoicesOverdue',      'extra' => '',                          'label' => 'overdue'],
 ['id' => 'client-invoices-unpaid-tab',   'target' => '#clientInvoicesUnpaid',       'extra' => '',                          'label' => 'unpaid'],
 ['id' => 'client-invoices-reminder-tab', 'target' => '#clientInvoicesReminderSent', 'extra' => '',                          'label' => 'reminder'],
 ['id' => 'client-invoices-seven-day-tab','target' => '#clientInvoicesSevenDay',     'extra' => '',                          'label' => 'letter'],
 ['id' => 'client-invoices-legal-claim-tab','target' => '#clientInvoicesLegalClaim', 'extra' => '',                          'label' => 'claim'],
 ['id' => 'client-invoices-judgement-tab','target' => '#clientInvoicesJudgement',    'extra' => '',                          'label' => 'judgement'],
 ['id' => 'client-invoices-officer-tab',  'target' => '#clientInvoicesOfficer',      'extra' => '',                          'label' => 'enforcement'],
 ['id' => 'client-invoices-credit-tab',   'target' => '#clientInvoicesCredit',       'extra' => '',                          'label' => 'credit.invoice.for.invoice'],
 ['id' => 'client-invoices-written-off-tab','target' => '#clientInvoicesWrittenOff', 'extra' => '',                          'label' => 'loss'],
 ['id' => 'client-payments-tab',          'target' => '#clientPayments',             'extra' => ' bg-info bg-opacity-25',    'label' => 'payments'],
];
/**
 * @var array{id:string,target:string,extra:string,label:string} $tab
 */
foreach ($tabs as $tab) {
 echo H::openTag('li', ['class' => 'nav-item', 'role' => 'presentation']); //1
  echo H::tag('button', $translator->translate($tab['label']), [
   'class' => 'nav-link' . $tab['extra'],
   'id' => $tab['id'],
   'data-bs-toggle' => 'tab',
   'data-bs-target' => $tab['target'],
   'style' => 'text-decoration:none',
  ]);
 echo H::closeTag('li'); //1
}
echo H::closeTag('ul'); //0

// Tab content
echo H::openTag('div', ['id' => 'content', 'class' => 'tabbable tabs-below no-padding']); //0
 echo H::openTag('div', ['class' => 'tab-content no-padding']); //1

  // Details tab
  echo H::openTag('div', ['id' => 'clientDetails', 'class' => 'tab-pane tab-rich-content active']); //2
   echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';
   echo H::openTag('div', ['class' => 'row']); //3
    echo H::openTag('div', ['class' => 'col-xs-12 col-sm-6 col-md-6']); //4
     echo H::tag('h3', H::encode($clientHelper->formatClient($client)));
     echo H::openTag('p'); //5
      echo $partial_client_address;
     echo H::closeTag('p'); //5
     echo H::openTag('p'); //5
      echo H::openTag('table', ['class' => 'table table-bordered no-margin']); //6
       $i = 1;
       /**
        * @var App\Invoice\Entity\CustomField $custom_field
        */
       foreach ($custom_fields as $custom_field) {
        if ($custom_field->getLocation() != 1) {
         continue;
        }
        $column = $custom_field->getLabel();
        $value  = $cvH->formValue($clientCustomValues, $custom_field->getId());
        echo H::openTag('tr'); //7
         echo H::tag('th', H::encode($column), ['id' => 'cf-col' . $i]);
         echo H::tag('td', H::encode($value), ['id' => 'cf-val' . $i]);
        echo H::closeTag('tr'); //7
        $i++;
       }
      echo H::closeTag('table'); //6
     echo H::closeTag('p'); //5
    echo H::closeTag('div'); //4
    echo H::openTag('div', ['class' => 'col-xs-12 col-sm-6 col-md-6']); //4
     echo H::openTag('table', ['class' => 'table table-bordered no-margin']); //5
      echo H::openTag('tr'); //6
       echo H::tag('th', $translator->translate('language'), ['id' => 'language']);
       echo H::tag('td', ucfirst($client->getClientLanguage() ?? ''), ['class' => 'td-amount']);
      echo H::closeTag('tr'); //6
      echo H::openTag('tr'); //6
       echo H::tag('th', $translator->translate('total.billed'), ['id' => 'total-billed']);
       $clientIdTotal = $client->reqId();
       echo H::tag('td', $s->formatCurrency($iR->withTotal($clientIdTotal, $iaR)),
        ['class' => 'td-amount']
       );
      echo H::closeTag('tr'); //6
      echo H::openTag('tr'); //6
       echo H::tag('th', $translator->translate('total.paid'), ['id' => 'total-paid']);
       $clientIdPaid = $client->reqId();
       echo H::tag('td', $s->formatCurrency($iR->withTotalPaid($clientIdPaid, $iaR)),
        ['class' => 'td-amount']
       );
      echo H::closeTag('tr'); //6
      echo H::openTag('tr'); //6
       echo H::tag('th', $translator->translate('total.balance'), ['id' => 'total-balance']);
       $clientIdBalance = $client->reqId();
       echo H::tag('td', $s->formatCurrency($iR->withTotalBalance($clientIdBalance, $iaR)),
        ['class' => 'td-amount']
       );
      echo H::closeTag('tr'); //6
     echo H::closeTag('table'); //5
    echo H::closeTag('div'); //4
   echo H::closeTag('div'); //3

   echo H::tag('hr', '');

   echo H::openTag('div', ['class' => 'row']); //3
    echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //4
     echo H::openTag('div', ['class' => 'panel panel-default no-margin']); //5
      echo H::tag('div', $translator->translate('delivery.location.client'), ['class' => 'panel-heading']);
      echo H::openTag('div', ['class' => 'panel-body table-content']); //6
       echo $delivery_locations;
      echo H::closeTag('div'); //6
     echo H::closeTag('div'); //5
    echo H::closeTag('div'); //4
   echo H::closeTag('div'); //3

   echo H::tag('hr', '');

   echo H::openTag('div', ['class' => 'row']); //3
    echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //4
     echo H::openTag('div', ['class' => 'panel panel-default no-margin']); //5
      echo H::tag('div', $translator->translate('contact.information'), ['class' => 'panel-heading']);
      echo H::openTag('div', ['class' => 'panel-body table-content']); //6
       echo H::openTag('table', ['class' => 'table no-margin']); //7
        if ($client->getClientEmail()) {
         echo H::openTag('tr'); //8
          echo H::tag('th', $translator->translate('email'));
          echo H::tag('td', H::mailto($client->getClientEmail()));
         echo H::closeTag('tr'); //8
        }
        if (strlen($client->getClientPhone() ?? '') > 0) {
         echo H::openTag('tr'); //8
          echo H::tag('th', $translator->translate('phone'));
          echo H::tag('td', H::encode($client->getClientPhone()));
         echo H::closeTag('tr'); //8
        }
        if (strlen($client->getClientMobile() ?? '') > 0) {
         echo H::openTag('tr'); //8
          echo H::tag('th', $translator->translate('mobile'));
          echo H::tag('td', H::encode($client->getClientMobile()));
         echo H::closeTag('tr'); //8
        }
        if (strlen($client->getClientFax() ?? '') > 0) {
         echo H::openTag('tr'); //8
          echo H::tag('th', $translator->translate('fax'));
          echo H::tag('td', H::encode($client->getClientFax()));
         echo H::closeTag('tr'); //8
        }
        if (strlen($client->getClientWeb() ?? '') > 0) {
         $clientWeb = $client->getClientWeb() ?? 'https://no_web_page.com';
         echo H::openTag('tr'); //8
          echo H::tag('th', $translator->translate('web'));
          echo H::tag('td',
           new A()->content($clientWeb)->href($clientWeb)->addAttributes(['target' => '_blank'])->render()
          );
         echo H::closeTag('tr'); //8
        }
        /**
         * @var App\Invoice\Entity\CustomField $custom_field
         */
        foreach ($custom_fields as $custom_field) {
         if ($custom_field->getLocation() != 2) {
          continue;
         }
         $column = $custom_field->getLabel();
         $value  = $cvH->formValue($clientCustomValues, $custom_field->getId());
         echo H::openTag('tr'); //8
          echo H::tag('th', H::encode($column));
          echo H::tag('td', H::encode($value));
         echo H::closeTag('tr'); //8
        }
       echo H::closeTag('table'); //7
      echo H::closeTag('div'); //6
     echo H::closeTag('div'); //5
    echo H::closeTag('div'); //4

    echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //4
     echo H::openTag('div', ['class' => 'panel panel-default no-margin']); //5
      echo H::tag('div', $translator->translate('tax.information'), ['class' => 'panel-heading']);
      echo H::openTag('div', ['class' => 'panel-body table-content']); //6
       echo H::openTag('table', ['class' => 'table no-margin']); //7
        if ($client->getClientVatId()) {
         echo H::openTag('tr'); //8
          echo H::tag('th', $translator->translate('vat.id'));
          echo H::tag('td', H::encode($client->getClientVatId()));
         echo H::closeTag('tr'); //8
        }
        $clientTaxCode = $client->getClientTaxCode() ?? '';
        if (strlen($clientTaxCode) > 0) {
         echo H::openTag('tr'); //8
          echo H::tag('th', $translator->translate('tax.code'));
          echo H::tag('td', H::encode($clientTaxCode));
         echo H::closeTag('tr'); //8
        }
        /**
         * @var App\Invoice\Entity\CustomField $custom_field
         */
        foreach ($custom_fields as $custom_field) {
         if ($custom_field->getLocation() != 4) {
          continue;
         }
         $column = $custom_field->getLabel();
         $value  = $cvH->formValue($clientCustomValues, $custom_field->getId());
         echo H::openTag('tr'); //8
          echo H::tag('th', H::encode($column));
          echo H::tag('td', H::encode($value));
         echo H::closeTag('tr'); //8
        }
       echo H::closeTag('table'); //7
      echo H::closeTag('div'); //6
     echo H::closeTag('div'); //5
    echo H::closeTag('div'); //4
   echo H::closeTag('div'); //3

   if ($client->getClientSurname() !== '') {
    echo H::tag('hr', '');
    echo H::openTag('div', ['class' => 'row']); //3
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //4
      echo H::openTag('div', ['class' => 'panel panel-default']); //5
       echo H::tag('div', $translator->translate('personal.information'), ['class' => 'panel-heading']);
       echo H::openTag('div', ['class' => 'panel-body table-content']); //6
        echo H::openTag('table', ['class' => 'table no-margin']); //7
         echo H::openTag('tr'); //8
          echo H::tag('th', $translator->translate('birthdate'));
          $clientBirthdate = $client->getClientBirthdate();
          echo H::tag('td',
           !is_string($clientBirthdate) && null !== $clientBirthdate
            ? $clientBirthdate->format('Y-m-d') : ''
          );
         echo H::closeTag('tr'); //8
         echo H::openTag('tr'); //8
          echo H::tag('th', $translator->translate('gender'));
          $clientGender = $client->getClientGender();
          echo H::tag('td', $clientHelper->formatGender($clientGender, $translator));
         echo H::closeTag('tr'); //8
         /**
          * @var App\Invoice\Entity\CustomField $custom_field
          */
         foreach ($custom_fields as $custom_field) {
          if ($custom_field->getLocation() !== 3) {
           continue;
          }
          $cvH->printFieldForView($custom_field, $clientCustomForm, $clientCustomValues);
         }
        echo H::closeTag('table'); //7
       echo H::closeTag('div'); //6
      echo H::closeTag('div'); //5
     echo H::closeTag('div'); //4
    echo H::closeTag('div'); //3
   }

   if ($custom_fields) {
    echo H::tag('hr', '');
    echo H::openTag('div', ['class' => 'row']); //3
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //4
      echo H::openTag('div', ['class' => 'panel panel-default no-margin']); //5
       echo H::tag('div', $translator->translate('custom.fields'), ['class' => 'panel-heading']);
       echo H::openTag('div', ['class' => 'panel-body table-content']); //6
        echo H::openTag('table', ['class' => 'table no-margin']); //7
         $i = 1;
         /**
          * @var App\Invoice\Entity\CustomField $custom_field
          */
         foreach ($custom_fields as $custom_field) {
          if ($custom_field->getLocation() !== 0) {
           continue;
          }
          echo H::openTag('tr'); //8
           echo H::tag('th', '', ['id' => 'client-cf-' . $i]);
           echo H::openTag('td'); //9
            $clientCustomForm = new ClientCustomForm(new ClientCustom());
            $cvH->printFieldForView($custom_field, $clientCustomForm, $clientCustomValues);
           echo H::closeTag('td'); //9
          echo H::closeTag('tr'); //8
          $i++;
         }
        echo H::closeTag('table'); //7
       echo H::closeTag('div'); //6
      echo H::closeTag('div'); //5
     echo H::closeTag('div'); //4
    echo H::closeTag('div'); //3
   }

   echo H::tag('hr', '');

   echo H::openTag('div', ['class' => 'row']); //3
    echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //4
     echo H::openTag('div', ['class' => 'panel panel-default no-margin']); //5
      echo H::tag('div', $translator->translate('notes'), ['class' => 'panel-heading']);
      echo H::openTag('div', ['class' => 'panel-body']); //6
       echo H::openTag('div', ['id' => 'notes_list']); //7
        echo $partial_notes;
       echo H::closeTag('div'); //7
       echo new Input()->type('hidden')->name('client_id')->id('client_id')->value((string) $client->reqId());
       echo H::openTag('div', ['class' => 'input-group']); //7
        echo H::openTag('textarea', [
         'id' => 'client_note',
         'class' => 'form-control form-control-lg',
         'rows' => '2',
         'style' => 'resize:none',
        ]); //8
        echo H::closeTag('textarea'); //8
        echo H::tag('span', $translator->translate('add.note'), [
         'id' => 'save_client_note_new',
         'class' => 'input-text-addon btn btn-info',
        ]);
       echo H::closeTag('div'); //7
      echo H::closeTag('div'); //6
     echo H::closeTag('div'); //5
    echo H::closeTag('div'); //4
   echo H::closeTag('div'); //3
  echo H::closeTag('div'); //2 clientDetails

  // Tab panes — quotes
  $tabPanes = [
   ['id' => 'clientQuotes',              'role' => 'client-quotes-tab',              'content' => $quote_table],
   ['id' => 'clientQuotesDraft',         'role' => 'client-quotes-draft-tab',        'content' => $quote_draft_table],
   ['id' => 'clientQuotesSent',          'role' => 'client-quotes-sent-tab',         'content' => $quote_sent_table],
   ['id' => 'clientQuotesViewed',        'role' => 'client-quotes-viewed-tab',       'content' => $quote_viewed_table],
   ['id' => 'clientQuotesApproved',      'role' => 'client-quotes-approved-tab',     'content' => $quote_approved_table],
   ['id' => 'clientQuotesCancelled',     'role' => 'client-quotes-cancelled-tab',    'content' => $quote_cancelled_table],
   ['id' => 'clientQuotesRejected',      'role' => 'client-quotes-rejected-tab',     'content' => $quote_rejected_table],
   ['id' => 'clientInvoices',            'role' => 'client-invoices-tab',            'content' => $invoice_table],
   ['id' => 'clientInvoicesDraft',       'role' => 'client-invoices-draft-tab',      'content' => $invoice_draft_table],
   ['id' => 'clientInvoicesSent',        'role' => 'client-invoices-sent-tab',       'content' => $invoice_sent_table],
   ['id' => 'clientInvoicesViewed',      'role' => 'client-invoices-viewed-tab',     'content' => $invoice_viewed_table],
   ['id' => 'clientInvoicesPaid',        'role' => 'client-invoices-paid-tab',       'content' => $invoice_paid_table],
   ['id' => 'clientInvoicesOverdue',     'role' => 'client-invoices-overdue-tab',    'content' => $invoice_overdue_table],
   ['id' => 'clientInvoicesUnpaid',      'role' => 'client-invoices-unpaid-tab',     'content' => $invoice_unpaid_table],
   ['id' => 'clientInvoicesReminderSent','role' => 'client-invoices-reminder-tab',   'content' => $invoice_reminder_sent_table],
   ['id' => 'clientInvoicesSevenDay',    'role' => 'client-invoices-seven-day-tab',  'content' => $invoice_seven_day_table],
   ['id' => 'clientInvoicesLegalClaim',  'role' => 'client-invoices-legal-claim-tab','content' => $invoice_legal_claim_table],
   ['id' => 'clientInvoicesJudgement',   'role' => 'client-invoices-judgement-tab',  'content' => $invoice_judgement_table],
   ['id' => 'clientInvoicesOfficer',     'role' => 'client-invoices-officer-tab',    'content' => $invoice_officer_table],
   ['id' => 'clientInvoicesCredit',      'role' => 'client-invoices-credit-tab',     'content' => $invoice_credit_table],
   ['id' => 'clientInvoicesWrittenOff',  'role' => 'client-invoices-written-off-tab','content' => $invoice_written_off_table],
   ['id' => 'clientPayments',            'role' => 'client-payments-tab',            'content' => $payment_table],
  ];
  /**
   * @var array{id:string,role:string,content:string} $pane
   */
  foreach ($tabPanes as $pane) {
   echo H::openTag('div', [
    'id' => $pane['id'],
    'class' => 'tab-pane table-content',
    'role' => 'tabpanel',
    'aria-labelledby' => $pane['role'],
   ]); //2
    echo $pane['content'];
   echo H::closeTag('div'); //2
  }

 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0

/**
 * Note: The quote modal is used in 3 places
 * Note: {origin} is set in QuoteController/index function ...
 *      'action' => ['quote/add', ['origin' => 'quote']],
 * Note: {origin} is set in resources/views/layout/invoice.php  ...
 *      $urlGenerator->generate('quote/add',['origin' => 'main'])],
 * Note: {origin} is set in ClientController/index function ...
 *      'action' => ['quote/add', ['origin' => $client_id]],
 * Related logic: see config/common/routes quote/add/{origin}
 * Related logic: see ClientController/view function 'client_modal_layout_quote' => [ .... ]
 * Related logic: see views\invoice\quote\modal_layout.php
 * Related logic: see views\invoice\quote\modal_add_quote_form.php contained in above file.
 * Note: 'action' is equivalent to $urlGenerator->generate('quote/add', [], ['origin' => $client->reqId() or 'quote' or 'main'])
 * Note: If origin is a client number, quote/add/{origin} route will return to url client/view/{origin}
 * Note: If origin is 'quote', quote/add/{origin} route will return to url quote/index
 * Note: If origin is 'main', quote/add/{origin} route will return to url invoice/
 */
echo $client_modal_layout_quote;
echo $client_modal_layout_inv;
