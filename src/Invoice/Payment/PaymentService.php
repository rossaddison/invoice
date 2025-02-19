<?php

declare(strict_types=1);

namespace App\Invoice\Payment;

use App\Invoice\Entity\Payment;

final readonly class PaymentService
{
    public function __construct(private PaymentRepository $repository)
    {
    }

    /**
     * @param Payment $model
     * @param array $array
     */
    public function savePayment(Payment $model, array $array): void
    {
        isset($array['payment_method_id']) ? $model->setPayment_method_id((int)$array['payment_method_id']) : '';

        $datetime = new \DateTime();
        /**
         * @var string $array['payment_date']
         */
        $payment_date = $array['payment_date'] ?? '';
        $model->setPayment_date($datetime::createFromFormat('Y-m-d', $payment_date));

        isset($array['amount']) ? $model->setAmount((float)$array['amount']) : '';
        isset($array['note']) ? $model->setNote((string)$array['note']) : '';
        isset($array['inv_id']) ? $model->setInv_id((int)$array['inv_id']) : '';
        $this->repository->save($model);
    }

    /**
     * @param Payment $model
     * @param array $array
     */
    public function addPayment_via_payment_handler(Payment $model, array $array): void
    {
        $model->setPayment_method_id((int)$array['payment_method_id']);
        /** @var \DateTime $array['payment_date'] */
        $model->setPayment_date($array['payment_date']);
        /** @var float $array['amount'] */
        $model->setAmount($array['amount']);
        /** @var string $array['note'] */
        $model->setNote($array['note']);
        $model->setInv_id((int)$array['inv_id']);
        $this->repository->save($model);
    }

    /**
     * @param Payment $model
     */
    public function deletePayment(Payment $model): void
    {
        $this->repository->delete($model);
    }
}
