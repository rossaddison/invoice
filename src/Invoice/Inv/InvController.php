<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Auth\Permissions;
use App\Widget\FormFields;
use App\Widget\ButtonsToolbarFull;
use App\Invoice\Entity\{Inv, InvItemAllowanceCharge, InvItem, InvTaxRate, TaxRate};
use App\User\UserService;
use App\User\User;

use App\Invoice\{
    BaseController, Inv\InvCustomFieldProcessor,
    InvAllowanceCharge\InvAllowanceChargeService, InvItem\InvItemService,
    InvItemAllowanceCharge\InvItemAllowanceChargeService,
    InvAmount\InvAmountService,
    InvTaxRate\InvTaxRateService, InvCustom\InvCustomService,
    InvTaxRate\InvTaxRateForm,
    Delivery\DeliveryRepository as DelRepo,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    InvCustom\InvCustomRepository as ICR,
    InvItem\InvItemRepository as IIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    InvTaxRate\InvTaxRateRepository as ITRR,
    Setting\SettingRepository as SR,
    TaxRate\TaxRateRepository as TRR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR
};
use App\User\UserRepository as UR;
use App\Service\WebControllerService;
use App\Invoice\Helpers\{DateHelper, NumberHelper, PdfHelper};
use App\Invoice\Inv\Trait\{Add, Archive, Attachment, Credit, Delete, Edit, Email,
    Flush, Guest, HtmlTrait, Index, MultipleCopy, OptionsData, PdfTrait, Peppol,
    Storecove, UrlKey, View};
use Yiisoft\{
    DataResponse\ResponseFactory\DataResponseFactoryInterface,
    FormModel\FormHydrator, Html\Html,
    Mailer\MailerInterface, Router\FastRoute\UrlGenerator,
    Router\HydratorAttribute\RouteArgument, Session\Flash\Flash,
    Session\SessionInterface, Translator\TranslatorInterface,
    Yii\View\Renderer\WebViewRenderer
};
use Psr\{
    Log\LoggerInterface, Http\Message\ResponseInterface as Response
};

final class InvController extends BaseController
{
    use Add, Archive, Attachment, Credit, Delete, Edit, Email, Flush, Guest,
        HtmlTrait, Index, MultipleCopy, OptionsData, PdfTrait, Peppol, Storecove,
        UrlKey, View;
    
    protected string $controllerName = 'invoice/inv';

    private readonly DateHelper $dateHelper;
    private readonly NumberHelper $numberHelper;
    private readonly PdfHelper $pdfHelper;

