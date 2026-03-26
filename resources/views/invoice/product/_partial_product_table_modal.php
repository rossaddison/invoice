<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $products
 */

echo H::openTag('div', ['class' => 'table-responsive']); //1
 echo H::openTag('table', ['class' => 'table table-hover table-bordered table-striped']); //2
  echo H::openTag('tr'); //3
   echo H::openTag('th'); //4
    echo '&nbsp;';
   echo H::closeTag('th'); //4
   echo H::openTag('th'); //4
    echo H::encode($translator->translate('product.sku'));
   echo H::closeTag('th'); //4
   echo H::openTag('th'); //4
    echo H::encode($translator->translate('family.name'));
   echo H::closeTag('th'); //4
   echo H::openTag('th'); //4
    echo H::encode($translator->translate('product.name'));
   echo H::closeTag('th'); //4
   echo H::openTag('th'); //4
    echo H::encode($translator->translate('product.description'));
   echo H::closeTag('th'); //4
   echo H::openTag('th', ['class' => 'text-right']); //4
    echo H::encode($translator->translate('product.price') . '> 0.00');
   echo H::closeTag('th'); //4
  echo H::closeTag('tr'); //3
  /**
   * @var App\Invoice\Entity\Product $product
   */
  foreach ($products as $product) {
   echo H::openTag('tr', ['class' => 'product']); //3
    echo H::openTag('td', ['class' => 'text-left']); //4
     echo H::openTag('input', [
         'type' => 'checkbox',
         'name' => 'product_ids[]',
         'value' => (int) $product->getProductId()
     ]);
    echo H::closeTag('td'); //4
    echo H::openTag('td', ['nowrap' => true, 'class' => 'text-left']); //4
     echo H::openTag('b'); //5
      echo H::encode($product->getProductSku());
     echo H::closeTag('b'); //5
    echo H::closeTag('td'); //4
    echo H::openTag('td'); //4
     echo H::openTag('b'); //5
      echo H::encode($product->getFamily()?->getFamilyName());
     echo H::closeTag('b'); //5
    echo H::closeTag('td'); //4
    echo H::openTag('td'); //4
     echo H::openTag('b'); //5
      echo H::encode($product->getProductName());
     echo H::closeTag('b'); //5
    echo H::closeTag('td'); //4
    echo H::openTag('td'); //4
     echo nl2br(H::encode($product->getProductDescription()));
    echo H::closeTag('td'); //4
    echo H::openTag('td', ['class' => 'text-right']); //4
     echo $numberHelper->formatCurrency($product->getProductPrice());
    echo H::closeTag('td'); //4
   echo H::closeTag('tr'); //3
  }
 echo H::closeTag('table'); //2
echo H::closeTag('div'); //1

