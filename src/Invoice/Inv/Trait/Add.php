<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\{Inv\Inv, User\User};
use App\Invoice\{
    Inv\InvAddDeps,
    Inv\InvForm,
};
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
        InvAddDeps $d,
    ): Response {
        $inv = new Inv();
        $errors = [];
        $form = new InvForm();
        $bootstrap5ModalInv = new Bootstrap5ModalInv(
            $this->translator,
            $this->webViewRenderer,
            $d->clientRepository,
            $d->gR,
            $this->sR,
            $d->ucR,
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
            if (is_array($body) && $formHydrator->populateFromPostAndValidate($form, $request)) {
                    // Only clients that were assigned to user accounts were
                    // made available in dropdown therefore use the 'user client'
                    // user id
                    /**
                     * @var string $body['client_id']
                     */
                    $client_id = (int) $body['client_id'];
                    $user_client = $d->ucR->repoUserquery($client_id);
                    if (null !== $user_client && null !==
                            $user_client->getClient()) {
                        // no warning necessary a user client relationship exists
                    } else {
                        $this->flashMessage('danger',
                            $d->clientRepository->repoClientquery($client_id)
                                             ->getClientFullName()
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

$user = $this->activeUser($client_id, $d->uR, $d->ucR, $d->uiR);
                    if (null !== $user) {
                        return $this->handleSaveForUser($user, $inv, $body, $formHydrator, $d);
                    }
                    $this->flashMessage('warning', $this->translator->translate(
                            'user.client.active.no'));
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
        // show the form inside a modal when engaging with a view (inv or client origin)
        $type = ($origin == 'inv') ? 'inv' : 'client';
        return $this->webViewRenderer->render('modal_layout', [
            // use type to id the inv\modal_layout.php eg.
            // ->options(['id' => 'modal-add-'.$type,
            'type' => $type,
            'form' => $bootstrap5ModalInv->renderPartialLayoutWithFormAsString(
                $origin, $errors),
            'return_url_action' => 'add',
        ]);
    }

    /** @param array<array-key, mixed> $body */
    private function handleSaveForUser(
        User $user,
        Inv $inv,
        array $body,
        FormHydrator $formHydrator,
        InvAddDeps $d,
    ): Response {
        $saved_model = null;
        $this->inv_service->withTransaction(
            function () use ($user, $inv, $body, $d, $formHydrator, &$saved_model): void {
                $saved_model = $this->inv_service->saveInv($user, $inv, $body, $this->sR, $d->gR);
                if ($saved_model->hasIdentity()) {
                    $this->defaultTaxes($saved_model, $d->trR, $formHydrator);
                }
            }
        );
        $model_id = $saved_model?->reqId() ?? 0;
        if ($model_id > 0) {
            $this->flashMessage('info', $this->sR->getSetting(
                'generate_invoice_number_for_draft') === '1'
                ? $this->translator->translate('generate.invoice.number.for.draft')
                    . '=>' . $this->translator->translate('yes')
                : $this->translator->translate('generate.invoice.number.for.draft')
                    . '=>' . $this->translator->translate('no'));
            $this->sR->getSetting('mark_invoices_sent_copy') === '1'
                ? $this->flashMessage('danger', $this->translator->translate('mark.sent.copy.on'))
                : '';
        }
        $this->flashMessage('success', $this->translator->translate('record.successfully.created'));
        return $this->webService->getRedirectResponse('inv/view', ['id' => $model_id]);
    }
}
