<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Option;

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Entity\QuoteAmount $quoteAmount
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository $acqiR
 * @var App\Invoice\QuoteItemAmount\QuoteItemAmountRepository $qiaR
 * @var App\Invoice\ProductImage\ProductImageRepository $piR
 * @var Yiisoft\Session\SessionInterface $session
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var array $packHandleShipTotal
 * @var array $quoteItems
 * @var array $quoteTaxRates
 * @var array $products
 * @var array $tasks
 * @var array $taxRates
 * @var array $units
 * @var bool $invEdit
 * @var string $csrf
 * @var string $included
 * @var string $excluded
 */

$vat = $s->getSetting('enable_vat_registration');

 echo H::openTag('div'); //1
  echo H::openTag('table', [
      'id' => 'item_table',
      'class' => 'items table table-responsive table-bordered no-margin',
  ]); //2
   echo H::openTag('thead'); //3
    echo H::openTag('tr'); //4
     echo H::openTag('i', [
         'class' => 'fa fa-info-circle',
         'data-bs-toggle' => 'tooltip',
         'title' => $s->isDebugMode(19),
     ]); //5
     echo H::closeTag('i'); //5
    echo H::closeTag('tr'); //4
    echo H::openTag('tr'); //4
     echo H::openTag('th'); //5
     echo H::closeTag('th'); //5
     echo H::openTag('th'); //5
     echo H::closeTag('th'); //5
     echo H::openTag('th'); //5
     echo H::closeTag('th'); //5
     echo H::openTag('th'); //5
     echo H::closeTag('th'); //5
     echo H::openTag('th'); //5
     echo H::closeTag('th'); //5
     echo H::openTag('th'); //5
     echo H::closeTag('th'); //5
     echo H::openTag('th'); //5
     echo H::closeTag('th'); //5
    echo H::closeTag('tr'); //4
   echo H::closeTag('thead'); //3

//*********
// Current
// ********
$count = 1;
/**
 * @var App\Invoice\Entity\QuoteItem $item
 */
