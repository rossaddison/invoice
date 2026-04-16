<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Widget\FormFields;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\Entity\{Quote, QuoteItem, QuoteTaxRate};
use App\User\UserService;
use App\User\User;
use App\Invoice\{
    Inv\InvService, InvItem\InvItemService,
    InvAllowanceCharge\InvAllowanceChargeService, InvAmount\InvAmountService, 
    InvTaxRate\InvTaxRateService, InvCustom\InvCustomService,
    SalesOrder\SalesOrderService as soS,
    SalesOrderAllowanceCharge\SalesOrderAllowanceChargeService as soACS,
    SalesOrderCustom\SalesOrderCustomService as soCS,
    SalesOrderItem\SalesOrderItemService as soIS,
    SalesOrderTaxRate\SalesOrderTaxRateService as soTRS,
    QuoteAllowanceCharge\QuoteAllowanceChargeService,
    QuoteAmount\QuoteAmountService, QuoteCustom\QuoteCustomService,
    QuoteItem\QuoteItemService,
    QuoteTaxRate\QuoteTaxRateService,
};
use App\Service\WebControllerService;
// Forms
use App\Invoice\Quote\Trait\{Add, Delete, Edit, Email, Guest, Index, OptionsData,
    PdfTrait, QuoteCopy, QuoteToInvoice, QuoteToSo, UrlKey, View};
use App\Invoice\{
    Quote\QuoteCustomFieldProcessor,
    QuoteTaxRate\QuoteTaxRateForm,
    Group\GroupRepository as GR,
    Quote\QuoteRepository as QR,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteItem\QuoteItemRepository as QIR,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    Setting\SettingRepository as SR,
    TaxRate\TaxRateRepository as TRR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};
