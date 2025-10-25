<?php

declare(strict_types=1);

namespace App\Invoice\Company;

use App\Invoice\Entity\Company;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Url;

final class CompanyForm extends FormModel
{
    private ?int $id = null;

    #[Integer(min: 0, max: 1)]
    private ?int $current = 0;

    #[Required]
    #[Length(min: 1, max: 255)]
    private ?string $name = '';

    #[Length(min: 0, max: 255)]
    private ?string $address_1 = '';

    #[Length(min: 0, max: 255)]
    private ?string $address_2 = '';

    #[Length(min: 0, max: 100)]
    private ?string $city = '';

    #[Length(min: 0, max: 100)]
    private ?string $state = '';

    #[Length(min: 0, max: 20)]
    private ?string $zip = '';

    #[Length(min: 0, max: 100)]
    private ?string $country = '';

    #[Length(min: 0, max: 20)]
    private ?string $phone = '';

    #[Length(min: 0, max: 20)]
    private ?string $fax = '';

    #[Required]
    #[Email()]
    #[Length(min: 1, max: 255)]
    private ?string $email = '';

    #[Url()]
    #[Length(min: 0, max: 255)]
    private ?string $web = '';

    #[Length(min: 0, max: 255)]
    private ?string $slack = '';

    #[Length(min: 0, max: 255)]
    private ?string $facebook = '';

    #[Length(min: 0, max: 255)]
    private ?string $twitter = '';

    #[Length(min: 0, max: 255)]
    private ?string $linkedin = '';

    #[Length(min: 0, max: 255)]
    private ?string $whatsapp = '';

    #[Length(min: 0, max: 255)]
    private ?string $arbitrationBody = '';

    #[Length(min: 0, max: 255)]
    private ?string $arbitrationJurisdiction = '';

    public function __construct(Company $company)
    {
        $this->id = $company->getId();
        $this->current = $company->getCurrent();
        $this->name = $company->getName();
        $this->address_1 = $company->getAddress_1();
        $this->address_2 = $company->getAddress_2();
        $this->city = $company->getCity();
        $this->state = $company->getState();
        $this->zip = $company->getZip();
        $this->country = $company->getCountry();
        $this->phone = $company->getPhone();
        $this->fax = $company->getFax();
        $this->email = $company->getEmail();
        $this->web = $company->getWeb();
        $this->slack = $company->getSlack();
        $this->facebook = $company->getFacebook();
        $this->twitter = $company->getTwitter();
        $this->linkedin = $company->getLinkedIn();
        $this->whatsapp = $company->getWhatsapp();
        $this->arbitrationBody = $company->getArbitrationBody();
        $this->arbitrationJurisdiction = $company->getArbitrationJurisdiction();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getCurrent(): int|null
    {
        return $this->current;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function getAddress_1(): string|null
    {
        return $this->address_1;
    }

    public function getAddress_2(): string|null
    {
        return $this->address_2;
    }

    public function getCity(): string|null
    {
        return $this->city;
    }

    public function getState(): string|null
    {
        return $this->state;
    }

    public function getZip(): string|null
    {
        return $this->zip;
    }

    public function getCountry(): string|null
    {
        return $this->country;
    }

    public function getPhone(): string|null
    {
        return $this->phone;
    }

    public function getFax(): string|null
    {
        return $this->fax;
    }

    public function getEmail(): string|null
    {
        return $this->email;
    }

    public function getWeb(): string|null
    {
        return $this->web;
    }

    public function getSlack(): string|null
    {
        return $this->slack;
    }

    public function getFacebook(): string|null
    {
        return $this->facebook;
    }

    public function getTwitter(): string|null
    {
        return $this->twitter;
    }

    public function getLinkedin(): string|null
    {
        return $this->linkedin;
    }

    public function getWhatsapp(): string|null
    {
        return $this->whatsapp;
    }

    public function getArbitrationBody(): string|null
    {
        return $this->arbitrationBody;
    }

    public function getArbitrationJurisdiction(): string|null
    {
        return $this->arbitrationJurisdiction;
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
