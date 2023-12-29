<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Invoice\Entity\UserInv;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class UserInvForm extends FormModel
{       
    #[Required]
    private ?int $user_id=null;
    
    #[Required]
    private ?int $type=null;
    
    private ?bool $active=false;
    
    #[Required]
    private ?string $language='';
    
    #[Required]
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
    
    public function __construct(UserInv $userinv)
    {
        $this->user_id = (int)$userinv->getUser_id();
        $this->type = $userinv->getType();
        $this->active = $userinv->getActive();
        $this->language = $userinv->getLanguage();
        $this->name = $userinv->getName();
        $this->company = $userinv->getCompany();
        $this->address_1 = $userinv->getAddress_1();
        $this->address_2 = $userinv->getAddress_2();
        $this->city = $userinv->getCity();
        $this->state = $userinv->getState();
        $this->zip = $userinv->getZip();
        $this->country = $userinv->getCountry();
        $this->phone = $userinv->getPhone();
        $this->fax = $userinv->getFax();
        $this->mobile = $userinv->getMobile();
        $this->email = $userinv->getEmail();
        $this->password = $userinv->getPassword();
        $this->web = $userinv->getWeb();
        $this->vat_id = $userinv->getVat_id();
        $this->tax_code = $userinv->getTax_code();
        $this->all_clients = $userinv->getAll_clients();
        $this->salt = $userinv->getSalt();
        $this->passwordreset_token = $userinv->getPasswordreset_token();
        $this->subscribernumber = $userinv->getSubscribernumber();
        $this->iban = $userinv->getIban();
        $this->gln = $userinv->getGln();
        $this->rcc = $userinv->getRcc();
    }        
    
    public function getUser_id() : int|null
    {
      return $this->user_id;
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
}
