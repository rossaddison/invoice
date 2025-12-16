<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use App\Invoice\Entity\Product;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Translator\TranslatorInterface;

class PeppolProductItemClassificationCodeSchemeIdNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    public function __construct(private readonly TranslatorInterface $translator, private readonly Product $product)
    {
    }

    #[\Override]
    public function getName(): string
    {
        $product_id = $this->product->getProduct_id();
        $product_name = $this->product->getProduct_name();
        return (!empty($product_id)
               && null !== $product_name)
          ? 'Product id: ' . $product_id
          . str_repeat(' ', 2) . $product_name
          . str_repeat(' ', 2)
          . $this->translator->translate('product.item.classification.code.scheme.id.not.found')
          : $this->translator->translate('product.item.classification.code.scheme.id.not.found');
    }

    /**
     * @return string
     */
    #[\Override]
    public function getSolution(): string
    {
        return <<<'SOLUTION'
                Please try again
            SOLUTION;
    }
}