foreach ($quoteItems as $item) {
    $productId = $item->getProduct_id();
    $taskId = $item->getTask_id();
    $productRef = '';
    $taskRef = '';
    if ($productId > 0) {
        $productRef = (new A())
            ->href($urlGenerator->generate(
                'product/view',
                [
                    '_language' => (string) $session->get('_language'),
                    'id' => $productId,
                ]
            ))
            ->content($productId)
            ->render();
    }
    if ($taskId > 0) {
        $taskRef = (new A())
            ->href($urlGenerator->generate(
                'task/view',
                [
                    '_language' => (string) $session->get('_language'),
                    'id' => $taskId,
                ]
            ))
            ->content($taskId)
            ->render();
    }
   echo H::openTag('tbody', ['class' => 'item']); //3
    echo H::openTag('tr'); //4
     echo H::openTag('td', [
         'class' => 'td-text',
         'style' => 'background-color: lightgreen',
     ]); //5
      echo H::openTag('b'); //6
       echo H::openTag('div', ['class' => 'input-group']); //7
       echo (string) $count
           . '-' . $item->getQuote_id() . '-'
           . $item->getId() . '-'
           . ($productId > 0 ? $productRef : '')
           . ($taskId > 0 ? $taskRef : '');
       echo H::closeTag('div'); //7
      echo H::closeTag('b'); //6
     echo H::closeTag('td'); //5
     echo H::openTag('td', ['class' => 'td-textarea']); //5
      echo H::openTag('div', ['class' => 'input-group']); //6
       echo H::openTag('span', ['class' => 'input-group-text']); //7
        echo H::openTag('b'); //8
        echo($productId > 0 ?
            $translator->translate('item') :
            $translator->translate('tasks'));
        echo H::closeTag('b'); //8
       echo H::closeTag('span'); //7
       echo H::openTag('select', [
           'name' => 'item_name',
           'class' => 'form-control',
           'disabled' => true,
       ]); //7
       if ($productId > 0) {
           echo (new Option())
               ->value('0')
               ->content($translator->translate('none'));
           /**
            * @var App\Invoice\Entity\Product $product
            */
           foreach ($products as $product) {
               echo (new Option())
                   ->value($product->getProduct_id())
                   ->selected($item->getProduct_id() == $product->getProduct_id())
                   ->content($product->getProduct_name() ?? '');
           }
       }
       if ($taskId > 0) {
           echo (new Option())
               ->value('0')
               ->content($translator->translate('none'));
           /**
            * @var App\Invoice\Entity\Task $task
            */
           foreach ($tasks as $task) {
               echo (new Option())
                   ->value($task->getId())
                   ->selected($item->getTask_id() == $task->getId())
                   ->content($task->getName() ?? '');
           }
       }
       echo H::closeTag('select'); //7
      echo H::closeTag('div'); //6
     echo H::closeTag('td'); //5
     echo H::openTag('td', ['class' => 'td-amount td-quantity']); //5
      echo H::openTag('div', ['class' => 'input-group']); //6
       echo H::openTag('span', ['class' => 'input-group-text']); //7
        echo H::openTag('b'); //8
        echo $translator->translate('quantity');
        echo H::closeTag('b'); //8
       echo H::closeTag('span'); //7
       echo H::openTag('input', [
           'disabled' => true,
           'type' => 'text',
           'maxlength' => '4',
           'size' => '4',
           'name' => 'item_quantity',
           'class' => 'input-sm form-control amount',
           'data-bs-toggle' => 'tooltip',
           'title' => 'quote_item->quantity',
           'value' => $numberHelper->format_amount($item->getQuantity()),
       ]);
       echo H::closeTag('input');
      echo H::closeTag('div'); //6
     echo H::closeTag('td'); //5
     echo H::openTag('td', ['class' => 'td-amount']); //5
      echo H::openTag('div', ['class' => 'input-group']); //6
       echo H::openTag('span', ['class' => 'input-group-text']); //7
        echo H::openTag('b'); //8
        echo $translator->translate('price');
        echo H::closeTag('b'); //8
       echo H::closeTag('span'); //7
       echo H::openTag('input', [
           'disabled' => true,
           'type' => 'text',
           'maxlength' => '4',
           'size' => '4',
           'name' => 'item_price',
           'class' => 'input-sm form-control amount',
           'data-bs-toggle' => 'tooltip',
           'title' => 'quote_item->price',
           'value' => $numberHelper->format_amount($item->getPrice()),
       ]);
       echo H::closeTag('input');
      echo H::closeTag('div'); //6
     echo H::closeTag('td'); //5
     echo H::openTag('td', ['class' => 'td-amount']); //5
      echo H::openTag('div', ['class' => 'input-group']); //6
       echo H::openTag('span', ['class' => 'input-group-text']); //7
        echo H::openTag('b'); //8
        echo $vat === '0' ? $translator->translate('item.discount') :
            $translator->translate('cash.discount');
        echo H::closeTag('b'); //8
       echo H::closeTag('span'); //7
       echo H::openTag('input', [
           'disabled' => true,
           'type' => 'text',
           'maxlength' => '4',
           'size' => '4',
           'name' => 'item_discount_amount',
           'class' => 'input-sm form-control amount',
           'data-bs-toggle' => 'tooltip',
           'title' => $s->getSetting('currency_symbol') . ' ' .
               $translator->translate('per.item'),
           'data-placement' => 'bottom',
           'value' => $numberHelper->format_amount($item->getDiscount_amount()),
       ]);
       echo H::closeTag('input');
      echo H::closeTag('div'); //6
     echo H::closeTag('td'); //5
     echo H::openTag('td'); //5
      echo H::openTag('div', ['class' => 'input-group']); //6
       echo H::openTag('span', ['class' => 'input-group-text']); //7
        echo H::openTag('b'); //8
        echo $vat === '0' ?
            $translator->translate('tax.rate') :
            $translator->translate('vat.rate');
        echo H::closeTag('b'); //8
       echo H::closeTag('span'); //7
       echo H::openTag('select', [
           'disabled' => true,
           'name' => 'item_tax_rate_id',
           'class' => 'form-control',
           'data-bs-toggle' => 'tooltip',
           'title' => 'quote_item->tax_rate_id',
       ]); //7
       echo (new Option())
           ->value('0')
           ->content($translator->translate('none'));
       /**
        * @var App\Invoice\Entity\TaxRate $taxRate
        */
       foreach ($taxRates as $taxRate) {
           $taxRatePercent = $numberHelper->format_amount(
               $taxRate->getTaxRatePercent()
           );
           $taxRateName = $taxRate->getTaxRateName();
           $taxRateContent = '';
           if ($taxRatePercent >= 0.00
               && null !== $taxRatePercent
               && null !== $taxRateName) {
               $taxRateContent = $taxRatePercent . '% - ' . $taxRateName;
           }
           echo (new Option())
               ->value((string) $taxRate->getTaxRateId())
               ->selected($item->getTax_rate_id() == $taxRate->getTaxRateId())
               ->content($taxRateContent);
       }
       echo H::closeTag('select'); //7
      echo H::closeTag('div'); //6
     echo H::closeTag('td'); //5
    // Buttons for line item start here
     echo H::openTag('td', ['class' => 'td-vert-middle btn-group']); //5
    if ($invEdit === true) {
        if ($piR->repoCount((int) $item->getProduct_id()) > 0) {
            echo H::openTag('span', [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('productimage.gallery')
                    . ($productId > 0
                        ? ($item->getProduct()?->getProduct_name()
                            ?? '') : ($item->getTask()?->getName()
                                ?? '')),
            ]); //6
             echo H::openTag('a', [
                 'class' => 'btn btn-info',
                 'data-bs-toggle' => 'modal',
                 'href' => '#view-product-' . $item->getId(),
                 'style' => 'text-decoration:none',
             ]); //7
              echo H::openTag('i', ['class' => 'fa fa-eye']); //8
              echo H::closeTag('i'); //8
             echo H::closeTag('a'); //7
            echo H::closeTag('span'); //6
            echo H::openTag('div', [
                'id' => 'view-product-' . $item->getId(),
                'class' => 'modal modal-lg',
                'tabindex' => '-1',
            ]); //6
             echo H::openTag('div', ['class' => 'modal-dialog']); //7
              echo H::openTag('div', ['class' => 'modal-content']); //8
               echo H::openTag('div', ['class' => 'modal-header']); //9
                echo H::openTag('button', [
                    'type' => 'button',
                    'class' => 'btn-close',
                    'data-bs-dismiss' => 'modal',
                    'aria-label' => 'Close',
                ]); //10
                echo H::closeTag('button'); //10
               echo H::closeTag('div'); //9
               echo H::openTag('div', ['class' => 'modal-body']); //9
                echo H::openTag('form'); //10
                 echo H::openTag('div', ['class' => 'form-group']); //11
                 echo H::openTag('input', [
                     'type' => 'hidden',
                     'name' => '_csrf',
                     'value' => $csrf,
                 ]);
                 echo H::closeTag('input');
                 $productImages = $piR->repoProductImageProductquery(
                     (int) $item->getProduct_id()
                 );
                 /**
                  * @var App\Invoice\Entity\ProductImage $productImage
                  */
                 foreach ($productImages as $productImage) {
                     if (!empty($productImage->getFile_name_original())) {
                         echo H::openTag('a', [
                             'data-bs-toggle' => 'modal',
                             'class' => 'col-sm-4',
                         ]); //12
                         echo H::openTag('img', [
                             'src' => '/products/' .
                                 $productImage->getFile_name_original(),
                             'class' => 'img-fluid',
                             'alt' => 'Original File Name',
                         ]);
                         echo H::closeTag('img');
                         echo H::closeTag('a'); //12
                     }
                 }
                 echo H::closeTag('div'); //11
                echo H::closeTag('form'); //10
               echo H::closeTag('div'); //9
               echo H::openTag('div', ['class' => 'modal-footer']); //9
                echo H::openTag('button', [
                    'type' => 'button',
                    'class' => 'btn btn-secondary',
                    'data-bs-dismiss' => 'modal',
                ]); //10
                echo $translator->translate('cancel');
                echo H::closeTag('button'); //10
               echo H::closeTag('div'); //9
              echo H::closeTag('div'); //8
             echo H::closeTag('div'); //7
            echo H::closeTag('div'); //6
        }
        // Make sure to fill the third parameter of
        // generate in order to use query parameters
        if ($s->getSetting('enable_peppol') === '1') {
            echo H::openTag('a', [
                'href' => $urlGenerator->generate(
                    'quoteitemallowancecharge/index',
                    [
                        'quote_item_id' => $item->getId(),
                        '_language' => $currentRoute->getArgument('_language'),
                    ],
                    ['quote_item_id' => $item->getId()]
                ),
                'class' => 'btn btn-primary btn',
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('allowance.or.charge.index'),
            ]); //6
             echo H::openTag('i', [
                 'class' => ($acqiR->repoQuoteItemCount(
                     $item->getId()
                 ) > 0 ?
                         'fa fa-list' : 'fa fa-plus'),
             ]); //7
             echo H::closeTag('i'); //7
            echo H::closeTag('a'); //6
        }
        echo H::openTag('a', [
            'href' => $urlGenerator->generate('quote/delete_quote_item', [
                'id' => $item->getId(),
                '_language' => $currentRoute->getArgument('_language'),
            ]),
            'class' => 'btn btn-secondary btn',
            'onclick' => "return confirm('" .
                $translator->translate('delete.record.warning') . "');",
        ]); //6
        echo '❌';
        echo H::closeTag('a'); //6
        if ($taskId > 0) {
            echo H::openTag('a', [
                'href' => $urlGenerator->generate('quoteitem/edit_task', [
                    'id' => $item->getId(),
                    '_language' => $currentRoute->getArgument('_language'),
                ]),
                'class' => 'btn btn-success btn',
            ]); //6
            echo '🖉';
            echo H::closeTag('a'); //6
        }
        if ($productId > 0) {
            echo H::openTag('a', [
                'href' => $urlGenerator->generate('quoteitem/edit_product', [
                    'id' => $item->getId(),
                    '_language' => $currentRoute->getArgument('_language'),
                ]),
                'class' => 'btn btn-success btn',
            ]); //6
            echo '🖉';
            echo H::closeTag('a'); //6
        }
    }
     echo H::closeTag('td'); //5
    echo H::closeTag('tr'); //4
    // Buttons for line item end here
    echo H::openTag('tr'); //4
     echo H::openTag('td'); //5
     echo H::closeTag('td'); //5
     echo H::openTag('td'); //5
      echo H::openTag('div', ['class' => 'input-group']); //6
       echo H::openTag('span', [
           'class' => 'input-group-text',
           'data-bs-toggle' => 'tooltip',
           'title' => 'quote_item->description',
       ]); //7
        echo H::openTag('b'); //8
        echo $translator->translate('description');
        echo H::closeTag('b'); //8
       echo H::closeTag('span'); //7
       echo H::openTag('textarea', [
           'disabled' => true,
           'name' => 'item_description',
           'class' => 'form-control',
           'rows' => '1',
       ]); //7
       echo H::encode($item->getDescription());
       echo H::closeTag('textarea'); //7
      echo H::closeTag('div'); //6
     echo H::closeTag('td'); //5
     echo H::openTag('td', ['class' => 'td-amount']); //5
      echo H::openTag('div', ['class' => 'input-group']); //6
      if ($productId > 0) {
          echo H::openTag('span', ['class' => 'input-group-text']); //7
           echo H::openTag('b'); //8
           echo $translator->translate('product.unit');
           echo H::closeTag('b'); //8
          echo H::closeTag('span'); //7
          echo H::openTag('span', [
              'class' => 'input-group-text',
              'name' => 'item_product_unit',
          ]); //7
          echo $item->getProduct_unit();
          echo H::closeTag('span'); //7
      }
      if ($taskId > 0) {
          echo H::openTag('span', ['class' => 'input-group-text']); //7
           echo H::openTag('b'); //8
           echo $item->getTask()?->getName();
           echo H::closeTag('b'); //8
          echo H::closeTag('span'); //7
          echo H::openTag('span', [
              'class' => 'input-group-text',
              'name' => 'item_task_unit',
          ]); //7
          echo !is_string(
              $finishDate =
                  $item->getTask()?->getFinish_date()
          ) ?
                      $finishDate?->format('Y-m-d') : '';
          echo H::closeTag('span'); //7
      }
      echo H::closeTag('div'); //6
     echo H::closeTag('td'); //5
     echo H::openTag('td', ['class' => 'td-amount']); //5
     if ($productId > 0) {
          echo H::openTag('b'); //6
          echo $numberHelper->format_amount(($item->getQuantity() ?? 0.00)
                                            * ($item->getPrice() ?? 0.00));
          echo H::closeTag('b'); //6
     }
     echo H::closeTag('td'); //5
     echo H::openTag('td', ['class' => 'td-amount']); //5
     echo H::closeTag('td'); //5
     echo H::openTag('td', ['class' => 'td-amount']); //5
      echo H::openTag('b'); //6
      echo $numberHelper->format_amount(($item->getQuantity() ?? 0.00)
                                        * ($item->getPrice() ?? 0.00)
                                        * ($item->getTaxRate()?->getTaxRatePercent()
                                        ?? 0.00) / 100);
      echo H::closeTag('b'); //6
     echo H::closeTag('td'); //5
     echo H::openTag('td', ['class' => 'td-amount']); //5
     echo H::closeTag('td'); //5
    echo H::closeTag('tr'); //4
    if ($s->getSetting('enable_peppol') === '1') {
        /**
         * Used if Peppol is enabled in order to generate electronic
         * invoices
         * @var App\Invoice\Entity\QuoteItemAllowanceCharge $quoteItemAllowanceCharge
         */
        foreach ($acqiR->repoQuoteItemquery($item->getId()) as $quoteItemAllowanceCharge) {
            $isCharge =
                ($quoteItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == 1 ?
                    true : false);
            echo H::openTag('tr'); //4
             echo H::openTag('td', ['class' => 'td-amount']); //5
              echo H::openTag('b'); //6
              echo $quoteItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == '1'
                  ? $translator->translate('allowance.or.charge.charge')
                  : '(' . $translator->translate('allowance.or.charge.allowance')
                      . ')';
              echo H::closeTag('b'); //6
             echo H::closeTag('td'); //5
             echo H::openTag('td', ['class' => 'td-amount']); //5
              echo H::openTag('b'); //6
              echo $translator->translate('allowance.or.charge.reason.code') . ': ' .
                  ($quoteItemAllowanceCharge->getAllowanceCharge()?->getReasonCode() ?? '#');
              echo H::closeTag('b'); //6
             echo H::closeTag('td'); //5
             echo H::openTag('td', ['class' => 'td-amount']); //5
              echo H::openTag('b'); //6
              echo $translator->translate('allowance.or.charge.reason') . ': '
                  . ($quoteItemAllowanceCharge->getAllowanceCharge()?->getReason() ?? '#');
              echo H::closeTag('b'); //6
             echo H::closeTag('td'); //5
             echo H::openTag('td', ['class' => 'td-amount']); //5
              echo H::openTag('b'); //6
              echo ($isCharge ? '' : '(') . $numberHelper->format_currency(
                  $quoteItemAllowanceCharge->getAmount()
              ) . ($isCharge ? '' : ')');
              echo H::closeTag('b'); //6
             echo H::closeTag('td'); //5
             echo H::openTag('td', ['class' => 'td-amount']); //5
             echo H::closeTag('td'); //5
             echo H::openTag('td', ['class' => 'td-amount']); //5
              echo H::openTag('b'); //6
              echo ($isCharge ? '' : '(') . $numberHelper->format_currency(
                  $quoteItemAllowanceCharge->getVatOrTax()
              ) . ($isCharge ? '' : ')');
              echo H::closeTag('b'); //6
             echo H::closeTag('td'); //5
             echo H::openTag('td', ['class' => 'td-amount']); //5
             echo H::closeTag('td'); //5
            echo H::closeTag('tr'); //4
        }
    }
    echo H::openTag('tr'); //4
     echo H::openTag('td', ['class' => 'td-amount']); //5
     echo H::closeTag('td'); //5
     echo H::openTag('td', ['class' => 'td-amount']); //5
     echo H::closeTag('td'); //5
     echo H::openTag('td', ['class' => 'td-amount']); //5
     echo H::closeTag('td'); //5
     echo H::openTag('td', [
         'class' => 'td-amount td-vert-middle',
         'style' => 'background-color: lightblue',
     ]); //5
      echo H::openTag('span'); //6
       echo H::openTag('b'); //7
       echo $translator->translate('subtotal');
       echo H::closeTag('b'); //7
      echo H::closeTag('span'); //6
      echo H::openTag('br');
      echo H::closeTag('br');
      echo H::openTag('span', [
          'name' => 'subtotal',
          'class' => 'amount',
          'data-bs-toggle' => 'tooltip',
          'title' =>
'quote_item_amount->subtotal using QuoteItemController/edit_product->saveQuoteItemAmount',
      ]); //6
      echo "\n";
      echo '    <!-- This subtotal is worked out in' . "\n";
      echo '        QuoteItemController/edit_product->saveQuoteItemAmount function -->' . "\n";
      echo $numberHelper->format_currency($qiaR->repoQuoteItemAmountquery(
          $item->getId()
      )?->getSubtotal());
      echo H::closeTag('span'); //6
     echo H::closeTag('td'); //5
     echo H::openTag('td', ['class' => 'td-amount td-vert-middle']); //5
      echo H::openTag('span'); //6
       echo H::openTag('b'); //7
       echo '(' . ($vat === '0' ? $translator->translate('discount') :
           $translator->translate('early.settlement.cash.discount')) . ')';
       echo H::closeTag('b'); //7
      echo H::closeTag('span'); //6
      echo H::openTag('br');
      echo H::closeTag('br');
      echo H::openTag('span', [
          'name' => 'item_discount_total',
          'class' => 'amount',
          'data-bs-toggle' => 'tooltip',
          'title' => 'quote_item_amount->discount',
      ]); //6
      echo '(' . $numberHelper->format_currency(
          $qiaR->repoQuoteItemAmountquery(
              $item->getId()
          )?->getDiscount()
      ) . ')';
      echo H::closeTag('span'); //6
     echo H::closeTag('td'); //5
     echo H::openTag('td', [
         'class' => 'td-amount td-vert-middle',
         'style' => 'background-color: lightpink',
     ]); //5
      echo H::openTag('span'); //6
       echo H::openTag('b'); //7
       echo $vat === '0' ? $translator->translate('tax') :
           $translator->translate('vat.abbreviation');
       echo H::closeTag('b'); //7
      echo H::closeTag('span'); //6
      echo H::openTag('br');
      echo H::closeTag('br');
      echo H::openTag('span', [
          'name' => 'item_tax_total',
          'class' => 'amount',
          'data-bs-toggle' => 'tooltip',
          'title' => 'quote_item_amount->tax_total',
      ]); //6
      echo $numberHelper->format_currency(
          $qiaR->repoQuoteItemAmountquery(
              $item->getId()
          )?->getTax_total()
      );
      echo H::closeTag('span'); //6
     echo H::closeTag('td'); //5
     echo H::openTag('td', [
         'class' => 'td-amount td-vert-middle',
         'style' => 'background-color: lightyellow',
     ]); //5
      echo H::openTag('span'); //6
       echo H::openTag('b'); //7
       echo $translator->translate('total');
       echo H::closeTag('b'); //7
      echo H::closeTag('span'); //6
      echo H::openTag('br');
      echo H::closeTag('br');
      echo H::openTag('span', [
          'name' => 'item_total',
          'class' => 'amount',
          'data-bs-toggle' => 'tooltip',
          'title' => 'quote_item_amount->total',
      ]); //6
      echo $numberHelper->format_currency(
          $qiaR->repoQuoteItemAmountquery(
              $item->getId()
          )?->getTotal()
      );
      echo H::closeTag('span'); //6
     echo H::closeTag('td'); //5
    echo H::closeTag('tr'); //4
   echo H::closeTag('tbody'); //3
    $count = $count + 1;
}

