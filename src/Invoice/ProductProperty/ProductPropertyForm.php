<?php

declare(strict_types=1);

namespace App\Invoice\ProductProperty;

use App\Invoice\Entity\Product;
use App\Invoice\Entity\ProductProperty;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ProductPropertyForm extends FormModel
{
    #[Required]
    private ?string $name = '';

    #[Required]
    private ?string $value = '';

    private ?int $product_id = null;

    private ?Product $product = null;

    public function __construct(ProductProperty $productProperty, int $product_id)
    {
        $this->name = $productProperty->getName();
        $this->value = $productProperty->getValue();
        $this->product_id = $product_id;
        $this->product = $productProperty->getProduct();
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getProduct_id(): int|null
    {
        return $this->product_id;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function getValue(): string|null
    {
        return $this->value;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    public function getFormName(): string
    {
        return '';
    }
}
