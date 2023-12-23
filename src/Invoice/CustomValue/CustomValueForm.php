<?php

declare(strict_types=1);

namespace App\Invoice\CustomValue;

use App\Invoice\Entity\CustomValue;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CustomValueForm extends FormModel
{    
    #[Required]
    private ?int $id=null;
    #[Required]
    private ?int $custom_field_id=null;
    #[Required]
    private ?string $value='';
    
    public function __construct(CustomValue $custom_value) 
    {
        $this->id = (int)$custom_value->getId();
        $this->custom_field_id = $custom_value->getCustom_field_id();
        $this->value = $custom_value->getValue();
    }
    
    public function getId() : int|null
    {
        return $this->id;
    }    

    public function getCustom_field_id() : int|null
    {
      return $this->custom_field_id;
    }

    public function getValue() : string|null
    {
      return $this->value;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    public function getFormName(): string
    {
      return '';
    }
}
