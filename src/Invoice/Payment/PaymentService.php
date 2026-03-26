<?php

declare(strict_types=1);

namespace App\Invoice\Payment;

use App\Invoice\Entity\Payment;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;

final readonly class PaymentService
{
    public function __construct(
        private PaymentRepository $repository,
        private IR $iR,
        private PMR $pmR,
    ) {
    }

    /**
     * @param Payment $model
     * @param array $array
     */
    public function savePayment(
        Payment $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['payment_method_id']) ? 
            $model->setPaymentMethodId(
                (int) $array['payment_method_id']) : '';

        $datetime = new \DateTime();
        /**
         * @var string $array['payment_date']
         */
        $payment_date = $array['payment_date'] ?? '';
        $model->setPaymentDate(
            $datetime::createFromFormat('Y-m-d', $payment_date));

        isset($array['amount']) ? 
            $model->setAmount((float) $array['amount']) : '';
        isset($array['note']) ? 
            $model->setNote((string) $array['note']) : '';
        isset($array['inv_id']) ? 
            $model->setInvId((int) $array['inv_id']) : '';
        $this->repository->save($model);
    }

    private function persist(
        Payment $model,
        array $array
    ): void {
        $inv = 'inv_id';
        if (isset($array[$inv])) {
            $invEntity = $this->iR->repoInvUnLoadedquery(
                (string) $array[$inv]);
            if ($invEntity) {
                $model->setInv($invEntity);
            }
        }
        $payment_method = 'payment_method_id';
        if (isset($array[$payment_method])) {
            $pmEntity = $this->pmR->repoPaymentMethodquery(
                (string) $array[$payment_method]);
            if ($pmEntity) {
                $model->setPaymentMethod($pmEntity);
            }
        }
    }

    /**
     * @param Payment $model
     * @param array $array
     */
    public function addPaymentViaPaymentHandler(
        Payment $model,
        array $array
    ): void {
        $this->persist($model, $array);
        $model->setPaymentMethodId(
            (int) $array['payment_method_id']);
        /** @var \DateTime $array['payment_date'] */
        $model->setPaymentDate($array['payment_date']);
        /** @var float $array['amount'] */
        $model->setAmount($array['amount']);
        /** @var string $array['note'] */
        $model->setNote($array['note']);
        $model->setInvId((int) $array['inv_id']);
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