/* Quote items end here */

  echo H::closeTag('table'); //2
 echo H::closeTag('div'); //1
echo H::openTag('br');
echo H::closeTag('br');

/*   Totals start here */

 echo H::openTag('div', ['class' => 'row']); //1

  echo H::openTag('div', [
      'class' => 'col-xs-12 col-md-4',
      'quote_tax_rates' => '',
  ]); //2
  echo H::closeTag('div'); //2
  echo H::openTag('div', ['class' => 'col-xs-12 visible-xs visible-sm']); //2
  echo H::openTag('br');
  echo H::closeTag('br');
  echo H::closeTag('div'); //2
  echo H::openTag('div', [
      'class' => 'col-xs-12 col-md-6 col-md-offset-2 col-lg-4 col-lg-offset-4',
  ]); //2
   echo H::openTag('table', ['class' => 'table table-bordered text-right']); //3
    echo H::openTag('thead'); //4
     echo H::openTag('tr', ['hidden' => true]); //5
      echo H::openTag('th'); //6
      echo '<!-- description header of quote totals -->';
      echo H::closeTag('th'); //6
      echo H::openTag('th'); //6
      echo '<!-- currency header of quote totals -->';
      echo H::closeTag('th'); //6
     echo H::closeTag('tr'); //5
    echo H::closeTag('thead'); //4
    echo H::openTag('tbody'); //4
     echo H::openTag('tr'); //5
      echo H::openTag('i', [
          'class' => 'fa fa-info-circle',
          'data-bs-toggle' => 'tooltip',
          'title' => $s->isDebugMode(19),
      ]); //6
      echo H::closeTag('i'); //6
     echo H::closeTag('tr'); //5
     echo H::openTag('tr'); //5
      echo H::openTag('td', ['style' => 'width: 40%;']); //6
       echo H::openTag('b'); //7
       echo $translator->translate('subtotal');
       echo H::closeTag('b'); //7
      echo H::closeTag('td'); //6
      echo H::openTag('td', [
          'style' => 'width: 60%;background-color: lightblue',
          'class' => 'amount',
          'id' => 'amount_subtotal',
          'data-bs-toggle' => 'tooltip',
          'title' => 'quote_amount->item_subtotal=quote_item(s)->
                            subtotal - quote_item(s)->discount + quote_item(s)->charge',
      ]); //6
      echo $numberHelper->format_currency($quoteAmount->getItem_subtotal() ?? 0.00);
      echo H::closeTag('td'); //6
     echo H::closeTag('tr'); //5
     echo H::openTag('tr'); //5
      echo H::openTag('td'); //6
       echo H::openTag('span'); //7
        echo H::openTag('b'); //8
        echo $vat === '1' ? $translator->translate('vat.break.down') :
            $translator->translate('item.tax');
        echo H::closeTag('b'); //8
       echo H::closeTag('span'); //7
      echo H::closeTag('td'); //6
      echo H::openTag('td', [
          'class' => 'amount',
          'style' => 'background-color: lightpink',
          'data-bs-toggle' => 'tooltip',
          'id' => 'amount_item_tax_total',
          'title' => 'quote_amount->item_tax_total',
      ]); //6
      echo $numberHelper->format_currency($quoteAmount->getItem_tax_total() ?? 0.00);
      echo H::closeTag('td'); //6
     echo H::closeTag('tr'); //5
     echo H::openTag('tr'); //5
      echo H::openTag('td'); //6
       echo H::openTag('b'); //7
       echo $translator->translate('allowance.or.charge.shipping.handling.packaging');
       echo H::closeTag('b'); //7
      echo H::closeTag('td'); //6
      echo H::openTag('td', [
          'class' => 'amount',
          'id' => 'amount_quote_allowance_charge_total',
          'data-bs-toggle' => 'tooltip',
          'title' => 'quote_amount->packhandleship_total',
      ]); //6
       echo H::openTag('b'); //7
       echo $numberHelper->format_currency(
           $packHandleShipTotal['totalAmount'] ?? 0.00
       );
       echo H::closeTag('b'); //7
      echo H::closeTag('td'); //6
     echo H::closeTag('tr'); //5
     echo H::openTag('tr'); //5
      echo H::openTag('td'); //6
       echo H::openTag('b'); //7
       echo (new A())->content(
           $vat === '1' ? $translator->translate(
               'allowance.or.charge.shipping.handling.packaging.vat'
           ) :
               $translator->translate(
                   'allowance.or.charge.shipping.handling.packaging.tax'
               )
       )->href(
           $urlGenerator->generate('quoteallowancecharge/index', [], [
               'filterQuoteNumber' => $quote->getNumber()])
       );
       echo H::closeTag('b'); //7
      echo H::closeTag('td'); //6
      echo H::openTag('td', [
          'class' => 'amount',
          'id' => 'amount_quote_allowance_charge_tax',
          'data-bs-toggle' => 'tooltip',
          'title' => 'quote_amount->packhandleship_tax',
      ]); //6
       echo H::openTag('b'); //7
       echo $numberHelper->format_currency(
           $packHandleShipTotal['totalTax'] ?? 0.00
       );
       echo H::closeTag('b'); //7
      echo H::closeTag('td'); //6
     echo H::closeTag('tr'); //5
