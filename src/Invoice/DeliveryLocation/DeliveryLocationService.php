<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryLocation;

use App\Invoice\Entity\DeliveryLocation;

final class DeliveryLocationService {

  private DeliveryLocationRepository $repository;

  public function __construct(DeliveryLocationRepository $repository) {
    $this->repository = $repository;
  }

  /**
   * @param DeliveryLocation $model
   * @param DeliveryLocationForm $form
   * @return void
   */
  public function saveDeliveryLocation(DeliveryLocation $model, DeliveryLocationForm $form): void {
    null !== $form->getClient_id() ? $model->setClient_id((int) $form->getClient_id()) : '';
    null !== $form->getName() ? $model->setName($form->getName()) : '';
    null !== $form->getAddress_1() ? $model->setAddress_1($form->getAddress_1()) : '';
    null !== $form->getAddress_2() ? $model->setAddress_2($form->getAddress_2()) : '';
    null !== $form->getCity() ? $model->setCity($form->getCity()) : '';
    null !== $form->getState() ? $model->setState($form->getState()) : '';
    null !== $form->getZip() ? $model->setZip($form->getZip()) : '';
    null !== $form->getCountry() ? $model->setCountry($form->getCountry()) : '';
    null !== $form->getGlobal_location_number() ? $model->setGlobal_location_number($form->getGlobal_location_number()) : '';
    null !== $form->getElectronic_address_scheme() ? $model->setElectronic_address_scheme($form->getElectronic_address_scheme()) : '';
    $this->repository->save($model);
  }

  public function deleteDeliveryLocation(DeliveryLocation $model): void {
    $this->repository->delete($model);
  }

}
