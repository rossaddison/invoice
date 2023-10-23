<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use App\Invoice\Entity\Product;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Translator\TranslatorInterface;

class PeppolProductUnitCodeNotFoundException extends \RuntimeException implements FriendlyExceptionInterface {

  private TranslatorInterface $translator;
  private Product $product;

  public function __construct(TranslatorInterface $translator, Product $product) {
    $this->translator = $translator;
    $this->product = $product;
  }

  public function getName(): string {
    $product_id = $this->product->getProduct_id();
    $product_name = $this->product->getProduct_name();
    return (!empty($product_id) && 
           !empty($product_name)) ? 
      'Product id: '. $product_id . 
      str_repeat(' ', 2).  $product_name . 
      str_repeat(' ', 2). 
      $this->translator->translate('invoice.product.unit.code.not.found')
                                 : 
      $this->translator->translate('invoice.product.unit.code.not.found');
  }

  /**
   * @return string
   * @psalm-return '    Please try again'
   */
  public function getSolution(): ?string {
    return <<<'SOLUTION'
                Please try again
            SOLUTION;
  }

}
