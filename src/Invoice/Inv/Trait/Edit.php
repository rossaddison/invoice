<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Auth\Permissions;
use App\Invoice\{
    Client\ClientRepository as CR,
    Contract\ContractRepository as ContractRepo,
    CustomValue\CustomValueRepository as CVR,
    CustomField\CustomFieldRepository as CFR,
    Delivery\DeliveryRepository as DelRepo,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    Inv\InvForm,
    InvCustom\InvCustomRepository as ICR,
    InvCustom\InvCustomForm,
    InvAmount\InvAmountRepository as IAR,
    PaymentMethod\PaymentMethodRepository as PMR,
    PostalAddress\PostalAddressRepository as paR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR
};
use App\User\UserRepository as UR;
use App\Infrastructure\Persistence\InvCustom\InvCustom;
use App\Invoice\Helpers\{
    CustomValuesHelper as CVH, Peppol\PeppolArrays
};
use Yiisoft\{
    FormModel\FormHydrator, Html\Html, Http\Method, 
    Router\HydratorAttribute\RouteArgument 
};
use Psr\{
    Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait Edit
{
    public function edit(
        Request $request,
        #[RouteArgument('id')]
        int $id,
        FormHydrator $formHydrator,
        IR $invRepo,
        CR $clientRepo,
        ContractRepo $contractRepo,
        DelRepo $deliveryRepo,
        DLR $delRepo,
        GR $groupRepo,
        PMR $pmRepo,
        UR $userRepo,
        IAR $iaR,
        CFR $cfR,
        CVR $cvR,
        ICR $icR,
        paR $paR,
        UCR $ucR,
        UIR $uiR,
    ): Response {
        $inv = $this->inv($id, $invRepo, true);
        if ($inv) {
            $form = InvForm::show($inv);
            $invCustomForm = new InvCustomForm();
            $inv_id = $inv->reqId();
            $client_id = $inv->reqClientId();
            $peppol_array = new PeppolArrays();
            $note_on_tax_point = '';
            $defaultGroupId = (int) $this->sR->getSetting('default_invoice_group');
            if (($this->sR->getSetting('debug_mode') == '1')
                    && $this->userService->hasPermission(Permissions::EDIT_INV)) {
                $note_on_tax_point = $this->webViewRenderer->renderPartialAsString(
                    '//invoice/info/taxpoint');
            }
            $parameters = [
                'actionName' => 'inv/edit',
                'actionArguments' => ['id' => $inv_id],
                'contractCount' => $contractRepo->repoClientCount(
                    $inv->reqClientId()),
                'customFields' => $this->fetchCustomFieldsAndValues(
                    $cfR, $cvR, 'inv_custom')['customFields'],
                'cvH' => new CVH($this->sR, $cvR),
                // Applicable to normally building up permanent selection lists
                // eg. dropdowns
                'customValues' => $this->fetchCustomFieldsAndValues(
                    $cfR, $cvR, 'inv_custom')['customValues'],
                // There will initially be no custom_values attached to this
                // invoice until they are filled in the field on the form
                'defaultGroupId' => $defaultGroupId,
                'delCount' => $delRepo->repoClientCount($inv->reqClientId()),
                'deliveryCount' => $deliveryRepo->repoCountInvoice($inv_id),
                'editInputAttributesPaymentMethod' =>
                    $this->editInputAttributesPaymentMethod($form),
                'editInputAttributesUrlKey' =>
                    $this->editInputAttributesUrlKey($form),
                'errors' => [],
                'form' => $form,
                'inv' => $inv,
                'invs' => $invRepo->findAllPreloaded(),
                'invCustomValues' => $this->invCustomValues($inv_id, $icR),
                'invCustomForm' => $invCustomForm,
                'noteOnTaxPoint' => $note_on_tax_point ?: '',
                'originId' => $inv->reqId(),
                'optionsData' => $this->editOptionsData(
                    $peppol_array,
                    $inv,
                    $client_id,
                    $clientRepo,
                    $contractRepo,
                    $deliveryRepo,
                    $delRepo,
                    $groupRepo,
                    $invRepo,
                    $paR,
                    $pmRepo,
                    $ucR,
                ),
                'paR' => $paR,
                'postalAddressCount' =>
                    $paR->repoClientCount($inv->reqClientId()),
                'formFields' => $this->formFields,
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                /**
                 * PossiblyInvalidArgument $body
                 */
                if (is_array($body)) {
                    // If the status has changed to 'paid', check that the
                    // balance on the invoice is zero
                    if (!$this->editCheckStatusReconcilingWithBalance(
                            $iaR, $inv_id) && $body['status_id'] === 4) {
                        return $this->factory->createResponse(
                            $this->webViewRenderer->renderPartialAsString(
                            '//invoice/setting/inv_message',
                            [
                                'heading' =>
                                    $this->translator->translate('errors'),
                                'message' =>
                                    $this->translator->translate('error')
                                    . $this->translator->translate(
                                            'balance.does.not.equal.zero'),
                                'url' => 'inv/view', 'id' => $inv_id],
                        ));
                    }
                    $ret_form = $this->editSaveFormFields(
                        $body, $id, $formHydrator, $invRepo, $groupRepo,
                            $userRepo, $ucR, $uiR);
                    $parameters['form'] = $ret_form;
                    if ($ret_form instanceof InvForm) {
                        if (!$ret_form->isValid()) {
                            $parameters['form'] = $ret_form;
                            $parameters['errors'] =
                                $ret_form->getValidationResult()
                                         ->getErrorMessagesIndexedByProperty();
                            return $this->webViewRenderer->render('_form_edit',
                                $parameters);
                        }
                        $this->processCustomFields($body, $formHydrator,
                                $this->customFieldProcessor, $inv_id);
                        $this->flashMessage('success',
                            $this->translator->translate(
                            'record.successfully.updated'));
                        return $this->webService->getRedirectResponse('inv/view',
                            ['id' => $inv_id]);
                    }
                } //$body
                return $this->webService->getRedirectResponse('inv/index');
            }
            if ($this->rbacAdmin()) {
                return $this->webViewRenderer->render('_form_edit', $parameters);
            }
        } // if $inv_id
        return $this->webService->getRedirectResponse('inv/index');
    }
    
    private function editInputAttributesUrlKey(InvForm $form): array
    {
        $inputAttributesUrlKey = [
            'class' => 'form-control form-control-lg',
            'readonly' => 'readonly',
            'value' => $form->getUrlKey(),
        ];
        // do not display the url key if it is a draft invoice otherwise
        // display the url key
        if ($form->getStatusId() == 1) {
            $inputAttributesUrlKey['hidden'] = 'hidden';
        } else {
            $inputAttributesUrlKey['placeholder'] =
                    $this->translator->translate('url.key');
        }
        return $inputAttributesUrlKey;
    }

    private function editInputAttributesPaymentMethod(InvForm $form): array
    {
        if ($form->getIsReadOnly() && $form->getStatusId() == 4) {
            $inputAttributesPaymentMethod = [
                'class' => 'form-control form-control-lg',
                'disabled' => 'disabled',
            ];
        } else {
            $inputAttributesPaymentMethod = [
                'class' => 'form-control form-control-lg',
                'value' => $form->getPaymentMethod() ??
                ($this->sR->getSetting('invoice_default_payment_method') ?: 1),
            ];
        }
        return $inputAttributesPaymentMethod;
    }    

    public function editCheckStatusReconcilingWithBalance(IAR $iaR,
        int $inv_id): bool
    {
        $invoice_amount = $iaR->repoInvquery($inv_id);
        if (null !== $invoice_amount) {
            // If the invoice is fully paid up allow the status
            // to change to 'paid'
            return $invoice_amount->getBalance() == 0.00;
        }
        return false;
    }

    public function editSaveFormFields(array|object|null $body, int $id,
        FormHydrator $formHydrator, IR $invRepo, GR $groupRepo, UR $uR,
            UCR $ucR, UIR $uiR): ?InvForm
    {
        $inv = $this->inv($id, $invRepo, true);
        if ($inv) {
            $client_id = $inv->reqClientId();
            $user = $this->activeUser($client_id, $uR, $ucR, $uiR);
            if (null !== $user) {
                $form = InvForm::show($inv);
                if (null !== $body && is_array($body)) {
                    if ($formHydrator->populateAndValidate($form, $body)) {
                        $this->inv_service->saveInv($user, $inv, $body,
                            $this->sR, $groupRepo);
                    }
                }
                return $form;
            } // null !== $user
        }  // $inv
        return null;
    }
}
