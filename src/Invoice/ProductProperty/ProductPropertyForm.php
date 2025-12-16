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

    private ?Product $product = null;

    public function __construct(ProductProperty $productProperty, private readonly ?int $product_id)
    {
        $this->name = $productProperty->getName();
        $this->value = $productProperty->getValue();
        $this->product = $productProperty->getProduct();
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getProduct_id(): ?int
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
