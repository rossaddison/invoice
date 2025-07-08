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

    private ?bool $active = true;

    public function __construct(PaymentMethod $paymentMethod)
    {
        $this->name = $paymentMethod->getName();
        $this->active = $paymentMethod->getActive();
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function getActive(): bool|null
    {
        return $this->active;
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
