<?php

declare(strict_types=1);

namespace App\Invoice\CustomField;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CustomFieldForm extends FormModel
{    
    
    private ?string $table='';
    private ?string $label='';
    private ?string $type='';
    private ?int $location=null;
    private ?int $order=null;

    public function getTable() : string|null
    {
      return $this->table;
    }

    public function getLabel() : string|null
    {
      return $this->label;
    }

    public function getType() : string|null
    {
      return $this->type;
    }

    public function getLocation() : int|null
    {
      return $this->location;
    }

    public function getOrder() : int|null
    {
      return $this->order;
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

    /**
     * @return Required[][]
     *
     * @psalm-return array{table: list{Required}, label: list{Required}, type: list{Required}, location: list{Required}, order: list{Required}}
     */
    public function getRules(): array    {
      return [
        'table' => [new Required()],
        'label' => [new Required()],
        'type' => [new Required()],
        'location' => [new Required()],
        'order' => [new Required()],
    ];
}
}
