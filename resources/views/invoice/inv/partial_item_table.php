<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\A;

/**
 * @var App\Infrastructure\Persistence\Inv\Inv $inv
 * @var App\Infrastructure\Persistence\InvAmount\InvAmount $invAmount
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository $aciiR
 * @var App\Invoice\InvItemAmount\InvItemAmountRepository $invItemAmountR
 * @var App\Invoice\ProductImage\ProductImageRepository $piR
 * @var Yiisoft\Session\SessionInterface $session
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var array $dlAcis
 * @var array $invItems
 * @var array $invTaxRates
 * @var array $packHandleShipTotal
 * @var array $products
 * @var array $tasks
 * @var array $taxRates
 * @var array $units
 * @var bool $draft
 * @var bool $showButtons
 * @var bool $userCanEdit
 * @var string $csrf
 * @var string $included
 * @var string $excluded
 */

$vat = $s->getSetting('enable_vat_registration');
$formControlAmount = 'form-control text-end';
echo H::openTag('div', ['class' => 'table-responsive']);
 echo H::openTag('table', [
     'id' => 'item_table',
     'class' => 'items table table-bordered m-0'
 ]);
  echo H::openTag('thead');
   echo H::openTag('tr');
    echo H::openTag('i', [
        'class' => 'bi bi-info-circle',
        'data-bs-toggle' => 'tooltip',
        'title' => $s->isDebugMode(7)
    ]);
    echo H::closeTag('i');
   echo H::closeTag('tr');
   echo H::openTag('tr');
    echo H::openTag('th');
    echo H::closeTag('th');
    echo H::openTag('th');
    echo H::closeTag('th');
    echo H::openTag('th');
    echo H::closeTag('th');
    echo H::openTag('th');
    echo H::closeTag('th');
    echo H::openTag('th');
    echo H::closeTag('th');
    echo H::openTag('th');
    echo H::closeTag('th');
    echo H::openTag('th');
    echo H::closeTag('th');
   echo H::closeTag('tr');
  echo H::closeTag('thead');

//*********
// Current
// ********
$count = 1;
/**
 * @var App\Infrastructure\Persistence\InvItem\InvItem $item
 */
