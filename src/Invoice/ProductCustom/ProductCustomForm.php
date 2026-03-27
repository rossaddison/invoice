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
        $this->product_id = (int) $product_custom->getProductId();
        $this->custom_field_id = (int) $product_custom->getCustomFieldId();
        $this->value = $product_custom->getValue();
    }

    public function getProductId(): ?int
    {
        return $this->product_id;
    }

    public function getCustomFieldId(): ?int
    {
        return $this->custom_field_id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
