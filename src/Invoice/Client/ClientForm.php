<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Invoice\Entity\Client;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Url;
use DateTimeImmutable;

final class ClientForm extends FormModel
{
    #[Length(min: 0, max: 10, skipOnEmpty: true)]
    private ?string $client_title = '';
    #[Required]
    #[Length(min: 0, max: 50)]
    private ?string $client_name = '';
    #[Length(min: 0, max: 3, skipOnEmpty: true)]
    private ?string $client_group = '';
    #[Length(min: 0, max: 15, skipOnEmpty: true)]
    private ?string $client_frequency = '';
    #[Length(min: 0, max: 12, skipOnEmpty: true)]
    private ?string $client_number = '';
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $client_address_1 = '';
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $client_address_2 = '';
    #[Length(min: 0, max: 10, skipOnEmpty: true)]
    private ?string $client_building_number = '';
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    private ?string $client_city = '';
    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    private ?string $client_state = '';
    #[Length(min: 0, max: 10, skipOnEmpty: true)]
    private ?string $client_zip = '';
    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    private ?string $client_country = '';
    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    private ?string $client_phone = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    private ?string $client_fax = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    private ?string $client_mobile = '';
    #[Required]
    #[Email()]
    #[Length(min: 0, max: 254)]
    private ?string $client_email = '';
    #[Url()]
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    private ?string $client_web = '';
    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    private ?string $client_vat_id = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    private ?string $client_tax_code = '';
    #[Length(min: 0, max: 151, skipOnEmpty: true)]
    private ?string $client_language = '';
    private ?bool $client_active = false;
    #[Length(min: 0, max: 151, skipOnEmpty: true)]
    private ?string $client_surname = '';
    #[Length(min: 0, max: 16, skipOnEmpty: true)]
    private ?string $client_avs = '';
    #[Length(min: 0, max: 151, skipOnEmpty: true)]
    private ?string $client_insurednumber = '';
    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    private ?string $client_veka = '';
    private readonly mixed $client_birthdate;

    #[Required]
    #[Integer(min: 16, max: 100)]
    private ?int $client_age = null;

    private ?int $client_gender = null;
    private ?int $postaladdress_id = null;

    public function __construct(Client $client)
    {
        $this->client_title = $client->getClient_title();
        $this->client_name = $client->getClient_name();
        $this->client_group = $client->getClient_group();
        $this->client_frequency = $client->getClient_frequency();
        $this->client_number = $client->getClient_number();
        $this->client_address_1 = $client->getClient_address_1();
        $this->client_address_2 = $client->getClient_address_2();
        $this->client_building_number = $client->getClient_building_number();
        $this->client_city = $client->getClient_city();
        $this->client_state = $client->getClient_state();
        $this->client_zip = $client->getClient_zip();
        $this->client_country = $client->getClient_country();
        $this->client_phone = $client->getClient_phone();
        $this->client_fax = $client->getClient_fax();
        $this->client_mobile = $client->getClient_mobile();
        $this->client_email = $client->getClient_email();
        $this->client_web = $client->getClient_web();
        $this->client_vat_id = $client->getClient_vat_id();
        $this->client_tax_code = $client->getClient_tax_code();
        $this->client_language = $client->getClient_language();
        $this->client_active = $client->getClient_active();
        $this->client_surname = $client->getClient_surname();
        $this->client_avs = $client->getClient_avs();
        $this->client_insurednumber = $client->getClient_insurednumber();
        $this->client_veka = $client->getClient_veka();
        $this->client_birthdate = $client->getClient_birthdate();
        $this->client_age = $client->getClient_age();
        $this->client_gender = $client->getClient_gender();
        //$this->postaladdress_id = $client->getPostaladdress_id();
    }

    public function getAttributeLabels(): array
    {
        return [];
    }

    public function getClient_active(): bool|null
    {
        return $this->client_active;
    }

    public function getClient_age(): int|null
    {
        return $this->client_age;
    }

    public function getClient_title(): string|null
    {
        return $this->client_title;
    }

    public function getClient_name(): string|null
    {
        return $this->client_name;
    }

    public function getClient_frequency(): string|null
    {
        return $this->client_frequency;
    }

    public function getClient_group(): string|null
    {
        return $this->client_group;
    }

    public function getClient_number(): string|null
    {
        return $this->client_number;
    }

    public function getClient_address_1(): string|null
    {
        return $this->client_address_1;
    }

    public function getClient_address_2(): string|null
    {
        return $this->client_address_2;
    }

    public function getClient_building_number(): string|null
    {
        return $this->client_building_number;
    }

    public function getClient_city(): string|null
    {
        return $this->client_city;
    }

    public function getClient_state(): string|null
    {
        return $this->client_state;
    }

    public function getClient_zip(): string|null
    {
        return $this->client_zip;
    }

    public function getClient_country(): string|null
    {
        return $this->client_country;
    }

    public function getClient_phone(): string|null
    {
        return $this->client_phone;
    }

    public function getClient_fax(): string|null
    {
        return $this->client_fax;
    }

    public function getClient_mobile(): string|null
    {
        return $this->client_mobile;
    }

    public function getClient_email(): string|null
    {
        return $this->client_email;
    }

    public function getClient_web(): string|null
    {
        return $this->client_web;
    }

    public function getClient_vat_id(): string|null
    {
        return $this->client_vat_id;
    }

    public function getClient_tax_code(): string|null
    {
        return $this->client_tax_code;
    }

    public function getClient_language(): string|null
    {
        return $this->client_language;
    }

    public function getClient_surname(): string|null
    {
        return $this->client_surname;
    }

    public function getClient_avs(): string|null
    {
        return $this->client_avs;
    }

    public function getClient_insurednumber(): string|null
    {
        return $this->client_insurednumber;
    }

    public function getClient_veka(): string|null
    {
        return $this->client_veka;
    }

    public function getClient_birthdate(): string|null|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string|null $this->client_birthdate
         */
        return $this->client_birthdate;
    }

    public function getClient_gender(): int|null
    {
        return $this->client_gender;
    }

    public function getPostaladdress_id(): int|null
    {
        return $this->postaladdress_id;
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
