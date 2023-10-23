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

    public function saveDelivery(Delivery $model, DeliveryForm $form, SettingRepository $s): void
    {
        $datehelper = new DateHelper($s);
        $datetime_created = $datehelper->get_or_set_with_style(null!==$form->getDate_created()? $form->getDate_created() : new \DateTime());
        $datetimeimmutable_created = new \DateTimeImmutable($datetime_created instanceof \DateTime ? $datetime_created->format('Y-m-d H:i:s') : 'now');
        $model->setDate_created($datetimeimmutable_created);
     
        $datetime_modified = $datehelper->get_or_set_with_style(null!==$form->getDate_modified()? $form->getDate_modified() : new \DateTime());
        $datetimeimmutable_modified = new \DateTimeImmutable($datetime_modified instanceof \DateTime ? $datetime_modified->format('Y-m-d H:i:s') : 'now');
        $model->setDate_modified($datetimeimmutable_modified);
        
        $datetime_start = $datehelper->get_or_set_with_style(null!==$form->getStart_date()? $form->getStart_date() : new \DateTime());
        $datetimeimmutable_start = new \DateTimeImmutable($datetime_start instanceof \DateTime ? $datetime_start->format('Y-m-d H:i:s') : 'now');
        $model->setStart_date($datetimeimmutable_start);
                
        $datetime_actual = $datehelper->get_or_set_with_style(null!==$form->getActual_delivery_date()? $form->getActual_delivery_date() : new \DateTime());
        $datetimeimmutable_actual = new \DateTimeImmutable($datetime_actual instanceof \DateTime ? $datetime_actual->format('Y-m-d H:i:s') : 'now');
        $model->setActual_delivery_date($datetimeimmutable_actual);
        
        $datetime_end = $datehelper->get_or_set_with_style(null!==$form->getEnd_date()? $form->getEnd_date() : new \DateTime());
        $datetimeimmutable_end = new \DateTimeImmutable($datetime_end instanceof \DateTime ? $datetime_end->format('Y-m-d H:i:s') : 'now');
        $model->setEnd_date($datetimeimmutable_end);
        
        null!==$form->getDelivery_location_id() ? $model->setDelivery_location_id($form->getDelivery_location_id()) : '';
        null!==$form->getDelivery_party_id() ? $model->setDelivery_party_id($form->getDelivery_party_id()) : '';
        null!==$form->getInv_id() ? $model->setInv_id($form->getInv_id()) : '';
        null!==$form->getInv_item_id() ? $model->setInv_item_id($form->getInv_item_id()) : '';
 
        $this->repository->save($model);
    }
    
    public function deleteDelivery(Delivery $model): void
    {
        $this->repository->delete($model);
    }
}