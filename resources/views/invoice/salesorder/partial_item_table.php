<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Input;
use Yiisoft\Html\Tag\Option;
use Yiisoft\Html\Tag\Textarea;

/**
 * @var App\Infrastructure\Persistence\SalesOrder\SalesOrder $so
 * @var App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount $soAmount
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\ProductImage\ProductImageRepository $piR
 * @var App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository $soiaR
 * @var App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository $acsoiR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Session\SessionInterface $session
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var array $soItems
 * @var array $soTaxRates
 * @var array $products
 * @var array $tasks
 * @var array $taxRates
 * @var array $units
 * @var bool $draft
 * @var bool $invEdit
 * @var bool $invView
 * @var bool $editClientPeppol
 * @var string $csrf
 * @var string $included
 * @var string $excluded
 */

$vat = $s->getSetting('enable_vat_registration');
$subtotalTooltip = 'sales_order_amount->item_subtotal ='
    . 'sales_order_item(s)->subtotal - sales_order_item(s)->discount'
    . '+ sales_order_item(s)->charge"';

$tdText       = ['class' => 'td-text', 'style' => 'background-color: lightgreen'];
$tdTextarea   = ['class' => 'td-textarea'];
$tdAmount     = ['class' => 'td-amount'];
$tdAmountQty  = ['class' => 'td-amount td-quantity'];
$tdVertMiddle = ['class' => 'td-amount td-vert-middle'];
$textMuted    = ['class' => 'text-muted'];

echo H::openTag('div'); //0
 echo H::openTag('table', ['id' => 'item_table',
    'class' => 'items table table-responsive table-bordered m-0']); //1
  echo H::openTag('thead'); //2
   echo H::openTag('tr'); //3
    echo H::tag('i', '', [
     'class'          => 'bi bi-info-circle',
     'data-bs-toggle' => 'tooltip',
     'title'          => $s->isDebugMode(20),
    ]); //4
   echo H::closeTag('tr'); //3
   echo H::openTag('tr'); //3
    echo H::tag('th', ''); //4
    echo H::tag('th', ''); //4
    echo H::tag('th', ''); //4
    echo H::tag('th', ''); //4
    echo H::tag('th', ''); //4
    echo H::tag('th', ''); //4
    echo H::tag('th', ''); //4
   echo H::closeTag('tr'); //3
  echo H::closeTag('thead'); //2

$count = 1;
/**
 * @var App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem $item
 */
