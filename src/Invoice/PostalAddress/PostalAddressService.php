<?php

declare(strict_types=1); 

namespace App\Invoice\PostalAddress;

use App\Invoice\Entity\PostalAddress;
use App\Invoice\Client\ClientRepository;

final class PostalAddressService
{

    private PostalAddressRepository $repository;
    
    public function __construct(PostalAddressRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * @param PostalAddress $model
     * @param PostalAddressForm $form
     * @param ClientRepository $clientRepo
     * @return void
     */
    public function savePostalAddress(PostalAddress $model, PostalAddressForm $form) : void
    {
        null!==$form->getId() ? $model->setId($form->getId()) : '';
        null!==$form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
        null!==$form->getStreet_name() ? $model->setStreet_name($form->getStreet_name()) : '';
        null!==$form->getAdditional_street_name() ? $model->setAdditional_street_name($form->getAdditional_street_name()) : '';
        null!==$form->getBuilding_number() ? $model->setBuilding_number($form->getBuilding_number()) : '';
        null!==$form->getCity_name() ? $model->setCity_name($form->getCity_name()) : '';
        null!==$form->getPostalzone() ? $model->setPostalzone($form->getPostalzone()) : '';
        null!==$form->getCountrysubentity() ? $model->setCountrysubentity($form->getCountrysubentity()) : '';
        null!==$form->getCountry() ? $model->setCountry($form->getCountry()) : '';
        $this->repository->save($model);
    }
    
    public function deletePostalAddress(PostalAddress $model): void
    {
        $this->repository->delete($model);
    }
}