if ($vat === '0') {
     echo H::openTag('tr'); //5
      echo H::openTag('td'); //6
       echo H::openTag('b'); //7
    if ($invEdit === true) {
         echo H::openTag('a', [
             'href' => '#add-quote-tax',
             'data-bs-toggle' => 'modal',
             'class' => 'btn-xs',
             'style' => 'text-decoration:none',
         ]); //8
         echo '➕';
         echo H::closeTag('a'); //8
    }
       echo $translator->translate('tax');
       echo H::closeTag('b'); //7
      echo H::closeTag('td'); //6
      echo H::openTag('td'); //6
    if ($quoteTaxRates) {
        /**
         * @var App\Invoice\Entity\QuoteTaxRate $quoteTaxRate
         */
        foreach ($quoteTaxRates as $quoteTaxRate) {
             echo H::openTag('div', [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $quoteTaxRate->getInclude_item_tax()
                     == '1' ? $included : $excluded,
                 'tabindex' => '0',
             ]); //7
             echo H::openTag('input', [
                 'type' => 'hidden',
                 'name' => '_csrf',
                 'value' => $csrf,
             ]);
             echo H::closeTag('input');
            if ($invEdit === true) {
                 echo H::openTag('span'); //8
                 echo (new A())
                     ->addAttributes([
                         'class' => 'btn btn-secondary',
                         'style' => 'text-decoration:none',
                         'data-bs-toggle' => 'tooltip',
                         'title' => $translator->translate('delete'),
                     ])
                     ->content('❌')
                     ->href($urlGenerator->generate('quote/delete_quote_tax_rate', [
                         '_language' => $currentRoute->getArgument('_language'),
                         'id'        => $quoteTaxRate->getId(),
                     ]));
                 echo H::closeTag('span'); //8
            }
             echo H::openTag('span', ['class' => 'text-muted']); //8
             $taxRatePercent = $quoteTaxRate->getTaxRate()?->getTaxRatePercent();
             $numberPercent = $numberHelper->format_amount($taxRatePercent);
             $taxRateName = $quoteTaxRate->getTaxRate()?->getTaxRateName();
             if ($taxRatePercent >= 0.00
                     && null !== $taxRateName
                     && $numberPercent >= 0.00
                 && null !== $numberPercent) {
                 echo H::encode(' ' . $taxRateName
                         . ' '
                         . $numberPercent
                         . ' ');
             }
             echo H::closeTag('span'); //8
             echo H::openTag('span', [
                 'class' => 'amount',
                 'data-bs-toggle' => 'tooltip',
                 'title' => 'quote_tax_rate->quote_tax_rate_amount',
             ]); //8
             echo $numberHelper->format_currency($quoteTaxRate->getQuote_tax_rate_amount());
             echo H::closeTag('span'); //8
             echo H::openTag('br');
             echo H::closeTag('br');
             echo H::closeTag('div'); //7
        }
    } else {
        echo $numberHelper->format_currency('0');
    }
      echo H::closeTag('td'); //6
     echo H::closeTag('tr'); //5
}
if (($quote->getDiscount_amount() ?? 0.00) !== 0.00) {
     echo H::openTag('tr'); //5
      echo H::openTag('td', ['class' => 'td-vert-middle']); //6
       echo H::openTag('b'); //7
       echo '(' . $translator->translate('discount') . ')';
       echo H::closeTag('b'); //7
      echo H::closeTag('td'); //6
      echo H::openTag('td', ['class' => 'clearfix']); //6
       echo H::openTag('div', ['class' => 'discount-field']); //7
        echo H::openTag('div', ['class' => 'input-group input-group']); //8
        echo $numberHelper->format_currency($quote->getDiscount_amount() ?? 0.00);
        echo H::closeTag('div'); //8
       echo H::closeTag('div'); //7
      echo H::closeTag('td'); //6
     echo H::closeTag('tr'); //5
}
     echo H::openTag('tr'); //5
      echo H::openTag('td'); //6
       echo H::openTag('b'); //7
       echo $translator->translate('total');
       echo H::closeTag('b'); //7
      echo H::closeTag('td'); //6
      echo H::openTag('td', [
          'class' => 'amount',
          'style' => 'background-color:lightyellow',
          'id' => 'amount_quote_total',
          'data-bs-toggle' => 'tooltip',
          'title' => 'quote_amount->total',
      ]); //6
       echo H::openTag('b'); //7
       echo $numberHelper->format_currency($quoteAmount->getTotal() ?? 0.00);
       echo H::closeTag('b'); //7
      echo H::closeTag('td'); //6
     echo H::closeTag('tr'); //5
    echo H::closeTag('tbody'); //4
   echo H::closeTag('table'); //3
  echo H::closeTag('div'); //2
 echo H::closeTag('div'); //1
echo H::openTag('hr');
echo H::closeTag('hr');
