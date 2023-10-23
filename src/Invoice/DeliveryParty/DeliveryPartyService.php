<?php
declare(strict_types=1); 

namespace App\Invoice\DeliveryParty;

use App\Invoice\Entity\DeliveryParty;

final class DeliveryPartyService
{
    private DeliveryPartyRepository $repository;

    public function __construct(DeliveryPartyRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveDeliveryParty(DeliveryParty $model, DeliveryPartyForm $form): void
    {
        null!==$form->getParty_name() ? $model->setPartyName($form->getParty_name()) : '';
        $this->repository->save($model);
    }
    
    public function deleteDeliveryParty(DeliveryParty $model): void
    {
        $this->repository->delete($model);
    }
}