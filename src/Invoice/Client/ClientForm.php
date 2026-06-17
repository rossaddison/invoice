<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Infrastructure\Persistence\Client\Client;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class ClientForm extends FormModel
{
    #[Length(min: 0, max: 10, skipOnEmpty: true)]
    public ?string $client_title = '';
    #[Required]
    #[Length(min: 0, max: 50)]
    public ?string $client_name = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    public ?string $client_group = '';
    #[Length(min: 0, max: 15, skipOnEmpty: true)]
    public ?string $client_frequency = '';
    #[Length(min: 0, max: 12, skipOnEmpty: true)]
    public ?string $client_number = '';
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $client_address_1 = '';
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $client_address_2 = '';
    #[Length(min: 0, max: 10, skipOnEmpty: true)]
    public ?string $client_building_number = '';
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $client_city = '';
    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    public ?string $client_state = '';
    #[Length(min: 0, max: 10, skipOnEmpty: true)]
    public ?string $client_zip = '';
    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    public ?string $client_country = '';
    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    public ?string $client_phone = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    public ?string $client_fax = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    public ?string $client_mobile = '';
    #[Required]
    #[Email()]
    #[Length(min: 0, max: 254, skipOnEmpty: true)]
    public ?string $client_email = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    public ?string $client_web = '';
    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    public ?string $client_vat_id = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    public ?string $client_tax_code = '';
    #[Length(min: 0, max: 151, skipOnEmpty: true)]
    public ?string $client_language = '';
    public ?bool $client_active = false;
    #[Length(min: 0, max: 151, skipOnEmpty: true)]
    public ?string $client_surname = '';
    public ?string $client_birthdate = null;

    #[Required]
    #[Integer(min: 16, max: 100)]
    public ?int $client_age = null;

    public ?int $client_gender = null;
    public ?int $postaladdress_id = null;

    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    public ?string $client_telegram_chat_id = null;

    public static function show(Client $client): self
    {
        $form = new self();
        $form->client_title = $client->getClientTitle();
        $form->client_name = $client->getClientName();
        $form->client_group = $client->getClientGroup();
        $form->client_frequency = $client->getClientFrequency();
        $form->client_number = $client->getClientNumber();
        $form->client_address_1 = $client->getClientAddress1();
        $form->client_address_2 = $client->getClientAddress2();
        $form->client_building_number = $client->getClientBuildingNumber();
        $form->client_city = $client->getClientCity();
        $form->client_state = $client->getClientState();
        $form->client_zip = $client->getClientZip();
        $form->client_country = $client->getClientCountry();
        $form->client_phone = $client->getClientPhone();
        $form->client_fax = $client->getClientFax();
        $form->client_mobile = $client->getClientMobile();
        $form->client_email = $client->getClientEmail();
        $form->client_web = $client->getClientWeb();
        $form->client_vat_id = $client->getClientVatId();
        $form->client_tax_code = $client->getClientTaxCode();
        $form->client_language = $client->getClientLanguage();
        $form->client_active = $client->getClientActive();
        $form->client_surname = $client->getClientSurname();
        $birthdate = $client->getClientBirthdate();
        $form->client_birthdate = $birthdate instanceof DateTimeImmutable
            ? $birthdate->format('Y-m-d')
            : null;
        $form->client_age = $client->getClientAge();
        $form->client_gender = $client->getClientGender();
        $form->client_telegram_chat_id = $client->getClientTelegramChatId();
        return $form;
    }

    public function getAttributeLabels(): array
    {
        return [];
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
