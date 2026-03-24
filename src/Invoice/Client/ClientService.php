<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Invoice\Entity\Client;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository;

final readonly class ClientService
{
    public function __construct(private ClientRepository $repository)
    {
    }

    /**
     * @param Client $model
     * @param array $body
     * @param SettingRepository $s
     * @return int|null
     */
    public function saveClient(Client $model, array $body, SettingRepository $s): ?int
    {
        $datehelper = new DateHelper($s);
        isset($body['client_title']) ? $model->setClientTitle((string) $body['client_title']) : '';
        isset($body['client_name']) ? $model->setClientName((string) $body['client_name']) : '';
        isset($body['client_surname']) ? $model->setClientSurname((string) $body['client_surname']) : '';
        $model->setClientFullName((string) $body['client_name'] . ' ' . (string) $body['client_surname']);
        isset($body['client_frequency']) ? $model->setClientFrequency((string) $body['client_frequency']) : '';
        isset($body['client_group']) ? $model->setClientGroup((string) $body['client_group']) : '';
        isset($body['client_number']) ? $model->setClientNumber((string) $body['client_number']) : '';
        isset($body['client_address_1']) ? $model->setClientAddress1((string) $body['client_address_1']) : '';
        isset($body['client_address_2']) ? $model->setClientAddress2((string) $body['client_address_2']) : '';
        isset($body['client_building_number']) ? $model->setClientBuildingNumber((string) $body['client_building_number']) : '';
        isset($body['client_city']) ? $model->setClientCity((string) $body['client_city']) : '';
        isset($body['client_state']) ? $model->setClientState((string) $body['client_state']) : '';
        isset($body['client_zip']) ? $model->setClientZip((string) $body['client_zip']) : '';
        isset($body['client_country']) ? $model->setClientCountry((string) $body['client_country']) : '';
        isset($body['client_phone']) ? $model->setClientPhone((string) $body['client_phone']) : '';
        isset($body['client_fax']) ? $model->setClientFax((string) $body['client_fax']) : '';
        isset($body['client_mobile']) ? $model->setClientMobile((string) $body['client_mobile']) : '';
        isset($body['client_email']) ? $model->setClientEmail((string) $body['client_email']) : '';
        isset($body['client_web']) ? $model->setClientWeb((string) $body['client_web']) : '';
        isset($body['client_vat_id']) ? $model->setClientVatId((string) $body['client_vat_id']) : '';
        isset($body['client_tax_code']) ? $model->setClientTaxCode((string) $body['client_tax_code']) : '';
        isset($body['client_language']) ? $model->setClientLanguage((string) $body['client_language']) : '';
        $model->setClientActive($body['client_active'] === '1' ? true : false);
        $datetime = new \DateTime();
        isset($body['client_birthdate']) ? $model->setClientBirthdate($datetime::createFromFormat('Y-m-d', (string) $body['client_birthdate']) ?: $datetime) : '';

        isset($body['client_age']) ? $model->setClientAge((int) $body['client_age']) : '';
        isset($body['client_gender']) ? $model->setClientGender((int) $body['client_gender']) : '';
        isset($body['postaladdress_id']) ? $model->setPostaladdressId((int) $body['postaladdress_id']) : '';
        if ($model->isNewRecord()) {
            $model->setClientActive(true);
            $model->setPostaladdressId(0);
        }
        $this->repository->save($model);
        return $model->getClientId();
    }

    /**
     * @param array|Client|null $model
     */
    public function deleteClient(array|Client|null $model): void
    {
        $this->repository->delete($model);
    }
}
