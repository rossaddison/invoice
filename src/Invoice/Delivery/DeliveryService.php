<?php

declare(strict_types=1);

namespace App\Invoice\Delivery;

use App\Invoice\Entity\Delivery;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\Setting\SettingRepository;

final readonly class DeliveryService
{
    public function __construct(
        private DeliveryRepository $repository,
        private DLR $dlR,
    ) {
    }

    public function saveDelivery(
        Delivery $model,
        array $array
    ): void {
        $this->persist($model, $array);
        $model->setDateCreated(new \DateTimeImmutable());
        $model->setDateModified(new \DateTimeImmutable());

        $datetime = new \DateTimeImmutable();
        $d = $datetime::createFromFormat(
            'Y-m-d',
            (string) $array['start_date']);
        $datetime2 = new \DateTimeImmutable();
        $d2 = $datetime2::createFromFormat(
            'Y-m-d',
            (string) $array['actual_delivery_date']);
        $datetime3 = new \DateTimeImmutable();
        $d3 = $datetime3::createFromFormat(
            'Y-m-d',
            (string) $array['end_date']);
        $d ? $model->setStartDate($d) : '';
        $d2 ? $model->setActualDeliveryDate($d2) : '';
        $d3 ? $model->setEndDate($d3) : '';

        isset($array['delivery_location_id']) ?
            $model->setDeliveryLocationId(
                (int) $array['delivery_location_id']) : '';
        isset($array['delivery_party_id']) ?
            $model->setDeliveryPartyId(
                (int) $array['delivery_party_id']) : '';
        isset($array['inv_id']) ?
            $model->setInvId((int) $array['inv_id']) : '';
        isset($array['inv_item_id']) ?
            $model->setInvItemId((int) $array['inv_item_id']) : '';

        $this->repository->save($model);
    }

    private function persist(
        Delivery $model,
        array $array
    ): void {
        $delivery_location = 'delivery_location_id';
        if (isset($array[$delivery_location])) {
            $model->setDeliveryLocation(
                $this->dlR->repoDeliveryLocationquery(
                    (string) $array[$delivery_location]));
        }
    }

    public function deleteDelivery(Delivery $model): void
    {
        $this->repository->delete($model);
    }
}
