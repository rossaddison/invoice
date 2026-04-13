<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Entity\{Inv, InvAmount};

use App\Invoice\{
    Client\ClientRepository as CR,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    Inv\InvForm,
    InvItem\InvItemRepository as IIR,
    InvItemAmount\InvItemAmountRepository as IIAR,
    TaxRate\TaxRateRepository as TRR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR
};
use App\User\UserRepository as UR;
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
        CR $clientRepository,
        GR $gR,
        TRR $trR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
    ): Response {
        $inv = new Inv();
        $form = new InvForm($inv);
        $invAmount = new InvAmount();
        $defaultGroupId = (int) $this->sR->getSetting('default_invoice_group');
        $optionsGroupData = [];
        $groups = $gR->findAllPreloaded();
        /**
         * @var \App\Invoice\Entity\Group
         */
        foreach ($groups as $group) {
            $optionsGroupData[$group->getId()] = $group->getName();
        }
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'inv/credit',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'clients' => $clientRepository->optionsData($ucR),
            'groups' => $optionsGroupData,
            'defaultGroupId' => $defaultGroupId,
            'urlKey' => Random::string(32),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    // Only clients that were assigned to user accounts were
                    // made available in dropdown therefore use the 'user client'
                    // user id

                    /**
                     * @var string $body['client_id']
                     */
                    $client_id = $body['client_id'];
                    $user_client = $ucR->repoUserquery($client_id);
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
                    $user = $this->activeUser($client_id, $uR, $ucR, $uiR);
                    if (null !== $user) {
                        $saved_model = $this->inv_service->saveInv($user,
                                $inv, $body, $this->sR, $gR);
                        $model_id = $saved_model->getId();
                        if (null !== $model_id) {
                            $this->inv_amount_service->initializeInvAmount(
                                    $invAmount, $model_id);
                            $this->defaultTaxes($inv, $trR, $formHydrator);
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
    public function createCreditConfirm(Request $request,
            FormHydrator $formHydrator, IR $iR, GR $gR, IIR $iiR, IIAR $iiaR,
                UCR $ucR, UIR $uiR, UR $uR): Response
    {
        $body = $request->getQueryParams();
        $basis_inv = $iR->repoInvLoadedquery((string) $body['inv_id']);
        if (null !== $basis_inv) {
            $basis_inv_id = (string) $body['inv_id'];
            // Set the basis_inv to read-only;
            $basis_inv->setIsReadOnly(true);
            // Credit Note's details
            $ajax_body = [
                'client_id' => $body['client_id'],
                'group_id' => 4,
                'user_id' => $body['user_id'],
                'status_id' => $basis_inv->getStatusId(),
                'is_read_only' => true,
                'number' => $gR->generateNumber(4, true),
                'discount_amount' => $basis_inv->getDiscountAmount(),
                'url_key' => '',
                'password' => $body['password'],
                'payment_method' => 0,
                'terms' => '',
                'delivery_location_id' => $basis_inv->getDeliveryLocationId(),
            ];
            // Save the basis invoice as soon as we have the new credit note's id
            $new_inv = new Inv();
            $form = new InvForm($new_inv);
            if ($formHydrator->populateAndValidate($form, $ajax_body)) {
                /**
                 * @var string $ajax_body['client_id']
                 */
                $client_id = $ajax_body['client_id'];
                $user = $this->activeUser($client_id, $uR, $ucR, $uiR);
                if (null !== $user) {
                    $saved_inv = $this->inv_service->saveInv($user, $new_inv,
                        $ajax_body, $this->sR, $gR);
                    $saved_inv_id = $saved_inv->getId();
                    if (null !== $saved_inv_id) {
                        $this->inv_item_service->initializeCreditInvItems(
                            (int) $basis_inv_id, $saved_inv_id, $iiR, $iiaR);
                        $this->inv_amount_service->initializeCreditInvAmount(
                            new InvAmount(), (int) $basis_inv_id, $saved_inv_id);
                        $this->inv_tax_rate_service->initializeCreditInvTaxRate(
                            (int) $basis_inv_id, $saved_inv_id);
                        $parameters = [
                            'success' => 1,
                            'flash_message' => $this->translator->translate(
                                'credit.note.creation.successful'),
                        ];
                        // Record the new Credit Note's $saved_inv_id in the
                        // basis invoice
                        $basis_inv->setCreditinvoiceParentId(
                            (int) $saved_inv_id);
                        $iR->save($basis_inv);
                        //return response to inv.js to reload page at location
                        return $this->factory->createResponse(
                            Json::encode($parameters));
                    } //null!== $saved_inv
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
