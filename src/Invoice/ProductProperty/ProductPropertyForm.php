<?php

declare(strict_types=1);

namespace App\Invoice\ProductProperty;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ProductPropertyForm extends FormModel
{    
    
    private ?int $product_id=null;
    private ?string $name='';
    private ?string $value='';

    public function getProduct_id() : int|null
    {
      return $this->product_id;
    }

    public function getName() : string|null
    {
      return $this->name;
    }

    public function getValue() : string|null
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

    public function getRules(): array    {
      return [
        'name' => [new Required()],        'value' => [new Required()],    ];
}
}