use App\User\UserRepository as UR;
use App\Invoice\Helpers\{
    NumberHelper, PdfHelper
};
use App\Widget\QuoteToolbar;
use Yiisoft\{
    DataResponse\ResponseFactory\DataResponseFactoryInterface,
    Json\Json,
    Mailer\MailerInterface,
    Router\FastRoute\UrlGenerator,
    Router\HydratorAttribute\RouteArgument,
    Session\Flash\Flash,
    Session\SessionInterface as Session,
    FormModel\FormHydrator,
    Translator\TranslatorInterface as Translator,
    Yii\View\Renderer\WebViewRenderer,
};
use Psr\{
    Log\LoggerInterface,
    Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

final class QuoteController extends BaseController
{
    use Add, Delete, Edit, Email, Guest, Index, OptionsData, PdfTrait, QuoteCopy,
        QuoteToInvoice, QuoteToSo, UrlKey, View;
    
    protected string $controllerName = 'invoice/quote';

    private readonly NumberHelper $numberHelper;
    private readonly PdfHelper $pdfHelper;

    public function __construct(
        private readonly DataResponseFactoryInterface $factory,
        private readonly FormFields $formFields,
        private readonly InvAllowanceChargeService $inv_allowance_charge_service,
        private readonly InvAmountService $inv_amount_service,
        private readonly InvService $inv_service,
        private readonly InvCustomService $inv_custom_service,
        private readonly InvItemService $inv_item_service,
        private readonly InvTaxRateService $inv_tax_rate_service,
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer,
        private readonly soACS $soac_service,
        private readonly soCS $so_custom_service,
        private readonly soIS $so_item_service,
        private readonly soS $so_service,
        private readonly soTRS $so_tax_rate_service,
        private readonly QuoteAllowanceChargeService $qac_Service,
        private readonly QuoteAmountService $quote_amount_service,
        private readonly QuoteCustomService $quote_custom_service,
        private readonly QuoteItemService $quote_item_service,
        private readonly QuoteService $quote_service,
        private readonly QuoteTaxRateService $quote_tax_rate_service,
        private readonly QuoteCustomFieldProcessor $quoteCustomFieldProcessor,
        private readonly QuoteToolbar $quoteToolbar,
        private readonly UrlGenerator $url_generator,
        Session $session,
        SR $sR,
        Translator $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
            $webViewRenderer, $session, $sR, $flash);
        $this->numberHelper = new NumberHelper($sR);
        $this->pdfHelper = new PdfHelper($sR, $session, $translator);
    }

    private function activeUser(string $client_id, UR $uR, UCR $ucR,
        UIR $uiR): ?User
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

    public function defaultTaxes(
        Quote $quote, TRR $trR, FormHydrator $formHydrator): void
    {
        if ($trR->repoCountAll() > 0) {
            $taxrates = $trR->findAllPreloaded();
            /** @var TaxRate $taxRate */
            foreach ($taxrates as $taxRate) {
                if ($taxRate->getTaxRateDefault()) {
                    $this->defaultTaxQuote($taxRate, $quote, $formHydrator);
                }
            }
        }
    }

    private function defaultTaxQuote(?TaxRate $taxRate,
            Quote $quote, FormHydrator $formHydrator): void
    {
        $quoteTaxRate = new QuoteTaxRate();
        $quoteTaxRateForm = new QuoteTaxRateForm($quoteTaxRate);
        $quote_tax_rate = [];
        $quote_tax_rate['quote_id'] = $quote->getId();
        if (null !== $taxRate) {
            $quote_tax_rate['tax_rate_id'] = $taxRate->reqId();
        } else {
            $quote_tax_rate['tax_rate_id'] = 1;
        }
        /**
         * Related logic: see Settings ... View ... Taxes
         * ... Default Invoice Tax Rate Placement
         * Related logic: see
         * ..\resources\views\invoice\setting\views partial_settings_taxes.php
         */
        $quote_tax_rate['include_item_tax'] =
            ($this->sR->getSetting('default_include_item_tax') == '1' ? 1 : 0);
        $quote_tax_rate['quote_tax_rate_amount'] = 0;
        if ($formHydrator->populate($quoteTaxRateForm, $quote_tax_rate)
                && $quoteTaxRateForm->isValid()) {
            $this->quote_tax_rate_service->saveQuoteTaxRate(
                $quoteTaxRate, $quote_tax_rate);
        }
    }

    public function deleteQuoteItem(#[RouteArgument('id')] int $id, QIR $qiR):
        Response
    {
        $quoteId = (string) $this->session->get('quote_id');
        try {
            $quoteItem = $this->quoteItem($id, $qiR);
            if ($quoteItem) {
                $this->quote_item_service->deleteQuoteItem($quoteItem);
                $this->flashMessage('info', $this->translator->translate(
                    'record.successfully.deleted'));
                return $this->webService->getRedirectResponse('quote/view',
                        ['id' => $quoteId]);
            }
            $this->flashMessage(
                'danger', $this->translator->translate(
                    'quote.item.cannot.delete'));
            return $this->webService->getRedirectResponse(
                'quote/view', ['id' => $quoteId]);
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger',
                    $this->translator->translate('quote.item.cannot.delete'));
        }
        return $this->factory->createResponse(
                $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/quote_successful',
            ['heading' => '','message' => $this->translator->translate(
                'record.successfully.deleted'),'url' => 'quote/view','id' =>
                $quoteId],
        ));
    }
    
    public function generateQuoteNumberIfApplicable(
        string $quote_id, QR $qR, SR $sR, GR $gR): void
    {
        $quote = $qR->repoQuoteUnloadedquery($quote_id);
        if (!empty($quote) && ($quote->getStatusId() == 1)
            && ($quote->getNumber() == '')) {
            // Generate new quote number if applicable
            if ((int) $sR->getSetting(
                'generate_quote_number_for_draft') === 0) {
                $quote_number = (string) $qR->getQuoteNumber(
                    $quote->getGroupId(), $gR);
                // Set new quote number and save
                $quote->setNumber($quote_number);
                $qR->save($quote);
            }
        }
    }

    // Delete, Edit, Email, View
    private function quote(
        int $id,
        QR $quoteRepo,
        bool $unloaded = false,
    ): ?Quote {
        if ($id) {
            return $unloaded ? $quoteRepo->repoQuoteUnLoadedquery((string) $id)
                : $quoteRepo->repoQuoteLoadedquery((string) $id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function quotes(QR $quoteRepo, int $status):
        \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $quoteRepo->findAllWithStatus($status);
    }

    public function quoteCustomValues(string $quote_id, QCR $qcR): array
    {
        // Get all the custom fields that have been registered with this
        // quote on creation, retrieve existing values via repo, and populate
        // custom_field_form_values array
        $custom_field_form_values = [];
        if ($qcR->repoQuoteCount($quote_id) > 0) {
            $quote_custom_fields = $qcR->repoFields($quote_id);
            /**
             * @var string $key
             * @var string $val
             */
            foreach ($quote_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' . $key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }

    private function quoteItem(int $id, QIR $quoteitemRepository): ?QuoteItem
    {
        if ($id) {
            $quoteitem = $quoteitemRepository->repoQuoteItemquery((string) $id);
            if (null !== $quoteitem) {
                return $quoteitem;
            }
            return null;
        }
        return null;
    }

    private function quotetaxrate(int $id, QTRR $quotetaxrateRepository):
        ?QuoteTaxRate
    {
        if ($id) {
            $quotetaxrate = $quotetaxrateRepository->repoQuoteTaxRatequery(
                (string) $id);
            if (null !== $quotetaxrate) {
                return $quotetaxrate;
            }
            return null;
        }
        return null;
    }
    
    // '#quote_tax_submit' => quote.js

    public function saveQuoteTaxRate(Request $request,
        FormHydrator $formHydrator): Response
    {
        $body = $request->getQueryParams();
        $ajax_body = [
            'quote_id' => $body['quote_id'],
            'tax_rate_id' => $body['tax_rate_id'],
            'include_item_tax' => $body['include_item_tax'],
            'quote_tax_rate_amount' => 0.00,
        ];
        $quoteTaxRate = new QuoteTaxRate();
        $ajax_content = new QuoteTaxRateForm($quoteTaxRate);
        if ($formHydrator->populateAndValidate($ajax_content, $ajax_body)) {
            $this->quote_tax_rate_service->saveQuoteTaxRate($quoteTaxRate,
                $ajax_body);
            $parameters = [
                'success' => 1,
                'flash_message' => $this->translator->translate(
                    'quote.tax.rate.saved'),
            ];
            //return response to quote.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));
        }
        $parameters = [
            'success' => 0,
            'flash_message' => $this->translator->translate(
                'quote.tax.rate.incomplete.fields'),
        ];
        //return response to quote.js to reload page at location
        return $this->factory->createResponse(Json::encode($parameters));
    }
    
    /**
     * Purpose:
     * Prevent browser manipulation and ensure that views are only accessible
     * to users 1. with the observer role's VIEW_INV permission and 2. supervise a
     * client requested quote and are an active current user for these client's
     * invoices.
     */
    private function rbacObserver(Quote $quote, UCR $ucR, UIR $uiR) : bool {
        $statusId = $quote->getStatusId();
        if (null!==$statusId) {
            // has observer role
            if ($this->userService->hasPermission(Permissions::VIEW_INV)
                && !($this->userService->hasPermission(Permissions::EDIT_INV))
                // the quote  is not a draft i.e. has been sent
                && !($statusId === 1)
                // the quote is intended for the current user
                && ($quote->getUserId() === $this->userService->getUser()?->getId())
                // the quote client is associated with the above user
                && ($ucR->repoUserClientqueryCount($quote->getUserId(), $quote->getClientId()) > 0)) {
                $userInv = $uiR->repoUserInvUserIdquery($quote->getUserId());
                // the current observer user is active
                if (null !== $userInv && $userInv->getActive()) {
                    return true;
                }
            }
        }
        return false;
    }

    private function rbacAccountant() : bool {
        // has accountant role
        return ($this->userService->hasPermission(Permissions::VIEW_INV)
            && ($this->userService->hasPermission(Permissions::VIEW_PAYMENT))
            && ($this->userService->hasPermission(Permissions::EDIT_PAYMENT)));
    }

    private function rbacAdmin() : bool {
        // has observer role
        return ($this->userService->hasPermission(Permissions::VIEW_INV)
            && ($this->userService->hasPermission(Permissions::EDIT_INV)));
    }
}
