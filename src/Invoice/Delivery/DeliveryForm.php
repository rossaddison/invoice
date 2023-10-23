<?php

declare(strict_types=1);

namespace App\Invoice\Delivery;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class DeliveryForm extends FormModel
{   
    private ?string $date_created='';
    private ?string $date_modified='';
    private ?string $start_date='';
    private ?string $actual_delivery_date='';
    private ?string $end_date='';
    private ?int $delivery_location_id=null;
    private ?int $delivery_party_id=null;
    private ?int $inv_id=null;
    private ?int $inv_item_id=null;

    public function getDate_created() : string|null
    {
      return $this->date_created;
    }

    public function getDate_modified() : string|null
    {
      return $this->date_modified;
    }

    public function getStart_date() : string|null
    {
      return $this->start_date;
    }
    
    public function getActual_delivery_date() : string|null
    {
      return $this->actual_delivery_date;
    }
    
    public function getEnd_date() : string|null
    {
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
    
    public function getRules(): array    {
      return [
        'date_created' => [new Required()],
        'date_modified' => [new Required()],
        'actual_delivery_date' => [new Required()],
        'delivery_location_id' => [new Required()],
        'delivery_party_id' => [new Required()]  
      ];
    }
}
