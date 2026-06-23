<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\{
    Quote\QuoteViewCoreDeps,
    Quote\QuoteViewItemDeps,
    Quote\QuoteViewRenderDeps,
    Quote\QuoteViewUIDeps,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
};
use App\Invoice\Helpers\{
    ClientHelper, CountryHelper, CustomValuesHelper as CVH, DateHelper,
    NumberHelper,
};
use App\Invoice\Quote\QuoteForm;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeForm;
use App\Invoice\QuoteCustom\QuoteCustomForm;
use App\Invoice\QuoteItem\QuoteItemForm;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Psr\Http\Message\ResponseInterface as Response;

trait View
{
    public function view(
        #[RouteArgument('id')]
        int $id,
        #[RouteArgument('_language')]
        string $_language,
        QuoteViewCoreDeps $core,
        QuoteViewItemDeps $item,
        QuoteViewRenderDeps $render,
        QuoteViewUIDeps $ui,
    ): Response {
        $quote = $this->quote($id, $core->qR, false);
        $quote_id = ($quote !== null) ? $quote->reqId() : 0;
        if ($quote === null || $quote_id <= 0) {
            return $this->webService->getNotFoundResponse();
        }
        $quoteAllowanceChargeForm = new QuoteAllowanceChargeForm();
        $this->session->set('quote_id', $quote_id);
        $this->numberHelper->calculateQuote($quote_id, $item->acqR, $item->qiR,
                $item->qiaR, $core->qtrR, $core->qaR, $core->qR);
        $quote_tax_rates = $core->qtrR->repoCount($quote_id) > 0
            ? $core->qtrR->repoQuotequery($quote_id)
            : null;
        $soId = $quote->getSoId();
        $sales_order_number = $soId > 0
            ? ($core->soR->repoSalesOrderUnloadedquery($soId)?->getNumber() ?? '')
            : '';
        $quote_amount = $core->qaR->repoQuoteAmountCount($quote_id) > 0
            ? $core->qaR->repoQuotequery($quote_id)
            : null;
        if (!$quote_amount) {
            return $this->webService->getNotFoundResponse();
        }
        $quote_custom_values = $this->quoteCustomValues($quote_id, $item->qcR);
        $quoteEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        $vat = $this->sR->getSetting('enable_vat_registration');
        $quoteAmountTotal = $quote_amount->getTotal();
        $customValues = $render->cvR->fixCfValueToCf(
            $render->cfR->repoTablequery('quote_custom'));
        $parameters = [
            '_language' => $_language,
            'body' => $this->body($quote),
            'alert' => $this->alert(),
            'invEdit' => $quoteEdit,
            'quote_amount_total' => $quoteAmountTotal,
            'sales_order_number' => $sales_order_number,
            'quoteToolbar' => $this->quoteToolbar->renderWithStatus(
                $quote, $quoteEdit, $vat, $quoteAmountTotal),
            'dateHelper' => new DateHelper($this->sR),
            'numberHelper' => $this->numberHelper,
            'view_product_task_tabs' =>
                $this->viewBuildProductTaskTabs($quote, $quoteEdit, $_language, $id, $core, $item, $render),
            'view_quote_number' =>
                $this->webViewRenderer->renderPartialAsString(
                    '//invoice/quote/view_quote_number', ['quote' => $quote]),
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
                $this->viewBuildDetailsBoxWithCustomField($quote, $render, $vat, $quote_custom_values, $customValues),
            'view_quote_approve_reject' =>
                $this->viewBuildViewQuoteApproveReject($quote, $quoteEdit, $sales_order_number, $core),
            'view_custom_fields' =>
                $this->viewBuildViewCustomFields($render, $quote_custom_values, $customValues),
            'fields' => $item->qcR->repoFields((int) $this->session->get('quote_id')),
            'customFields' => $render->cfR->repoTablequery('quote_custom'),
            'customValues' => $render->cvR->fixCfValueToCf(
                $render->cfR->repoTablequery('quote_custom')),
            'cvH' => new CVH($this->sR, $render->cvR),
            'quoteCustomValues' => $quote_custom_values,
            'quoteStatuses' => $core->qR->getStatuses($this->translator),
            'quote' => $quote,
            'partial_item_table' =>
                $this->viewBuildPartialItemTable($quote, $item, $render, $ui, $quote_tax_rates, $quote_amount, $_language),
            'modal_choose_products' => $this->viewBuildModalChooseProducts($item, $render),
            'modal_choose_tasks' => $this->viewBuildModalChooseTasks($render, $ui),
            'modal_add_quote_tax' => $this->viewBuildModalAddQuoteTax($render),
            'modal_add_allowance_charge' =>
                $this->viewBuildModalAddAllowanceCharge($ui, $quoteAllowanceChargeForm),
            'modal_copy_quote' =>
                $this->webViewRenderer->renderPartialAsString(
                    '//invoice/quote/modal_copy_quote', [
                        's' => $this->sR,
                        'quote' => $core->qR->repoQuoteLoadedquery(
                            (int) $this->session->get('quote_id')),
                        'clients' => $ui->cR->findAllPreloaded(),
                        'groups' => $ui->gR->findAllPreloaded(),
                    ]),
            'modal_delete_quote' =>
                $this->webViewRenderer->renderPartialAsString(
                    '//invoice/quote/modal_delete_quote', [
                        'actionName' => 'quote/delete',
                        'actionArguments' => [
                            '_language' => $_language,
                            'id' => $this->session->get('quote_id')],
                    ]),
            'modal_delete_items' => $this->viewBuildModalDeleteItems($item, $render),
            'modal_quote_to_invoice' =>
                $this->webViewRenderer->renderPartialAsString(
                    '//invoice/quote/modal_quote_to_invoice', [
                    'quote' => $quote,
                    'groups' => $ui->gR->findAllPreloaded(),
                ]),
            'modal_quote_to_so' =>
                $this->webViewRenderer->renderPartialAsString(
                    '//invoice/quote/modal_quote_to_so', [
                    'quote' => $quote,
                    'groups' => $ui->gR->findAllPreloaded(),
                ]),
            'modal_quote_to_pdf' =>
                $this->webViewRenderer->renderPartialAsString(
                    '//invoice/quote/modal_quote_to_pdf', ['quote' => $quote]),
            'partial_quote_delivery_location' =>
                $this->viewPartialDeliveryLocation(
                    $_language, $ui->dlR, $quote->getDeliveryLocationId()),
        ];
        return ($this->rbacObserver($quote, $core->ucR, $core->uiR) || $this->rbacAdmin() || $this->rbacAccountant())
            ? $this->webViewRenderer->render('view', $parameters)
            : $this->webService->getNotFoundResponse();
    }

