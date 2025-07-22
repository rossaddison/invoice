<?php

declare(strict_types=1);

namespace App\Invoice\ProductCustom;

use App\Invoice\Entity\ProductCustom;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\StringValue;

final class ProductCustomForm extends FormModel
{
    #[Integer]
    #[Required]
    private ?int $product_id = null;

    #[Integer]
    #[Required]
    private ?int $custom_field_id = null;

    #[StringValue()]
    #[Required]
    private ?string $value = '';

    public function __construct(ProductCustom $product_custom)
    {
        $this->product_id      = (int) $product_custom->getProduct_id();
        $this->custom_field_id = (int) $product_custom->getCustom_field_id();
        $this->value           = $product_custom->getValue();
    }

    public function getProduct_id(): ?int
    {
        return $this->product_id;
    }

    public function getCustom_field_id(): ?int
    {
        return $this->custom_field_id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
