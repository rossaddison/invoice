<?php

declare(strict_types=1);

namespace App\Invoice\SquareMerchant;

use App\Infrastructure\Persistence\SquareMerchant\SquareMerchant;
use App\Invoice\Inv\InvRepository as IR;

final readonly class SquareMerchantService
{
    public function __construct(
        private SquareMerchantRepository $repository,
        private IR $iR,
    ) {
    }

    private function persist(SquareMerchant $model, array $array): void
    {
        $inv = 'inv_id';
        if (isset($array[$inv])) {
            $invEntity = $this->iR->repoInvUnLoadedquery((int) $array[$inv]);
            if ($invEntity) {
                $model->setInv($invEntity);
            }
        }
    }

    /**
     * Mirrors MerchantService::saveMerchantViaPaymentHandler(), plus
     * order_id/payment_id in place of the generic provider_reference.
     *
     * @param array{
     *     inv_id: int,
     *     merchant_response_successful: bool,
     *     merchant_response_date: \DateTime,
     *     merchant_response: string,
     *     merchant_response_reference: string,
     *     merchant_response_order_id?: string|null,
     *     merchant_response_payment_id?: string|null,
     * } $array
     */
    public function saveSquareMerchantViaPaymentHandler(
        SquareMerchant $model,
        array $array,
    ): void {
        $this->persist($model, $array);
        $model->setInvId($array['inv_id']);
        $model->setSuccessful($array['merchant_response_successful']);
        $model->setDate($array['merchant_response_date']);
        $model->setResponse($array['merchant_response']);
        $model->setReference($array['merchant_response_reference']);
        $model->setOrderId($array['merchant_response_order_id'] ?? null);
        $model->setPaymentId($array['merchant_response_payment_id'] ?? null);
        $this->repository->save($model);
    }
}
