<?php

declare(strict_types=1);

namespace App\Invoice\Delivery;

use App\Invoice\Entity\Delivery;
use App\Invoice\Setting\SettingRepository;

final class DeliveryService
{
    private DeliveryRepository $repository;

    public function __construct(DeliveryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveDelivery(Delivery $model, array $array, SettingRepository $s): void
    {
        $model->setDate_created(new \DateTimeImmutable());
        $model->setDate_modified(new \DateTimeImmutable());

        $datetime = new \DateTimeImmutable();
        $d = $datetime::createFromFormat('Y-m-d', (string)$array['start_date']);
        $datetime2 = new \DateTimeImmutable();
        $d2 = $datetime2::createFromFormat('Y-m-d', (string)$array['actual_delivery_date']);
        $datetime3 = new \DateTimeImmutable();
        $d3 = $datetime3::createFromFormat('Y-m-d', (string)$array['end_date']);
        $d ? $model->setStart_date($d) : '';
        $d2 ? $model->setActual_delivery_date($d2) : '';
        $d3 ? $model->setEnd_Date($d3) : '';

        isset($array['delivery_location_id']) ? $model->setDelivery_location_id((int)$array['delivery_location_id']) : '';
        isset($array['delivery_party_id']) ? $model->setDelivery_party_id((int)$array['delivery_party_id']) : '';
        isset($array['inv_id']) ? $model->setInv_id((int)$array['inv_id']) : '';
        isset($array['inv_item_id']) ? $model->setInv_item_id((int)$array['inv_item_id']) : '';

        $this->repository->save($model);
    }

    public function deleteDelivery(Delivery $model): void
    {
        $this->repository->delete($model);
    }
}
