<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\{
    Group\Group, Inv\Inv, InvAmount\InvAmount
};

use App\Invoice\{
    Inv\InvCreditDeps,
    Inv\InvCreateCreditCoreDeps,
    Inv\InvCreateCreditUserDeps,
    Inv\InvForm,
};
use Yiisoft\{FormModel\FormHydrator, Http\Method, Json\Json, Security\Random};
use Psr\{Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait Credit
{
    // Reverse an invoice with a credit invoice /debtor/client/customer
    // credit note

    public function credit(
        Request $request,
        FormHydrator $formHydrator,
        InvCreditDeps $d,
    ): Response {
        $inv = new Inv();
        $form = new InvForm();
        $invAmount = new InvAmount();
        $defaultGroupId = (int) $this->sR->getSetting('default_invoice_group');
        $optionsGroupData = [];
        $groups = $d->gR->findAllPreloaded();
        /**
         * @var Group
         */
        foreach ($groups as $group) {
            $optionsGroupData[$group->reqId()] = $group->getName();
        }
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'inv/credit',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'clients' => $d->clientRepository->optionsData($d->ucR),
            'groups' => $optionsGroupData,
            'defaultGroupId' => $defaultGroupId,
            'urlKey' => Random::string(32),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body) && $formHydrator->populateFromPostAndValidate($form, $request)) {
                    // Only clients that were assigned to user accounts were
                    // made available in dropdown therefore use the 'user client'
                    // user id

                    /**
                     * @var string $body['client_id']
                     */
                    $client_id = (int) $body['client_id'];
                    $user_client = $d->ucR->repoUserquery($client_id);
                    if (null !== $user_client
                            && null !== $user_client->getClient()) {
                        $client_first_name =
                                $user_client->getClient()?->getClientName();
                        $client_surname =
                                $user_client->getClient()?->getClientSurname();
                        $client_fullname = ($client_first_name ?? '')
                                         . ' '
                                         . ($client_surname ?? '');
                    } else {
                        $this->flashMessage('warning',
                        $this->translator->translate('user.client.no.account'));
                    }
                    // Ensure that the client has only one (paying) user account
                    // otherwise reject this invoice
                    // Related logic: see UserClientRepository function
                    // get_not_assigned_to_user which ensures that only
                    // clients that have   NOT   been assigned to a user account
                    // are presented in the dropdown box for available clients
                    // So this line is an extra measure to ensure that the
                    // invoice is being made out to the correct payer
                    // ie. not more than one user is associated with the client.
                    $user = $this->activeUser($client_id, $d->uR, $d->ucR, $d->uiR);
                    if (null !== $user) {
                        $model_id = 0;
                        $this->inv_service->withTransaction(
                            function () use (
                                $user, $inv, $body, $d, $formHydrator,
                                $invAmount, &$model_id
                            ): void {
                                $saved_model = $this->inv_service->saveInv(
                                    $user, $inv, $body, $this->sR, $d->gR);
                                $model_id = $saved_model->reqId();
                                if ($model_id > 0) {
                                    $this->inv_amount_service->initializeInvAmount(
                                        $invAmount, $model_id);
                                    $this->defaultTaxes(
                                        $saved_model, $d->trR, $formHydrator);
                                }
                            }
                        );
                        if ($model_id > 0) {
                            $this->flashMessage('info', $this->sR->getSetting(
                                    'generate_invoice_number_for_draft') === '1'
                            ? $this->translator->translate(
                                    'generate.invoice.number.for.draft')
                                    . '=>' . $this->translator->translate('yes')
                            : $this->translator->translate(
                                    'generate.invoice.number.for.draft')
                                    . '=>' . $this->translator->translate('no'));
                        } //$model_id
                        return $this->webService->getRedirectResponse('inv/index');
                    } //null!==$user
                    // In the event of the database being manually edited
                    // (highly unlikely) present this warning anyway
                    if (!empty($client_fullname)) {
                        $message = $this->translator->translate(
                            'user.inv.more.than.one.assigned')
                                . ' ' . (string) $client_fullname;
                        $this->flashMessage('warning', $message);
                    }
                    return $this->webService->getRedirectResponse('inv/index');
            }
            $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->webViewRenderer->render('_form_create_confirm', $parameters);
    }

    /**
     * Related logic: see src/Invoice/Asset/rebuild1.13/js/inv.js function
     *  $(document).on('click', '#create-credit-confirm', function ()
     * Related logic: see resources/views/invoice/inv/modal_create_credit
     */
    public function createCreditConfirm(
        Request $request,
        FormHydrator $formHydrator,
        InvCreateCreditCoreDeps $core,
        InvCreateCreditUserDeps $userDeps,
    ): Response {
        $body = $request->getQueryParams();
        $basis_inv = $core->iR->repoInvLoadedquery((int) $body['inv_id']);
        if (null !== $basis_inv) {
            $basis_inv_id = (int) $body['inv_id'];
            // Set the basis_inv to read-only;
            $basis_inv->setIsReadOnly(true);
            // Credit Note's details
            $ajax_body = [
                'client_id' => $body['client_id'],
                'group_id' => 4,
                'user_id' => $body['user_id'],
                'status_id' => $basis_inv->reqStatusId(),
                'is_read_only' => true,
                'number' => $core->gR->generateNumber(4, true),
                'discount_amount' => $basis_inv->getDiscountAmount(),
                'url_key' => '',
                'password' => $body['password'],
                'payment_method' => 0,
                'terms' => '',
                'delivery_location_id' => $basis_inv->getDeliveryLocationId(),
            ];
            // Save the basis invoice as soon as we have the new credit note's id
            $new_inv = new Inv();
            $form = new InvForm();
            if ($formHydrator->populateAndValidate($form, $ajax_body)) {
                /**
                 * @var string $ajax_body['client_id']
                 */
                $client_id = (int) $ajax_body['client_id'];
                $user = $this->activeUser($client_id, $userDeps->uR, $userDeps->ucR, $userDeps->uiR);
                if (null !== $user) {
                    $saved_inv_id = 0;
                    $this->inv_service->withTransaction(
                        function () use (
                            $user, $new_inv, $ajax_body, $core, $basis_inv_id,
                            $basis_inv, &$saved_inv_id
                        ): void {
                            $saved_inv = $this->inv_service->saveInv(
                                $user, $new_inv, $ajax_body, $this->sR, $core->gR);
                            $saved_inv_id = $saved_inv->reqId();
                            if ($saved_inv_id > 0) {
                                $savedInvId = (string) $saved_inv_id;
                                $this->inv_item_service->initializeCreditInvItems(
                                    $basis_inv_id, $savedInvId, $core->iiR, $core->iiaR);
                                $this->inv_amount_service->initializeCreditInvAmount(
                                    new InvAmount(), $basis_inv_id, $savedInvId);
                                $this->inv_tax_rate_service->initializeCreditInvTaxRate(
                                    $basis_inv_id, $savedInvId);
                                // Record the new Credit Note's id in the basis invoice
                                $basis_inv->setCreditinvoiceParentId($saved_inv_id);
                                $core->iR->save($basis_inv);
                            }
                        }
                    );
                    if ($saved_inv_id > 0) {
                        $parameters = [
                            'success' => 1,
                            'flash_message' => $this->translator->translate(
                                'credit.note.creation.successful'),
                        ];
                        //return response to inv.js to reload page at location
                        return $this->factory->createResponse(
                            Json::encode($parameters));
                    }
                } //null!==$user
            } // ajax
        } //null!==$basis_inv
        return $this->factory->createResponse(Json::encode([
            'success' => 0,
            'message' =>
            $this->translator->translate('credit.note.creation.unsuccessful'),
        ]));
    }
}
