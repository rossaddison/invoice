<?php

declare(strict_types=1);

namespace App\Invoice\Company;

use App\Infrastructure\Persistence\Company\Company;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Url;

final class CompanyForm extends FormModel
{
    #[Integer(min: 0, max: 1)]
    private ?int $current = 0;

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $name = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $address_1 = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $address_2 = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $city = '';

    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    private ?string $state = '';

    #[Length(min: 0, max: 10, skipOnEmpty: true)]
    private ?string $zip = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $country = '';

    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    private ?string $phone = '';

    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    private ?string $fax = '';

    #[Email()]
    #[Length(min: 0, max: 254, skipOnEmpty: true)]
    private ?string $email = '';

    #[Url()]
    #[Length(min: 0, max: 255, skipOnEmpty: true)]
    private ?string $web = '';

    #[Length(min: 0, max: 255, skipOnEmpty: true)]
    private ?string $seo_description = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $slack = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $facebook = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $twitter = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $linkedin = '';

    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    private ?string $whatsapp = '';

    #[Length(min: 0, max: 200, skipOnEmpty: true)]
    private ?string $arbitration_body = '';

    #[Length(min: 0, max: 200, skipOnEmpty: true)]
    private ?string $arbitration_jurisdiction = '';

    public static function show(Company $company): self
    {
        $form = new self();
        $form->current = $company->getCurrent();
        $form->name = $company->getName();
        $form->address_1 = $company->getAddress1();
        $form->address_2 = $company->getAddress2();
        $form->city = $company->getCity();
        $form->state = $company->getState();
        $form->zip = $company->getZip();
        $form->country = $company->getCountry();
        $form->phone = $company->getPhone();
        $form->fax = $company->getFax();
        $form->email = $company->getEmail();
        $form->web = $company->getWeb();
        $form->seo_description = $company->getSeoDescription();
        $form->slack = $company->getSlack();
        $form->facebook = $company->getFacebook();
        $form->twitter = $company->getTwitter();
        $form->linkedin = $company->getLinkedIn();
        $form->whatsapp = $company->getWhatsapp();
        $form->arbitration_body = $company->getArbitrationBody();
        $form->arbitration_jurisdiction = $company->getArbitrationJurisdiction();
        return $form;
    }

    public function getCurrent(): ?int
    {
        return $this->current;
    }

    public function getName(): ?string
    {
        return $this->name;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seo_description;
    }

    public function getSlack(): ?string
    {
        return $this->slack;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function getTwitter(): ?string
    {
        return $this->twitter;
    }

    public function getLinkedin(): ?string
    {
        return $this->linkedin;
    }

    public function getWhatsapp(): ?string
    {
        return $this->whatsapp;
    }

    public function getArbitrationBody(): ?string
    {
        return $this->arbitration_body;
    }

    public function getArbitrationJurisdiction(): ?string
    {
        return $this->arbitration_jurisdiction;
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
