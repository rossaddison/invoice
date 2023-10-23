<?php

declare(strict_types=1);

namespace App\Invoice\Company;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Email;

final class CompanyForm extends FormModel
{  
    private ?int $current=0;
    private ?string $name='';
    private ?string $address_1='';
    private ?string $address_2='';
    private ?string $city='';
    private ?string $state='';
    private ?string $zip='';
    private ?string $country='';
    private ?string $phone='';
    private ?string $fax='';
    private ?string $email='';
    private ?string $web='';

    public function getCurrent() : int|null
    {
      return $this->current;
    }

    public function getName() : string|null
    {
      return $this->name;
    }

    public function getAddress_1() : string|null
    {
      return $this->address_1;
    }

    public function getAddress_2() : string|null
    {
      return $this->address_2;
    }

    public function getCity() : string|null
    {
      return $this->city;
    }

    public function getState() : string|null
    {
      return $this->state;
    }

    public function getZip() : string|null
    {
      return $this->zip;
    }

    public function getCountry() : string|null
    {
      return $this->country;
    }

    public function getPhone() : string|null
    {
      return $this->phone;
    }

    public function getFax() : string|null
    {
      return $this->fax;
    }

    public function getEmail() : string|null
    {
      return $this->email;
    }

    public function getWeb() : string|null
    {
      return $this->web;
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
     * @psalm-return array{name: list{Required}, email: list{Required, Email}}
     */
    public function getRules(): array    {
      return [
        'name' => [new Required()],       
        'email' => [new Required(), new Email()],
    ];
}
}
