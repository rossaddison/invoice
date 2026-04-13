<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Entity\{Inv};
use App\Invoice\{
    Client\ClientRepository as CR,
    Group\GroupRepository as GR,
    Inv\InvForm,
    TaxRate\TaxRateRepository as TRR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR
};
use App\User\UserRepository as UR;
use App\Widget\{Bootstrap5ModalInv};
use Yiisoft\{
    FormModel\FormHydrator, Http\Method, Router\HydratorAttribute\RouteArgument,
};
use Psr\{
    Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait Add
{
    /**
     * Related logic: see config/common/routes.php search 'inv/add'
     * Only the admin has the EDIT_INV permission and can add an invoice.
     */
    public function add(
        Request $request,
        #[RouteArgument('origin')]
        string $origin,
        FormHydrator $formHydrator,
        CR $clientRepository,
        GR $gR,
        TRR $trR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
    ): Response {
        $inv = new Inv();
        $errors = [];
        $form = new InvForm($inv);
        $bootstrap5ModalInv = new Bootstrap5ModalInv(
            $this->translator,
            $this->webViewRenderer,
            $clientRepository,
            $gR,
            $this->sR,
            $ucR,
            $form,
        );
        // An invoice can originate and be added from the following pages:
        // 1. Main Menu e.g /invoice
        // 2. Client Menu e.g. /invoice/client/view/25
        // 3. Invoice Menu e.g. /invoice/inv
        // 4. Dashboard e.g. /invoice/dashboard
        // Use the RouteArgument's origin argument to return to correct origin

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
                    if (null !== $user_client && null !==
                            $user_client->getClient()) {
                        // no warning necessary a user client relationship exists
                    } else {
                        $this->flashMessage('danger',
                            $clientRepository->repoClientquery(
                                $client_id)->getClientFullName()
                                    . ': ' . $this->translator->translate(
                                            'user.client.no.account'));
                    }
// Ensure that the client has only one (paying) user account otherwise reject
// this invoice
// Related logic: see UserClientRepository function
// get_not_assigned_to_user which ensures that only clients that have   NOT
// been assigned to a user account are presented in the dropdown box for
// available clients. So this line is an extra measure to ensure that the
// invoice is being made out to the correct payer ie. not more than one user
// is associated with the client.

$user = $this->activeUser($client_id, $uR, $ucR, $uiR);
                    if (null !== $user) {
                        $saved_model = $this->inv_service->saveInv(
                                $user, $inv, $body, $this->sR, $gR);
                        /**
                         * The InvAmount entity is created automatically during
                         * the above saveInv
                         * Related logic: see src\Invoice\Entity\Inv ...
                         * New InvAmount();
                         */
                        $model_id = $saved_model->getId();
                        if (null !== $model_id) {
                            $this->defaultTaxes($inv, $trR, $formHydrator);
                            // Inform the user of generated invoice number for
                            // draft setting
                            $this->flashMessage('info', $this->sR->getSetting(
                                'generate_invoice_number_for_draft') === '1'
                            ? $this->translator->translate(
                                    'generate.invoice.number.for.draft')
                                    . '=>' . $this->translator->translate('yes')
                            : $this->translator->translate(
                                    'generate.invoice.number.for.draft')
                                    . '=>' . $this->translator->translate('no'));
                            $this->sR->getSetting('mark_invoices_sent_copy') === '1'
                            ? $this->flashMessage('danger',
                                $this->translator->translate('mark.sent.copy.on'))
                            : '';
                        } //$model_id
                        $this->flashMessage('success',
                                $this->translator->translate(
                            'record.successfully.created'));
                        if (($origin == 'main') || ($origin == 'inv')) {
                            return $this->webService->getRedirectResponse(
                                'inv/view', ['id' => $model_id]);
                        }
                        if ($origin == 'dashboard') {
                            return $this->webService->getRedirectResponse(
                                'inv/view', ['id' => $model_id]);
                        }
                        // otherwise return to new invoice view (client origin)
                        return $this->webService->getRedirectResponse(
                                'inv/view', ['id' => $model_id]);
                    }
                    $this->flashMessage('warning', $this->translator->translate(
                            'user.client.active.no'));
                }
            }
            $this->flashMessage('warning', $this->translator->translate(
                    'creation.unsuccessful'));
            $errors =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        } // POST
        // show the form without a modal when using the main menu
        if (($origin == 'main') || ($origin == 'dashboard')) {
            // update the errors array with latest errors
            $bootstrap5ModalInv->renderPartialLayoutWithFormAsString(
                $origin, $errors);
            // do not use the layout just get the formParameters
            $parameters = $bootstrap5ModalInv->getFormParameters();
            /**
             * @psalm-suppress MixedArgumentTypeCoercion $parameters
             */
            return $this->webViewRenderer->render('modal_add_inv_form', $parameters);
        }
        // show the form inside a modal when engaging with a view
        if ($origin == 'inv') {
            return $this->webViewRenderer->render('modal_layout', [
                // use type to id the inv\modal_layout.php eg.
                // ->options(['id' => 'modal-add-'.$type,
                'type' => 'inv',
                'form' => $bootstrap5ModalInv->renderPartialLayoutWithFormAsString(
                    $origin, $errors),
                'return_url_action' => 'add',
            ]);
        }
        // Otherwise return to client
        if (($origin != 'main') && ($origin != 'inv') && ($origin != 'dashboard')) {
            return $this->webViewRenderer->render('modal_layout', [
                'type' => 'client',
                'form' =>
                $bootstrap5ModalInv->renderPartialLayoutWithFormAsString(
                    $origin, $errors),
                'return_url_action' => 'add',
            ]);
        }
        return $this->webService->getNotFoundResponse();
    } 
}
