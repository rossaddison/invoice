<?php

declare(strict_types=1);

namespace App\Invoice\PaymentMethod;

use App\Infrastructure\Persistence\PaymentMethod\PaymentMethod;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class PaymentMethodForm extends FormModel
{
    #[Required]
    private ?string $name = '';

    private ?bool $active = true;

    public static function show(PaymentMethod $paymentMethod): self
    {
        $form = new self();
        $form->name = $paymentMethod->getName();
        $form->active = $paymentMethod->getActive();
        return $form;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getActive(): ?bool
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