    private function viewBuildProductTaskTabs(
        Quote $quote,
        bool $quoteEdit,
        string $_language,
        int $id,
        QuoteViewCoreDeps $core,
        QuoteViewItemDeps $item,
        QuoteViewRenderDeps $render,
    ): string {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/quote/view_product_task_tabs', [
            'quote' => $quote,
            'invEdit' => $quoteEdit,
            'add_quote_product' =>
                $this->webViewRenderer->renderPartialAsString(
                    '//invoice/quoteitem/_item_form_product', [
                'actionName' => 'quoteitemhtmx/addProduct',
                'actionArguments' => ['_language' => $_language],
                'errors' => [],
                'form' => new QuoteItemForm(),
                'quote_id' => $this->quote($id, $core->qR, true),
                'taxRates' => $render->trR->findAllPreloaded(),
                'products' => $item->pR->findAllPreloaded(),
                'units' => $render->uR->findAllPreloaded(),
                'numberHelper' => new NumberHelper($this->sR),
            ]),
            'add_quote_task' =>
                $this->webViewRenderer->renderPartialAsString(
                    '//invoice/quoteitem/_item_form_task', [
                'actionName' => 'quoteitemhtmx/addTask',
                'actionArguments' => ['_language' => $_language],
                'errors' => [],
                'form' => new QuoteItemForm(),
                'quote_id' => $this->quote($id, $core->qR, true),
                'tasks' => $render->taskR->repoTaskStatusquery(1),
                'taxRates' => $render->trR->findAllPreloaded(),
                'numberHelper' => new NumberHelper($this->sR),
            ]),
        ]);
    }

    private function viewBuildDetailsBoxWithCustomField(
        Quote $quote,
        QuoteViewRenderDeps $render,
        string $vat,
        array $quote_custom_values,
        array $customValues,
    ): string {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/quote/view_details_box_with_custom_field',
            [
                'quote' => $quote,
                'cvH' => new CVH($this->sR, $render->cvR),
                'cvR' => $render->cvR,
                'quoteForm' => new QuoteForm(),
                'quoteCustomValues' => $quote_custom_values,
                'customValues' => $customValues,
                'customFields' => $render->cfR->repoTablequery('quote_custom'),
                'vat' => $vat,
            ]);
    }

    private function viewBuildViewQuoteApproveReject(
        Quote $quote,
        bool $quoteEdit,
        string $sales_order_number,
        QuoteViewCoreDeps $core,
    ): string {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/quote/view_quote_approve_reject', [
            'quote' => $quote,
            'body' => $this->body($quote),
            'invEdit' => $quoteEdit,
            'quoteStatuses' => $core->qR->getStatuses($this->translator),
            'sales_order_number' => $sales_order_number,
        ]);
    }

    private function viewBuildViewCustomFields(
        QuoteViewRenderDeps $render,
        array $quote_custom_values,
        array $customValues,
    ): string {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/quote/view_custom_fields', [
            'custom_fields' => $render->cfR->repoTablequery('quote_custom'),
            'custom_values' => $customValues,
            'quote_custom_values' => $quote_custom_values,
            'cvH' => new CVH($this->sR, $render->cvR),
            'cvR' => $render->cvR,
            'quoteCustomForm' => new QuoteCustomForm(),
        ]);
    }

    private function viewBuildPartialItemTable(
        Quote $quote,
        QuoteViewItemDeps $item,
        QuoteViewRenderDeps $render,
        QuoteViewUIDeps $ui,
        mixed $quote_tax_rates,
        mixed $quote_amount,
        string $_language,
    ): string {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/quote/partial_item_table', [
            'acqiR' => $item->acqiR,
            'packHandleShipTotal' =>
                $item->acqR->getPackHandleShipTotal($quote->reqId()),
            'included' => $this->translator->translate('item.tax.included'),
            'excluded' => $this->translator->translate('item.tax.excluded'),
            'invEdit' => $this->userService->hasPermission(Permissions::EDIT_INV) ? true : false,
            'piR' => $ui->piR,
            'products' => $item->pR->findAllPreloaded(),
            'quoteItems' => $item->qiR->repoQuotequery(
                    (int) $this->session->get('quote_id')),
            'qiaR' => $item->qiaR,
            'quoteTaxRates' => $quote_tax_rates,
            'quoteAmount' => $quote_amount,
            'quote' => $quote,
            'language' => $_language,
            'taxRates' => $render->trR->findAllPreloaded(),
            'tasks' => $render->taskR->findAllPreloaded(),
            'units' => $render->uR->findAllPreloaded(),
        ]);
    }

    private function viewBuildModalChooseProducts(
        QuoteViewItemDeps $item,
        QuoteViewRenderDeps $render,
    ): string {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/product/modal_product_lookups_quote',
            [
                'numberHelper' => $this->numberHelper,
                'translator' => $this->translator,
                'csrf' => '',
                'families' => $render->fR->findAllPreloaded(),
                'default_item_tax_rate' =>
                    $this->sR->getSetting('default_item_tax_rate') !== '' ?: 0,
                'filter_product' => '',
                'filter_family' => '',
                'reset_table' => '',
                'products' => $item->pR->findAllPreloadedwithPrice(),
                'partial_product_table_modal' =>
                    $this->webViewRenderer->renderPartialAsString(
                        '//invoice/product/_partial_product_table_modal',
                        [
                            'products' => $item->pR->findAllPreloadedwithPrice(),
                            'numberHelper' => $this->numberHelper,
                            'translator' => $this->translator,
                        ]),
            ],
        );
    }

    private function viewBuildModalChooseTasks(
        QuoteViewRenderDeps $render,
        QuoteViewUIDeps $ui,
    ): string {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/task/modal_task_lookups_quote',
            [
                'default_item_tax_rate' =>
                    $this->sR->getSetting('default_item_tax_rate') !== '' ?: 0,
                'partial_task_table_modal' =>
                    $this->webViewRenderer->renderPartialAsString(
                        '//invoice/task/partial_task_table_modal', [
                        'tasks' => $render->taskR->repoTaskStatusquery(1),
                        'projectR' => $ui->projectR,
                    ]),
            ],
        );
    }

    private function viewBuildModalAddQuoteTax(
        QuoteViewRenderDeps $render,
    ): string {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/quote/modal_add_quote_tax',
            [
                'taxRates' => $render->trR->findAllPreloaded(),
                's' => $this->sR,
                'numberHelper' => $this->numberHelper,
                'translator' => $this->translator,
            ],
        );
    }

    private function viewBuildModalAddAllowanceCharge(
        QuoteViewUIDeps $ui,
        QuoteAllowanceChargeForm $quoteAllowanceChargeForm,
    ): string {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/quote/modal_add_allowance_charge',
            [
                'modal_add_allowance_charge_form' =>
                    $this->webViewRenderer->renderPartialAsString(
                        '//invoice/quote/modal_add_allowance_charge_form',
                        [
                            'optionsDataAllowanceCharges' =>
                                $ui->acR->optionsDataAllowanceCharges(),
                            'acTemplateData' => $ui->acR->acTemplateDataForJs(),
                            'actionName' => 'quoteallowancecharge/add',
                            'actionArguments' => [
                                'quote_id' => (string) $this->session->get('quote_id')],
                            'errors' => [],
                            'title' => $this->translator->translate('allowance.or.charge.add'),
                            'form' => $quoteAllowanceChargeForm,
                        ],
                    ),
            ],
        );
    }

    private function viewBuildModalDeleteItems(
        QuoteViewItemDeps $item,
        QuoteViewRenderDeps $render,
    ): string {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/quote/modal_delete_item', [
            'partial_item_table_modal' =>
                $this->webViewRenderer->renderPartialAsString(
                    '//invoice/quoteitem/_partial_item_table_modal',
                    [
                        'quoteItems' => $item->qiR->repoQuotequery(
                            (int) $this->session->get('quote_id')),
                        'taskR' => $render->taskR,
                        'numberHelper' => new NumberHelper($this->sR),
                    ]),
        ]);
    }

    private function body(Quote $quote): array
    {
        return [
            'number' => $quote->getNumber(),

            'id' => $quote->reqId(),
            'inv_id' => $quote->getInvId(),
            'so_id' => $quote->getSoId(),

            'user_id' => $quote->getUser()?->reqId(),
            'group_id' => $quote->getGroup()?->reqId(),
            'client_id' => $quote->getClient()?->reqId(),

            'date_created' => $quote->getDateCreated(),
            'date_modified' => $quote->getDateModified(),
            'date_expires' => $quote->getDateExpires(),

            'status_id' => $quote->reqStatusId(),

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
            $this->flashMessage('info', $this->translator->translate(
                'record.successfully.deleted'));
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate(
                'quote.tax.rate.cannot.delete'));
        }
        $quote_id = (string) $this->session->get('quote_id');
        return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
    }
}
