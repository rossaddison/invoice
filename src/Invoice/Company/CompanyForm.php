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
    public ?int $current = 0;

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $name = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $address_1 = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $address_2 = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $city = '';

    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    public ?string $state = '';

    #[Length(min: 0, max: 10, skipOnEmpty: true)]
    public ?string $zip = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $country = '';

    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    public ?string $phone = '';

    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    public ?string $fax = '';

    #[Email()]
    #[Length(min: 0, max: 254, skipOnEmpty: true)]
    public ?string $email = '';

    #[Url()]
    #[Length(min: 0, max: 255, skipOnEmpty: true)]
    public ?string $web = '';

    #[Length(min: 0, max: 255, skipOnEmpty: true)]
    public ?string $seo_description = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $slack = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $facebook = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $twitter = '';

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $linkedin = '';

    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    public ?string $whatsapp = '';

    #[Length(min: 0, max: 200, skipOnEmpty: true)]
    public ?string $arbitration_body = '';

    #[Length(min: 0, max: 200, skipOnEmpty: true)]
    public ?string $arbitration_jurisdiction = '';

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
