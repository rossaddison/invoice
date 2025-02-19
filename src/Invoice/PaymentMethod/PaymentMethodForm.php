<?php

declare(strict_types=1);

namespace App\Invoice\PaymentMethod;

use App\Invoice\Entity\PaymentMethod;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class PaymentMethodForm extends FormModel
{
    #[Required]
    private ?string $name = '';

    public function __construct(PaymentMethod $paymentMethod)
    {
        $this->name = $paymentMethod->getName();
    }

    public function getName(): string|null
    {
        return $this->name;
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
