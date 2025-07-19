<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Invoice\Entity\Client;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository;

final readonly class ClientService
{
    public function __construct(private ClientRepository $repository) {}

    /**
     * @param Client $model
     * @param array $body
     * @param SettingRepository $s
     * @return int|null
     */
    public function saveClient(Client $model, array $body, SettingRepository $s): int|null
    {
        $datehelper = new DateHelper($s);
        isset($body['client_title']) ? $model->setClient_title((string) $body['client_title']) : '';
        isset($body['client_name']) ? $model->setClient_name((string) $body['client_name']) : '';
        isset($body['client_surname']) ? $model->setClient_surname((string) $body['client_surname']) : '';
        $model->setClient_full_name((string) $body['client_name'] . ' ' . (string) $body['client_surname']);
        isset($body['client_frequency']) ? $model->setClient_frequency((string) $body['client_frequency']) : '';
        isset($body['client_group']) ? $model->setClient_group((string) $body['client_group']) : '';
        isset($body['client_number']) ? $model->setClient_number((string) $body['client_number']) : '';
        isset($body['client_address_1']) ? $model->setClient_address_1((string) $body['client_address_1']) : '';
        isset($body['client_address_2']) ? $model->setClient_address_2((string) $body['client_address_2']) : '';
        isset($body['client_building_number']) ? $model->setClient_building_number((string) $body['client_building_number']) : '';
        isset($body['client_city']) ? $model->setClient_city((string) $body['client_city']) : '';
        isset($body['client_state']) ? $model->setClient_state((string) $body['client_state']) : '';
        isset($body['client_zip']) ? $model->setClient_zip((string) $body['client_zip']) : '';
        isset($body['client_country']) ? $model->setClient_country((string) $body['client_country']) : '';
        isset($body['client_phone']) ? $model->setClient_phone((string) $body['client_phone']) : '';
        isset($body['client_fax']) ? $model->setClient_fax((string) $body['client_fax']) : '';
        isset($body['client_mobile']) ? $model->setClient_mobile((string) $body['client_mobile']) : '';
        isset($body['client_email']) ? $model->setClient_email((string) $body['client_email']) : '';
        isset($body['client_web']) ? $model->setClient_web((string) $body['client_web']) : '';
        isset($body['client_vat_id']) ? $model->setClient_vat_id((string) $body['client_vat_id']) : '';
        isset($body['client_tax_code']) ? $model->setClient_tax_code((string) $body['client_tax_code']) : '';
        isset($body['client_language']) ? $model->setClient_language((string) $body['client_language']) : '';
        $model->setClient_active($body['client_active'] === '1' ? true : false);
        isset($body['client_avs']) ? $model->setClient_avs((string) $body['client_avs']) : '';
        isset($body['client_insurednumber']) ? $model->setClient_insurednumber((string) $body['client_insurednumber']) : '';
        isset($body['client_veka']) ? $model->setClient_veka((string) $body['client_veka']) : '';

        $datetime = new \DateTime();
        isset($body['client_birthdate']) ? $model->setClient_birthdate($datetime::createFromFormat('Y-m-d', (string) $body['client_birthdate']) ?: $datetime) : '';

        isset($body['client_age']) ? $model->setClient_age((int) $body['client_age']) : '';
        isset($body['client_gender']) ? $model->setClient_gender((int) $body['client_gender']) : '';
        isset($body['postaladdress_id']) ? $model->setPostaladdress_id((int) $body['postaladdress_id']) : '';
        if ($model->isNewRecord()) {
            $model->setClient_active(true);
            $model->setPostaladdress_id(0);
        }
        $this->repository->save($model);
        return $model->getClient_id();
    }

    /**
     * @param array|Client|null $model
     */
    public function deleteClient(array|Client|null $model): void
    {
        $this->repository->delete($model);
    }
}
