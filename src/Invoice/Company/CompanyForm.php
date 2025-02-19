<?php

declare(strict_types=1);

namespace App\Invoice\Company;

use App\Invoice\Entity\Company;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CompanyForm extends FormModel
{
    private ?int $id = null;
    private ?int $current = 0;

    #[Required]
    private ?string $name = '';

    private ?string $address_1 = '';
    private ?string $address_2 = '';
    private ?string $city = '';
    private ?string $state = '';
    private ?string $zip = '';
    private ?string $country = '';
    private ?string $phone = '';
    private ?string $fax = '';

    #[Required]
    private ?string $email = '';

    private ?string $web = '';

    private ?string $arbitrationBody = '';

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
