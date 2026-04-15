<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Input;
use Yiisoft\Html\Tag\Option;
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\CountryHelper;

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
 * @var array $customFields
 * @var array $customValues
 * @var array $salesOrderCustomValues
 * @var string $alert
 * @var string $csrf
 * @var string $invNumber
 * @var string $quoteNumber
 * @var string $modal_salesorder_to_pdf
 * @var string $modal_so_to_invoice
 * @var string $partial_item_table
 * @var string $partial_quote_delivery_location
 * @var string $view_custom_fields
 * @var string $title
 * @var bool $invEdit
 * @var bool $invView
 */

$this->setTitle($translator->translate('salesorder'));

$vat           = $s->getSetting('enable_vat_registration');
$clienthelper  = new ClientHelper($s);
$countryhelper = new CountryHelper();

echo $modal_salesorder_to_pdf;
echo $modal_so_to_invoice;

echo H::openTag('div', ['class' => 'panel panel-default']); //0
 echo H::openTag('div', ['class' => 'panel-heading']); //1
  echo H::encode($this->getTitle());
 echo H::closeTag('div'); //1
 echo H::tag('br', '');
 echo H::tag('br', '');
 echo H::tag('input', '', [
  'type' => 'hidden',
  'id' => '_csrf',
  'name' => '_csrf',
  'value' => $csrf,
 ]);
 echo H::openTag('div', ['id' => 'headerbar']); //1
  echo H::openTag('h1', ['class' => 'headerbar-title']); //2
   echo $translator->translate('salesorder');
   $soNumber = $so->getNumber();
   echo null !== $soNumber ? ' #' . $soNumber : $so->getId();
  echo H::closeTag('h1'); //2
  echo H::tag('br', '');
  echo H::openTag('div', ['class' => 'headerbar-item pull-left btn-group']); //2
   echo H::openTag('div', ['class' => 'dropdown']); //3
    echo H::openTag('button', [
     'class' => 'btn btn-primary dropdown-toggle',
     'type' => 'button',
     'data-bs-toggle' => 'dropdown',
     'aria-expanded' => 'false',
    ]); //4
     echo $translator->translate('options');
    echo H::closeTag('button'); //4
    echo H::openTag('ul', ['class' => 'dropdown-menu dropdown-menu']); //4
     if ($invEdit) {
      echo H::openTag('li'); //5
       echo H::openTag('a', [
        'href' => $urlGenerator->generate('salesorder/edit', ['id' => $so->getId()]),
        'style' => 'text-decoration:none',
       ]); //6
        echo H::openTag('i', ['class' => 'bi-pencil-square']); //7
        echo H::closeTag('i'); //7
        echo ' ' . $translator->translate('edit');
       echo H::closeTag('a'); //6
      echo H::closeTag('li'); //5
     }
     echo H::openTag('li'); //5
      echo H::openTag('a', [
       'href' => $urlGenerator->generate('salesorder/pdf', ['include' => 1]),
       'target' => '_blank',
       'style' => 'text-decoration:none',
      ]); //6
       echo H::openTag('i', ['class' => 'fa bi-file-pdf']); //7
       echo H::closeTag('i'); //7
       echo ' ' . H::encode(
        $translator->translate('download.pdf') . ': '
        . $translator->translate('custom.fields') . '✅'
       );
      echo H::closeTag('a'); //6
     echo H::closeTag('li'); //5
     echo H::openTag('li'); //5
      echo H::openTag('a', [
       'href' => $urlGenerator->generate('salesorder/pdf', ['include' => 0]),
       'target' => '_blank',
       'style' => 'text-decoration:none',
      ]); //6
       echo H::openTag('i', ['class' => 'fa bi-file-pdf']); //7
       echo H::closeTag('i'); //7
       echo ' ' . H::encode(
        $translator->translate('download.pdf') . ': '
        . $translator->translate('custom.fields') . '❌'
       );
      echo H::closeTag('a'); //6
     echo H::closeTag('li'); //5
     // only show SO→Invoice button if status is 6 (invoice generate stage) and no invoice yet
     if (null === $so->getInvId() && !in_array($so->getStatusId(), [1,2,3,4,5])) {
      if ($invEdit) {
       echo H::openTag('li'); //5
        echo H::openTag('a', [
         'href' => '#so-to-invoice',
         'data-bs-toggle' => 'modal',
         'style' => 'text-decoration:none',
        ]); //6
         echo H::openTag('i', ['class' => 'bi bi-arrow-clockwise']); //7
         echo H::closeTag('i'); //7
         echo ' ' . $translator->translate('salesorder.to.invoice');
        echo H::closeTag('a'); //6
       echo H::closeTag('li'); //5
      }
     }
    echo H::closeTag('ul'); //4
   echo H::closeTag('div'); //3
  echo H::closeTag('div'); //2
 echo H::closeTag('div'); //1

 echo H::openTag('div', ['id' => 'content']); //1
  echo $alert;
  echo H::openTag('div', ['id' => 'salesorder_form']); //2
   echo H::openTag('div', ['class' => 'salesorder']); //3
    echo H::openTag('div', ['class' => 'row']); //4
     echo H::openTag('div', ['class' => 'col-xs-12 col-sm-6 col-md-5']); //5
      echo H::openTag('h3'); //6
       echo H::openTag('a', ['href' => $urlGenerator->generate('client/view',
        ['id' => $so->getClient()?->reqClientId()])]); //7
        echo H::encode($clienthelper->formatClient($so->getClient()));
       echo H::closeTag('a'); //7
      echo H::closeTag('h3'); //6
      echo H::tag('br', '');
      echo H::openTag('div', [
       'id' => 'pre_save_client_id',
       'value' => $so->getClient()?->reqClientId(),
       'hidden' => true,
      ]); //6
      echo H::closeTag('div'); //6
      echo H::openTag('div', ['class' => 'client-address']); //6
       echo H::openTag('span', ['class' => 'client-address-street-line-1']); //7
        if (null !== $so->getClient()?->getClientAddress1()) {
         echo H::encode($so->getClient()?->getClientAddress1()) . H::tag('br', '');
        }
       echo H::closeTag('span'); //7
       echo H::openTag('span', ['class' => 'client-address-street-line-2']); //7
        if (null !== $so->getClient()?->getClientAddress2()) {
         echo H::encode($so->getClient()?->getClientAddress2()) . H::tag('br', '');
        }
       echo H::closeTag('span'); //7
       echo H::openTag('span', ['class' => 'client-address-town-line']); //7
        if (null !== $so->getClient()?->getClientCity()) {
         echo H::encode($so->getClient()?->getClientCity()) . H::tag('br', '');
        }
        if (null !== $so->getClient()?->getClientState()) {
         echo H::encode($so->getClient()?->getClientState()) . H::tag('br', '');
        }
        if (null !== $so->getClient()?->getClientZip()) {
         echo H::encode($so->getClient()?->getClientZip());
        }
       echo H::closeTag('span'); //7
       echo H::openTag('span', ['class' => 'client-address-country-line']); //7
        $soCountry = $so->getClient()?->getClientCountry();
        if (null !== $soCountry) {
         echo H::tag('br', '')
          . $countryhelper->getCountryName($translator->translate('cldr'), $soCountry);
        }
       echo H::closeTag('span'); //7
      echo H::closeTag('div'); //6
      echo H::tag('hr', '');
      if (null !== $so->getClient()?->getClientPhone()) {
       echo H::openTag('div', ['class' => 'client-phone']); //6
        echo H::encode($translator->translate('phone')) . ":\u{00A0}"
         . H::encode($so->getClient()?->getClientPhone());
       echo H::closeTag('div'); //6
      }
      if (null !== $so->getClient()?->getClientMobile()) {
       echo H::openTag('div', ['class' => 'client-mobile']); //6
        echo H::encode($translator->translate('mobile')) . ":\u{00A0}"
         . H::encode($so->getClient()?->getClientMobile());
       echo H::closeTag('div'); //6
      }
      if (null !== $so->getClient()?->getClientEmail()) {
       echo H::openTag('div', ['class' => 'client-email']); //6
        echo H::encode($translator->translate('email')) . ":\u{00A0}"
         . H::encode($so->getClient()?->getClientEmail());
       echo H::closeTag('div'); //6
      }
      echo H::tag('br', '');
     echo H::closeTag('div'); //5

     echo H::openTag('div', ['class' => 'col-xs-12 visible-xs']); //5
      echo H::tag('br', '');
     echo H::closeTag('div'); //5

     echo H::openTag('div', ['class' => 'col-xs-12 col-sm-6 col-md-7']); //5
      echo H::openTag('div', ['class' => 'details-box']); //6
       echo H::openTag('div', ['class' => 'row']); //7
        echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //8
         echo H::openTag('div'); //9
          echo H::openTag('label', ['for' => 'salesorder_number']); //10
           echo $translator->translate('salesorder') . ' #';
          echo H::closeTag('label'); //10
          echo H::tag('input', '', [
           'type' => 'text',
           'id' => 'salesorder_number',
           'class' => 'form-control form-control-lg',
           'readonly' => true,
           'value' => null !== $so->getNumber() ? $so->getNumber() : null,
           'placeholder' => null === $so->getNumber() ? $translator->translate('not.set') : null,
          ]);
         echo H::closeTag('div'); //9
         echo H::openTag('div'); //9
          echo H::openTag('label', ['for' => 'salesorder_date_created']); //10
           echo $vat == '0'
            ? $translator->translate('date.issued')
            : $translator->translate('salesorder.date.created');
          echo H::closeTag('label'); //10
          echo H::openTag('div', ['class' => 'input-group']); //10
           echo H::tag('input', '', [
            'name' => 'salesorder_date_created',
            'id' => 'salesorder_date_created',
            'disabled' => true,
            'class' => 'form-control form-control-lg',
            'value' => H::encode(
             $so->getDateCreated() instanceof \DateTimeImmutable
              ? $so->getDateCreated()->format('Y-m-d')
              : (is_string($so->getDateCreated()) ? $so->getDateCreated() : '')
            ),
           ]);
           echo H::openTag('span', ['class' => 'input-group-text']); //11
            echo H::openTag('i', ['class' => 'bi bi-calendar']); //12
            echo H::closeTag('i'); //12
           echo H::closeTag('span'); //11
          echo H::closeTag('div'); //10
         echo H::closeTag('div'); //9
         if ($quoteNumber) {
          echo H::openTag('div'); //9
           echo H::openTag('label', ['for' => 'salesorder_to_quote']); //10
            echo $translator->translate('salesorder.quote');
           echo H::closeTag('label'); //10
           echo H::openTag('div', ['class' => 'input-group']); //10
            echo H::a(
             $quoteNumber,
             $urlGenerator->generate('quote/view', ['id' => $so->getQuoteId()]),
             ['class' => 'btn btn-info']
            );
           echo H::closeTag('div'); //10
          echo H::closeTag('div'); //9
         }
         if ($invNumber) {
          echo H::openTag('div'); //9
           echo H::openTag('label', ['for' => 'salesorder_to_url']); //10
            echo $translator->translate('salesorder.invoice');
           echo H::closeTag('label'); //10
           echo H::openTag('div', ['class' => 'input-group']); //10
            echo H::a(
             $invNumber,
             $urlGenerator->generate('inv/view', ['id' => $so->getInvId()]),
             ['class' => 'btn btn-success']
            );
           echo H::closeTag('div'); //10
          echo H::closeTag('div'); //9
         }
         echo H::openTag('div'); //9
          /**
           * @var App\Invoice\Entity\CustomField $customField
           */
          foreach ($customFields as $customField) {
           if ($customField->getLocation() !== 1) {
            continue;
           }
           $cvH->printFieldForView($customField, $form, $salesOrderCustomValues);
          }
         echo H::closeTag('div'); //9
        echo H::closeTag('div'); //8

        echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //8
         echo H::openTag('div'); //9
          echo H::openTag('label', ['for' => 'status_id']); //10
           echo $translator->translate('status');
          echo H::closeTag('label'); //10
          echo H::openTag('select', [
           'name' => 'status_id',
           'id' => 'status_id',
           'disabled' => true,
           'class' => 'form-control form-control-lg',
          ]); //10
           /**
            * @var string $key
            * @var array $status
            * @var string $status['label']
            */
           foreach ($soStatuses as $key => $status) {
            echo new Option()
             ->value($key)
             ->selected($key == $so->getStatusId())
             ->content(H::encode($status['label']));
           }
          echo H::closeTag('select'); //10
         echo H::closeTag('div'); //9
         echo H::openTag('div'); //9
          echo H::openTag('label', ['for' => 'salesorder_password', 'hidden' => true]); //10
           echo $translator->translate('salesorder.password');
          echo H::closeTag('label'); //10
          echo H::tag('input', '', [
           'type' => 'text',
           'id' => 'salesorder_password',
           'class' => 'form-control form-control-lg',
           'disabled' => true,
           'value' => H::encode($so->getPassword() ?? ''),
           'hidden' => true,
          ]);
         echo H::closeTag('div'); //9
         echo H::openTag('div'); //9
          echo H::openTag('label', ['for' => 'salesorder_client_purchase_order_number']); //10
           echo $translator->translate('salesorder.clients.purchase.order.number');
          echo H::closeTag('label'); //10
          echo H::tag('input', '', [
           'type' => 'text',
           'id' => 'salesorder_client_purchase_order_number',
           'class' => 'form-control form-control-lg',
           'disabled' => true,
           'value' => H::encode($so->getClientPoNumber() ?? ''),
          ]);
         echo H::closeTag('div'); //9
         echo H::openTag('div'); //9
          echo H::openTag('label', ['for' => 'salesorder_client_purchase_order_person']); //10
           echo $translator->translate('salesorder.clients.purchase.order.person');
          echo H::closeTag('label'); //10
          echo H::tag('input', '', [
           'type' => 'text',
           'id' => 'salesorder_client_purchase_order_person',
           'class' => 'form-control form-control-lg',
           'disabled' => true,
           'value' => H::encode($so->getClientPoPerson() ?? ''),
          ]);
         echo H::closeTag('div'); //9
         // 2 => Terms Agreement Required, 8 => Rejected
         if (in_array($so->getStatusId(), [2, 8]) && !$invEdit) {
          echo H::openTag('div'); //9
           echo H::tag('br', '');
           echo H::a(
            H::encode(
             $translator->translate('salesorder.agree.to.terms')
             . '/' . $translator->translate('salesorder.reject')
            ),
            $urlGenerator->generate('salesorder/urlKey', ['key' => $so->getUrlKey()]),
            ['class' => 'btn btn-success']
           );
          echo H::closeTag('div'); //9
         }
         echo H::tag('input', '', [
          'type' => 'text',
          'id' => 'dropzone_client_id',
          'readonly' => true,
          'hidden' => true,
          'class' => 'form-control form-control-lg',
          'value' => $so->getClient()?->reqClientId(),
         ]);
        echo H::closeTag('div'); //8
       echo H::closeTag('div'); //7
      echo H::closeTag('div'); //6
     echo H::closeTag('div'); //5
    echo H::closeTag('div'); //4
   echo H::closeTag('div'); //3
  echo H::closeTag('div'); //2

  echo H::openTag('div', ['id' => 'partial_item_table_parameters', 'disabled' => true]); //2
   echo $partial_item_table;
  echo H::closeTag('div'); //2

  echo H::openTag('div', ['class' => 'row']); //2
   echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //3
    echo H::openTag('div', ['class' => 'panel panel-default no-margin']); //4
     echo H::openTag('div', ['class' => 'panel-heading']); //5
      echo $translator->translate('notes');
     echo H::closeTag('div'); //5
     echo H::openTag('div', ['class' => 'panel-body']); //5
      echo H::openTag('textarea', [
       'name' => 'notes',
       'id' => 'notes',
       'rows' => '3',
       'disabled' => true,
       'class' => 'input-sm form-control',
      ]); //6
       echo H::encode($so->getNotes() ?? '');
      echo H::closeTag('textarea'); //6
     echo H::closeTag('div'); //5
    echo H::closeTag('div'); //4
    echo H::tag('br', '');
    echo H::openTag('div', ['class' => 'col-xs-12 visible-xs visible-sm']); //4
     echo H::tag('br', '');
    echo H::closeTag('div'); //4
   echo H::closeTag('div'); //3
   echo H::openTag('div', ['id' => 'view_partial_inv_delivery_location', 'class' => 'col-xs-12 col-md-6']); //3
    echo $partial_quote_delivery_location;
   echo H::closeTag('div'); //3
   echo H::openTag('div', ['id' => 'view_custom_fields', 'class' => 'col-xs-12 col-md-6']); //3
    echo $view_custom_fields;
   echo H::closeTag('div'); //3
  echo H::closeTag('div'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0