foreach ($soItems as $item) {
 $productId  = $item->getProduct()?->reqId();
 $taskId     = $item->getTask()?->reqId();
 $productRef = '';
 $taskRef    = '';
 if ($productId !== null) {
  $productRef = (new A())
   ->href($urlGenerator->generate('product/view', [
    '_language' => (string) $session->get('_language'),
    'id'        => $productId,
   ]))
   ->content((string) $productId)
   ->render();
 }
 if ($taskId !== null) {
  $taskRef = (new A())
   ->href($urlGenerator->generate('task/view', [
    '_language' => (string) $session->get('_language'),
    'id'        => $taskId,
   ]))
   ->content((string) $taskId)
   ->render();
 }

 echo H::openTag('tbody', ['class' => 'item']); //2
  echo H::openTag('tr'); //3
   echo H::openTag('td', $tdText); //4
    echo H::openTag('b'); //5
     echo $count
      . '-'
      . (string) $item->reqSalesOrderId()
      . '-'
      . (string) $item->reqId()
      . '-'
      . ($productId !== null ? $productRef : '')
      . ($taskId !== null ? $taskRef : '');
     echo H::tag('br');
     echo new Input()
      ->type('text')
      ->disabled(true)
      ->addAttributes([
       'placeholder'    => 'Item Id',
       'name'           => 'item_peppol_po_itemid',
       'value'          => H::encode((string) $item->getPeppolPoItemid()),
       'data-bs-toggle' => 'tooltip',
       'title'          => 'salesorder_item->peppol_po_itemid',
      ]);
     echo new Input()
      ->type('text')
      ->disabled(true)
      ->addAttributes([
       'placeholder'    => 'Line Id',
       'name'           => 'item_peppol_po_lineid',
       'value'          => H::encode((string) $item->getPeppolPoLineid()),
       'data-bs-toggle' => 'tooltip',
       'title'          => 'salesorder_item->peppol_po_lineid',
      ]);
    echo H::closeTag('b'); //5
   echo H::closeTag('td'); //4
   echo H::openTag('td', $tdTextarea); //4
    echo H::openTag('div'); //5
     echo H::openTag('span', $textMuted); //6
      echo H::openTag('b'); //7
       echo $productId !== null
        ? $translator->translate('item')
        : $translator->translate('tasks');
      echo H::closeTag('b'); //7
     echo H::closeTag('span'); //6
     echo H::openTag('select', [
      'name'     => 'item_name',
      'class'    => 'form-control form-control-lg',
      'disabled' => true,
     ]); //6
      if ($productId !== null) {
       echo new Option()->value('0')->content($translator->translate('none'));
       /**
        * @var App\Infrastructure\Persistence\Product\Product $product
        */
       foreach ($products as $product) {
        echo new Option()
         ->value((string) $product->reqId())
         ->selected($productId == $product->reqId())
         ->content(H::encode((string) $product->getProductName()));
       }
      }
      if ($taskId !== null) {
       echo new Option()->value('0')->content($translator->translate('none'));
       /**
        * @var App\Infrastructure\Persistence\Task\Task $task
        */
       foreach ($tasks as $task) {
        echo new Option()
         ->value((string) $task->reqId())
         ->selected($taskId == $task->reqId())
         ->content(H::encode((string) $task->getName()));
       }
      }
     echo H::closeTag('select'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('td'); //4
   echo H::openTag('td', $tdAmountQty); //4
    echo H::openTag('div'); //5
     echo H::openTag('span', $textMuted); //6
      echo H::openTag('b'); //7
       echo $translator->translate('quantity');
      echo H::closeTag('b'); //7
     echo H::closeTag('span'); //6
     echo new Input()
      ->type('text')
      ->disabled(true)
      ->name('item_quantity')
      ->class('form-control form-control-sm text-end')
      ->addAttributes([
       'data-bs-toggle' => 'tooltip',
       'title'          => 'sales_order_item->quantity',
       'value'          => $numberHelper->formatAmount($item->getQuantity()),
      ]);
    echo H::closeTag('div'); //5
   echo H::closeTag('td'); //4
   echo H::openTag('td', $tdAmount); //4
    echo H::openTag('div'); //5
     echo H::openTag('span', $textMuted); //6
      echo H::openTag('b'); //7
       echo $translator->translate('price');
      echo H::closeTag('b'); //7
     echo H::closeTag('span'); //6
     echo new Input()
      ->type('text')
      ->disabled(true)
      ->name('item_price')
      ->class('form-control form-control-sm text-end')
      ->addAttributes([
       'maxlength'      => '4',
       'size'           => '4',
       'data-bs-toggle' => 'tooltip',
       'title'          => 'sales_order_item->price',
       'value'          => $numberHelper->formatAmount($item->getPrice()),
      ]);
    echo H::closeTag('div'); //5
   echo H::closeTag('td'); //4
   echo H::openTag('td', $tdAmount); //4
    echo H::openTag('div'); //5
     echo H::openTag('span', $textMuted); //6
      echo H::openTag('b'); //7
       echo $vat === '0'
        ? $translator->translate('item.discount')
        : $translator->translate('cash.discount');
      echo H::closeTag('b'); //7
     echo H::closeTag('span'); //6
     echo new Input()
      ->type('text')
      ->disabled(true)
      ->name('item_discount_amount')
      ->class('form-control form-control-sm text-end')
      ->addAttributes([
       'maxlength'      => '4',
       'size'           => '4',
       'data-bs-toggle' => 'tooltip',
       'data-placement' => 'bottom',
       'title'          => $s->getSetting('currency_symbol') .
          ' ' . $translator->translate('per.item'),
       'value'          => $numberHelper->formatAmount($item->getDiscountAmount()),
      ]);
    echo H::closeTag('div'); //5
   echo H::closeTag('td'); //4
   echo H::openTag('td'); //4
    echo H::openTag('div'); //5
     echo H::openTag('span', $textMuted); //6
      echo H::openTag('b'); //7
       echo $vat === '0'
        ? $translator->translate('tax.rate')
        : $translator->translate('vat.rate');
      echo H::closeTag('b'); //7
     echo H::closeTag('span'); //6
     echo H::openTag('select', [
      'disabled'       => true,
      'name'           => 'item_tax_rate_id',
      'class'          => 'form-control form-control-lg',
      'data-bs-toggle' => 'tooltip',
      'title'          => 'quote_item->tax_rate_id',
     ]); //6
      echo new Option()->value('0')->content($translator->translate('none'));
      /**
       * @var App\Infrastructure\Persistence\TaxRate\TaxRate $taxRate
       */
      foreach ($taxRates as $taxRate) {
       $taxRatePercent = $numberHelper->formatAmount($taxRate->getTaxRatePercent());
       $taxRateName    = $taxRate->getTaxRateName();
       $label = ($taxRatePercent >= 0.00 &&
            null !== $taxRatePercent && null !== $taxRateName)
        ? $taxRatePercent . '% - ' . $taxRateName
        : '';
       echo new Option()
        ->value((string) $taxRate->reqId())
        ->selected($item->getTaxRateId() == $taxRate->reqId())
        ->content(H::encode($label));
      }
     echo H::closeTag('select'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('td'); //4
   echo H::openTag('td', ['class' => 'td-vert-middle btn-group']); //4
    if ($invEdit === true) {
     if ($productId !== null && $piR->repoCount($productId) > 0) {
      echo H::openTag('span', [
       'data-bs-toggle' => 'tooltip',
       'title'          => $translator->translate('productimage.gallery')
            . ($item->getProduct()?->getProductName() ?? ''),
      ]); //5
       echo (new A())
        ->href('#view-product-' . $item->reqId())
        ->content(H::tag('i', '', ['class' => 'bi bi-eye']))
        ->class('btn btn-info')
        ->addAttributes([
         'data-bs-toggle' => 'modal',
        ])
        ->render();
      echo H::closeTag('span'); //5
      echo H::openTag('div', [
       'id'       => 'view-product-' . $item->reqId(),
       'class'    => 'modal modal-lg',
       'tabindex' => '-1',
      ]); //5
       echo H::openTag('div', ['class' => 'modal-dialog']); //6
        echo H::openTag('div', ['class' => 'modal-content']); //7
         echo H::openTag('div', ['class' => 'modal-header']); //8
          echo H::tag('button', '', [
           'type'            => 'button',
           'class'           => 'btn-close',
           'data-bs-dismiss' => 'modal',
           'aria-label'      => 'Close',
          ]); //9
         echo H::closeTag('div'); //8
         echo H::openTag('div', ['class' => 'modal-body']); //8
          echo H::openTag('form'); //9
           echo H::openTag('div', ['class' => 'mb-3']); //10
            echo new Input()->type('hidden')->name('_csrf')->value($csrf);
            $productImages = $piR->repoProductImageProductquery($productId);
/**
 * @var App\Infrastructure\Persistence\ProductImage\ProductImage $productImage
 */
            foreach ($productImages as $productImage) {
             if (!empty($productImage->getFileNameOriginal())) {
              echo H::openTag('a', ['data-bs-toggle' => 'modal',
                'class' => 'col-sm-4']); //11
               echo H::tag('img', '', [
                'src'   => '/products/' . $productImage->getFileNameOriginal(),
                'class' => 'img-fluid',
               ]); //12
              echo H::closeTag('a'); //11
             }
            }
           echo H::closeTag('div'); //10
          echo H::closeTag('form'); //9
         echo H::closeTag('div'); //8
         echo H::openTag('div', ['class' => 'modal-footer']); //8
          echo H::tag('button', $translator->translate('cancel'), [
           'type'            => 'button',
           'class'           => 'btn btn-secondary',
           'data-bs-dismiss' => 'modal',
          ]); //9
         echo H::closeTag('div'); //8
        echo H::closeTag('div'); //7
       echo H::closeTag('div'); //6
      echo H::closeTag('div'); //5
     }
    }
    if ($editClientPeppol === true) {
     echo H::openTag('span'); //5
      echo (new A())
       ->href($urlGenerator->generate('salesorderitem/edit', ['id' => $item->reqId()]))
       ->content('🖉')
       ->class('btn btn-primary')
       ->addClass('text-decoration-none')
       ->render();
     echo H::closeTag('span'); //5
    }
   echo H::closeTag('td'); //4
  echo H::closeTag('tr'); //3

  echo H::openTag('tr'); //3
   echo H::tag('td', ''); //4
   echo H::openTag('td', $tdTextarea); //4
    echo H::openTag('div'); //5
     echo H::openTag('span', array_merge($textMuted, [
      'data-bs-toggle' => 'tooltip',
      'title'          => 'quote_item->description',
     ])); //6
      echo H::openTag('b'); //7
       echo $translator->translate('description');
      echo H::closeTag('b'); //7
     echo H::closeTag('span'); //6
     echo new Textarea()
      ->addAttributes(['disabled' => true])
      ->name('item_description')
      ->class('form-control form-control-lg')
      ->rows(1)
      ->value(H::encode((string) $item->getDescription()));
    echo H::closeTag('div'); //5
   echo H::closeTag('td'); //4
   echo H::openTag('td', $tdAmount); //4
    echo H::openTag('div'); //5
     if ($productId !== null) {
      echo H::openTag('span', $textMuted); //6
       echo H::openTag('b'); //7
        echo $translator->translate('product.unit');
       echo H::closeTag('b'); //7
      echo H::closeTag('span'); //6
      echo H::tag('br');
      echo H::tag('span', H::encode((string) $item->getProductUnit()),
        array_merge($textMuted, ['name' => 'item_product_unit']));
     }
     if ($taskId !== null) {
      echo H::openTag('span', $textMuted); //6
       echo H::openTag('b'); //7
        echo $item->getTask()?->getName();
       echo H::closeTag('b'); //7
      echo H::closeTag('span'); //6
      echo H::tag('br');
      $finishDate = $item->getTask()?->getFinishDate();
      echo H::tag('span', !is_string($finishDate) ?
            ($finishDate?->format('Y-m-d') ?? '') :
            '', array_merge($textMuted, ['name' => 'item_task_unit']));
     }
    echo H::closeTag('div'); //5
   echo H::closeTag('td'); //4
   echo H::openTag('td', $tdAmount); //4
    if ($productId !== null) {
     echo H::openTag('b'); //5
      echo $numberHelper->formatAmount(($item->getQuantity() ?? 0.00) *
        ($item->getPrice() ?? 0.00));
     echo H::closeTag('b'); //5
    }
   echo H::closeTag('td'); //4
   echo H::tag('td', '', $tdAmount); //4
   echo H::openTag('td', $tdAmount); //4
    echo H::openTag('b'); //5
     echo $numberHelper->formatAmount(
      ($item->getQuantity() ?? 0.00)
      * ($item->getPrice() ?? 0.00)
      * ($item->getTaxRate()?->getTaxRatePercent() ?? 0.00)
      / 100
     );
    echo H::closeTag('b'); //5
   echo H::closeTag('td'); //4
   echo H::tag('td', '', $tdAmount); //4
  echo H::closeTag('tr'); //3

  if ($s->getSetting('enable_peppol') == '1') {
/**
 * @var App\Infrastructure\Persistence\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceCharge $acsoi
 */
   foreach ($acsoiR->repoSalesOrderItemquery($item->reqId()) as $acsoi) {
    $isCharge = ($acsoi->getAllowanceCharge()?->getIdentifier() == 1);
    echo H::openTag('tr'); //3
     echo H::openTag('td', $tdAmount); //4
      echo H::openTag('b'); //5
       echo $acsoi->getAllowanceCharge()?->getIdentifier() == '1'
        ? $translator->translate('allowance.or.charge.charge')
        : '(' . $translator->translate('allowance.or.charge.allowance') . ')';
      echo H::closeTag('b'); //5
     echo H::closeTag('td'); //4
     echo H::openTag('td', $tdAmount); //4
      echo H::openTag('b'); //5
       echo $translator->translate('allowance.or.charge.reason.code') . ': '
        . ($acsoi->getAllowanceCharge()?->getReasonCode() ?? '#');
      echo H::closeTag('b'); //5
     echo H::closeTag('td'); //4
     echo H::openTag('td', $tdAmount); //4
      echo H::openTag('b'); //5
       echo $translator->translate('allowance.or.charge.reason') . ': '
        . ($acsoi->getAllowanceCharge()?->getReason() ?? '#');
      echo H::closeTag('b'); //5
     echo H::closeTag('td'); //4
     echo H::openTag('td', $tdAmount); //4
      echo H::openTag('b'); //5
       echo ($isCharge ? '' : '(') .
        $numberHelper->formatCurrency($acsoi->getAmount()) .
            ($isCharge ? '' : ')');
      echo H::closeTag('b'); //5
     echo H::closeTag('td'); //4
     echo H::tag('td', '', $tdAmount); //4
     echo H::openTag('td', $tdAmount); //4
      echo H::openTag('b'); //5
       echo ($isCharge ? '' : '(') .
        $numberHelper->formatCurrency($acsoi->getVatOrTax()) .
            ($isCharge ? '' : ')');
      echo H::closeTag('b'); //5
     echo H::closeTag('td'); //4
     echo H::tag('td', '', $tdAmount); //4
    echo H::closeTag('tr'); //3
   }
  }

  echo H::openTag('tr'); //3
   echo H::tag('td', '', $tdAmount); //4
   echo H::tag('td', '', $tdAmount); //4
   echo H::tag('td', '', $tdAmount); //4
   echo H::openTag('td', array_merge($tdVertMiddle,
        ['style' => 'background-color: lightblue'])); //4
    echo H::openTag('span'); //5
     echo H::openTag('b'); //6
      echo $translator->translate('subtotal');
     echo H::closeTag('b'); //6
    echo H::closeTag('span'); //5
    echo H::tag('br');
    echo H::openTag('span', [
     'name'           => 'subtotal',
     'class'          => 'text-end',
     'data-bs-toggle' => 'tooltip',
     'title'          => 'sales_order_item_amount',
    ]); //5
     echo $numberHelper->formatCurrency(
      $soiaR->repoSalesOrderItemAmountquery($item->reqId())?->getSubtotal()
     );
    echo H::closeTag('span'); //5
   echo H::closeTag('td'); //4
   echo H::openTag('td', $tdVertMiddle); //4
    echo H::openTag('span'); //5
     echo H::openTag('b'); //6
      echo '(' . ($vat === '0'
       ? $translator->translate('discount')
       : $translator->translate('early.settlement.cash.discount')) . ')';
     echo H::closeTag('b'); //6
    echo H::closeTag('span'); //5
    echo H::tag('br');
    echo H::openTag('span', [
     'name'           => 'item_discount_total',
     'class'          => 'text-end',
     'data-bs-toggle' => 'tooltip',
     'title'          => 'sales_order_item_amount->discount',
    ]); //5
     echo '(' . $numberHelper->formatCurrency(
      $soiaR->repoSalesOrderItemAmountquery($item->reqId())?->getDiscount()
     ) . ')';
    echo H::closeTag('span'); //5
   echo H::closeTag('td'); //4
   echo H::openTag('td', array_merge($tdVertMiddle,
        ['style' => 'background-color: lightpink'])); //4
    echo H::openTag('span'); //5
     echo H::openTag('b'); //6
      echo $vat === '0'
       ? $translator->translate('tax')
       : $translator->translate('vat.abbreviation');
     echo H::closeTag('b'); //6
    echo H::closeTag('span'); //5
    echo H::tag('br');
    echo H::openTag('span', [
     'name'           => 'item_tax_total',
     'class'          => 'text-end',
     'data-bs-toggle' => 'tooltip',
     'title'          => 'sales_order_item_amount->tax_total',
    ]); //5
     echo $numberHelper->formatCurrency(
      $soiaR->repoSalesOrderItemAmountquery($item->reqId())?->getTaxTotal()
     );
    echo H::closeTag('span'); //5
   echo H::closeTag('td'); //4
   echo H::openTag('td', array_merge($tdVertMiddle,
        ['style' => 'background-color: lightyellow'])); //4
    echo H::openTag('span'); //5
     echo H::openTag('b'); //6
      echo $translator->translate('total');
     echo H::closeTag('b'); //6
    echo H::closeTag('span'); //5
    echo H::tag('br');
    echo H::openTag('span', [
     'name'           => 'item_total',
     'class'          => 'text-end',
     'data-bs-toggle' => 'tooltip',
     'title'          => 'sales_order_item_amount->total',
    ]); //5
     echo $numberHelper->formatCurrency(
      $soiaR->repoSalesOrderItemAmountquery($item->reqId())?->getTotal()
     );
    echo H::closeTag('span'); //5
   echo H::closeTag('td'); //4
  echo H::closeTag('tr'); //3
 echo H::closeTag('tbody'); //2
 $count++;
}
echo H::closeTag('table'); //1
echo H::closeTag('div'); //0

echo H::tag('br');

echo H::openTag('div', ['class' => 'row']); //0
 echo H::openTag('div', ['class' => 'col-12 col-md-4']); //1
 echo H::closeTag('div'); //1
 echo H::openTag('div', ['class' => 'col-12 d-block d-sm-none']); //1
  echo H::tag('br');
 echo H::closeTag('div'); //1
 echo H::openTag('div', ['class' =>
     'col-12 col-md-6 offset-md-2 col-lg-4 offset-lg-4']); //1
  echo H::openTag('table', ['class' =>
     'table table-bordered text-end']); //2
   echo H::openTag('tr'); //3
    echo H::tag('i', '', [
     'class'          => 'bi bi-info-circle',
     'data-bs-toggle' => 'tooltip',
     'title'          => $s->isDebugMode(20),
    ]); //4
   echo H::closeTag('tr'); //3
   echo H::openTag('tr'); //3
    echo H::openTag('td', ['style' => 'width: 40%;']); //4
     echo H::openTag('b'); //5
      echo $translator->translate('subtotal');
     echo H::closeTag('b'); //5
    echo H::closeTag('td'); //4
    echo H::openTag('td', [
     'style'          => 'width: 60%;background-color: lightblue',
     'class'          => 'text-end',
     'id'             => 'amount_subtotal',
     'data-bs-toggle' => 'tooltip',
     'title'          => $subtotalTooltip,
    ]); //4
     echo $numberHelper->formatCurrency(
      $soAmount->getItemSubtotal() > 0.00 ? $soAmount->getItemSubtotal() : 0.00
     );
    echo H::closeTag('td'); //4
   echo H::closeTag('tr'); //3
   echo H::openTag('tr'); //3
    echo H::openTag('td'); //4
     echo H::openTag('span'); //5
      echo H::openTag('b'); //6
       echo $vat == '1'
        ? $translator->translate('vat.break.down')
        : $translator->translate('item.tax');
      echo H::closeTag('b'); //6
     echo H::closeTag('span'); //5
    echo H::closeTag('td'); //4
    echo H::openTag('td', [
     'class'          => 'text-end',
     'style'          => 'background-color: lightpink',
     'data-bs-toggle' => 'tooltip',
     'id'             => 'amount_item_tax_total',
     'title'          => 'sales_order_amount->item_tax_total',
    ]); //4
     echo $numberHelper->formatCurrency(
      $soAmount->getItemTaxTotal() > 0 ? $soAmount->getItemTaxTotal() : 0.00
     );
    echo H::closeTag('td'); //4
   echo H::closeTag('tr'); //3
   echo H::openTag('tr'); //3
    echo H::openTag('td'); //4
     echo H::openTag('b'); //5
      echo $translator->translate('allowance.or.charge.shipping.handling.packaging');
     echo H::closeTag('b'); //5
    echo H::closeTag('td'); //4
    echo H::openTag('td', [
     'class'          => 'text-end',
     'id'             => 'amount_sales_order_allowance_charge_total',
     'data-bs-toggle' => 'tooltip',
     'title'          => 'sales_order_amount->packhandleship_total',
    ]); //4
     echo H::openTag('b'); //5
      echo $numberHelper->formatCurrency($packHandleShipTotal['totalAmount'] ?? 0.00);
     echo H::closeTag('b'); //5
    echo H::closeTag('td'); //4
   echo H::closeTag('tr'); //3
   echo H::openTag('tr'); //3
    echo H::openTag('td'); //4
     echo H::openTag('b'); //5
      echo $vat == '1'
       ? $translator->translate('allowance.or.charge.shipping.handling.packaging.vat')
       : $translator->translate('allowance.or.charge.shipping.handling.packaging.tax');
     echo H::closeTag('b'); //5
    echo H::closeTag('td'); //4
    echo H::openTag('td', [
     'class'          => 'text-end',
     'id'             => 'amount_sales_order_allowance_charge_tax',
     'data-bs-toggle' => 'tooltip',
     'title'          => 'sales_order_amount->packhandleship_tax',
    ]); //4
     echo H::openTag('b'); //5
      echo $numberHelper->formatCurrency($packHandleShipTotal['totalTax'] ?? 0.00);
     echo H::closeTag('b'); //5
    echo H::closeTag('td'); //4
   echo H::closeTag('tr'); //3
   if ($vat === '0') {
    echo H::openTag('tr'); //3
     echo H::openTag('td'); //4
      echo H::openTag('b'); //5
       echo $translator->translate('tax');
      echo H::closeTag('b'); //5
     echo H::closeTag('td'); //4
     echo H::openTag('td'); //4
      if ($soTaxRates) {
       /**
        * @var App\Infrastructure\Persistence\SalesOrderTaxRate\SalesOrderTaxRate $soTaxRate
        */
       foreach ($soTaxRates as $soTaxRate) {
        echo H::openTag('div', [
         'data-bs-toggle' => 'tooltip',
         'title'          => $soTaxRate->getIncludeItemTax() == '1' ? $included : $excluded,
        ]); //5
         echo new Input()->type('hidden')->name('_csrf')->value($csrf);
         $taxRatePercent = $soTaxRate->getTaxRate()?->getTaxRatePercent();
         $numberPercent  = $numberHelper->formatAmount($taxRatePercent);
         $taxRateName    = $soTaxRate->getTaxRate()?->getTaxRateName();
         if ($taxRatePercent >= 0.00 && null !== $taxRateName
          && $numberPercent >= 0.00 && null !== $numberPercent) {
          echo H::tag('span', H::encode(' '
                . $taxRateName
                . ' '
                . $numberPercent
                . ' '), $textMuted);
         }
         echo H::openTag('span', [
          'class'          => 'text-end',
          'data-bs-toggle' => 'tooltip',
          'title'          => 'sales_order_tax_rate->sales_order_tax_rate_amount',
         ]); //5
          echo $numberHelper->formatCurrency($soTaxRate->getSalesOrderTaxRateAmount());
         echo H::closeTag('span'); //5
         echo H::tag('br');
        echo H::closeTag('div'); //5
       }
      } else {
       echo $numberHelper->formatCurrency('0');
      }
     echo H::closeTag('td'); //4
    echo H::closeTag('tr'); //3
   }
   if (($so->getDiscountAmount() ?? 0.00) != 0.00) {
    echo H::openTag('tr'); //3
     echo H::openTag('td', ['class' => 'td-vert-middle']); //4
      echo H::openTag('b'); //5
       echo '(' . $translator->translate('discount') . ')';
      echo H::closeTag('b'); //5
     echo H::closeTag('td'); //4
     echo H::openTag('td'); //4
      echo H::openTag('div', ['class' => 'discount-field']); //5
       echo $numberHelper->formatCurrency($so->getDiscountAmount() ?? 0.00);
      echo H::closeTag('div'); //5
     echo H::closeTag('td'); //4
    echo H::closeTag('tr'); //3
   }
   echo H::openTag('tr'); //3
    echo H::openTag('td'); //4
     echo H::openTag('b'); //5
      echo $translator->translate('total');
     echo H::closeTag('b'); //5
    echo H::closeTag('td'); //4
    echo H::openTag('td', [
     'class'          => 'text-end',
     'style'          => 'background-color:lightyellow',
     'id'             => 'amount_sales_order_total',
     'data-bs-toggle' => 'tooltip',
     'title'          => 'sales_order_amount->total',
    ]); //4
     echo H::openTag('b'); //5
      echo $numberHelper->formatCurrency($soAmount->getTotal() ?? 0.00);
     echo H::closeTag('b'); //5
    echo H::closeTag('td'); //4
   echo H::closeTag('tr'); //3
  echo H::closeTag('table'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0
echo H::tag('hr');
