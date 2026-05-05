<?php

declare(strict_types=1);

namespace App\Invoice\PaymentCustom;

use App\Infrastructure\Persistence\PaymentCustom\PaymentCustom;
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

    public static function show(PaymentCustom $paymentCustom): self
    {
        $form = new self();
        $form->payment_id = $paymentCustom->reqPaymentId();
        $form->custom_field_id = $paymentCustom->reqCustomFieldId();
        $form->value = $paymentCustom->getValue();
        return $form;
    }

    public function getPaymentId(): ?int
    {
        return $this->payment_id;
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
