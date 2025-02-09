<?php

declare(strict_types=1);

namespace App\Invoice\PostalAddress;

use App\Invoice\Entity\PostalAddress;

final class PostalAddressService
{
    private PostalAddressRepository $repository;

    public function __construct(PostalAddressRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param PostalAddress $model
     * @param array $array
     */
    public function savePostalAddress(PostalAddress $model, array $array): void
    {
        isset($array['id']) ? $model->setId((int)$array['id']) : '';
        isset($array['client_id']) ? $model->setClient_id((int)$array['client_id']) : '';
        isset($array['street_name']) ? $model->setStreet_name((string)$array['street_name']) : '';
        isset($array['additional_street_name']) ? $model->setAdditional_street_name((string)$array['additional_street_name']) : '';
        isset($array['building_number']) ? $model->setBuilding_number((string)$array['building_number']) : '';
        isset($array['city_name']) ? $model->setCity_name((string)$array['city_name']) : '';
        isset($array['postalzone']) ? $model->setPostalzone((string)$array['postalzone']) : '';
        isset($array['countrysubentity']) ? $model->setCountrysubentity((string)$array['countrysubentity']) : '';
        isset($array['country']) ? $model->setCountry((string)$array['country']) : '';
        $this->repository->save($model);
    }

    public function deletePostalAddress(PostalAddress $model): void
    {
        $this->repository->delete($model);
    }
}