foreach ($invItems as $item) {
    $productId = $item->getProductId();
    $taskId = $item->getTaskId();
    $productRef = '';
    $taskRef = '';
    if ($productId !== null) {
        $productRef =  new A()
            ->href($urlGenerator->generate('product/view',
                [
                    '_language' => (string) $session->get('_language'),
                    'id' => $productId
                ]
            ))
            ->content((string) $productId)
            ->render();
    }
    if ($taskId !== null) {
        $taskRef =  new A()
            ->href($urlGenerator->generate('task/view',
                [
                    '_language' => (string) $session->get('_language'),
                    'id' => $taskId
                ]
            ))
            ->content((string) $taskId)
            ->render();
    }
    echo H::openTag('tbody', ['class' => 'item']);
     echo H::openTag('tr');
      echo H::openTag('td', [
          'class' => 'td-text',
          'style' => 'background-color: lightgreen'
      ]);
       echo H::openTag('b');
        echo H::openTag('div');
         echo (string) $count
             . '-' . $item->reqInvId() . '-'
             . (string) $item->reqId() . '-'
             . (null !== $productId ? $productRef : '')
             . (null !== $taskId ? $taskRef : '');
        echo H::closeTag('div');
       echo H::closeTag('b');
       if ($s->getSetting('enable_peppol') == '1') {
           echo H::openTag('div');
            echo H::openTag('input', [
                'type' => 'text',
                'disabled' => true,
                'placeholder' => 'Peppol PO Item Id',
                'name' => 'item_peppol_po_itemid',
                'value' => $item->getPeppolPoItemid() ?? '',
                'data-bs-toggle' => 'tooltip',
                'title' => 'inv_item->peppol_po_itemid — '
                    . 'https://docs.peppol.eu/poacc/billing/3.0/'
                    . 'syntax/ubl-invoice/cac-InvoiceLine/'
                    . 'cac-Item/cac-BuyersItemIdentification/cbc-ID/'
            ]);
            echo H::closeTag('input');
            echo H::openTag('input', [
                'type' => 'text',
                'disabled' => true,
                'placeholder' => 'Peppol PO Line Id',
                'name' => 'item_peppol_po_lineid',
                'value' => $item->getPeppolPoLineid() ?? '',
                'data-bs-toggle' => 'tooltip',
                'title' => 'inv_item->peppol_po_lineid — '
                    . 'https://docs.peppol.eu/poacc/billing/3.0/'
                    . 'syntax/ubl-invoice/cac-InvoiceLine/'
                    . 'cac-OrderLineReference/cbc-LineID/'
            ]);
            echo H::closeTag('input');
           echo H::closeTag('div');
       }
      echo H::closeTag('td');
      echo H::openTag('td', ['class' => 'td-textarea']);
       echo H::openTag('div');
        echo H::openTag('span', ['class' => 'input-group-text']);
         echo H::openTag('b');
         echo null !== $item->getProductId() ?
              $translator->translate('item') :
              $translator->translate('tasks');
         echo H::closeTag('b');
        echo H::closeTag('span');
        echo H::openTag('select', [
            'name' => 'item_name',
            'class' => 'form-control',
            'disabled' => true
        ]);
        if (null !== $item->getProductId()) {
            echo H::openTag('option', ['value' => '0']);
             echo $translator->translate('none');
            echo H::closeTag('option');
            /**
             * @var App\Infrastructure\Persistence\Product\Product $product
             */
            foreach ($products as $product) {
                $selected = ($item->getProductId() == $product->reqId());
                echo H::openTag('option', [
                    'value' => $product->reqId(),
                    'selected' => $selected ? 'selected' : null
                ]);
                 echo $product->getProductName();
                echo H::closeTag('option');
            }
        }
        if (null !== $item->getTaskId()) {
            echo H::openTag('option', ['value' => '0']);
             echo $translator->translate('none');
            echo H::closeTag('option');
            /**
             * @var App\Infrastructure\Persistence\Task\Task $task
             */
           foreach ($tasks as $task) {
                $selected = ($item->getTaskId() == $task->reqId());
                echo H::openTag('option', [
                    'value' => $task->reqId(),
                    'selected' => $selected ? 'selected' : null
                ]);
                 echo $task->getName();
                echo H::closeTag('option');
            }
        }
        echo H::closeTag('select');
       echo H::closeTag('div');
      echo H::closeTag('td');
      echo H::openTag('td', ['class' => 'td-amount td-quantity']);
       echo H::openTag('div');
        echo H::openTag('span', ['class' => 'input-group-text']);
         echo H::openTag('b');
          echo $translator->translate('quantity');
         echo H::closeTag('b');
        echo H::closeTag('span');
        echo H::openTag('input', [
            'disabled' => true,
            'type' => 'text',
            'class' => $formControlAmount,
            'name' => 'item_quantity',
            'data-bs-toggle' => 'tooltip',
            'title' => 'inv_item->quantity',
            'value' => $numberHelper->formatAmount($item->getQuantity())
        ]);
        echo H::closeTag('input');
       echo H::closeTag('div');
      echo H::closeTag('td');
      echo H::closeTag('td');
      echo H::openTag('td', ['class' => 'td-amount']);
       echo H::openTag('div');
        echo H::openTag('span', ['class' => 'input-group-text']);
         echo H::openTag('b');
          echo $translator->translate('price');
         echo H::closeTag('b');
        echo H::closeTag('span');
        echo H::openTag('input', [
            'disabled' => true,
            'type' => 'text',
            'class' => $formControlAmount,
            'name' => 'item_price',
            'data-bs-toggle' => 'tooltip',
            'title' => 'inv_item->price',
            'value' => $numberHelper->formatAmount($item->getPrice())
        ]);
        echo H::closeTag('input');
       echo H::closeTag('div');
      echo H::closeTag('td');
      echo H::openTag('td', ['class' => 'td-amount']);
       echo H::openTag('div');
        echo H::openTag('span', ['class' => 'input-group-text']);
         echo H::openTag('b');
          echo $vat === '0' ? $translator->translate('item.discount') :
              $translator->translate('cash.discount');
         echo H::closeTag('b');
        echo H::closeTag('span');
        echo H::openTag('input', [
            'disabled' => true,
            'type' => 'text',
            'class' => $formControlAmount,
            'name' => 'item_discount_amount',
            'data-bs-toggle' => 'tooltip',
            'title' => $s->getSetting('currency_symbol') . ' ' .
                $translator->translate('per.item'),
            'data-placement' => 'bottom',
            'value' => $numberHelper->formatAmount($item->getDiscountAmount())
        ]);
        echo H::closeTag('input');
       echo H::closeTag('div');
      echo H::closeTag('td');

      echo H::openTag('td');
       echo H::openTag('div');
        echo H::openTag('span', ['class' => 'input-group-text']);
         echo H::openTag('b');
          echo $vat === '0' ?
              $translator->translate('tax.rate') :
              $translator->translate('vat.rate');
         echo H::closeTag('b');
        echo H::closeTag('span');
        echo H::openTag('select', [
            'disabled' => true,
            'name' => 'item_tax_rate_id',
            'class' => 'form-control',
            'data-bs-toggle' => 'tooltip',
            'title' => 'inv_item->tax_rate_id'
        ]);
         echo H::openTag('option', ['value' => '0']);
          echo $translator->translate('none');
         echo H::closeTag('option');
         /**
          * @var App\Infrastructure\Persistence\TaxRate\TaxRate $taxRate
          */
         foreach ($taxRates as $taxRate) {
             $selected = ($item->reqTaxRateId() == $taxRate->reqId());
             echo H::openTag('option', [
                 'value' => $taxRate->reqId(),
                 'selected' => $selected ? 'selected' : null
             ]);
              $taxRatePercent = $numberHelper->formatAmount(
                  $taxRate->getTaxRatePercent());
              $taxRateName = $taxRate->getTaxRateName();
              if ($taxRatePercent >= 0.00
                  && null !== $taxRatePercent
                  && null !== $taxRateName) {
                  echo $taxRatePercent . '% - ' . $taxRateName;
              }
             echo H::closeTag('option');
         }
        echo H::closeTag('select');
       echo H::closeTag('div');
      echo H::closeTag('td');
// Buttons for line item start here
      echo H::openTag('td', ['class' => 'td-vert-middle btn-group']);
       if ($showButtons === true && $userCanEdit === true
           && $draft === true
// Do not show the buttons if the invoice is linked to a sales order
           && null === $inv->getSoId()) {
           if ($piR->repoCount((int) $item->getProductId()) > 0) {
               echo H::openTag('span', [
                   'data-bs-toggle' => 'tooltip',
                   'title' => $translator->translate('productimage.gallery')
                       . (null !== ($item->getProductId())
                           ? ($item->getProduct()?->getProductName()
                               ?? '') : ($item->getTask()?->getName()
                                   ?? ''))
               ]);
                echo H::openTag('a', [
                    'class' => 'btn btn-info',
                    'data-bs-toggle' => 'modal',
                    'href' => '#view-product-' . (string) $item->reqId(),
                    'style' => 'text-decoration:none'
                ]);
                 echo H::openTag('i', ['class' => 'bi bi-eye']);
                 echo H::closeTag('i');
                echo H::closeTag('a');
               echo H::closeTag('span');
               echo H::openTag('div', [
                   'id' => 'view-product-' . (string) $item->reqId(),
                   'class' => 'modal modal-lg',
                   'tabindex' => '-1'
               ]);
                echo H::openTag('div', ['class' => 'modal-dialog']);
                 echo H::openTag('div', ['class' => 'modal-content']);
                  echo H::openTag('div', ['class' => 'modal-header']);
                   echo H::openTag('button', [
                       'type' => 'button',
                       'class' => 'btn-close',
                       'data-bs-dismiss' => 'modal',
                       'aria-label' => 'Close'
                   ]);
                   echo H::closeTag('button');
                  echo H::closeTag('div');
                  echo H::openTag('div', ['class' => 'modal-body']);
                   echo H::openTag('form');
                    echo H::openTag('div', ['class' => 'mb-3']);
                     echo H::openTag('input', [
                         'type' => 'hidden',
                         'name' => '_csrf',
                         'value' => $csrf
                     ]);
                     echo H::closeTag('input');
                     $productImages = $piR->repoProductImageProductquery(
                         (int) $item->getProductId());
                     /**
                      * @var App\Infrastructure\Persistence\ProductImage\ProductImage $productImage
                      */
                     foreach ($productImages as $productImage) {
                         if (!empty($productImage->getFileNameOriginal())) {
                             echo H::openTag('a', [
                                 'data-bs-toggle' => 'modal',
                                 'class' => 'col-sm-4'
                             ]);
                              echo H::openTag('img', [
                                  'src' => '/products/' .
                                      $productImage->getFileNameOriginal(),
                                  'class' => 'img-fluid',
                                  'alt' => 'Original File Name'
                              ]);
                              echo H::closeTag('img');
                             echo H::closeTag('a');
                         }
                     }
                    echo H::closeTag('div');
                   echo H::closeTag('form');
                  echo H::closeTag('div');
                  echo H::openTag('div', ['class' => 'modal-footer']);
                   echo H::openTag('button', [
                       'type' => 'button',
                       'class' => 'btn btn-secondary',
                       'data-bs-dismiss' => 'modal'
                   ]);
                    echo $translator->translate('cancel');
                   echo H::closeTag('button');
                  echo H::closeTag('div');
                 echo H::closeTag('div');
                echo H::closeTag('div');
               echo H::closeTag('div');
           }
           // Make sure to fill the third parameter of
           // generate in order to use query parameters
           if ($s->getSetting('enable_peppol') == '1') {
               echo H::openTag('a', [
                   'href' => $urlGenerator->generate('invitemallowancecharge/index', [
                       'inv_item_id' => $item->reqId(),
                       '_language' => $currentRoute->getArgument('_language')],
                       ['inv_item_id' => $item->reqId()]),
                   'class' => 'btn btn-primary btn',
                   'data-bs-toggle' => 'tooltip',
                   'title' => $translator->translate('allowance.or.charge.index')
               ]);
                echo H::openTag('i', [
                    'class' => ($aciiR->repoInvItemCount(
                        $item->reqId()) > 0 ?
                            'bi bi-list' : 'bi bi-plus-lg')
                ]);
                echo H::closeTag('i');
               echo H::closeTag('a');
           }
           echo H::openTag('a', [
               'href' => $urlGenerator->generate('inv/deleteInvItem', [
                   'id' => $item->reqId(),
                   '_language' => $currentRoute->getArgument('_language')]),
               'class' => 'btn btn-secondary btn',
               'onclick' => "return confirm('" .
                   $translator->translate('delete.record.warning') . "');"
           ]);
            echo '❌';
           echo H::closeTag('a');
           if (null !== $item->getTaskId()) {
               echo H::openTag('a', [
                   'href' => $urlGenerator->generate('invitem/editTask', [
                       'id' => $item->reqId(),
                       '_language' => $currentRoute->getArgument('_language')]),
                   'class' => 'btn btn-success btn'
               ]);
                echo '🖉';
               echo H::closeTag('a');
           }
           if (null !== $item->getProductId()) {
               echo H::openTag('a', [
                   'href' => $urlGenerator->generate('invitem/editProduct', [
                       'id' => $item->reqId(),
                       '_language' => $currentRoute->getArgument('_language')]),
                   'class' => 'btn btn-success btn'
               ]);
                echo '🖉';
               echo H::closeTag('a');
           }
       }
      echo H::closeTag('td');
     echo H::closeTag('tr');
// Buttons for line item end here
     echo H::openTag('tr');
      echo H::openTag('td');
       echo H::openTag('div');
        echo H::openTag('span', [
            'class' => 'input-group-text',
            'data-bs-toggle' => 'tooltip',
            'title' => 'inv_item->note'
        ]);
         echo H::openTag('b');
          echo $translator->translate('note');
         echo H::closeTag('b');
        echo H::closeTag('span');
        echo H::openTag('textarea', [
            'disabled' => true,
            'name' => 'item_note'
        ]);
         echo H::encode($item->getNote());
        echo H::closeTag('textarea');
       echo H::closeTag('div');
      echo H::closeTag('td');
      echo H::openTag('td');
       echo H::openTag('div');
        echo H::openTag('span', [
            'class' => 'input-group-text',
            'data-bs-toggle' => 'tooltip',
            'title' => 'inv_item->description'
        ]);
         echo H::openTag('b');
          echo $translator->translate('description');
         echo H::closeTag('b');
        echo H::closeTag('span');
        echo H::openTag('textarea', [
            'disabled' => true,
            'name' => 'item_description',
        ]);
         echo H::encode($item->getDescription());
        echo H::closeTag('textarea');
       echo H::closeTag('div');
      echo H::closeTag('td');
      echo H::openTag('td', ['class' => 'td-amount']);
       echo H::openTag('div');
        if (null !== $item->getProductId()) {
            echo H::openTag('span', ['class' => 'input-group-text']);
             echo H::openTag('b');
              echo $translator->translate('product.unit');
             echo H::closeTag('b');
            echo H::closeTag('span');
            echo H::openTag('span', [
                'class' => 'input-group-text',
                'name' => 'item_product_unit'
            ]);
             echo $item->getProductUnit();
            echo H::closeTag('span');
        }
        if (null !== $item->getTaskId()) {
            echo H::openTag('span', ['class' => 'input-group-text']);
             echo H::openTag('b');
              echo $item->getTask()?->getName();
             echo H::closeTag('b');
            echo H::closeTag('span');
            echo H::openTag('span', [
                'class' => 'input-group-text',
                'name' => 'item_task_unit'
            ]);
             echo !is_string(
                 $finishDate =
                     $item->getTask()?->getFinishDate()) ?
                         $finishDate?->format('Y-m-d') : '';
            echo H::closeTag('span');
        }
       echo H::closeTag('div');
      echo H::closeTag('td');
      echo H::openTag('td', ['class' => 'td-amount']);
        if ($item->getProductId() > 0) {
            echo H::openTag('b');
             echo $numberHelper->formatAmount(($item->getQuantity() ?? 0.00)
                                               * ($item->getPrice() ?? 0.00));
            echo H::closeTag('b');
        }
      echo H::closeTag('td');
      echo H::openTag('td', ['class' => 'td-amount']);
      echo H::closeTag('td');
      echo H::openTag('td', ['class' => 'td-amount']);
       echo H::openTag('b');
        echo $numberHelper->formatAmount(($item->getQuantity() ?? 0.00)
                                          * ($item->getPrice() ?? 0.00)
                                          * ($item->getTaxRate()?->getTaxRatePercent()
                                          ?? 0.00) / 100);
       echo H::closeTag('b');
      echo H::closeTag('td');
      echo H::openTag('td', ['class' => 'td-amount']);
      echo H::closeTag('td');
     echo H::closeTag('tr');
    if ($s->getSetting('enable_peppol') == '1') {
/**
 * Used if Peppol is enabled in order to generate electronic
 * invoices
 * @var App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge $invItemAllowanceCharge
 */
        foreach ($aciiR->repoInvItemquery($item->reqId())
            as $invItemAllowanceCharge) {
            $isCharge =
                ($invItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == 1 ?
                    true : false);
             echo H::openTag('tr');
              echo H::openTag('td', ['class' => 'td-amount']);
               echo H::openTag('b');
                echo $invItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == '1'
                    ? $translator->translate('allowance.or.charge.charge')
                    : '(' . $translator->translate('allowance.or.charge.allowance')
                        . ')';
               echo H::closeTag('b');
              echo H::closeTag('td');
              echo H::openTag('td', ['class' => 'td-amount']);
               echo H::openTag('b');
                echo $translator->translate('allowance.or.charge.reason.code') . ': ' .
                    ($invItemAllowanceCharge->getAllowanceCharge()?->getReasonCode() ?? '#');
               echo H::closeTag('b');
              echo H::closeTag('td');
              echo H::openTag('td', ['class' => 'td-amount']);
               echo H::openTag('b');
                echo $translator->translate('allowance.or.charge.reason') . ': '
                    . ($invItemAllowanceCharge->getAllowanceCharge()?->getReason() ?? '#');
               echo H::closeTag('b');
              echo H::closeTag('td');
              echo H::openTag('td', ['class' => 'td-amount']);
               echo H::openTag('b');
                echo ($isCharge ? '' : '(') . $numberHelper->formatCurrency(
                    $invItemAllowanceCharge->getAmount()) . ($isCharge ? '' : ')');
               echo H::closeTag('b');
              echo H::closeTag('td');
              echo H::openTag('td', ['class' => 'td-amount']);
              echo H::closeTag('td');
              echo H::openTag('td', ['class' => 'td-amount']);
               echo H::openTag('b');
                echo ($isCharge ? '' : '(') . $numberHelper->formatCurrency(
                    $invItemAllowanceCharge->getVatOrTax()) . ($isCharge ? '' : ')');
               echo H::closeTag('b');
              echo H::closeTag('td');
              echo H::openTag('td', ['class' => 'td-amount']);
              echo H::closeTag('td');
             echo H::closeTag('tr');
        }
    }
     echo H::openTag('tr');
      echo H::openTag('td', ['class' => 'td-amount']);
      echo H::closeTag('td');
      echo H::openTag('td', ['class' => 'td-amount']);
      echo H::closeTag('td');
      echo H::openTag('td', ['class' => 'td-amount']);
      echo H::closeTag('td');
      $tdAmountVerticle = 'td-amount td-vert-middle';
      echo H::openTag('td', [
          'class' => $tdAmountVerticle,
          'style' => 'background-color: lightblue'
      ]);
       echo H::openTag('span');
        echo H::openTag('b');
         echo $translator->translate('subtotal');
        echo H::closeTag('b');
       echo H::closeTag('span');
       echo H::openTag('br');
       echo H::closeTag('br');
       echo H::openTag('span', [
           'name' => 'subtotal',
           'class' => 'text-end',
           'data-bs-toggle' => 'tooltip',
           'title' =>
'inv_item_amount->subtotal using InvItemController/edit_product->saveInvItemAmount'
       ]);
        echo "\n";
        echo '    <!-- This subtotal is worked out in' . "\n";
        echo '        InvItemController/edit_product->saveInvItemAmount function -->' . "\n";
        echo $numberHelper->formatCurrency($invItemAmountR->repoInvItemAmountquery(
            $item->reqId())?->getSubtotal());
       echo H::closeTag('span');
      echo H::closeTag('td');
      echo H::openTag('td', ['class' => $tdAmountVerticle]);
       echo H::openTag('span');
        echo H::openTag('b');
         echo '(' . ($vat === '0' ? $translator->translate('discount') :
             $translator->translate('early.settlement.cash.discount')) . ')';
        echo H::closeTag('b');
       echo H::closeTag('span');
       echo H::openTag('br');
       echo H::closeTag('br');
       echo H::openTag('span', [
           'name' => 'item_discount_total',
           'class' => 'text-end',
           'data-bs-toggle' => 'tooltip',
           'title' => 'inv_item_amount->discount'
       ]);
        echo '(' . $numberHelper->formatCurrency(
            $invItemAmountR->repoInvItemAmountquery(
                $item->reqId())?->getDiscount()) . ')';
       echo H::closeTag('span');
      echo H::closeTag('td');
      echo H::openTag('td', [
          'class' => $tdAmountVerticle,
          'style' => 'background-color: lightpink'
      ]);
       echo H::openTag('span');
        echo H::openTag('b');
         echo $vat === '0' ? $translator->translate('tax') :
             $translator->translate('vat.abbreviation');
        echo H::closeTag('b');
       echo H::closeTag('span');
       echo H::openTag('br');
       echo H::closeTag('br');
       echo H::openTag('span', [
           'name' => 'item_tax_total',
           'class' => 'text-end',
           'data-bs-toggle' => 'tooltip',
           'title' => 'inv_item_amount->tax_total'
       ]);
        echo $numberHelper->formatCurrency(
            $invItemAmountR->repoInvItemAmountquery(
                $item->reqId())?->getTaxTotal());
       echo H::closeTag('span');
      echo H::closeTag('td');
      echo H::openTag('td', [
          'class' => 'td-amount td-vert-middle',
          'style' => 'background-color: lightyellow'
      ]);
       echo H::openTag('span');
        echo H::openTag('b');
         echo $translator->translate('total');
        echo H::closeTag('b');
       echo H::closeTag('span');
       echo H::openTag('br');
       echo H::closeTag('br');
       echo H::openTag('span', [
           'name' => 'item_total',
           'class' => 'text-end',
           'data-bs-toggle' => 'tooltip',
           'title' => 'inv_item_amount->total'
       ]);
        echo $numberHelper->formatCurrency(
            $invItemAmountR->repoInvItemAmountquery(
                $item->reqId())?->getTotal());
       echo H::closeTag('span');
      echo H::closeTag('td');
     echo H::closeTag('tr');
    echo H::closeTag('tbody');
    $count = $count + 1;
}
/**************************/
/* Invoice items end here */
/**************************/
   echo H::closeTag('table');
  echo H::closeTag('div');
  echo H::openTag('br');
  echo H::closeTag('br');
