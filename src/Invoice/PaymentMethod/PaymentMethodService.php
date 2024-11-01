<?php

declare(strict_types=1);

namespace App\Invoice\PaymentMethod;

use App\Invoice\Entity\PaymentMethod;

final class PaymentMethodService
{
    private PaymentMethodRepository $repository;

    public function __construct(PaymentMethodRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param PaymentMethod $model
     * @param array $array
     * @return void
     */
    public function savePaymentMethod(PaymentMethod $model, array $array): void
    {
        isset($array['name']) ? $model->setName((string)$array['name']) : '';
        $this->repository->save($model);
    }

    /**
     * @param PaymentMethod $model
     * @return void
     */
    public function deletePaymentMethod(PaymentMethod $model): void
    {
        $this->repository->delete($model);
    }
}
