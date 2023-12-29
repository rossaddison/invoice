<?php

declare(strict_types=1);

namespace App\Invoice\Delivery;

use App\Invoice\Entity\Delivery;
use Yiisoft\FormModel\FormModel;

use DateTimeImmutable;

final class DeliveryForm extends FormModel
{   
    private mixed $date_created;
    private mixed $date_modified;
    private mixed $start_date;
    private mixed $actual_delivery_date;
    private mixed $end_date;
    private ?int $delivery_location_id=null;
    private ?int $delivery_party_id=null;
    private ?int $inv_id=null;
    private ?int $inv_item_id=null;
    
    public function __construct(Delivery $delivery)
    {
        $this->date_created = $delivery->getDate_created();
        $this->date_modified = $delivery->getDate_modified();
        $this->start_date = $delivery->getStart_date();
        $this->actual_delivery_date = $delivery->getActual_delivery_date();
        $this->end_date = $delivery->getEnd_date();
        $this->delivery_location_id = (int)$delivery->getDelivery_location_id();
        $this->delivery_party_id = (int)$delivery->getDelivery_party_id();
        $this->inv_id = $delivery->getInv_id();
        $this->inv_item_id = $delivery->getInv_item_id();
    }

    public function getDate_created() : string|DateTimeImmutable
    {
        /**
         * @var string|DateTimeImmutable $this->date_created 
         */
        return $this->date_created;
    }

    public function getDate_modified() : string|DateTimeImmutable
    {
        /**
         * @var string|DateTimeImmutable $this->date_modified 
         */
        return $this->date_modified;
    }

    public function getStart_date() : string|DateTimeImmutable
    {
        /**
         * @var string|DateTimeImmutable $this->start_date 
         */
        return $this->start_date;
    }
    
    public function getActual_delivery_date() : string|DateTimeImmutable
    {
        /**
         * @var string|DateTimeImmutable $this->actual_delivery_date 
         */
        return $this->actual_delivery_date;
    }
    
    public function getEnd_date() : string|DateTimeImmutable
    {
        /**
         * @var string|DateTimeImmutable $this->end_date 
         */
        return $this->end_date;
    }

    public function getDelivery_location_id() : int|null
    {
      return $this->delivery_location_id;
    }

    public function getDelivery_party_id() : int|null
    {
      return $this->delivery_party_id;
    }

    public function getInv_id() : int|null
    {
      return $this->inv_id;
    }

    public function getInv_item_id() : int|null
    {
      return $this->inv_item_id;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    public function getFormName(): string
    {
      return '';
    }
    
}
