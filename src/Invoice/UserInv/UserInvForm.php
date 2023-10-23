<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;
use \DateTimeImmutable;

final class UserInvForm extends FormModel
{       
    private ?int $user_id=null;
    private ?int $type=null;
    private ?bool $active=false;
    private ?string $language='';
    private ?string $name='';
    private ?string $company='';
    private ?string $address_1='';
    private ?string $address_2='';
    private ?string $city='';
    private ?string $state='';
    private ?string $zip='';
    private ?string $country='';
    private ?string $phone='';
    private ?string $fax='';
    private ?string $mobile='';
    private ?string $email='';
    private ?string $password='';
    private ?string $web='';
    private ?string $vat_id='';
    private ?string $tax_code='';
    private ?bool $all_clients=false;
    private ?string $salt='';
    private ?string $passwordreset_token='';
    private ?string $subscribernumber='';
    private ?string $iban='';
    private ?int $gln=null;
    private ?string $rcc='';

    public function getUser_id() : int
    {
      return (int)$this->user_id;
    }

    public function getType() : int|null
    {
      return $this->type;
    }

    public function getActive() : bool|null
    {
      return $this->active;
    }

    public function getLanguage() : string|null
    {
      return $this->language;
    }

    public function getName() : string|null
    {
      return $this->name;
    }

    public function getCompany() : string|null
    {
      return $this->company;
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

    public function getMobile() : string|null
    {
      return $this->mobile;
    }

    public function getEmail() : string|null
    {
      return $this->email;
    }

    public function getPassword() : string|null
    {
      return $this->password;
    }

    public function getWeb() : string|null
    {
      return $this->web;
    }

    public function getVat_id() : string|null
    {
      return $this->vat_id;
    }

    public function getTax_code() : string|null
    {
      return $this->tax_code;
    }

    public function getAll_clients() : bool|null
    {
      return $this->all_clients;
    }

    public function getSalt() : string|null
    {
      return $this->salt;
    }

    public function getPasswordreset_token() : string|null
    {
      return $this->passwordreset_token;
    }

    public function getSubscribernumber() : string|null
    {
      return $this->subscribernumber;
    }

    public function getIban() : string|null
    {
      return $this->iban;
    }

    public function getGln() : int|null
    {
      return $this->gln;
    }

    public function getRcc() : string|null
    {
      return $this->rcc;
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
     * @psalm-return array{user_id: list{Required}, type: list{Required}, language: list{Required}, name: list{Required}}
     */
    public function getRules(): array  {
        return [
            'user_id' => [new Required()],
            'type' => [new Required()],
            'language' => [new Required()],
            'name' => [new Required()],
        ];
    }
}
