<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\User\User;
use App\Invoice\Entity\UserInv;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class UserInvForm extends FormModel
{
    #[Required]
    private ?int $user_id = null;

    /**
     * Related logic: see Dropdown 0 = Admin, 1 = Not Admin i.e. User with viewInv permission (not editInv Permission)
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
    private ?int $listLimit = 10;

    private readonly ?User $user;

    public function __construct(UserInv $userinv)
    {
        $this->user_id = (int) $userinv->getUser_id();
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
        $this->web = $userinv->getWeb();
        $this->vat_id = $userinv->getVat_id();
        $this->tax_code = $userinv->getTax_code();
        $this->all_clients = $userinv->getAll_clients();
        $this->subscribernumber = $userinv->getSubscribernumber();
        $this->iban = $userinv->getIban();
        $this->gln = $userinv->getGln();
        $this->rcc = $userinv->getRcc();
        $this->listLimit = $userinv->getListLimit();
        $this->user = $userinv->getUser();
    }

    public function getUser_id(): ?int
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

    public function getAddress_1(): ?string
    {
        return $this->address_1;
    }

    public function getAddress_2(): ?string
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

    public function getVat_id(): ?string
    {
        return $this->vat_id;
    }

    public function getTax_code(): ?string
    {
        return $this->tax_code;
    }

    public function getAll_clients(): ?bool
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
        return $this->listLimit;
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
