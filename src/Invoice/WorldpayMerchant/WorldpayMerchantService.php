<?php

declare(strict_types=1);

namespace App\Invoice\WorldpayMerchant;

use App\Infrastructure\Persistence\WorldpayMerchant\WorldpayMerchant;
use App\Invoice\Inv\InvRepository as IR;

final readonly class WorldpayMerchantService
{
    public function __construct(
        private WorldpayMerchantRepository $repository,
        private IR $iR,
    ) {
    }

    private function persist(WorldpayMerchant $model, array $array): void
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
     * Mirrors SquareMerchantService::saveSquareMerchantViaPaymentHandler(),
     * with payment_id/self_href in place of order_id/payment_id.
     *
     * @param array{
     *     inv_id: int,
     *     merchant_response_successful: bool,
     *     merchant_response_date: \DateTime,
     *     merchant_response: string,
     *     merchant_response_reference: string,
     *     merchant_response_transaction_reference?: string|null,
     *     merchant_response_payment_id?: string|null,
     *     merchant_response_self_href?: string|null,
     *     merchant_response_pending_action_href?: string|null,
     * } $array
     */
    public function saveWorldpayMerchantViaPaymentHandler(
        WorldpayMerchant $model,
        array $array,
    ): void {
        $this->persist($model, $array);
        $model->setInvId($array['inv_id']);
        $model->setSuccessful($array['merchant_response_successful']);
        $model->setDate($array['merchant_response_date']);
        $model->setResponse($array['merchant_response']);
        $model->setReference($array['merchant_response_reference']);
        $model->setTransactionReference($array['merchant_response_transaction_reference'] ?? null);
        $model->setPaymentId($array['merchant_response_payment_id'] ?? null);
        $model->setSelfHref($array['merchant_response_self_href'] ?? null);
        $model->setPendingActionHref($array['merchant_response_pending_action_href'] ?? null);
        $this->repository->save($model);
    }
}
