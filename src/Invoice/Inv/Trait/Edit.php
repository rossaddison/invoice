<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Payment\Payment;
use App\Invoice\{
    Inv\InvEditCoreDeps,
    Inv\InvEditFormDeps,
    Inv\InvEditLocationDeps,
    Inv\InvEditPaymentDeps,
    Inv\InvForm,
    InvAmount\InvAmountRepository as IAR,
    InvCustom\InvCustomForm,
    Payment\PaymentService,
};
use App\Invoice\Helpers\{
    CustomValuesHelper as CVH, InvRecalculator, Peppol\PeppolArrays
};
use Yiisoft\{
    FormModel\FormHydrator, Http\Method,
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
        InvEditCoreDeps $core,
        InvEditLocationDeps $loc,
        InvEditFormDeps $form,
        InvEditPaymentDeps $pay,
    ): Response {
        $inv = $this->inv($id, $core->invRepo, true);
        if (!$inv) {
            return $this->webService->getRedirectResponse('inv/index');
        }
        $invForm = InvForm::show($inv);
        $invCustomForm = new InvCustomForm();
        $inv_id = $inv->reqId();
        $client_id = $inv->reqClientId();
        $peppol_array = new PeppolArrays();
        $note_on_tax_point = '';
        $defaultGroupId = (int) $this->sR->getSetting('default_invoice_group');
        // Tax point is a UK VAT concept (references informi.co.uk, UK VAT
        // reporting rules) — only relevant, so only shown, for the GBP/UK
        // locale. Both must hold: the session locale is 'en', and that
        // locale's own flag (SettingLocaleTrait::getLocaleFlags()) is
        // 'gb' — belt-and-braces against the flag mapping drifting
        // independently of the locale code later.
        $locale = (string) $this->session->get('_language');
        if ($locale === 'en'
                && ($this->sR->getLocaleFlags()[$locale] ?? null) === 'gb') {
            $note_on_tax_point = $this->webViewRenderer->renderPartialAsString(
                '//invoice/info/taxpoint'
            );
        }
        $parameters = [
            'actionName' => 'inv/edit',
            'actionArguments' => ['id' => $inv_id],
            'contractCount' => $loc->contractRepo->repoClientCount(
                $inv->reqClientId()
            ),
            'customFields' => $this->fetchCustomFieldsAndValues(
                $form->cfR,
                $form->cvR,
                'inv_custom'
            )['customFields'],
            'cvH' => new CVH($this->sR, $form->cvR),
            // Applicable to normally building up permanent selection lists
            // eg. dropdowns
            'customValues' => $this->fetchCustomFieldsAndValues(
                $form->cfR,
                $form->cvR,
                'inv_custom'
            )['customValues'],
            // There will initially be no custom_values attached to this
            // invoice until they are filled in the field on the form
            'defaultGroupId' => $defaultGroupId,
            'delCount' => $loc->delRepo->repoClientCount($inv->reqClientId()),
            'deliveryCount' => $loc->deliveryRepo->repoCountInvoice($inv_id),
            'editInputAttributesPaymentMethod' =>
                $this->editInputAttributesPaymentMethod($invForm),
            'editInputAttributesUrlKey' =>
                $this->editInputAttributesUrlKey($invForm),
            'errors' => [],
            'form' => $invForm,
            'inv' => $inv,
            'invs' => $core->invRepo->findAllPreloaded(),
            'invCustomValues' => $this->invCustomValues($inv_id, $form->icR),
            'invCustomForm' => $invCustomForm,
            'noteOnTaxPoint' => $note_on_tax_point ?: '',
            'originId' => $inv->reqId(),
            'optionsData' => $this->editOptionsData(
                $peppol_array,
                $inv,
                $client_id,
                $core,
                $loc,
                $form->pmRepo,
            ),
            'paR' => $loc->paR,
            'postalAddressCount' =>
                $loc->paR->repoClientCount($inv->reqClientId()),
            'formFields' => $this->formFields,
        ];
        $response = null;
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            /**
             * PossiblyInvalidArgument $body
             */
            if (!is_array($body)) {
                $response = $this->webService->getRedirectResponse('inv/index');
            } else {
                if ((int) ($body['status_id'] ?? 0) === 4) {
                    $this->settleBalanceOnStatusPaid(
                        $inv_id,
                        $form->iaR,
                        $core,
                        $pay->paymentService,
                        $pay->invRecalculator,
                    );
                }
                $response = $this->handleEditPost($body, $id, $inv_id, $parameters, $formHydrator, $core);
            }
        }
        if ($response !== null) {
            return $response;
        }
        return $this->rbacAdmin()
            ? $this->webViewRenderer->render('_form_edit', $parameters)
            : $this->webService->getRedirectResponse('inv/index');
    }

    /** @param array<string, mixed> $parameters */
    private function handleEditPost(
        array $body,
        int $id,
        int $inv_id,
        array $parameters,
        FormHydrator $formHydrator,
        InvEditCoreDeps $core,
    ): Response {
        $ret_form = $this->editSaveFormFields($body, $id, $formHydrator, $core);
        if (!($ret_form instanceof InvForm)) {
            return $this->webService->getRedirectResponse('inv/index');
        }
        $parameters['form'] = $ret_form;
        if (!$ret_form->isValid()) {
            $parameters['errors'] = $ret_form->getValidationResult()->getErrorMessagesIndexedByProperty();
            return $this->webViewRenderer->render('_form_edit', $parameters);
        }
        $this->processCustomFields($body, $formHydrator, $this->customFieldProcessor, $inv_id);
        $this->flashMessage('success', $this->translator->translate('record.successfully.updated'));
        return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
    }

    private function editInputAttributesUrlKey(InvForm $form): array
    {
        $inputAttributesUrlKey = [
            'class' => 'form-control form-control-lg',
            'readonly' => 'readonly',
            'value' => $form->url_key,
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
        if ($form->is_read_only && $form->getStatusId() == 4) {
            $inputAttributesPaymentMethod = [
                'class' => 'form-control form-control-lg',
                'disabled' => 'disabled',
            ];
        } else {
            $inputAttributesPaymentMethod = [
                'class' => 'form-control form-control-lg',
                'value' => $form->payment_method ??
                ($this->sR->getSetting('invoice_default_payment_method') ?: 1),
            ];
        }
        return $inputAttributesPaymentMethod;
    }

    private function settleBalanceOnStatusPaid(
        int $invId,
        IAR $iaR,
        InvEditCoreDeps $core,
        PaymentService $paymentService,
        InvRecalculator $invRecalculator,
    ): void {
        $invAmount = $iaR->repoInvquery($invId);
        if (null === $invAmount) {
            return;
        }
        $balance = $invAmount->getBalance() ?? 0.00;
        if ($balance <= 0.00) {
            return;
        }
        $inv = $core->invRepo->repoInvUnLoadedquery($invId);
        $pmId = $inv?->getPaymentMethod() ?? 0;
        $paymentService->savePayment(new Payment(), array_filter([
            'inv_id'            => $invId,
            'payment_date'      => (new \DateTimeImmutable())->format('Y-m-d'),
            'amount'            => $balance,
            'note'              => '',
            'payment_method_id' => $pmId > 0 ? $pmId : null,
        ], static fn (mixed $v): bool => $v !== null));
        $invRecalculator->recalculate($invId);
    }

    public function editSaveFormFields(
        array|object|null $body,
        int $id,
        FormHydrator $formHydrator,
        InvEditCoreDeps $core
    ): ?InvForm {
        $inv = $this->inv($id, $core->invRepo, true);
        if ($inv) {
            $client_id = $inv->reqClientId();
            $user = $this->activeUser($client_id, $core->userRepo, $core->ucR, $core->uiR);
            if (null !== $user) {
                $form = InvForm::show($inv);
                if (null !== $body && is_array($body) && $formHydrator->populateAndValidate($form, $body)) {
                    $this->inv_service->saveInv(
                        $user,
                        $inv,
                        $body,
                        $this->sR,
                        $core->groupRepo
                    );
                }
                return $form;
            } // null !== $user
        }  // $inv
        return null;
    }
}
