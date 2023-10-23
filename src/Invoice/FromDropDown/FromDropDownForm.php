<?php

declare(strict_types=1);

namespace App\Invoice\FromDropDown;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class FromDropDownForm extends FormModel
{    
    
    private ?int $id=null;
    private ?string $email='';
    private ?bool $include=false;
    private ?bool $default_email=false;

    public function getId() : int|null
    {
      return $this->id;
    }

    public function getEmail() : string|null
    {
      return $this->email;
    }

    public function getInclude() : bool|null
    {
      return $this->include;
    }

    public function getDefault_email() : bool|null
    {
      return $this->default_email;
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
        'email' => [new Required()],
        'include' => [new Required()],
        'default_email' => [new Required()],
      ];
}
}
