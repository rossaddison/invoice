<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class UserInvForm extends FormModel
{
    #[Required]
    public ?int $user_id = null;

    /**
     * Related logic: see Dropdown 0 = Admin, 1 = Not Admin i.e.
     * User with viewInv permission (not editInv Permission)
     */
    #[Required]
    public ?int $type = null;

    public ?bool $active = false;

    #[Required]
    public ?string $language = '';

    #[Required]
    public ?string $name = '';

    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    public ?string $company = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    public ?string $address_1 = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    public ?string $address_2 = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    public ?string $city = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    public ?string $state = '';
    #[Length(min: 0, max: 10, skipOnEmpty: true)]
    public ?string $zip = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    public ?string $country = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    public ?string $phone = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    public ?string $fax = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    public ?string $mobile = '';
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $web = '';
    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    public ?string $vat_id = '';
    #[Length(min: 0, max: 15, skipOnEmpty: true)]
    public ?string $tax_code = '';
    public ?bool $all_clients = false;
    #[Length(min: 0, max: 40, skipOnEmpty: true)]
    public ?string $subscribernumber = '';
    #[Length(min: 0, max: 34, skipOnEmpty: true)]
    public ?string $iban = '';
    public ?int $gln = null;
    #[Length(min: 0, max: 7, skipOnEmpty: true)]
    public ?string $rcc = '';
    public ?int $list_limit = 10;

    public bool $consent_periodic_invoice = false;
    public bool $consent_telegram_outstanding = false;
    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $telegram_chat_id = null;

    public ?User $user = null;

    public static function show(UserInv $userinv): self
    {
        $form = new self();
        $form->user_id = $userinv->reqUserId();
        $form->type = $userinv->getType();
        $form->active = $userinv->getActive();
        $form->language = $userinv->getLanguage();
        $form->name = $userinv->getName();
        $form->company = $userinv->getCompany();
        $form->address_1 = $userinv->getAddress1();
        $form->address_2 = $userinv->getAddress2();
        $form->city = $userinv->getCity();
        $form->state = $userinv->getState();
        $form->zip = $userinv->getZip();
        $form->country = $userinv->getCountry();
        $form->phone = $userinv->getPhone();
        $form->fax = $userinv->getFax();
        $form->mobile = $userinv->getMobile();
        $form->web = $userinv->getWeb();
        $form->vat_id = $userinv->getVatId();
        $form->tax_code = $userinv->getTaxCode();
        $form->all_clients = $userinv->getAllClients();
        $form->subscribernumber = $userinv->getSubscribernumber();
        $form->iban = $userinv->getIban();
        $form->gln = $userinv->getGln();
        $form->rcc = $userinv->getRcc();
        $form->list_limit = $userinv->getListLimit();
        $form->consent_periodic_invoice = $userinv->getConsentPeriodicInvoice();
        $form->consent_telegram_outstanding = $userinv->getConsentTelegramOutstanding();
        $form->telegram_chat_id = $userinv->getTelegramChatId();
        $form->user = $userinv->getUser();
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
