<?php

declare(strict_types=1);

namespace App\Invoice\PaymentCustom;

use App\Invoice\Entity\PaymentCustom;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class PaymentCustomForm extends FormModel
{
    #[Required]
    private ?int $payment_id = null;
    #[Required]
    private ?int $custom_field_id = null;
    #[Required]
    private ?string $value = '';

    public function __construct(PaymentCustom $paymentCustom)
    {
        $this->payment_id = (int)$paymentCustom->getPayment_id();
        $this->custom_field_id = (int)$paymentCustom->getCustom_field_id();
        $this->value = $paymentCustom->getValue();
    }

    public function getPayment_id(): int|null
    {
        return $this->payment_id;
    }

    public function getCustom_field_id(): int|null
    {
        return $this->custom_field_id;
    }

    public function getValue(): string|null
    {
        return $this->value;
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
}
