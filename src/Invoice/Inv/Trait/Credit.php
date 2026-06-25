<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\{
    Group\Group, Inv\Inv, InvAmount\InvAmount, User\User
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
                return $this->handleCreditPost($body, $inv, $invAmount, $formHydrator, $d);
            }
            $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->webViewRenderer->render('_form_create_confirm', $parameters);
    }

    private function resolveClientFullname(int $clientId, InvCreditDeps $d): string
    {
        $userClient = $d->ucR->repoUserquery($clientId);
        if (null === $userClient || null === $userClient->getClient()) {
            $this->flashMessage('warning',
                $this->translator->translate('user.client.no.account'));
            return '';
        }
        return ($userClient->getClient()?->getClientName() ?? '')
             . ' '
             . ($userClient->getClient()?->getClientSurname() ?? '');
    }

    private function saveCreditInv(
        User $user,
        Inv $inv,
        InvAmount $invAmount,
        array $body,
        FormHydrator $formHydrator,
        InvCreditDeps $d,
    ): int {
        $modelId = 0;
        $this->inv_service->withTransaction(
            function () use ($user, $inv, $body, $d, $formHydrator, $invAmount, &$modelId): void {
                $saved = $this->inv_service->saveInv($user, $inv, $body, $this->sR, $d->gR);
                $modelId = $saved->reqId();
                if ($modelId > 0) {
                    $this->inv_amount_service->initializeInvAmount($invAmount, $modelId);
                    $this->defaultTaxes($saved, $d->trR, $formHydrator);
                }
            }
        );
        return $modelId;
    }

    private function flashDraftNumberSetting(): void
    {
        $key = 'generate_invoice_number_for_draft';
        $label = $this->translator->translate($key);
        $value = $this->sR->getSetting($key) === '1'
            ? $this->translator->translate('yes')
            : $this->translator->translate('no');
        $this->flashMessage('info', $label . '=>' . $value);
    }

    private function handleCreditPost(
        array $body,
        Inv $inv,
        InvAmount $invAmount,
        FormHydrator $formHydrator,
        InvCreditDeps $d,
    ): Response {
        /**
         * @var string $body['client_id']
         */
        $clientId = (int) $body['client_id'];
        $clientFullname = $this->resolveClientFullname($clientId, $d);
        $user = $this->activeUser($clientId, $d->uR, $d->ucR, $d->uiR);
        if (null !== $user) {
            $modelId = $this->saveCreditInv($user, $inv, $invAmount, $body, $formHydrator, $d);
            if ($modelId > 0) {
                $this->flashDraftNumberSetting();
            }
            return $this->webService->getRedirectResponse('inv/index');
        }
        // In the event of the database being manually edited
        // (highly unlikely) present this warning anyway
        if (!empty($clientFullname)) {
            $this->flashMessage('warning',
                $this->translator->translate('user.inv.more.than.one.assigned')
                    . ' ' . $clientFullname);
        }
        return $this->webService->getRedirectResponse('inv/index');
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
