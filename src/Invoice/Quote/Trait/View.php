<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Auth\Permissions;
use App\Invoice\Entity\{
    Quote, QuoteAllowanceCharge, QuoteCustom, QuoteItem,
};
use App\Invoice\{
    AllowanceCharge\AllowanceChargeRepository as ACR,
    Client\ClientRepository as CR,
    CustomField\CustomFieldRepository as CFR,
    CustomValue\CustomValueRepository as CVR,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Family\FamilyRepository as FR,
    Group\GroupRepository as GR,
    Product\ProductRepository as PR,
    ProductImage\ProductImageRepository as PIR,
    Project\ProjectRepository as PROJECTR,
    Quote\QuoteRepository as QR,
    QuoteAllowanceCharge\QuoteAllowanceChargeForm,
    QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteCustom\QuoteCustomForm,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteItem\QuoteItemForm,
    QuoteItem\QuoteItemRepository as QIR,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR,
    QuoteItemAmount\QuoteItemAmountRepository as QIAR,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    SalesOrder\SalesOrderRepository as SOR,
    Task\TaskRepository as TASKR,
    TaxRate\TaxRateRepository as TRR,
    Unit\UnitRepository as UNR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};
use App\Invoice\Helpers\{
    ClientHelper, CountryHelper, CustomValuesHelper as CVH, DateHelper,
    NumberHelper,
};
use App\Invoice\Quote\QuoteForm;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Psr\Http\Message\ResponseInterface as Response;

