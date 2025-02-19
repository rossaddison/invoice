<?php

declare(strict_types=1);

namespace App\Invoice\PaymentPeppol;

use App\Invoice\Entity\PaymentPeppol;

final readonly class PaymentPeppolService
{
    public function __construct(private PaymentPeppolRepository $repository)
    {
    }

    /**
     * @param PaymentPeppol $model
     * @param array $array
     */
    public function savePaymentPeppol(PaymentPeppol $model, array $array): void
    {
        isset($array['inv_id']) ? $model->setInv_id((int)$array['inv_id']) : '';
        isset($array['id']) ? $model->setId((int)$array['id']) : '';
        isset($array['provider']) ? $model->setProvider((string)$array['provider']) : '';

        $timestamp = (new \DateTimeImmutable())->setTimestamp((int)$array['auto_reference'])->getTimestamp();
        $model->setAuto_reference($timestamp);

        $this->repository->save($model);
    }

    public function deletePaymentPeppol(PaymentPeppol $model): void
    {
        $this->repository->delete($model);
    }
}