    public function __construct(
        private readonly DataResponseFactoryInterface $factory,
        private readonly FormFields $formFields,
        private readonly ButtonsToolbarFull $buttonsToolbarFull,
        private readonly DelRepo $delRepo,
        private readonly InvAllowanceChargeService $inv_allowance_charge_service,
        private readonly InvAmountService $inv_amount_service,
        private readonly InvService $inv_service,
        private readonly InvCustomService $inv_custom_service,
        private readonly InvItemService $inv_item_service,
        private readonly InvItemAllowanceChargeService $aciis,
        private readonly InvTaxRateService $inv_tax_rate_service,
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer,
        private readonly UrlGenerator $url_generator,
        private readonly InvCustomFieldProcessor $customFieldProcessor,
        SessionInterface $session,
        SR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer,
                $session, $sR, $flash);
        $this->dateHelper = new DateHelper($sR);
        $this->numberHelper = new NumberHelper($sR);
        $this->pdfHelper = new PdfHelper($sR, $session, $translator);
    }

    // Add, Credit, MultipleCopy
    private function activeUser(string $client_id, UR $uR, UCR $ucR, UIR $uiR):
        ?User
    {
        $user_client = $ucR->repoUserquery($client_id);
        if (null !== $user_client) {
            $user_client_count = $ucR->repoUserquerycount($client_id);
            if ($user_client_count == 1) {
                $user_id = $user_client->getUserId();
                $user = $uR->findById($user_id);
                if (null !== $user) {
                    $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                    if (null !== $user_inv && $user_inv->getActive()) {
                        return $user;
                    }
                }
            }
        }
        return null;
    }       

    // Add, Credit
    public function defaultTaxes(Inv $inv, TRR $trR,
        FormHydrator $formHydrator): void
    {
        if ($trR->repoCountAll() > 0) {
            $taxrates = $trR->findAllPreloaded();
            /** @var TaxRate $taxrate */
            foreach ($taxrates as $taxrate) {
                $taxrate->getTaxRateDefault() == 1 ? $this->defaultTaxInv(
                    $taxrate, $inv, $formHydrator) : '';
            }
        }
    }

    public function defaultTaxInv(TaxRate $taxrate, Inv $inv,
            FormHydrator $formHydrator): void
    {
        $invTaxRate = new InvTaxRate();
        $invTaxRateForm = new InvTaxRateForm($invTaxRate);
        $inv_tax_rate = [];
        $inv_tax_rate['inv_id'] = $inv->getId();
        $inv_tax_rate['tax_rate_id'] = $taxrate->getTaxRateId();
        /**
        * Related logic: see Settings ... View ... Taxes ...
        * Default Invoice Tax Rate Placement
        * Related logic: see
        *  ..\resources\views\invoice\setting\views partial_settings_taxes.php
        */
        $inv_tax_rate['include_item_tax'] = (
            $this->sR->getSetting('default_include_item_tax') == '1' ? 1 : 0);

        $inv_tax_rate['inv_tax_rate_amount'] = 0;
        ($formHydrator->populateAndValidate($invTaxRateForm, $inv_tax_rate))
                        ? $this->inv_tax_rate_service->saveInvTaxRate(
                            new InvTaxRate(), $inv_tax_rate) : '';
    }

    // resources/views/invoice/inv/partial_item_table 
    public function deleteInvItem(#[RouteArgument('id')] int $id, IIR $iiR,
            ACIIR $aciiR, IIAR $iiaR):
        Response
    {
        try {
            $invItem = $this->invItem($id, $iiR);
            if ($invItem) {
                // Do not allow the item to be deleted if the invoice
                // status is sent ie. 2
                if ($invItem->getInv()?->getStatusId() !== 2) {
                    $aciis = $aciiR->repoInvItemquery((string) $invItem->getId());
                    /** @var InvItemAllowanceCharge $acii */
                    foreach ($aciis as $acii) {
                        $this->aciis->deleteInvItemAllowanceCharge($acii, $iiaR,
                                $aciiR);
                    }
                    $this->inv_item_service->deleteInvItem($invItem);
                    $this->flashMessage('info', $this->translator->translate(
                        'record.successfully.deleted'));
                    return $this->webService->getRedirectResponse(
                        'inv/view', ['id' => $invItem->getInvId()]);
                }
                $this->flashMessage('warning',
                    $this->translator->translate('delete.sent'));
                return $this->webService->getRedirectResponse('inv/view',
                        ['id' => $invItem->getInvId()]);
            }
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            unset($e);
        }
        $inv_id = (string) $this->session->get('inv_id');
        return $this->factory->createResponse(
                $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/inv_message',
            ['heading' => $this->translator->translate('items'),
                'message' =>
                    $this->translator->translate('record.successfully.deleted'),
                'url' => 'inv/view', 'id' => $inv_id],
        ));
    }    
    
    // Email, PdfTrait
    public function generateInvNumberIfApplicable(?string $inv_id, IR $iR,
        SR $sR, GR $gR): void
    {
        if (null !== $inv_id) {
            $inv = $iR->repoInvUnloadedquery($inv_id);
            if ($inv) {
                $group_id = $inv->getGroupId();
                if ($iR->repoCount($inv_id) > 0) {
                    if ($inv->getStatusId() === 1 && $inv->getNumber() === '') {
                        // Generate new inv number if applicable
                        $inv->setNumber((string) $this->generateInvGetNumber(
                            $group_id, $sR, $iR, $gR));
                        $iR->save($inv);
                    }
                }
            }
        }
    }

    // above function
    private function generateInvGetNumber(string $group_id, SR $sR, IR $iR,
        GR $gR): mixed
    {
        $inv_number = '';
        if ($sR->getSetting('generate_invoice_number_for_draft') == '0') {
            /** @var mixed $inv_number */
            $inv_number = $iR->getInvNumber($group_id, $gR);
        }
        return $inv_number;
    }

    // Delete, Edit, Email, View
    private function inv(int $id, IR $invRepo, bool $unloaded = false): ?Inv
    {
        if ($id) {
            $inv = ($unloaded ? $invRepo->repoInvUnLoadedquery((string) $id) :
                $invRepo->repoInvLoadedquery((string) $id));
            if (null !== $inv) {
                return $inv;
            }
            return null;
        }
        return null;
    }

    /**
     * Used in: Edit, Email, HtmlTrait, PdfTrait, View
     * @param string|null $inv_id
     * @param icR $icR
     * @return array
     */
    public function invCustomValues(?string $inv_id, ICR $icR): array
    {
        // Get all the custom fields that have been registered with this inv on
        // creation, retrieve existing values via repo, and populate
        // custom_field_form_values array
        $custom_field_form_values = [];
        if (null !== $inv_id) {
            if ($icR->repoInvCount($inv_id) > 0) {
                $inv_custom_fields = $icR->repoFields($inv_id);
                /**
                 * @var int $key
                 * @var string $val
                 */
                foreach ($inv_custom_fields as $key => $val) {
                    $custom_field_form_values['custom['
                        . (string) $key . ']'] = $val;
                }
            }
            return $custom_field_form_values;
        }
        return [];
    }

    // deleteInvItem
    private function invItem(int $id, IIR $invitemRepository): ?InvItem
    {
        if ($id) {
            $invitem = $invitemRepository->repoInvItemquery((string) $id) ?: null;
            if ($invitem === null) {
                return null;
            }
            return $invitem;
        }
        return null;
    }
    
    // deleteInvTaxRate
    private function invtaxrate(int $id, ITRR $invtaxrateRepository): ?InvTaxRate
    {
        if ($id) {
            $invtaxrate =
                $invtaxrateRepository->repoInvTaxRatequery((string) $id);
            if (null !== $invtaxrate) {
                return $invtaxrate;
            }
        }
        return null;
    }
    
    /**
     * Purpose:
     * Prevent browser manipulation and ensure that views are only accessible
     * to users 1. with the observer role's VIEW_INV permission and 2. supervise a
     * client requested invoice and are an active current user for these client's
     * invoices.
     * Used in: Attachment, PdfTrait, View
     */
    private function rbacObserver(Inv $inv, UCR $ucR, UIR $uiR) : bool {
        $statusId = $inv->getStatusId();
        if (null!==$statusId) {
            // has observer role
            if ($this->userService->hasPermission(Permissions::VIEW_INV)
                && !($this->userService->hasPermission(Permissions::EDIT_INV))
                // the invoice  is not a draft i.e. has been sent
                && !($statusId === 1)
                // the invoice is intended for the current user
                && ($inv->getUserId() === $this->userService->getUser()?->getId())
                // the invoice client is associated with the above user
                // the observer user may be paying for more than one client
                && ($ucR->repoUserClientqueryCount($inv->getUserId(),
                                                $inv->getClientId()) > 0)) {
                $userInv = $uiR->repoUserInvUserIdquery($inv->getUserId());
                // the current observer user is active
                if (null !== $userInv && $userInv->getActive()) {
                    return true;
                }
            }
        }
        return false;
    }

    // PdfTrait, View
    private function rbacAccountant() : bool {
        // has accountant role
        if (($this->userService->hasPermission(Permissions::VIEW_INV)
            && ($this->userService->hasPermission(Permissions::VIEW_PAYMENT))
            && ($this->userService->hasPermission(Permissions::EDIT_PAYMENT)))) {
            return true;
        } else {
            return false;
        }
    }

    // Attachment, Edit, PdfTrait, View 
    private function rbacAdmin() : bool {
        // has observer role
        if ($this->userService->hasPermission(Permissions::VIEW_INV)
            && ($this->userService->hasPermission(Permissions::EDIT_INV))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Purpose: Warning: Setting 'Mark invoices as sent when copy' should
     * only be ON during development
     * Use: Toggle Button on Flash message reminder
     */
    private function markSentFlash(string $_language): void
    {
        // Get the current mark_invoice_sent_copy setting
        $mark_sent = $this->sR->getSetting('mark_invoices_sent_copy');
        // Get the setting_id to allow for editing
        $setting = $this->sR->withKey('mark_invoices_sent_copy');
        $setting_url = '';
        if (null !== $setting) {
            $setting_id = $setting->getSettingId();
            $setting_url = $this->url_generator->generate('setting/markSent',
                ['_language' => $_language, 'setting_id' => $setting_id]);
        }
        $level = $mark_sent == '0' ? 'success' : 'danger';
        $on_off = $mark_sent == '0' ? 'off' : 'on';
        /**
         * @link https://emojipedia.or/check-mark
         * @link https://emojipedia.org/cross-mark  ️
         */
        $message = ($mark_sent == '0' ? '✔' : '❌')
            . $this->translator->translate('mark.sent.'
            . $on_off) . str_repeat('&nbsp;', 2)
            . (!empty($setting_url) ? (string) Html::a(
              Html::tag(
                  'i',
                  '',
                  ['class' => 'bi bi-pencil'],
              ),
              $setting_url,
              ['class' =>
                    $mark_sent == '0' ? 'btn btn-success' : 'btn btn-danger'],
          )
          : '');
        $this->flashMessage($level, $message);
    }
}
