<?php

declare(strict_types=1);

namespace App\Invoice\PostalAddress;

use App\Invoice\Entity\PostalAddress;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Validator\Rule\Required;

final class PostalAddressForm extends FormModel
{
    private ?int    $id = null;
    #[Required]
    private ?int    $client_id = null;
    #[Required]
    private ?string $street_name = '';
    #[Required]
    private ?string $additional_street_name = '';

    private ?string $building_number = '';
    #[Required]
    private ?string $city_name = '';
    #[Required]
    private ?string $postalzone = '';
    #[Required]
    private ?string $countrysubentity = '';
    #[Required]
    private ?string $country = '';

    private Translator $translator;

    public function __construct(Translator $translator, PostalAddress $postalAddress, int $client_id)
    {
        $this->translator = $translator;

        // two hidden fields with ->hideLabel(true) in the view
        $this->id = (int)$postalAddress->getId();
        $this->client_id = $client_id;

        // not hidden fields
        $this->street_name = $postalAddress->getStreet_name();
        $this->additional_street_name = $postalAddress->getAdditional_street_name();
        $this->building_number = $postalAddress->getBuilding_number();
        $this->city_name = $postalAddress->getCity_name();
        $this->postalzone = $postalAddress->getPostalzone();
        $this->countrysubentity = $postalAddress->getCountrysubentity();
        $this->country = $postalAddress->getCountry();
    }

    public function getPropertyLabels(): array
    {
        return [
            'street_name' => $this->translator->translate('invoice.client.postaladdress.street.name'),
            'additional_street_name' => $this->translator->translate('invoice.client.postaladdress.additional.street.name'),
            'building_number' => $this->translator->translate('invoice.client.postaladdress.building.number'),
            'city_name' => $this->translator->translate('invoice.client.postaladdress.city.name'),
            'postalzone' => $this->translator->translate('invoice.client.postaladdress.postalzone'),
            'countrysubentity' => $this->translator->translate('invoice.client.postaladdress.countrysubentity'),
            'country' => $this->translator->translate('invoice.client.postaladdress.country')
        ];
    }

    public function getPropertyHints(): array
    {
        $required = 'invoice.hint.this.field.is.required';
        $not_required = 'invoice.hint.this.field.is.not.required';
        return [
            'street_name' => $this->translator->translate($required),
            'additional_street_name' => $this->translator->translate($required),
            'building_number' => $this->translator->translate($not_required),
            'city_name' => $this->translator->translate($required),
            'postalzone' => $this->translator->translate($required),
            'countrysubentity' => $this->translator->translate($required),
            'country' => $this->translator->translate($required)
        ];
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getClient_id(): int|null
    {
        return $this->client_id;
    }

    public function getStreet_name(): string|null
    {
        return $this->street_name;
    }

    public function getAdditional_street_name(): string|null
    {
        return $this->additional_street_name;
    }

    public function getBuilding_number(): string|null
    {
        return $this->building_number;
    }

    public function getCity_name(): string|null
    {
        return $this->city_name;
    }

    public function getPostalzone(): string|null
    {
        return $this->postalzone;
    }

    public function getCountrysubentity(): string|null
    {
        return $this->countrysubentity;
    }

    public function getCountry(): string|null
    {
        return $this->country;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    public function getFormName(): string
    {
        return '';
    }
}
