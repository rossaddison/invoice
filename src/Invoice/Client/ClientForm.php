<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Invoice\Entity\Client;
use DateTimeImmutable;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Required;

final class ClientForm extends FormModel
{
    private ?string $client_title           = '';
    private ?string $client_name            = '';
    private ?string $client_group           = '';
    private ?string $client_frequency       = '';
    private ?string $client_number          = '';
    private ?string $client_address_1       = '';
    private ?string $client_address_2       = '';
    private ?string $client_building_number = '';
    private ?string $client_city            = '';
    private ?string $client_state           = '';
    private ?string $client_zip             = '';
    private ?string $client_country         = '';
    private ?string $client_phone           = '';
    private ?string $client_fax             = '';
    private ?string $client_mobile          = '';
    private ?string $client_email           = '';
    private ?string $client_web             = '';
    private ?string $client_vat_id          = '';
    private ?string $client_tax_code        = '';
    private ?string $client_language        = '';
    private ?bool $client_active            = false;
    private ?string $client_surname         = '';
    private ?string $client_avs             = '';
    private ?string $client_insurednumber   = '';
    private ?string $client_veka            = '';
    private readonly mixed $client_birthdate;

    #[Required]
    #[Integer(min: 16, max: 100)]
    private ?int $client_age = null;

    private ?int $client_gender    = null;
    private ?int $postaladdress_id = null;

    public function __construct(Client $client)
    {
        $this->client_title           = $client->getClient_title();
        $this->client_name            = $client->getClient_name();
        $this->client_group           = $client->getClient_group();
        $this->client_frequency       = $client->getClient_frequency();
        $this->client_number          = $client->getClient_number();
        $this->client_address_1       = $client->getClient_address_1();
        $this->client_address_2       = $client->getClient_address_2();
        $this->client_building_number = $client->getClient_building_number();
        $this->client_city            = $client->getClient_city();
        $this->client_state           = $client->getClient_state();
        $this->client_zip             = $client->getClient_zip();
        $this->client_country         = $client->getClient_country();
        $this->client_phone           = $client->getClient_phone();
        $this->client_fax             = $client->getClient_fax();
        $this->client_mobile          = $client->getClient_mobile();
        $this->client_email           = $client->getClient_email();
        $this->client_web             = $client->getClient_web();
        $this->client_vat_id          = $client->getClient_vat_id();
        $this->client_tax_code        = $client->getClient_tax_code();
        $this->client_language        = $client->getClient_language();
        $this->client_active          = $client->getClient_active();
        $this->client_surname         = $client->getClient_surname();
        $this->client_avs             = $client->getClient_avs();
        $this->client_insurednumber   = $client->getClient_insurednumber();
        $this->client_veka            = $client->getClient_veka();
        $this->client_birthdate       = $client->getClient_birthdate();
        $this->client_age             = $client->getClient_age();
        $this->client_gender          = $client->getClient_gender();
        // $this->postaladdress_id = $client->getPostaladdress_id();
    }

    public function getAttributeLabels(): array
    {
        return [];
    }

    public function getClient_active(): ?bool
    {
        return $this->client_active;
    }

    public function getClient_age(): ?int
    {
        return $this->client_age;
    }

    public function getClient_title(): ?string
    {
        return $this->client_title;
    }

    public function getClient_name(): ?string
    {
        return $this->client_name;
    }

    public function getClient_frequency(): ?string
    {
        return $this->client_frequency;
    }

    public function getClient_group(): ?string
    {
        return $this->client_group;
    }

    public function getClient_number(): ?string
    {
        return $this->client_number;
    }

    public function getClient_address_1(): ?string
    {
        return $this->client_address_1;
    }

    public function getClient_address_2(): ?string
    {
        return $this->client_address_2;
    }

    public function getClient_building_number(): ?string
    {
        return $this->client_building_number;
    }

    public function getClient_city(): ?string
    {
        return $this->client_city;
    }

    public function getClient_state(): ?string
    {
        return $this->client_state;
    }

    public function getClient_zip(): ?string
    {
        return $this->client_zip;
    }

    public function getClient_country(): ?string
    {
        return $this->client_country;
    }

    public function getClient_phone(): ?string
    {
        return $this->client_phone;
    }

    public function getClient_fax(): ?string
    {
        return $this->client_fax;
    }

    public function getClient_mobile(): ?string
    {
        return $this->client_mobile;
    }

    public function getClient_email(): ?string
    {
        return $this->client_email;
    }

    public function getClient_web(): ?string
    {
        return $this->client_web;
    }

    public function getClient_vat_id(): ?string
    {
        return $this->client_vat_id;
    }

    public function getClient_tax_code(): ?string
    {
        return $this->client_tax_code;
    }

    public function getClient_language(): ?string
    {
        return $this->client_language;
    }

    public function getClient_surname(): ?string
    {
        return $this->client_surname;
    }

    public function getClient_avs(): ?string
    {
        return $this->client_avs;
    }

    public function getClient_insurednumber(): ?string
    {
        return $this->client_insurednumber;
    }

    public function getClient_veka(): ?string
    {
        return $this->client_veka;
    }

    public function getClient_birthdate(): string|\DateTimeImmutable|null
    {
        /*
         * @var DateTimeImmutable|string|null $this->client_birthdate
         */
        return $this->client_birthdate;
    }

    public function getClient_gender(): ?int
    {
        return $this->client_gender;
    }

    public function getPostaladdress_id(): ?int
    {
        return $this->postaladdress_id;
    }

    /**
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
