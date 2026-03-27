<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Button;

/**
 * Related logic:
    see ...src\Invoice\Inv\InvController function view $parameters['modal_choose_items']
 * Related logic:
    see ...\resources\views\invoice\product\_partial_product_table_modal.php
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $families
 * @var array $products
 * @var string $csrf
 * @var string $filter_product
 * @var string $default_item_tax_rate
 * @var string $partial_product_table_modal
 */

echo H::openTag('div', [
    'id' => 'modal-choose-items',
    'class' => 'modal',
    'tabindex' => '-1']); //1
 echo H::openTag('div', ['class' => 'modal-dialog']); //2
  echo H::openTag('div', ['class' => 'modal-content']); //3
   echo H::openTag('div', ['class' => 'modal-header']); //4
    echo H::openTag('button', [
        'type' => 'button',
        'class' => 'btn-close',
        'data-bs-dismiss' => 'modal',
        'aria-label' => 'Close'
    ]);
    echo H::closeTag('button');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'modal-body']); //4
    echo H::openTag('form');
     echo H::openTag('input', [
         'type' => 'hidden',
         'name' => '_csrf',
         'value' => $csrf
     ]);
     echo H::openTag('div', ['class' => 'modal-body']); //5
      echo H::openTag('div', ['class' => 'form-group']); //6
       echo H::openTag('label', ['for' => 'filter_family_inv']);
        echo H::encode($translator->translate('any.family'));
       echo H::closeTag('label');
       echo H::openTag('div', ['class' => 'form-group']); //7
        echo H::openTag('select', [
            'name' => 'filter_family_inv',
            'id' => 'filter_family_inv',
            'class' => 'form-control'
        ]);
         echo H::openTag('option', ['value' => '0']);
          echo H::encode($translator->translate('any.family'));
         echo H::closeTag('option');
         /**
          * @var App\Invoice\Entity\Family $family
          */
         foreach ($families as $family) {
             $attributes = ['value' => $family->getFamilyId()];
             if (isset($filter_family)
                     && $family->getFamilyId() == $filter_family) {
                 $attributes['selected'] = 'selected';
             }
             echo H::openTag('option', $attributes);
              echo H::encode($family->getFamilyName() ?? '');
             echo H::closeTag('option');
         }
        echo H::closeTag('select');
       echo H::closeTag('div'); //7
       echo H::openTag('div', ['class' => 'form-group panel panel-primary']); //7
        echo H::openTag('label', ['for' => 'filter_product_inv']);
         echo H::encode($translator->translate('product.name'));
        echo H::closeTag('label');
        echo H::openTag('input', [
            'type' => 'text',
            'class' => 'form-control',
            'name' => 'filter_product_inv',
            'id' => 'filter_product_inv',
            'placeholder' => $translator->translate('product.name'),
            'value' => $filter_product
        ]);
        echo H::openTag('button', [
            'type' => 'button',
            'id' => 'filter-button-inv',
            'class' => 'btn btn-info'
        ]);
         echo H::encode($translator->translate('search.product'));
        echo H::closeTag('button');
        echo H::openTag('button', [
            'type' => 'button',
            'id' => 'product-reset-button-inv',
            'class' => 'btn btn-danger'
        ]);
         echo H::encode($translator->translate('reset'));
        echo H::closeTag('button');
       echo H::closeTag('div'); //7
      echo H::closeTag('div'); //6
      echo H::openTag('br');
      echo H::openTag('div', ['class' => 'modal-header']); 
// see src\Invoice\Asset\rebuild-1.13\js\modal-product-lookups.js line 64
// Note: The above js will pass selected products to invoice/product/selection_inv function
       echo H::openTag('button', [
           'class' => 'select-items-confirm-inv btn btn-success alignment:center',
           'type' => 'button',
           'disabled' => true
       ]);
        echo H::openTag('i', ['class' => 'fa fa-check']);
        echo H::closeTag('i');
        echo H::encode($translator->translate('submit'));
       echo H::closeTag('button');
      echo H::closeTag('div'); //6
      echo H::openTag('div', ['id' => 'product-lookup-table']); //6
       echo $partial_product_table_modal;
      echo H::closeTag('div'); //6
     echo H::closeTag('div'); //5
     echo H::openTag('div', ['class' => 'modal-footer']); //5
      echo  new Button()
          ->addClass('btn btn-danger')
          ->content($translator->translate('close'))
          ->addAttributes(['data-bs-dismiss' => 'modal'])
          ->render();
     echo H::closeTag('div'); //5
    echo H::closeTag('form');
   echo H::closeTag('div'); //4
   echo H::openTag('div', [ //4
       'id' => 'default_item_tax_rate',
       'value' => $default_item_tax_rate
   ]);
   echo H::closeTag('div');
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