/***********************/
/*   Totals start here */
/***********************/
  echo H::openTag('div', ['class' => 'row']);
   $invTaxRates;
   echo H::openTag('div', [
       'class' => 'col-12 col-md-4',
       'inv_tax_rates' => ''
   ]);
   echo H::closeTag('div');
   echo H::openTag('div', ['class' => 'col-12 d-block d-sm-none visible-sm']);
    echo H::openTag('br');
    echo H::closeTag('br');
   echo H::closeTag('div');
   echo H::openTag('div', [
       'class' => 'col-12 col-md-6 offset-md-2 col-lg-4 offset-lg-4'
   ]);
    echo H::openTag('table', ['class' => 'table table-bordered text-end']);
     echo H::openTag('thead');
      echo H::openTag('tr', ['hidden' => true]);
       echo H::openTag('th');
        echo '<!-- description header of invoice totals -->';
       echo H::closeTag('th');
       echo H::openTag('th');
        echo '<!-- currency header of invoice totals -->';
       echo H::closeTag('th');
      echo H::closeTag('tr');
     echo H::closeTag('thead');
     echo H::openTag('tbody');
      echo H::openTag('tr');
       echo H::openTag('i', [
           'class' => 'bi bi-info-circle',
           'data-bs-toggle' => 'tooltip',
           'title' => $s->isDebugMode(7)
       ]);
       echo H::closeTag('i');
      echo H::closeTag('tr');
      echo H::openTag('tr');
       echo H::openTag('td', ['style' => 'width: 40%;']);
        echo H::openTag('b');
         echo $translator->translate('subtotal');
        echo H::closeTag('b');
       echo H::closeTag('td');
       echo H::openTag('td', [
           'style' => 'width: 60%;background-color: lightblue',
           'class' => 'text-end',
           'id' => 'amount_subtotal',
           'data-bs-toggle' => 'tooltip',
           'title' => 'inv_amount->item_subtotal=inv_item(s)->
                            subtotal - inv_item(s)->discount + inv_item(s)->charge'
       ]);
        echo $numberHelper->formatCurrency($invAmount->getItemSubtotal() ?: 0.00);
       echo H::closeTag('td');
      echo H::closeTag('tr');
      echo H::openTag('tr');
       echo H::openTag('td');
        echo H::openTag('span');
         echo H::openTag('b');
          echo $vat == '1' ? $translator->translate('vat.break.down') :
              $translator->translate('item.tax');
         echo H::closeTag('b');
        echo H::closeTag('span');
       echo H::closeTag('td');
       echo H::openTag('td', [
           'class' => 'text-end',
           'style' => 'background-color: lightpink',
           'data-bs-toggle' => 'tooltip',
           'id' => 'amount_item_tax_total',
           'title' => 'inv_amount->item_tax_total'
       ]);
        echo $numberHelper->formatCurrency($invAmount->getItemTaxTotal() ?: 0.00);
       echo H::closeTag('td');
      echo H::closeTag('tr');
      echo H::openTag('tr');
       echo H::openTag('td');
        echo H::openTag('b');
         echo $translator->translate('allowance.or.charge.shipping.handling.packaging');
        echo H::closeTag('b');
       echo H::closeTag('td');
       echo H::openTag('td', [
           'class' => 'text-end',
           'id' => 'amount_inv_allowance_charge_total',
           'data-bs-toggle' => 'tooltip',
           'title' => 'inv_amount->packhandleship_total'
       ]);
        echo H::openTag('b');
         echo $numberHelper->formatCurrency(
             $packHandleShipTotal['totalAmount'] ?? 0.00);
        echo H::closeTag('b');
       echo H::closeTag('td');
      echo H::closeTag('tr');
      echo H::openTag('tr');
       echo H::openTag('td');
        echo H::openTag('b');
         echo  new A()->content(
             $vat == '1' ? $translator->translate(
                 'allowance.or.charge.shipping.handling.packaging.vat') :
                 $translator->translate(
                     'allowance.or.charge.shipping.handling.packaging.tax')
         )->href(
             $urlGenerator->generate('invallowancecharge/index', [], [
                 'filterInvNumber' => $inv->getNumber()]));
        echo H::closeTag('b');
       echo H::closeTag('td');
       echo H::openTag('td', [
           'class' => 'text-end',
           'id' => 'amount_inv_allowance_charge_tax',
           'data-bs-toggle' => 'tooltip',
           'title' => 'inv_amount->packhandleship_tax'
       ]);
        echo H::openTag('b');
         echo $numberHelper->formatCurrency(
             $packHandleShipTotal['totalTax'] ?? 0.00);
        echo H::closeTag('b');
       echo H::closeTag('td');
      echo H::closeTag('tr');
      if ($vat === '0') {
          echo H::openTag('tr');
           echo H::openTag('td');
            echo H::openTag('b');
             if ($showButtons === true
                && $userCanEdit === true
                && null === $inv->getQuoteId()
                && null === $inv->getSoId()
                && $draft === true) {
                 echo H::openTag('a', [
                     'href' => '#add-inv-tax',
                     'data-bs-toggle' => 'modal',
                     'class' => 'btn-xs',
                     'style' => 'text-decoration:none'
                 ]);
                  echo '➕';
                 echo H::closeTag('a');
             }
             echo $translator->translate('tax');
            echo H::closeTag('b');
           echo H::closeTag('td');
           echo H::openTag('td');
            if ($invTaxRates) {
                /**
                 * @var App\Infrastructure\Persistence\InvTaxRate\InvTaxRate $invTaxRate
                 */
                foreach ($invTaxRates as $invTaxRate) {
                    echo H::openTag('div', [
                        'data-bs-toggle' => 'tooltip',
                        'title' => $invTaxRate->getIncludeItemTax()
                            == '1' ? $included : $excluded,
                        'tabindex' => '0'
                    ]);
                     echo H::openTag('input', [
                         'type' => 'hidden',
                         'name' => '_csrf',
                         'value' => $csrf
                     ]);
                     echo H::closeTag('input');
                     if ($showButtons === true
                             && $userCanEdit === true
                             && null === $inv->getQuoteId()
                             && null === $inv->getSoId()
                             && $draft === true) {
                         echo H::openTag('span');
                          echo  new A()
                              ->addAttributes([
                                  'class' => 'btn btn-secondary',
                                  'style' => 'text-decoration:none',
                                  'data-bs-toggle' => 'tooltip',
                                  'title' => $translator->translate('delete'),
                              ])
                              ->content('❌')
                              ->href($urlGenerator->generate('inv/deleteInvTaxRate', [
                                  '_language' => $currentRoute->getArgument('_language'),
                                  'id'        => $invTaxRate->reqId()
                              ]));
                         echo H::closeTag('span');
                     }
                     echo H::openTag('span', ['class' => 'text-muted']);
                      $taxRatePercent = $invTaxRate->getTaxRate()?->getTaxRatePercent();
                      $numberPercent = $numberHelper->formatAmount($taxRatePercent);
                      $taxRateName = $invTaxRate->getTaxRate()?->getTaxRateName();
                      if ($taxRatePercent >= 0.00
                              && null !== $taxRateName
                              && $numberPercent >= 0.00
                          && null !== $numberPercent) {
                          echo H::encode(' ' . $taxRateName
                                  . ' '
                                  . $numberPercent
                                  . ' ');
                      }
                     echo H::closeTag('span');
                     echo H::openTag('span', [
                         'class' => 'text-end',
                         'data-bs-toggle' => 'tooltip',
                         'title' => 'inv_tax_rate->inv_tax_rate_amount'
                     ]);
                      echo $numberHelper->formatCurrency($invTaxRate->getInvTaxRateAmount());
                     echo H::closeTag('span');
                     echo H::openTag('br');
                     echo H::closeTag('br');
                    echo H::closeTag('div');
                }
            } else {
                echo $numberHelper->formatCurrency('0');
            }
           echo H::closeTag('td');
          echo H::closeTag('tr');
      }
      if (($inv->getDiscountAmount() ?? 0.00) != 0.00) {
          echo H::openTag('tr');
           echo H::openTag('td', ['class' => 'td-vert-middle']);
            echo H::openTag('b');
             echo '(' . $translator->translate('discount') . ')';
            echo H::closeTag('b');
           echo H::closeTag('td');
           echo H::openTag('td', ['class' => 'clearfix']);
            echo H::openTag('div', ['class' => 'discount-field']);
             echo H::openTag('div', ['class' => 'input-group input-group']);
              echo $numberHelper->formatCurrency($inv->getDiscountAmount() ?? 0.00);
             echo H::closeTag('div');
            echo H::closeTag('div');
           echo H::closeTag('td');
          echo H::closeTag('tr');
      }
      echo H::openTag('tr');
       echo H::openTag('td');
        echo H::openTag('b');
         echo $translator->translate('total');
        echo H::closeTag('b');
       echo H::closeTag('td');
       echo H::openTag('td', [
           'class' => 'text-end',
           'style' => 'background-color:lightyellow',
           'id' => 'amount_inv_total',
           'data-bs-toggle' => 'tooltip',
           'title' => 'inv_amount->total'
       ]);
        echo H::openTag('b');
         echo $numberHelper->formatCurrency($invAmount->getTotal() ?? 0.00);
        echo H::closeTag('b');
       echo H::closeTag('td');
      echo H::closeTag('tr');
     echo H::closeTag('tbody');
    echo H::closeTag('table');
   echo H::closeTag('div');
  echo H::closeTag('div');
  echo H::openTag('hr');
  echo H::closeTag('hr');