trait View
{
    public function view(
        #[RouteArgument('id')]
        int $id,
        #[RouteArgument('_language')]
        string $_language,
        CFR $cfR,
        CVR $cvR,
        DLR $dlR,
        PIR $piR,
        PR $pR,
        PROJECTR $projectR,
        QAR $qaR,
        QIAR $qiaR,
        QIR $qiR,
        QR $qR,
        QTRR $qtrR,
        TASKR $taskR,
        TRR $trR,
        FR $fR,
        UNR $uR,
        ACR $acR,
        ACQR $acqR,
        ACQIR $acqiR,
        CR $cR,
        GR $gR,
        QCR $qcR,
        SOR $soR,
        UCR $ucR,
        UIR $uiR
    ): Response {
        $quote = $this->quote($id, $qR, false);
        if (null !== $quote) {
            $quote_id = $quote->getId();
            $quoteAllowanceCharge = new QuoteAllowanceCharge();
            $quoteAllowanceChargeForm = new QuoteAllowanceChargeForm(
                $quoteAllowanceCharge, (int) $quote_id);
            if (null !== $quote_id) {
                $this->session->set('quote_id', $quote_id);
                $this->numberHelper->calculateQuote(
                    (string) $this->session->get('quote_id'), $acqR, $qiR,
                        $qiaR, $qtrR, $qaR, $qR);
                $quote_tax_rates = (($qtrR->repoCount(
                    (string) $this->session->get('quote_id')) > 0) ?
                        $qtrR->repoQuotequery(
                            (string) $this->session->get('quote_id')) : null);
                $sales_order_number = '';
                if ($quote->getSoId()) {
                    $so = $soR->repoSalesOrderUnloadedquery($quote->getSoId());
                    $sales_order_number = $so ? ($so->getNumber() ?? '') : '';
                }
                $quote_amount = (($qaR->repoQuoteAmountCount(
                    (string) $this->session->get('quote_id')) > 0) ?
                        $qaR->repoQuotequery(
                            (string) $this->session->get('quote_id')) : null);
                if ($quote_amount) {
                    $quote_custom_values = $this->quoteCustomValues(
                            (string) $this->session->get('quote_id'), $qcR);
                    $quoteEdit = $this->userService->hasPermission(
                            Permissions::EDIT_INV) ? true : false;
                    $vat = $this->sR->getSetting('enable_vat_registration');
                    $quoteAmountTotal = $quote_amount->getTotal();
                    $customValues =
                    $cvR->fixCfValueToCf(
                        $cfR->repoTablequery('quote_custom'));
                    $parameters = [
                        '_language' => $_language,
                        'body' => $this->body($quote),
                        'alert' => $this->alert(),
                        // Hide buttons on the view if a 'viewInv' user does not
                        // have 'editInv' permission
                        'invEdit' => $quoteEdit,
                        // if the quote amount total is greater than zero show
                        // the buttons eg. Send email
                        'quote_amount_total' => $quoteAmountTotal,
                        'sales_order_number' => $sales_order_number,
                        'quoteToolbar' => $this->quoteToolbar->renderWithStatus(
                            $quote, $quoteEdit, $vat, $quoteAmountTotal),
                        'dateHelper' => new DateHelper($this->sR),
                        'numberHelper' => $this->numberHelper,
                        'view_product_task_tabs' =>
                            $this->webViewRenderer->renderPartialAsString(
                                '//invoice/quote/view_product_task_tabs', [
                            'quote' => $quote,
                            'invEdit' => $quoteEdit,
                            'add_quote_product' =>
                                $this->webViewRenderer->renderPartialAsString(
                                    '//invoice/quoteitem/_item_form_product', [
                                'actionName' => 'quoteitem/addProduct',
                                'actionArguments' => ['_language' => $_language],
                                'errors' => [],
                                'form' => new QuoteItemForm(
                                    new QuoteItem(), $quote_id),
                                'quote_id' => $this->quote($id, $qR, true),
                                'taxRates' => $trR->findAllPreloaded(),
                                'products' => $pR->findAllPreloaded(),
                                'units' => $uR->findAllPreloaded(),
                                'numberHelper' => new NumberHelper($this->sR),
                            ]),
                            'add_quote_task' =>
                                    $this->webViewRenderer->renderPartialAsString(
                                        '//invoice/quoteitem/_item_form_task', [
                                'actionName' => 'quoteitem/addTask',
                                'actionArguments' => ['_language' => $_language],
                                'errors' => [],
                                'form' => new QuoteItemForm(
                                    new QuoteItem(), $quote_id),
                                'quote_id' => $this->quote($id, $qR, true),
                                'tasks' => $taskR->repoTaskStatusquery(1),
                                'taxRates' => $trR->findAllPreloaded(),
                                'numberHelper' => new NumberHelper($this->sR),
                            ]),
                        ]),
                        'view_quote_number' =>
                            $this->webViewRenderer->renderPartialAsString(
                                '//invoice/quote/view_quote_number', [
                            'quote' => $quote,
                        ]),
                        'view_quote_vat_enabled_switch' =>
                            $this->webViewRenderer->renderPartialAsString(
                                '//invoice/quote/view_quote_vat_enabled_switch'),
                        'view_quote_client_details' =>
                            $this->webViewRenderer->renderPartialAsString(
                                '//invoice/quote/view_quote_client_details', [
                            'clientHelper' => new ClientHelper($this->sR),
                            'countryHelper' => new CountryHelper(),
                            'quote' => $quote,
                            '_language' => $_language,
                        ]),
                        'view_details_box_with_custom_field' =>
                            $this->webViewRenderer->renderPartialAsString(
                            '//invoice/quote/view_details_box_with_custom_field',
                            [
                                'quote' => $quote,
                                'cvH' => new CVH($this->sR, $cvR),
                                'cvR' => $cvR,
                                'quoteForm' => new QuoteForm($quote),
                                'quoteCustomValues' => $quote_custom_values,
                                'customValues' =>  $customValues,
                                'customFields' => $cfR->repoTablequery(
                                    'quote_custom'),
                                'vat' => $vat,
                            ]),
                        'view_quote_approve_reject' =>
                            $this->webViewRenderer->renderPartialAsString(
                                '//invoice/quote/view_quote_approve_reject', [
                            'quote' => $quote,
                            'body' => $this->body($quote),
                            'invEdit' => $quoteEdit,
                            'quoteStatuses' => $qR->getStatuses(
                                $this->translator),
                            'sales_order_number' => $sales_order_number,
                        ]),
                        'view_custom_fields' =>
                            $this->webViewRenderer->renderPartialAsString(
                                '//invoice/quote/view_custom_fields', [
                            'custom_fields' => $cfR->repoTablequery(
                                'quote_custom'),
                            'custom_values' => $customValues,
                            'quote_custom_values' => $quote_custom_values,
                            'cvH' => new CVH($this->sR, $cvR),
                            'cvR' => $cvR,
                            'quoteCustomForm' => new QuoteCustomForm(
                                new QuoteCustom()),
                        ]),
                        // Get all the fields that have been setup for this
                        // SPECIFIC quote in quote_custom.
                        'fields' => $qcR->repoFields(
                            (string) $this->session->get('quote_id')),
                        // Get the standard extra custom fields built for EVERY
                        // quote.
                        'customFields' => $cfR->repoTablequery('quote_custom'),
                        'customValues' =>
                        $cvR->fixCfValueToCf(
                            $cfR->repoTablequery('quote_custom')),
                        'cvH' => new CVH($this->sR, $cvR),
                        'quoteCustomValues' => $quote_custom_values,
                        'quoteStatuses' => $qR->getStatuses($this->translator),
                        'quote' => $quote,
                        'partial_item_table' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/quote/partial_item_table', [
                            'acqiR' => $acqiR,
                            'packHandleShipTotal' =>
                                $acqR->getPackHandleShipTotal(
                                    (string) $quote->getId()),
                            'included' => $this->translator->translate(
                                'item.tax.included'),
                            'excluded' => $this->translator->translate(
                                'item.tax.excluded'),
                            'invEdit' => $this->userService->hasPermission(
                                Permissions::EDIT_INV) ? true : false,
                            'piR' => $piR,
                            'products' => $pR->findAllPreloaded(),
                            'quoteItems' => $qiR->repoQuotequery(
                                (string) $this->session->get('quote_id')),
                            'qiaR' => $qiaR,
                            'quoteTaxRates' => $quote_tax_rates,
                            'quoteAmount' => $quote_amount,
                            'quote' => $quote,
                            'language' => $_language,
                            'taxRates' => $trR->findAllPreloaded(),
                            'tasks' => $taskR->findAllPreloaded(),
                            'units' => $uR->findAllPreloaded(),
                        ]),
                        'modal_choose_products' =>
                            $this->webViewRenderer->renderPartialAsString(
                            '//invoice/product/modal_product_lookups_quote',
                            [
                                'numberHelper' => $this->numberHelper,
                                'translator' => $this->translator,
                                'csrf' => '',
                                'families' => $fR->findAllPreloaded(),
                                'default_item_tax_rate' =>
                                $this->sR->getSetting(
                                    'default_item_tax_rate') !== '' ?: 0,
                                'filter_product' => '',
                                'filter_family' => '',
                                'reset_table' => '',
                                'products' => $pR->findAllPreloadedwithPrice(),
                                'partial_product_table_modal' =>
                                $this->webViewRenderer->renderPartialAsString(
                                '//invoice/product/_partial_product_table_modal',
                                [
                                    'products' => $pR->findAllPreloadedwithPrice(),
                                    'numberHelper' => $this->numberHelper,
                                    'translator' => $this->translator,
                                ]),
                            ],
                        ),
                        'modal_choose_tasks' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/task/modal_task_lookups_quote',
                            [
                                'default_item_tax_rate' =>
                                $this->sR->getSetting(
                                        'default_item_tax_rate') !== '' ?: 0,
                                'partial_task_table_modal' =>
                                $this->webViewRenderer->renderPartialAsString(
                                    '//invoice/task/partial_task_table_modal', [
                                    'tasks' => $taskR->repoTaskStatusquery(1),
                                    'projectR' => $projectR,
                                ]),
                            ],
                        ),
                        'modal_add_quote_tax' =>
                            $this->webViewRenderer->renderPartialAsString(
                            '//invoice/quote/modal_add_quote_tax',
                            [
                                'taxRates' => $trR->findAllPreloaded(),
                                's' => $this->sR,
                                'numberHelper' => $this->numberHelper,
                                'translator' => $this->translator,
                            ],
                        ),
                        'modal_add_allowance_charge' =>
                            $this->webViewRenderer->renderPartialAsString(
                            '//invoice/quote/modal_add_allowance_charge',
                            [
                                'modal_add_allowance_charge_form' =>
                                $this->webViewRenderer->renderPartialAsString(
                                '//invoice/quote/modal_add_allowance_charge_form',
                                    [
                                        'optionsDataAllowanceCharges' =>
                                        $acR->optionsDataAllowanceCharges(),
                                        'actionName' => 'quoteallowancecharge/add',
                                        'actionArguments' => [
                                            'quote_id' =>
                                            (string) $this->session->get(
                                                    'quote_id')],
                                        'errors' => [],
                                        'title' => $this->translator->translate(
                                            'allowance.or.charge.add'),
                                        'form' => $quoteAllowanceChargeForm,
                                    ],
                                ),
                            ],
                        ),
                        'modal_copy_quote' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/quote/modal_copy_quote', [
                                's' => $this->sR,
                            'quote' => $qR->repoQuoteLoadedquery(
                                (string) $this->session->get('quote_id')),
                            'clients' => $cR->findAllPreloaded(),
                            'groups' => $gR->findAllPreloaded(),
                        ]),
                        'modal_delete_quote' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/quote/modal_delete_quote',
                            [
                                'actionName' => 'quote/delete',
                                'actionArguments' => [
                                    '_language' => $_language,
                                    'id' => $this->session->get('quote_id')],
                            ],
                        ),
                        'modal_delete_items' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/quote/modal_delete_item', [
                            'partial_item_table_modal' =>
                                $this->webViewRenderer->renderPartialAsString(
                                '//invoice/quoteitem/_partial_item_table_modal',
                                [
                                    'quoteItems' => $qiR->repoQuotequery(
                                        (string) $this->session->get(
                                            'quote_id')),
                                    'taskR' => $taskR,
                                    'numberHelper' => new NumberHelper(
                                        $this->sR),
                            ]),
                        ]),
                        'modal_quote_to_invoice' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/quote/modal_quote_to_invoice', [
                            'quote' => $quote,
                            'groups' => $gR->findAllPreloaded(),
                        ]),
                        'modal_quote_to_so' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/quote/modal_quote_to_so', [
                            'quote' => $quote,
                            'groups' => $gR->findAllPreloaded(),
                        ]),
                        'modal_quote_to_pdf' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/quote/modal_quote_to_pdf', [
                            'quote' => $quote,
                        ]),
                        'partial_quote_delivery_location' =>
                        $this->viewPartialDeliveryLocation(
                            $_language, $dlR, $quote->getDeliveryLocationId()),
                    ];
                    if ($this->rbacObserver($quote, $ucR, $uiR)) {
                        return $this->webViewRenderer->render('view', $parameters);
                    }
                    if ($this->rbacAdmin()) {
                        return $this->webViewRenderer->render('view', $parameters);
                    }
                    if ($this->rbacAccountant()) {
                        return $this->webViewRenderer->render('view', $parameters);
                    }
                } // quote_amount
                $this->flashMessage('info', 'no quote tax');
            } // null!= $quote_id
        } //quote
        return $this->webService->getNotFoundResponse();
    }

    private function body(Quote $quote): array
    {
        return [
            'number' => $quote->getNumber(),

            'id' => $quote->getId(),
            'inv_id' => $quote->getInvId(),
            'so_id' => $quote->getSoId(),

            'user_id' => $quote->getUser()?->getId(),
            'group_id' => $quote->getGroup()?->getId(),
            'client_id' => $quote->getClient()?->reqId(),

            'date_created' => $quote->getDateCreated(),
            'date_modified' => $quote->getDateModified(),
            'date_expires' => $quote->getDateExpires(),

            'status_id' => $quote->getStatusId(),

            'discount_amount' => $quote->getDiscountAmount(),
            'url_key' => $quote->getUrlKey(),
            'password' => $quote->getPassword(),
            'notes' => $quote->getNotes(),
        ];
    }
    
    public function deleteQuoteTaxRate(
        #[RouteArgument('id')] int $id, QTRR $quotetaxrateRepository):
        Response
    {
        try {
            $this->quote_tax_rate_service->deleteQuoteTaxRate(
                $this->quotetaxrate($id, $quotetaxrateRepository));
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate(
                'quote.tax.rate.cannot.delete'));
        }
        $quote_id = (string) $this->session->get('quote_id');
        return $this->factory->createResponse(
            $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/inv_message',
            ['heading' => $this->translator->translate(
                'quote.tax.rate'),'message' => $this->translator->translate(
                    'record.successfully.deleted'),'url' =>
                        'quote/view','id' => $quote_id],
        ));
    }
}