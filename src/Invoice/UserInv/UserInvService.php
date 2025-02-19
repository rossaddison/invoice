<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Invoice\Entity\UserInv;

final readonly class UserInvService
{
    public function __construct(private UserInvRepository $repository)
    {
    }

    /**
     * @param UserInv $model
     * @param array $array
     */
    public function saveUserInv(UserInv $model, array $array): void
    {
        $model->setUser_id((int)$array['user_id']);
        isset($array['type']) ? $model->setType((int)$array['type']) : '';
        $model->setActive($array['active'] === '1') ? true : false;
        isset($array['language']) ? $model->setLanguage((string)$array['language']) : '';
        $model->setAll_clients($array['all_clients'] === '1') ? true : false;
        isset($array['name']) ? $model->setName((string)$array['name']) : '';
        isset($array['company']) ? $model->setCompany((string)$array['company']) : '';
        isset($array['address_1']) ? $model->setAddress_1((string)$array['address_1']) : '';
        isset($array['address_2 ']) ? $model->setAddress_2((string)$array['address_2']) : '';
        isset($array['city']) ? $model->setCity((string)$array['city']) : '';
        isset($array['state']) ? $model->setState((string)$array['state']) : '';
        isset($array['zip']) ? $model->setZip((string)$array['zip']) : '';
        isset($array['country']) ? $model->setCountry((string)$array['country']) : '';
        isset($array['phone']) ? $model->setPhone((string)$array['phone']) : '';
        isset($array['fax']) ? $model->setFax((string)$array['fax']) : '';
        isset($array['mobile']) ? $model->setMobile((string)$array['mobile']) : '';
        isset($array['web']) ? $model->setWeb((string)$array['web']) : '';
        isset($array['vat_id']) ? $model->setVat_id((string)$array['vat_id']) : '';
        isset($array['tax_code']) ? $model->setTax_code((string)$array['tax_code']) : '';
        isset($array['subscribernumber']) ? $model->setSubscribernumber((string)$array['subscribernumber']) : '';
        isset($array['iban']) ? $model->setIban((string)$array['iban']) : '';
        isset($array['gln']) ? $model->setGln((int)$array['gln']) : '';
        isset($array['rcc']) ? $model->setRcc((string)$array['rcc']) : '';
        isset($array['listLimit']) ? $model->setListLimit((int)$array['listLimit']) : '';
        $this->repository->save($model);
    }

    /**
     * @param UserInv $model
     */
    public function deleteUserInv(UserInv $model): void
    {
        $this->repository->delete($model);
    }
}
