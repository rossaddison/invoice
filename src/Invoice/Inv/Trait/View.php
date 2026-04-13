<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Auth\Permissions;
use App\Invoice\Entity\{InvAllowanceCharge, InvAmount, InvCustom, InvItem};
use App\Invoice\{
    InvAllowanceCharge\InvAllowanceChargeForm, InvItem\InvItemForm,
    AllowanceCharge\AllowanceChargeRepository as ACR,
    Client\ClientRepository as CR,
    CustomValue\CustomValueRepository as CVR,
    CustomField\CustomFieldRepository as CFR,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Family\FamilyRepository as FR,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    Inv\InvAttachmentsForm,
    Inv\InvForm,
    InvAllowanceCharge\InvAllowanceChargeRepository as ACIR,
    InvCustom\InvCustomRepository as ICR,
    InvCustom\InvCustomForm,
    InvItem\InvItemRepository as IIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    InvAmount\InvAmountRepository as IAR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    InvRecurring\InvRecurringRepository as IRR,
    InvTaxRate\InvTaxRateRepository as ITRR,
    Payment\PaymentRepository as PYMR,
    PaymentMethod\PaymentMethodRepository as PMR,
    ProductImage\ProductImageRepository as PIR,
    Product\ProductRepository as PR,
    Project\ProjectRepository as PRJCTR,
    SalesOrder\SalesOrderRepository as SOR,
    Task\TaskRepository as TASKR,
    TaxRate\TaxRateRepository as TRR,
    Unit\UnitRepository as UNR,
    Upload\UploadRepository as UPR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR
};
use App\Invoice\Helpers\CustomValuesHelper as CVH;
use App\Widget\{Bootstrap5ModalTranslatorMessageWithoutAction
};
use Yiisoft\{Data\Paginator\OffsetPaginator,
    Router\HydratorAttribute\RouteArgument,
    Yii\View\Renderer\WebViewRenderer
};
use Psr\Http\Message\ResponseInterface as Response;

trait View
{
    // The accesschecker in config/routes ensures that only users with viewInv
    // permission can reach this

