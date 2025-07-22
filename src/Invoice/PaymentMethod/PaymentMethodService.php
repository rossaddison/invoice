<?php

declare(strict_types=1);

namespace App\Invoice\PaymentMethod;

use App\Invoice\Entity\PaymentMethod;

final readonly class PaymentMethodService
{
    public function __construct(private PaymentMethodRepository $repository)
    {
    }

    public function savePaymentMethod(PaymentMethod $model, array $array): void
    {
        isset($array['name']) ? $model->setName((string) $array['name']) : '';
        $model->setActive('1' === $array['active'] ? true : false);
        $this->repository->save($model);
    }

    public function deletePaymentMethod(PaymentMethod $model): void
    {
        $this->repository->delete($model);
    }
}
