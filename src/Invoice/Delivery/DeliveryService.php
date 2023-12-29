<?php
declare(strict_types=1); 

namespace App\Invoice\Delivery;

use App\Invoice\Entity\Delivery;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Helpers\DateHelper;

final class DeliveryService
{
    private DeliveryRepository $repository;

    public function __construct(DeliveryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveDelivery(Delivery $model, array $array, SettingRepository $s): void
    {
        $datehelper = new DateHelper($s);
        $dim_created = new \DateTimeImmutable();
        $dim_modified = new \DateTimeImmutable();
        $dim_start_date = new \DateTimeImmutable();
        $dim_actual_delivery_date = new \DateTimeImmutable();
        $dim_end_date = new \DateTimeImmutable();
        $model->setDate_created($dim_created::createFromFormat($datehelper->style(),(string)$array['date_created']));
     
        $model->setDate_modified($dim_modified::createFromFormat($datehelper->style(),(string)$array['date_modified']));
        
        $model->setStart_date($dim_start_date::createFromFormat($datehelper->style(),(string)$array['start_date']));
        
        $model->setActual_delivery_date($dim_actual_delivery_date::createFromFormat($datehelper->style(),(string)$array['actual_delivery_date']));
        
        $model->setEnd_Date($dim_end_date::createFromFormat($datehelper->style(),(string)$array['end_date']));
        
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