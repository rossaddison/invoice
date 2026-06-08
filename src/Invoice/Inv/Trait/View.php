<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\{
    InvAllowanceCharge\InvAllowanceChargeForm, InvItem\InvItemForm,
    Client\ClientRepository as CR,
    CustomValue\CustomValueRepository as CVR,
    CustomField\CustomFieldRepository as CFR,
    Inv\InvAttachmentsForm,
    Inv\InvForm,
    Inv\InvViewDeps,
    InvCustom\InvCustomForm,
    InvItem\InvItemRepository as IIR,
    InvTaxRate\InvTaxRateRepository as ITRR,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    Upload\UploadRepository as UPR
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
        InvViewDeps $d,
    ): Response {
        $inv = $this->inv($id, $d->iR, false);
        $enabled_gateways = $this->sR->paymentGatewaysEnabledDriverList();
        $this->flashNoEnabledGateways($enabled_gateways,
            $this->translator->translate('payment.gateway.no'));
        if ($inv) {
            $sales_order_number = '';
            if (null !== $inv->getSoId()) {
                $so = $d->soR->repoSalesOrderUnloadedquery((int) $inv->getSoId());
                if ($so) {
                    $sales_order_number = $so->getNumber();
                }
            }
            $invoice = $inv->reqId();
            $invAllowanceChargeForm = new InvAllowanceChargeForm();
            $read_only = $inv->getIsReadOnly();
            $this->session->set('inv_id', $inv->reqId());
            $this->numberHelper->calculateInv(
                (int) $this->session->get('inv_id'), $d->aciR, $d->iiR, $d->iiaR,
                    $d->itrR, $d->iaR, $d->iR, $d->pymR);
            $inv_amount = (($d->iaR->repoInvAmountCount($inv->reqId()) > 0) ?
                    $d->iaR->repoInvquery((int) $this->session->get('inv_id')) :
                null);
            if ($inv_amount) {
                $inv_custom_values = $this->invCustomValues(
                    (int) $this->session->get('inv_id'), $d->icR);
                $is_recurring = $d->irR->repoCount((int) $this->session->get('inv_id')) > 0;
                $show_buttons = $this->displayEditDeleteButtons($read_only);
                $url_key = $inv->getUrlKey();
                $client_id = $inv->reqClientId();
                $delivery_location_id = $inv->getDeliveryLocationId();
                $bootstrap5ModalTranslatorMessageWithoutAction =
                    new Bootstrap5ModalTranslatorMessageWithoutAction(
                    $this->webViewRenderer,
                );
                $parameters = [
                    'aciR' => $d->aciR,
                    'alert' => $this->alert(),
                    'custom_fields' => $d->cfR->repoTablequery('inv_custom'),
                    'custom_values' =>
                    $d->cvR->fixCfValueToCf(
                        $d->cfR->repoTablequery('inv_custom')),
                    'cvH' => new CVH($this->sR, $d->cvR),
                    'enabled_gateways' => $enabled_gateways,
                    'fields' => $d->icR->repoFields(
                            (int) $this->session->get('inv_id')),
                    'form' => InvForm::show($inv),
                    'iaR' => $d->iaR,
                    'inv' => $inv,
                    'invEdit' => $this->userService->hasPermission(
                            Permissions::EDIT_INV),
                    'inv_custom_values' => $inv_custom_values,
                    'isRecurring' => $is_recurring,
                    'inv_statuses' => $d->iR->getStatuses($this->translator),
                    'paymentCfExist' =>
                        $d->cfR->repoTableCountquery('payment_custom') > 0,
                    'paymentView' => $this->userService->hasPermission(
                        Permissions::VIEW_PAYMENT),
                    'email_templates_invoice' => $d->etR->findAllPreloaded(),
                    'invoice_groups' => $d->gR->findAllPreloaded(),
                    'payment_methods' => $d->pmR->findAllWithActive(1),
                    'payments' => $d->pymR->repoCount(
                        (int) $this->session->get('inv_id')) > 0 ?
                            $d->pymR->repoInvquery(
                                (int) $this->session->get('inv_id')) : null,
                    'peppol_doc_currency_toggle' =>
                        $this->sR->getSetting('peppol_doc_currency_toggle'),
                    'peppol_stream_toggle' =>
                        $this->sR->getSetting('peppol_xml_stream'),
                    'readOnly' => $read_only,
                    'sales_order_number' => $sales_order_number,
                    'showButtons' => $show_buttons,
                    'title' => $this->translator->translate('view'),
                    'add_inv_item_product' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/invitem/_item_form_product', [
                        'actionName' => 'invitemhtmx/addProduct',
                        'actionArguments' => ['_language' => $_language],
                        'errors' => [],
                        'form' => new InvItemForm(),
                        'inv' => $d->iR->repoInvLoadedquery($invoice),
                        'isRecurring' => $d->irR->repoCount($invoice) > 0,
                        'inv_id' => $this->session->get('inv_id'),
                        'invItemAllowancesCharges' => $d->aciiR->repoACIquery(
                            (int) $this->session->get('inv_id')),
                        'invItemAllowancesChargesCount' => $d->aciiR->repoInvcount(
                            (int) $this->session->get('inv_id')),
                        'taxRates' => $d->trR->findAllPreloaded(),
                        'products' => $d->pR->findAllPreloaded(),
                        'units' => $d->unR->findAllPreloaded(),
                    ]),
                    'add_inv_item_task' =>
                        $this->webViewRenderer->renderPartialAsString(
                            '//invoice/invitem/_item_form_task', [
                        'actionName' => 'invitemhtmx/addTask',
                        'actionArguments' => ['_language' => $_language],
                        'errors' => [],
                        'form' => new InvItemForm(),
                        'inv' => $d->iR->repoInvLoadedquery(
                            (int) $this->session->get('inv_id')),
                        'isRecurring' => $is_recurring,
                        'inv_id' => (int) $this->session->get('inv_id'),
                        'taxRates' => $d->trR->findAllPreloaded(),
                        'tasks' => $d->taskR->repoTaskStatusquery(3),
                        'units' => $d->unR->findAllPreloaded(),
                    ]),
                    'modal_choose_items' =>
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/product/modal_product_lookups_inv',
                        [
                            'families' => $d->fR->findAllPreloaded(),
                            'default_item_tax_rate' =>
                                $this->sR->getSetting('default_item_tax_rate')
                                    !== '' ?: 0,
                            'filter_product' => '',
                            'filter_family' => '',
                            'reset_table' => '',
                            'products' => $d->pR->findAllPreloadedWithPrice(),
                            'partial_product_table_modal' =>
                                $this->webViewRenderer->renderPartialAsString(
                                '//invoice/product/_partial_product_table_modal',
                                [
                                    'products' => $d->pR->findAllPreloadedWithPrice(),
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
                                'tasks' => $d->taskR->repoTaskStatusquery(3),
                                'projectR' => $d->prjctR,
                                'dateHelper' => $this->dateHelper,
                                'numberHelper' => $this->numberHelper,
                            ]),
                            'default_item_tax_rate' =>
                            $this->sR->getSetting('default_item_tax_rate')
                                !== '' ?: 0,
                            'tasks' => $d->taskR->findAllPreloaded(),
                            'head' => $head,
                        ],
                    ),
                    'modal_add_inv_tax' =>
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/inv/modal_add_inv_tax', [
                            'taxRates' => $d->trR->findAllPreloaded(),
                        ]),
                    'modal_add_allowance_charge' =>
                        $this->webViewRenderer->renderPartialAsString(
                        '//invoice/inv/modal_add_allowance_charge', [
                            'modal_add_allowance_charge_form' =>
                            $this->webViewRenderer->renderPartialAsString(
                                '//invoice/inv/modal_add_allowance_charge_form',
                                [
                                    'optionsDataAllowanceCharges' =>
                                        $d->acR->optionsDataAllowanceCharges(),
                                    'acTemplateData' =>
                                        $d->acR->acTemplateDataForJs(),
                                    'actionName' => 'invallowancecharge/add',
                                    'actionArguments' => [
                                        'inv_id' =>
                                        (int) $this->session->get('inv_id')],
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
                        'inv' => $d->iR->repoInvLoadedquery(
                            (int) $this->session->get('inv_id')),
                        'clients' => $d->cR->repoUserClient(
                            $d->ucR->getClientsWithUserAccounts()),
                        'groups' => $d->gR->findAllPreloaded(),
                    ]),
                    'partial_item_table' => $this->viewPartialItemTable(
                        $show_buttons,
                        $id,
                        $d,
                        $inv_amount,
                    ),
                    'modal_delete_inv' =>
                        $this->viewModalDeleteInv($_language),
                    'modal_delete_items' => $this->viewModalDeleteItems($d->iiR),
                    'modal_change_client' =>
                        $this->viewModalChangeClient($id, $d->cR, $d->iR),
                    'modal_inv_to_pdf' => $this->viewModalInvToPdf($id, $d->iR),
                    'modal_inv_to_modal_pdf' =>
                        $this->viewModalInvToModalPdf($id, $d->iR),
                    'modal_pdf' => $this->viewModalPdf(),
                    'modal_inv_to_html' =>
                        $this->viewModalInvToHtml($id, $d->iR),
                    'modal_create_credit' =>
                        $this->viewModalCreateCredit($id, $d->gR, $d->iR),
                    'view_custom_fields' =>
                        $this->viewCustomFields($d->cfR, $d->cvR, $inv_custom_values),
                    'partial_inv_attachments' =>
                        $this->viewPartialInvAttachments(
                            $_language, $url_key, $client_id, $d->upR),
                    'partial_inv_delivery_location' =>
                            $this->viewPartialDeliveryLocation(
                                $_language, $d->dlR, (int) $delivery_location_id),
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
                        $d->iaR,
                        $this->userService->hasPermission(Permissions::EDIT_INV),
                        $read_only,
                        $enabled_gateways,
                        $this->sR->getSetting('enable_vat_registration'),
                        $d->cfR->repoTableCountquery('payment_custom') > 0,
                    ),
                ];
                if ($this->rbacObserver($inv, $d->ucR, $d->uiR)) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
                if ($this->rbacAdmin()) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
                if ($this->rbacAccountant()) {
                    return $this->webViewRenderer->render('view', $parameters);
                }
            }
            return $this->webService->getNotFoundResponse();
        }
        return $this->webService->getNotFoundResponse();
    }

    // resources/views/invoice/inv/partial_item_table has this route
    public function deleteInvTaxRate(#[RouteArgument('id')] int $id,
            ITRR $invtaxrateRepository):
        Response {
        try {
            $inv_tax_rate = $this->invtaxrate($id, $invtaxrateRepository);
            $this->inv_tax_rate_service->deleteInvTaxRate($inv_tax_rate);
            $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            unset($e);
        }
        $inv_id = (string) $this->session->get('inv_id');
        return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
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
            'invCustomForm' => new InvCustomForm(),
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
                    (int) $this->session->get('inv_id')),
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

    private function viewPartialItemTable(
        bool $show_buttons,
        int $id,
        InvViewDeps $d,
        ?InvAmount $invAmount,
    ): string {
        $inv = $this->inv($id, $d->iR, false);
        if ($inv) {
            $draft = $inv->reqStatusId() == '1';
            $inv_tax_rates = (($d->itrR->repoCount(
                (int) $this->session->get('inv_id')) > 0) ?
                    $d->itrR->repoInvquery((int) $this->session->get('inv_id'))
                    : null);
            $packHandleShipTotal = $d->aciR->getPackHandleShipTotal($inv->reqId());
            return $this->webViewRenderer->renderPartialAsString(
                '//invoice/inv/partial_item_table', [
                'packHandleShipTotal' => $packHandleShipTotal,
                'aciiR' => $d->aciiR,
                'draft' => $draft,
                'piR' => $d->piR,
                'showButtons' => $show_buttons,
                'included' => $this->translator->translate('item.tax.included'),
                'excluded' => $this->translator->translate('item.tax.excluded'),
                'products' => $d->pR->findAllPreloadedWithPrice(),
                'tasks' => $d->taskR->repoTaskStatusquery(3),
                'userCanEdit' => $this->userService->hasPermission(
                    Permissions::EDIT_INV),
                'invItems' => $d->iiR->repoInvquery((int) $this->session->get('inv_id')),
                'invItemAmountR' => $d->iiaR,
                'invTaxRates' => $inv_tax_rates,
                'invAmount' => $invAmount,
                'inv' => $d->iR->repoInvLoadedquery(
                    (int) $this->session->get('inv_id')),
                'taxRates' => $d->trR->findAllPreloaded(),
                'units' => $d->unR->findAllPreloaded(),
            ]);
        }
        return '';
    }

    private function displayEditDeleteButtons(bool $read_only): bool
    {
        if (!$read_only
                && ($this->sR->getSetting('disable_read_only') === (string) 0)) {
            return true;
        }
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
