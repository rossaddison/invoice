<?php

declare(strict_types=1);

namespace App\Invoice\ProductProperty;

use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductProperty\ProductProperty;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ProductPropertyForm extends FormModel
{
    #[Required]
    private ?string $name = '';

    #[Required]
    private ?string $value = '';

    private ?Product $product = null;

    private ?int $product_id = null;
    
    public static function show(
        ProductProperty $productProperty,
        ?int $product_id): self
    {
        $form = new self();
        $form->name = $productProperty->getName();
        $form->value = $productProperty->getValue();
        $form->product = $productProperty->getProduct();
        $form->product_id = $product_id;
        return $form;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getProductId(): ?int
    {
        return $this->product_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
