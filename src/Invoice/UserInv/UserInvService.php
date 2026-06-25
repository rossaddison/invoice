<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Infrastructure\Persistence\UserInv\UserInv;
use App\User\UserRepository as UR;

final readonly class UserInvService
{
    public function __construct(
        private UserInvRepository $repository,
        private UR $userR,
    ) {
    }

    private function persist(UserInv $model, array $array): void
    {
        $user = $this->userR->findById(
            (int) $array['user_id']
        );
        $model->setUser($user);
        $model->setUserId($user->reqId());
    }

    /**
     * @param UserInv $model
     * @param array $array
     */
    public function saveUserInv(UserInv $model, array $array): void
    {
        $this->persist($model, $array);
        $this->applyUserInvProfileFields($model, $array);
        $this->applyUserInvAddressFields($model, $array);
        $this->applyUserInvContactFields($model, $array);
        $this->applyUserInvFinancialFields($model, $array);
        $this->applyUserInvConsentFields($model, $array);
        $this->repository->save($model);
    }

    private function applyUserInvProfileFields(UserInv $model, array $array): void
    {
        isset($array['type']) ? $model->setType((int) $array['type']) : '';
        $model->setActive($array['active'] === '1' ? true : false);
        isset($array['language']) ? $model->setLanguage((string) $array['language']) : '';
        $model->setAllClients($array['all_clients'] === '1' ? true : false);
        isset($array['name']) ? $model->setName((string) $array['name']) : '';
        isset($array['company']) ? $model->setCompany((string) $array['company']) : '';
        isset($array['listLimit']) ? $model->setListLimit((int) $array['listLimit']) : '';
    }

    private function applyUserInvAddressFields(UserInv $model, array $array): void
    {
        isset($array['address_1']) ? $model->setAddress1((string) $array['address_1']) : '';
        isset($array['address_2 ']) ? $model->setAddress2((string) $array['address_2']) : '';
        isset($array['city']) ? $model->setCity((string) $array['city']) : '';
        isset($array['state']) ? $model->setState((string) $array['state']) : '';
        isset($array['zip']) ? $model->setZip((string) $array['zip']) : '';
        isset($array['country']) ? $model->setCountry((string) $array['country']) : '';
    }

    private function applyUserInvContactFields(UserInv $model, array $array): void
    {
        isset($array['phone']) ? $model->setPhone((string) $array['phone']) : '';
        isset($array['fax']) ? $model->setFax((string) $array['fax']) : '';
        isset($array['mobile']) ? $model->setMobile((string) $array['mobile']) : '';
        isset($array['web']) ? $model->setWeb((string) $array['web']) : '';
    }

    private function applyUserInvFinancialFields(UserInv $model, array $array): void
    {
        isset($array['vat_id']) ? $model->setVatId((string) $array['vat_id']) : '';
        isset($array['tax_code']) ? $model->setTaxCode((string) $array['tax_code']) : '';
        isset($array['subscribernumber']) ?
            $model->setSubscribernumber((string) $array['subscribernumber']) : '';
        isset($array['iban']) ? $model->setIban((string) $array['iban']) : '';
        isset($array['gln']) ? $model->setGln((int) $array['gln']) : '';
        isset($array['rcc']) ? $model->setRcc((string) $array['rcc']) : '';
    }

    private function applyUserInvConsentFields(UserInv $model, array $array): void
    {
        $model->setConsentPeriodicInvoice(
            isset($array['consent_periodic_invoice']) && $array['consent_periodic_invoice'] === '1'
        );
        $model->setConsentTelegramOutstanding(
            isset($array['consent_telegram_outstanding']) && $array['consent_telegram_outstanding'] === '1'
        );
        $model->setTelegramChatId(
            isset($array['telegram_chat_id']) && $array['telegram_chat_id'] !== ''
                ? (string) $array['telegram_chat_id']
                : null
        );
    }

    /**
     * @param UserInv $model
     */
    public function deleteUserInv(UserInv $model): void
    {
        $this->repository->delete($model);
    }
}
