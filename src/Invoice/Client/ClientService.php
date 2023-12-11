<?php

declare(strict_types=1); 

namespace App\Invoice\Client;

use App\Invoice\Entity\Client;
use App\Invoice\Helpers\DateHelper;

use App\Invoice\Setting\SettingRepository;

final class ClientService
{
    private ClientRepository $repository;

    public function __construct(ClientRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param Client $model
     * @param array $body
     * @param SettingRepository $s
     * @return int|null
     */
    public function saveClient(Client $model, array $body, SettingRepository $s): int|null
    {
        $datehelper = new DateHelper($s);
        null!==$body['client_name'] ? $model->setClient_name((string)$body['client_name']) : '';
        null!==$body['client_surname'] ? $model->setClient_surname((string)$body['client_surname']): '';
        null!==$body['client_number'] ? $model->setClient_number((string)$body['client_number']): '';
        null!==$body['client_address_1'] ? $model->setClient_address_1((string)$body['client_address_1']): '';
        null!==$body['client_address_2'] ? $model->setClient_address_2((string)$body['client_address_2']): '';
        null!==$body['client_building_number'] ? $model->setClient_building_number((string)$body['client_building_number']): '';
        null!==$body['client_city'] ? $model->setClient_city((string)$body['client_city']): '';
        null!==$body['client_state'] ? $model->setClient_state((string)$body['client_state']): '';
        null!==$body['client_zip'] ? $model->setClient_zip((string)$body['client_zip']): '';
        null!==$body['client_country'] ? $model->setClient_country((string)$body['client_country']): '';
        null!==$body['client_phone'] ? $model->setClient_phone((string)$body['client_phone']): '';
        null!==$body['client_fax'] ? $model->setClient_fax((string)$body['client_fax']): '';
        null!==$body['client_mobile'] ? $model->setClient_mobile((string)$body['client_mobile']): '';
        null!==$body['client_email'] ? $model->setClient_email((string)$body['client_email']): '';
        null!==$body['client_web'] ? $model->setClient_web((string)$body['client_web']): '';
        null!==$body['client_vat_id'] ? $model->setClient_vat_id((string)$body['client_vat_id']): '';
        null!==$body['client_tax_code'] ? $model->setClient_tax_code((string)$body['client_tax_code']): '';
        null!==$body['client_language'] ? $model->setClient_language((string)$body['client_language']): '';
        $model->setClient_active($body['client_active'] === '0' ? true : false);
        null!==$body['client_avs'] ? $model->setClient_avs((string)$body['client_avs']): '';
        null!==$body['client_insurednumber'] ? $model->setClient_insurednumber((string)$body['client_insurednumber']): '';
        null!==$body['client_veka'] ? $model->setClient_veka((string)$body['client_veka']): '';
        
        $datetime = new \DateTime();
        $body['client_birthdate'] ? $model->setClient_birthdate($datetime::createFromFormat($datehelper->style(),(string)$body['client_birthdate'])) : '';
        
        null!==$body['client_age'] ? $model->setClient_age((int)$body['client_age']) : '';
        null!==$body['client_gender'] ? $model->setClient_gender((int)$body['client_gender']): '';
        //$model->setPostaladdress_id((int)$body['postaladdress_id'] ?: 0);
        if ($model->isNewRecord()) {
            $model->setClient_active(true);
            $model->setPostaladdress_id(0);
        }
        $this->repository->save($model);
        return $model->getClient_id();
    }
    
    /**
     * 
     * @param array|Client|null $model
     * @return void
     */
    public function deleteClient(array|Client|null $model): void
    {
        $this->repository->delete($model);
    }
    
    public function getClient_birthdate(\App\Invoice\Setting\SettingRepository $s, string $date_string) : \DateTime|null
    {
        $datehelper = new DateHelper($s);         
        $datetime = new \DateTime();
        $datetime->setTimezone(new \DateTimeZone($s->get_setting('time_zone') ?: 'Europe/London')); 
        $datetime->format($datehelper->style());
        if (!empty($date_string)) { 
            $date = $datehelper->date_to_mysql($date_string);
            $str_replace = str_replace($datehelper->separator(), '-', $date);
            $datetime->modify($str_replace);
            return $datetime;        
        }
        return null;
    }
}