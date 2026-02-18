<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
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

echo H::openTag('div', ['class' => 'panel panel-default']);
 echo H::openTag('div', ['class' => 'panel-heading']);
  echo H::encode($this->getTitle());
 echo H::closeTag('div');
 
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

echo H::openTag('input', [
    'type' => 'hidden',
    'id' => '_csrf',
    'name' => '_csrf',
    'value' => $csrf
]);
echo H::closeTag('input');

echo H::openTag('div', ['id' => 'headerbar']);
 echo H::openTag('h1', ['class' => 'headerbar-title']);
  echo $view_quote_number;
 echo H::closeTag('h1');
 echo H::openTag('div', ['class' => 'headerbar-item pull-right']);
  echo $view_quote_vat_enabled_switch;
  echo $quoteToolbar;
 echo H::closeTag('div');
echo H::closeTag('div');

echo H::openTag('div', ['id' => 'content']);
 echo $alert;
 echo H::openTag('div', ['id' => 'quote_form']);
  echo H::openTag('div', ['class' => 'quote']);
   echo H::openTag('div', ['class' => 'row']);
    echo $view_quote_client_details;
    echo H::openTag('div', ['class' => 'col-xs-12 visible-xs']);
     echo '<br>';
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'col-xs-12 col-sm-6 col-md-7']);
     echo H::openTag('div', ['class' => 'details-box']);
      echo H::openTag('div', ['class' => 'row']);
       echo $view_details_box_with_custom_field;
       echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']);
        echo $view_quote_approve_reject;
       echo H::closeTag('div');
      echo H::closeTag('div');
     echo H::closeTag('div');
    echo H::closeTag('div');
   echo H::closeTag('div');
  echo H::closeTag('div');
 echo H::closeTag('div');
 
 echo H::openTag('div', [
     'id' => 'partial_item_table_parameters',
     'disabled' => true
 ]);
  echo $partial_item_table;
 echo H::closeTag('div');
 
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']);
   echo H::openTag('div', ['class' => 'panel panel-default no-margin']);
    echo H::openTag('div', ['class' => 'panel-heading']);
     echo $translator->translate('notes');
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'panel-body']);
     echo H::openTag('textarea', [
         'name' => 'notes',
         'id' => 'notes',
         'rows' => '3',
         'disabled' => true,
         'class' => 'input-sm form-control'
     ]);
      echo H::encode($body['notes'] ?? '');
     echo H::closeTag('textarea');
    echo H::closeTag('div');
   echo H::closeTag('div');
   echo H::openTag('div', ['class' => 'col-xs-12 visible-xs visible-sm']);
    echo '<br>';
   echo H::closeTag('div');
  echo H::closeTag('div');
  echo H::openTag('div', [
      'id' => 'view_partial_inv_delivery_location',
      'class' => 'col-xs-12 col-md-6'
  ]);
   echo $partial_quote_delivery_location;
  echo H::closeTag('div');
  echo H::openTag('div', [
      'id' => 'view_custom_fields',
      'class' => 'col-xs-12 col-md-6'
  ]);
   echo $view_custom_fields;
  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
echo H::closeTag('div');
echo $modal_add_allowance_charge;
