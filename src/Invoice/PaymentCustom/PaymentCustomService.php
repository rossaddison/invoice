<?php

declare(strict_types=1);

namespace App\Invoice\PaymentCustom;

use App\Invoice\Entity\PaymentCustom;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\Payment\PaymentRepository as PR;

final readonly class PaymentCustomService
{
    public function __construct(
        private PaymentCustomRepository $repository,
        private PR $pR,
        private CFR $cfR,
    ) {
    }

    /**
     * @param PaymentCustom $model
     * @param array $array
     */
    public function savePaymentCustom(
        PaymentCustom $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['payment_id']) ? 
            $model->setPayment_id(
                (int) $array['payment_id']) : '';
        isset($array['custom_field_id']) ? 
            $model->setCustom_field_id(
                (int) $array['custom_field_id']) : '';
        isset($array['value']) ? 
            $model->setValue((string) $array['value']) : '';

        $this->repository->save($model);
    }

    private function persist(
        PaymentCustom $model,
        array $array
    ): PaymentCustom {
        $payment = 'payment_id';
        if (isset($array[$payment])) {
            $paymentEntity = $this->pR->repoPaymentquery(
                (string) $array[$payment]);
            if ($paymentEntity) {
                $model->setPayment($paymentEntity);
            }
        }
        $custom_field = 'custom_field_id';
        if (isset($array[$custom_field])) {
            $model->setCustomField(
                $this->cfR->repoCustomFieldquery(
                    (string) $array[$custom_field]));
        }
        return $model;
    }

    /**
     * @param PaymentCustom $model
     */
    public function deletePaymentCustom(PaymentCustom $model): void
    {
        $this->repository->delete($model);
    }
}
