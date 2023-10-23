<?php

declare(strict_types=1);

namespace App\Invoice\Profile;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Email;

final class ProfileForm extends FormModel
{    
    private ?int $company_id=null;
    private ?int $current=0;
    private ?string $mobile='';
    private ?string $email='';
    private ?string $description='';
    private ?string $date_created='';
    private ?string $date_modified='';

    public function getCompany_id() : int|null
    {
      return $this->company_id;
    }

    public function getCurrent() : int|null
    {
      return $this->current;
    }

    public function getMobile() : string|null
    {
      return $this->mobile;
    }

    public function getEmail() : string|null
    {
      return $this->email;
    }
    
    public function getDescription() : string|null
    {
      return $this->description;
    }

    public function getDate_created() : string|null
    {
      return $this->date_created;
    }

    public function getDate_modified() : string|null
    {
      return $this->date_modified;
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
     * @return (Email|Required)[][]
     *
     * @psalm-return array{mobile: list{Required}, email: list{Required, Email}, description: list{Required}}
     */
    public function getRules(): array    {
      return [
        'mobile' => [new Required()],
        'email' => [new Required(),
            new Email(),
        ],
        'description' => [new Required()],
    ];
}
}
