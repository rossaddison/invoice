<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class UserInvForm extends FormModel
{
    #[Required]
    private ?int $user_id = null;

    /**
     * Related logic: see Dropdown 0 = Admin, 1 = Not Admin i.e.
     * User with viewInv permission (not editInv Permission)
     */
    #[Required]
    private ?int $type = null;

    private ?bool $active = false;

    #[Required]
    private ?string $language = '';

    #[Required]
    private ?string $name = '';

    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    private ?string $company = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    private ?string $address_1 = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    private ?string $address_2 = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    private ?string $city = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    private ?string $state = '';
    #[Length(min: 0, max: 10, skipOnEmpty: true)]
    private ?string $zip = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    private ?string $country = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    private ?string $phone = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    private ?string $fax = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    private ?string $mobile = '';
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $web = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    private ?string $vat_id = '';
    #[Length(min: 0, max: 15, skipOnEmpty: true)]
    private ?string $tax_code = '';
    private ?bool $all_clients = false;
    #[Length(min: 0, max: 40, skipOnEmpty: true)]
    private ?string $subscribernumber = '';
    #[Length(min: 0, max: 34, skipOnEmpty: true)]
    private ?string $iban = '';
    private ?int $gln = null;
    #[Length(min: 0, max: 7, skipOnEmpty: true)]
    private ?string $rcc = '';
    private ?int $list_limit = 10;

    private ?User $user = null;

    public static function show(UserInv $userinv): self
    {
        $form = new self();
        $form->user_id = $userinv->reqUserId();
        $form->type = $userinv->getType();
        $form->active = $userinv->getActive();
        $form->language = $userinv->getLanguage();
        $form->name = $userinv->getName();
        $form->company = $userinv->getCompany();
        $form->address_1 = $userinv->getAddress1();
        $form->address_2 = $userinv->getAddress2();
        $form->city = $userinv->getCity();
        $form->state = $userinv->getState();
        $form->zip = $userinv->getZip();
        $form->country = $userinv->getCountry();
        $form->phone = $userinv->getPhone();
        $form->fax = $userinv->getFax();
        $form->mobile = $userinv->getMobile();
        $form->web = $userinv->getWeb();
        $form->vat_id = $userinv->getVatId();
        $form->tax_code = $userinv->getTaxCode();
        $form->all_clients = $userinv->getAllClients();
        $form->subscribernumber = $userinv->getSubscribernumber();
        $form->iban = $userinv->getIban();
        $form->gln = $userinv->getGln();
        $form->rcc = $userinv->getRcc();
        $form->list_limit = $userinv->getListLimit();
        $form->user = $userinv->getUser();
        return $form;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function getAddress1(): ?string
    {
        return $this->address_1;
    }

    public function getAddress2(): ?string
    {
        return $this->address_2;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function getVatId(): ?string
    {
        return $this->vat_id;
    }

    public function getTaxCode(): ?string
    {
        return $this->tax_code;
    }

    public function getAllClients(): ?bool
    {
        return $this->all_clients;
    }

    public function getSubscribernumber(): ?string
    {
        return $this->subscribernumber;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function getGln(): ?int
    {
        return $this->gln;
    }

    public function getRcc(): ?string
    {
        return $this->rcc;
    }

    public function getListLimit(): ?int
    {
        return $this->list_limit;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