    public function view(
        WebViewRenderer $head,
        #[RouteArgument('id')]
        int $id,
        #[RouteArgument('_language')]
        string $_language,
        CFR $cfR,
        CVR $cvR,
        PR $pR,
        PIR $piR,
        IAR $iaR,
        IIAR $iiaR,
        IIR $iiR,
        IR $iR,
        IRR $irR,
        ITRR $itrR,
        PMR $pmR,
        TRR $trR,
        FR $fR,
        UNR $uR,
        ACR $acR,
        ACIR $aciR,
        ACIIR $aciiR,
        CR $cR,
        GR $gR,
        ICR $icR,
        PYMR $pymR,
        TASKR $taskR,
        PRJCTR $prjctR,
        UIR $uiR,
        UCR $ucR,
        UPR $upR,
        SOR $soR,
        DLR $dlR,
    ): Response {
        $inv = $this->inv($id, $iR, false);
        $enabled_gateways = $this->sR->paymentGatewaysEnabledDriverList();
        $this->flashNoEnabledGateways($enabled_gateways,
            $this->translator->translate('payment.gateway.no'));
        if ($inv) {
            $sales_order_number = '';
            if ($inv->getSoId()) {
                $so = $soR->repoSalesOrderUnloadedquery($inv->getSoId());
                if ($so) {
                    $sales_order_number = $so->getNumber();
                }
            }
            $invoice = $inv->getId();
            $invAllowanceCharge = new InvAllowanceCharge();
            $invAllowanceChargeForm =
                new InvAllowanceChargeForm($invAllowanceCharge, (int) $invoice);
            $read_only = $inv->getIsReadOnly();
            $this->session->set('inv_id', $inv->getId());
            $this->numberHelper->calculateInv(
                (string) $this->session->get('inv_id'), $aciR, $iiR, $iiaR,
                    $itrR, $iaR, $iR, $pymR);
            $inv_amount = (($iaR->repoInvAmountCount((int) $inv->getId()) > 0) ?
                    $iaR->repoInvquery((int) $this->session->get('inv_id')) :
                null);
            if ($inv_amount) {
                $inv_custom_values = $this->invCustomValues(
                    (string) $this->session->get('inv_id'), $icR);
                $is_recurring = $irR->repoCount(
                    (string) $this->session->get('inv_id')) > 0;
                $show_buttons = $this->displayEditDeleteButtons($read_only);
                // Each file attachment is recorded in Upload table with
                // invoice's url_key, and client_id
                $url_key = $inv->getUrlKey();
                $client_id = $inv->getClientId();
                $delivery_location_id = $inv->getDeliveryLocationId();
                $bootstrap5ModalTranslatorMessageWithoutAction =
                    new Bootstrap5ModalTranslatorMessageWithoutAction(
                    $this->webViewRenderer,
                );
                $parameters = [
                    'aciR' => $aciR,
                    'alert' => $this->alert(),
                    // Get the standard extra custom fields built for EVERY invoice.
                    'custom_fields' => $cfR->repoTablequery('inv_custom'),
                    'custom_values' =>
                    $cvR->fixCfValueToCf(
                        $cfR->repoTablequery('inv_custom')),
                    'cvH' => new CVH($this->sR, $cvR),
                    'enabled_gateways' => $enabled_gateways,
                    // Get all the fields that have been setup for this SPECIFIC
                    // invoice in inv_custom.
                    'fields' => $icR->repoFields(
                            (string) $this->session->get('inv_id')),
                    'form' => new InvForm($inv),
                    'iaR' => $iaR,
                    'inv' => $inv,
                    // Determine if a 'viewInv' user has 'editInv' permission
                    'invEdit' => $this->userService->hasPermission(
                            Permissions::EDIT_INV),
                    'inv_custom_values' => $inv_custom_values,
                    'isRecurring' => $is_recurring,
                    'inv_statuses' => $iR->getStatuses($this->translator),
                    // Determine if a 'viewInv' user has 'viewPayment' permission
                    // This permission is necessary for a guest viewing a
                    // read-only view to go to the Pay now section. If a custom
                    // field exists for payments, use it on the payment form.
                    'paymentCfExist' =>
                        $cfR->repoTableCountquery('payment_custom') > 0,
                    'paymentView' => $this->userService->hasPermission(
                        Permissions::VIEW_PAYMENT),
                    'payment_methods' => $pmR->findAllWithActive(1),
                    'payments' => $pymR->repoCount(
                        (string) $this->session->get('inv_id')) > 0 ?
                            $pymR->repoInvquery(
                                (string) $this->session->get('inv_id')) : null,
                    'peppol_doc_currency_toggle' =>
                        $this->sR->getSetting('peppol_doc_currency_toggle'),
                    'peppol_stream_toggle' =>
                        $this->sR->getSetting('peppol_xml_stream'),
                    'readOnly' => $read_only,
                    'sales_order_number' => $sales_order_number,
                    'showButtons' => $show_buttons,
                    'title' => $this->translator->translate('view'),
                    // Sits above options section of invoice allowing the
                    // adding of a new row to the invoice
                    'add_inv_item_product' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/invitem/_item_form_product', [
                        'actionName' => 'invitem/addProduct',
                        'actionArguments' => ['_language' => $_language],
                        'errors' => [],
                        'form' => new InvItemForm(
                            new InvItem(), (int) $this->session->get('inv_id')),
                        'inv' => $iR->repoInvLoadedquery((string) $invoice),
                        'isRecurring' => $irR->repoCount(
                            (string) $invoice) > 0,
                        'inv_id' => $this->session->get('inv_id'),
                        'invItemAllowancesCharges' => $aciiR->repoACIquery(
                            (string) $this->session->get('inv_id')),
                        'invItemAllowancesChargesCount' => $aciiR->repoInvcount(
                            (string) $this->session->get('inv_id')),
                        'taxRates' => $trR->findAllPreloaded(),
                        // Tasks are excluded
                        'products' => $pR->findAllPreloaded(),
                        'units' => $uR->findAllPreloaded(),
                    ]),
                    'add_inv_item_task' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/invitem/_item_form_task', [
                        'actionName' => 'invitem/addTask',
                        'actionArguments' => ['_language' => $_language],
                        'errors' => [],
                        'form' => new InvItemForm(
                            new InvItem(), (int) $this->session->get('inv_id')),
                        'inv' => $iR->repoInvLoadedquery(
                            (string) $this->session->get('inv_id')),
                        'isRecurring' => $is_recurring,
                        'inv_id' => (string) $this->session->get('inv_id'),
                        'taxRates' => $trR->findAllPreloaded(),
                        // Only tasks with complete or status of 3 are made
                        // available for selection
                        'tasks' => $taskR->repoTaskStatusquery(3),
                        // Products are excluded
                        'units' => $uR->findAllPreloaded(),
                    ]),
                    'modal_choose_items' =>
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/product/modal_product_lookups_inv',
                        [
                            'families' => $fR->findAllPreloaded(),
                            'default_item_tax_rate' =>
                                $this->sR->getSetting('default_item_tax_rate')
                                    !== '' ?: 0,
                            'filter_product' => '',
                            'filter_family' => '',
                            'reset_table' => '',
                            'products' => $pR->findAllPreloadedWithPrice(),
                            'partial_product_table_modal' =>
                                $this->webViewRenderer->renderPartialAsString(
                                '//invoice/product/_partial_product_table_modal',
                                [
                                    'products' => $pR->findAllPreloadedWithPrice(),
                                ],
                            ),
                        ],
                    ),
                    'modal_choose_tasks' =>
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/task/modal_task_lookups_inv',
                        [
                            'partial_task_table_modal' =>
                            $this->webViewRenderer->renderPartialAsString(
                                '//invoice/task/partial_task_table_modal', [
                                'tasks' => $taskR->repoTaskStatusquery(3),
                                'projectR' => $prjctR,
                                'dateHelper' => $this->dateHelper,
                                'numberHelper' => $this->numberHelper,
                            ]),
                            'default_item_tax_rate' =>
                            $this->sR->getSetting('default_item_tax_rate')
                                !== '' ?: 0,
                            'tasks' => $taskR->findAllPreloaded(),
                            'head' => $head,
                        ],
                    ),
                    'modal_add_inv_tax' =>
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/inv/modal_add_inv_tax', [
                            'taxRates' => $trR->findAllPreloaded(),
                        ]),
                    'modal_add_allowance_charge' =>
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/inv/modal_add_allowance_charge', [
                            'modal_add_allowance_charge_form' =>
                            $this->webViewRenderer->renderPartialAsString(
                                '//invoice/inv/modal_add_allowance_charge_form',
                                [
                                    'optionsDataAllowanceCharges' =>
                                        $acR->optionsDataAllowanceCharges(),
                                    'actionName' => 'invallowancecharge/add',
                                    'actionArguments' => [
                                        'inv_id' =>
                                        (string) $this->session->get('inv_id')],
                                    'errors' => [],
                                    'title' =>
                                        $this->translator->translate(
                                            'allowance.or.charge.add'),
                                    'form' => $invAllowanceChargeForm,
                                ],
                            ),
                        ],
                    ),
                    'modal_copy_inv' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/inv/modal_copy_inv', [
                        'inv' => $iR->repoInvLoadedquery(
                            (string) $this->session->get('inv_id')),
                        'clients' => $cR->repoUserClient(
                            $ucR->getClientsWithUserAccounts()),
                        'groups' => $gR->findAllPreloaded(),
                    ]),
                    // Partial item table: Used to build items either
                    // products/tasks that make up the invoice
                    // Partial item table: Items and Grand Totals
                    'partial_item_table' => $this->viewPartialItemTable(
                        $show_buttons,
                        $id,
                        $aciR,
                        $aciiR,
                        $pR,
                        $piR,
                        $taskR,
                        $iiR,
                        $iiaR,
                        $iR,
                        $trR,
                        $uR,
                        $itrR,
                        $inv_amount,
                    ),
                    'modal_delete_inv' =>
                        $this->viewModalDeleteInv($_language),
                    'modal_delete_items' => $this->viewModalDeleteItems($iiR),
                    'modal_change_client' =>
                        $this->viewModalChangeClient($id, $cR, $iR),
                    'modal_inv_to_pdf' => $this->viewModalInvToPdf($id, $iR),
                    'modal_inv_to_modal_pdf' =>
                        $this->viewModalInvToModalPdf($id, $iR),
                    'modal_pdf' => $this->viewModalPdf(),
                    'modal_inv_to_html' =>
                        $this->viewModalInvToHtml($id, $iR),
                    'modal_create_credit' =>
                        $this->viewModalCreateCredit($id, $gR, $iR),
                    'view_custom_fields' =>
                        $this->viewCustomFields($cfR, $cvR, $inv_custom_values),
                    'partial_inv_attachments' =>
                        $this->viewPartialInvAttachments(
                            $_language, $url_key, (int) $client_id, $upR),
                    'partial_inv_delivery_location' =>
                            $this->viewPartialDeliveryLocation(
                                $_language, $dlR, $delivery_location_id),
                    'modal_message_no_payment_method' =>
                        $bootstrap5ModalTranslatorMessageWithoutAction
                        ->renderPartialLayoutWithTranslatorMessageAsString(
                            $this->translator->translate('payment.method'),
                            $this->translator->translate(
                                'payment.information.payment.method.required'),
                            'inv',
                        ),
                    'buttonsToolbarFull' => $this->buttonsToolbarFull->render(
                        $inv,
                        $iaR,
                        $this->userService->hasPermission(Permissions::EDIT_INV),
                        $this->userService->hasPermission(
                            Permissions::VIEW_PAYMENT),
                        $read_only,
                        $enabled_gateways,
                        $this->sR->getSetting('enable_vat_registration'),
                        $is_recurring,
                        $cfR->repoTableCountquery('payment_custom') > 0,
                    ),
                ];
                if ($this->rbacObserver($inv, $ucR, $uiR)) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
                if ($this->rbacAdmin()) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
                if ($this->rbacAccountant()) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
            } // if $inv_amount
            return $this->webService->getNotFoundResponse();
        } // if $inv
        return $this->webService->getNotFoundResponse();
    }
    
    private function viewCustomFields(
        CFR $cfR, CVR $cvR, array $inv_custom_values): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/view_custom_fields', [
            'custom_fields' => $cfR->repoTablequery('inv_custom'),
            'custom_values' =>
                $cvR->fixCfValueToCf(
                    $cfR->repoTablequery('inv_custom')),
            'inv_custom_values' => $inv_custom_values,
            'cvH' => new CVH($this->sR, $cvR),
            'invCustomForm' => new InvCustomForm(new InvCustom()),
        ]);
    }

    private function viewModalChangeClient(int $id, CR $cR, IR $iR): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/modal_change_client', [
            'inv' => $this->inv($id, $iR, true),
            'clients' => $cR->findAllPreloaded(),
        ]);
    }

    private function viewModalCreateCredit(int $id, GR $gR, IR $iR): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/modal_create_credit', [
            'invoice_groups' => $gR->repoCountAll() > 0 ? $gR->findAllPreloaded()
                : null,
            'inv' => $this->inv($id, $iR, false),
        ]);
    }

    private function viewModalDeleteInv(string $_language): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/modal_delete_inv', [
            'actionName' => 'inv/delete',
            'actionArguments' => ['id' =>
                $this->session->get('inv_id'), '_language' => $_language],
        ]);
    }

    private function viewModalDeleteItems(IIR $iiR): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/modal_delete_item', [
            'partial_item_table_modal' =>
                $this->webViewRenderer->renderPartialAsString(
                    '//invoice/invitem/_partial_item_table_modal', [
                'invItems' => $iiR->repoInvquery(
                    (string) $this->session->get('inv_id')),
            ]),
        ]);
    }

    private function viewModalInvToPdf(int $id, IR $iR): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/modal_inv_to_pdf', [
            'inv' => $this->inv($id, $iR, true),
        ]);
    }

    private function viewModalInvToModalPdf(int $id, IR $iR): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/modal_inv_to_modal_pdf', [
            'inv' => $this->inv($id, $iR, true),
        ]);
    }

    private function viewModalInvToHtml(int $id, IR $iR): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/modal_inv_to_html', [
            'inv' => $this->inv($id, $iR, true),
        ]);
    }

    private function viewPartialInvAttachments(
        string $_language, string $url_key, int $client_id, UPR $upR): string
    {
        $uploads = $upR->repoUploadUrlClientquery($url_key, $client_id);
        $paginator = new OffsetPaginator($uploads);
        $invEdit = $this->userService->hasPermission(Permissions::EDIT_PAYMENT);
        $invView = $this->userService->hasPermission(Permissions::VIEW_PAYMENT);
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/partial_inv_attachments', [
            'form' => new InvAttachmentsForm(),
            'invEdit' => $invEdit,
            'invView' => $invView,
            'partial_inv_attachments_list' =>
                $this->webViewRenderer->renderPartialAsString(
                    '//invoice/inv/partial_inv_attachments_list', [
                    'paginator' => $paginator,
                    'invEdit' => $invEdit,
                ]),
            'actionName' => 'inv/attachment',
            'actionArguments' => ['id' => $this->session->get('inv_id'),
                '_language' => $_language],
        ]);
    }

    private function viewPartialItemTable(bool $show_buttons, int $id,
        ACIR $aciR, ACIIR $aciiR, PR $pR, PIR $piR, TASKR $taskR, IIR $iiR,
            IIAR $iiaR, IR $iR, TRR $trR, UNR $uR, ITRR $itrR,
                ?InvAmount $invAmount): string
    {
        $inv = $this->inv($id, $iR, false);
        if ($inv) {
            $draft = $inv->getStatusId() == '1';
            $inv_tax_rates = (($itrR->repoCount(
                (string) $this->session->get('inv_id')) > 0) ?
                    $itrR->repoInvquery((string) $this->session->get('inv_id'))
                    : null);
            // Allowances or Charges: DOCUMENT Level using $aciR
            // Allowances or Charges: ITEM Level using $aciiR
            // $inv_item_allowances_charges=
            //      $aciiR->repoACIquery((string)$inv->getId());
            // $inv_item_allowances_charges_count=
            //      $aciiR->repoCount((string)$inv->getId());
            $packHandleShipTotal = $aciR->getPackHandleShipTotal(
                (string) $inv->getId());
            return $this->webViewRenderer->renderPartialAsString(
                '//invoice/inv/partial_item_table', [
                'packHandleShipTotal' => $packHandleShipTotal,
                'aciiR' => $aciiR,
                // Only make buttons available if status is draft
                'draft' => $draft,
                'piR' => $piR,
                'showButtons' => $show_buttons,
                'included' => $this->translator->translate('item.tax.included'),
                'excluded' => $this->translator->translate('item.tax.excluded'),
                'products' => $pR->findAllPreloadedWithPrice(),
                // Only tasks with complete or status of 3 are made available for selection
                'tasks' => $taskR->repoTaskStatusquery(3),
                'userCanEdit' => $this->userService->hasPermission(
                    Permissions::EDIT_INV),
                'invItems' => $iiR->repoInvquery(
                    (string) $this->session->get('inv_id')),
                'invItemAmountR' => $iiaR,
                'invTaxRates' => $inv_tax_rates,
                'invAmount' => $invAmount,
                'inv' => $iR->repoInvLoadedquery(
                    (string) $this->session->get('inv_id')),
                'taxRates' => $trR->findAllPreloaded(),
                'units' => $uR->findAllPreloaded(),
            ]);
        } // inv
        return '';
    }
    
    private function displayEditDeleteButtons(bool $read_only): bool
    {
        if (!$read_only
                && ($this->sR->getSetting('disable_read_only') === (string) 0)) {
            return true;
        }
        // Override the invoice's readonly
        return $this->sR->getSetting('disable_read_only') === (string) 1;
    }    
    
    private function flashNoEnabledGateways(
        array $enabled_gateways, string $message): void
    {
        if (empty(array_filter($enabled_gateways))) {
            $this->flashMessage('warning', $message);
        }
    }
}
