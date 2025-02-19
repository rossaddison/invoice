<?php

declare(strict_types=1);

namespace App\Invoice\Merchant;

use App\Invoice\Entity\Merchant;

final readonly class MerchantService
{
    public function __construct(private MerchantRepository $repository)
    {
    }

    /**
     * @param Merchant $model
     * @param array $array
     */
    public function saveMerchant(Merchant $model, array $array): void
    {
        isset($array['inv_id']) ? $model->setInv_id((int)$array['inv_id']) : '';
        $model->setSuccessful((bool)$array['successful']);

        $datetime = new \DateTime();
        /**
         * @var string $array['date']
         */
        $date = $array['date'] ?? '';
        $model->setDate($datetime::createFromFormat('Y-m-d', $date));

        isset($array['driver']) ? $model->setDriver((string)$array['driver']) : '';
        isset($array['response']) ? $model->setResponse((string)$array['response']) : '';
        isset($array['reference']) ? $model->setReference((string)$array['reference']) : '';
        $this->repository->save($model);
    }

    /**
     * @param Merchant $model
     * @param array $array
     */
    public function saveMerchant_via_payment_handler(Merchant $model, array $array): void
    {
        $model->setInv_id((int)$array['inv_id']);
        /** @var bool $array['merchant_response_successful'] */
        $model->setSuccessful($array['merchant_response_successful']);
        /** @var \DateTime $array['merchant_response_date'] */
        $model->setDate($array['merchant_response_date']);
        /** @var string $array['merchant_response_driver'] */
        $model->setDriver($array['merchant_response_driver']);
        // Payment success message
        /** @var string $array['merchant_response'] */
        $model->setResponse($array['merchant_response']);
        /** @var string $array['merchant_response_reference'] */
        $model->setReference($array['merchant_response_reference']);
        $this->repository->save($model);
    }

    /**
     * @param Merchant $model
     */
    public function deleteMerchant(Merchant $model): void
    {
        $this->repository->delete($model);
    }
}
