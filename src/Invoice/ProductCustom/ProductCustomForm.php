<?php

declare(strict_types=1);

namespace App\Invoice\ProductCustom;

use App\Infrastructure\Persistence\ProductCustom\ProductCustom;
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

    public static function show(ProductCustom $product_custom): self
    {
        $form = new self();
        $form->product_id = $product_custom->reqProductId();
        $form->custom_field_id = $product_custom->reqCustomFieldId();
        $form->value = $product_custom->getValue();
        return $form;
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
