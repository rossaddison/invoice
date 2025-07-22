<?php

declare(strict_types=1);

namespace App\Invoice\PaymentCustom;

use App\Invoice\Entity\PaymentCustom;

final readonly class PaymentCustomService
{
    public function __construct(private PaymentCustomRepository $repository)
    {
    }

    public function savePaymentCustom(PaymentCustom $model, array $array): void
    {
        isset($array['payment_id']) ? $model->setPayment_id((int) $array['payment_id']) : '';
        isset($array['custom_field_id']) ? $model->setCustom_field_id((int) $array['custom_field_id']) : '';
        isset($array['value']) ? $model->setValue((string) $array['value']) : '';

        $this->repository->save($model);
    }

    public function deletePaymentCustom(PaymentCustom $model): void
    {
        $this->repository->delete($model);
    }
}
