<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryLocation;

use App\Invoice\Entity\DeliveryLocation;

final class DeliveryLocationService
{
    private DeliveryLocationRepository $repository;

    public function __construct(DeliveryLocationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param DeliveryLocation $model
     * @param array $array
     * @return void
     */
    public function saveDeliveryLocation(DeliveryLocation $model, array $array): void
    {
        isset($array['client_id']) ? $model->setClient_id((int) $array['client_id']) : '';
        isset($array['name']) ? $model->setName((string)$array['name']) : '';
        isset($array['building_number']) ? $model->setBuildingNumber((string)$array['building_number']) : '';
        isset($array['address_1']) ? $model->setAddress_1((string)$array['address_1']) : '';
        isset($array['address_2']) ? $model->setAddress_2((string)$array['address_2']) : '';
        isset($array['city']) ? $model->setCity((string)$array['city']) : '';
        isset($array['state']) ? $model->setState((string)$array['state']) : '';
        isset($array['zip']) ? $model->setZip((string)$array['zip']) : '';
        isset($array['country']) ? $model->setCountry((string)$array['country']) : '';
        isset($array['global_location_number']) ? $model->setGlobal_location_number((string)$array['global_location_number']) : '';
        isset($array['electronic_address_scheme']) ? $model->setElectronic_address_scheme((string)$array['electronic_address_scheme']) : '';
        $this->repository->save($model);
    }

    public function deleteDeliveryLocation(DeliveryLocation $model): void
    {
        $this->repository->delete($model);
    }

}
