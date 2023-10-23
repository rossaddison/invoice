<?php

declare(strict_types=1);

namespace App\Invoice\ItemLookup;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ItemLookupForm extends FormModel
{    
    
    private ?string $name='';
    private ?string $description='';
    private ?float $price=null;

    public function getName() : string|null
    {
      return $this->name;
    }

    public function getDescription() : string|null
    {
      return $this->description;
    }

    public function getPrice() : float|null
    {
      return $this->price;
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
     * @psalm-return array{name: list{Required}, description: list{Required}, price: list{Required}}
     */
    public function getRules(): array    {
      return [
        'name' => [new Required()],
        'description' => [new Required()],
        'price' => [new Required()],
    ];
}
}
