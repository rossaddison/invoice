<?php

declare(strict_types=1);

namespace App\Invoice\UnitPeppol;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class UnitPeppolForm extends FormModel
{    
    
    private ?int $unit_id=null;
    private ?string $code='';
    private ?string $name='';
    private ?string $description='';

    public function getUnit_id() : int|null
    {
      return $this->unit_id;
    }

    public function getCode() : string|null
    {
      return $this->code;
    }

    public function getName() : string|null
    {
      return $this->name;
    }

    public function getDescription() : string|null
    {
      return $this->description;
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
        'unit_id' => [new Required()],  
        'code' => [new Required()],
        'name' => [new Required()],
        'description' => [new Required()],
      ];
}
}
