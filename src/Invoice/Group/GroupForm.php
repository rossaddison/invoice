<?php

declare(strict_types=1);

namespace App\Invoice\Group;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class GroupForm extends FormModel
{    
    
    private ?string $name='';
    private string $identifier_format='';
    private ?int $next_id=null;
    private ?int $left_pad=null;

    public function getName() : string|null
    {
      return $this->name;
    }

    public function getIdentifier_format() : string
    {
      return $this->identifier_format;
    }

    public function getNext_id() : int|null
    {
      return $this->next_id;
    }

    public function getLeft_pad() : int|null
    {
      return $this->left_pad;
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
     * @psalm-return array{name: list{Required}, identifier_format: list{Required}, next_id: list{Required}, left_pad: list{Required}}
     */
    public function getRules(): array    {
      return [
        'name' => [new Required()],
        'identifier_format' => [new Required()],
        'next_id' => [new Required()],
        'left_pad' => [new Required()],
    ];
}
}
