<?php

declare(strict_types=1);

namespace App\Invoice\PaymentMethod;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class PaymentMethodForm extends FormModel
{    
    
    private ?string $name='';

    public function getName() : string|null
    {
      return $this->name;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    public function getFormName(): string
    {
      return '';
    }

    /**
     * @return Required[][]
     *
     * @psalm-return array{name: list{Required}}
     */
    public function getRules(): array    {
      return [
        'name' => [new Required()],
    ];
}
}
