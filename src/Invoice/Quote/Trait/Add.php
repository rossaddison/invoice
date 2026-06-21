<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Infrastructure\Persistence\{
    Quote\Quote, QuoteAmount\QuoteAmount
};
use App\Invoice\{
    Group\GroupRepository as GR,
    Quote\QuoteAddDeps,
    Quote\QuoteForm,
    TaxRate\TaxRateRepository as TRR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};
use App\User\UserRepository as UR;
use App\Widget\Bootstrap5ModalQuote;
use Yiisoft\{
    FormModel\FormHydrator,
    Http\Method,
    Json\Json,
    Router\HydratorAttribute\RouteArgument,
};
use Psr\{
    Http\Message\ResponseInterface as Response,
    Http\Message\ServerRequestInterface as Request,
};

trait Add
{
    public function add(
        Request $request,
        #[RouteArgument('origin')]
        string $origin,
        FormHydrator $formHydrator,
        QuoteAddDeps $d,
    ): Response {
        $quote = new Quote();
        $errors = [];
        $form = new QuoteForm();
        $bootstrap5ModalQuote = new Bootstrap5ModalQuote(
            $this->translator,
            $this->webViewRenderer,
            $d->clientRepository,
            $d->gR,
            $this->sR,
            $d->ucR,
            $form,
        );

        // A quote can originate and be added from the following pages:
        // 1. Main Menu e.g /invoice
        // 2. Client Menu e.g. /invoice/client/view/25
        // 3. Quote Menu e.g. /invoice/quote
        // 4. Dashboard e.g. /invoice/dashboard
        // Use the RouteArgument's origin argument to return to correct origin
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                // Only clients that were assigned to user accounts were made
                // available in dropdown
                // therefore use the 'user client' user id
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $client_id = (int) $body['client_id'];
                    $client_fullname = '';
                    $user_client = $d->ucR->repoUserquery($client_id);
                    if (null !== $user_client &&
                        null !== $user_client->getClient()) {
                        $client_first_name =
                            $user_client->getClient()?->getClientName();
                        $client_surname =
                            $user_client->getClient()?->getClientSurname();
                        $client_fullname =
                            ($client_first_name ?? '')
                                         . ' '
                                         . ($client_surname ?? '');
                    } else {
                        $this->flashMessage('danger',
                            $d->clientRepository->repoClientquery($client_id)
                                ->getClientFullName() . ': '
                                    . $this->translator->translate(
                                        'user.client.no.account'));
                    }
                    // Ensure that the client has only one (paying) user account
                    // otherwise reject this quote
                    // Related logic: see UserClientRepository function
                    // get_not_assigned_to_user which ensures that only
                    // clients that have   NOT   been assigned to a user account
                    // are presented in the dropdown box for available clients
                    // So this line is an extra measure to ensure that the quote
                    // is being made out to the correct payer
                    // ie. not more than one user is associated with the client.
                    $user = $this->activeUser($client_id, $d->uR, $d->ucR, $d->uiR);
                    if (null !== $user) {
                        $saved_model =
                            $this->quote_service->saveQuote(
                                $user, $quote, $body, $this->sR, $d->gR);
                        if ($saved_model->hasIdentity()) {
                            /**
                             * The QuoteAmount entity is created automatically
                             * during the above saveQuote
                             * Related logic: see src\Invoice\Entity\Quote
                             * $this->quoteAmount = new QuoteAmount();
                             */
                            $model_id = $saved_model->reqId();
                            $this->defaultTaxes($quote, $d->trR, $formHydrator);
                            // Inform the user of generated quote number for
                            // draft setting
                            $this->flashMessage('info',
                                $this->sR->getSetting(
                                    'generate_quote_number_for_draft') === '1'
                            ? $this->translator->translate(
                                'generate.quote.number.for.draft')
                                    . '=>'
                                    . $this->translator->translate('yes')
                            : $this->translator->translate(
                                'generate.quote.number.for.draft')
                                    . '=>'
                                    . $this->translator->translate('no'));
                            $this->flashMessage('success',
                                $this->translator->translate(
                                    'record.successfully.created')
                                    . '➡️ '
                                    . $client_fullname);
                            return $this->webService->getRedirectResponse(
                                'quote/view', ['id' => $model_id]);
                        }
                    }
                }
            }
            $errors = $form->getValidationResult()
                           ->getErrorMessagesIndexedByProperty();
        } // POST
        // show the form without a modal when using the main menu or dashboard
        if ($origin == 'main' || $origin == 'dashboard') {
            // update the errors array with latest errors
            $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString(
                $origin, $errors);
            // do not use the layout just get the formParameters
            $parameters = $bootstrap5ModalQuote->getFormParameters();
            /**
             * @psalm-suppress MixedArgumentTypeCoercion $parameters
             */
            return $this->webViewRenderer->render(
                'modal_add_quote_form', $parameters);
        }
        // show the form inside a modal when engaging with a view (quote or client origin)
        $type = ($origin == 'quote') ? 'quote' : 'client';
        return $this->webViewRenderer->render('modal_layout', [
            // use type to id the quote\modal_layout.php eg.
            // ->options(['id' => 'modal-add-'.$type,
            'type' => $type,
            'form' =>
                $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString(
                    $origin, $errors),
            'return_url_action' => 'add',
        ]);
    }

    /**
     * Data fed from quote.js->$(document).on('click',
     * '#quote_create_confirm', function () {
     */
    public function createConfirm(Request $request, FormHydrator $formHydrator,
        GR $gR, TRR $trR, UR $uR, UCR $ucR, UIR $uiR):
            Response
    {
        $body = $request->getQueryParams();
        $ajax_body = [
            'inv_id' => null,
            'so_id' => 0,
            'client_id' => (int) $body['client_id'],
            'group_id' => (int) $body['quote_group_id'],
            'status_id' => 1,
            // Generate a number based on the GroupRepository Next id value
            // and not on a newly generated quote_id
            // if generate_quote_number_for_draft is set to 'yes' otherwise set
            // to empty string ie. nothing.
            // Note: Clients cannot see draft quotes
            'number' => $this->sR->getSetting('generate_quote_number_for_draft')
                == '1' ? $gR->generateNumber(
                    (int) $body['quote_group_id'], true) : '',
            'discount_amount' => (float) 0,
            'discount_percent' => (float) 0,
            'url_key' => '',
            'password' => $body['quote_password'],
            'notes' => '',
        ];
        $unsuccessful =
            $this->translator->translate('quote.creation.unsuccessful');
        $quote = new Quote();
        $ajax_content = QuoteForm::show($quote);
        $successful = false;
        if ($formHydrator->populate($ajax_content, $ajax_body)
            && $ajax_content->isValid()) {
            $client_id = $ajax_body['client_id'];
            $user_client = $ucR->repoUserquery($client_id);
            // Ensure that the client has only one (paying) user account
            // otherwise reject this quote
            // Related logic: see UserClientRepository
            // function getNotAssignedToUser which ensures that only
            // clients that have   NOT   been assigned to a user account are
            // presented in the dropdown box for available clients
            // So this line is an extra measure to ensure that the invoice is
            // being made out to the correct payer
            // ie. not more than one user is associated with the client.
            $user_client_count = $ucR->repoUserquerycount($client_id);
            if (null !== $user_client && $user_client_count == 1) {
                // Only one user account per client
                $user_id = $user_client->reqUserId();
                $user = $uR->findById($user_id);
                $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                if (null !== $user_inv && $user_inv->getActive()) {
                    $saved_model = $this->quote_service->saveQuote(
                        $user, $quote, $ajax_body, $this->sR, $gR);
                    $model_id = $saved_model->reqId();
                    $this->quote_amount_service->initializeQuoteAmount(
                        new QuoteAmount(), $model_id);
                    $this->defaultTaxes($quote, $trR, $formHydrator);
                    // Inform the user of generated invoice number for
                    // draft setting
                    $this->flashMessage(
                        'info',
                        $this->sR->getSetting(
                            'generate_quote_number_for_draft') === '1'
                          ? $this->translator->translate(
                                'generate.quote.number.for.draft')
                                . '=>'
                                . $this->translator->translate('yes')
                          : $this->translator->translate(
                            'generate.quote.number.for.draft')
                            . '=>'
                            . $this->translator->translate('no'),
                    );
                    $successful = true;
                }
            // In the event of the database being manually edited
            // (highly unlikely) present this warning anyway
            } elseif ($user_client_count > 1) {
                $this->flashMessage('warning', $this->translator->translate(
                    'user.inv.more.than.one.assigned'));
            }
        }
        //return response to quote.js to reload page at location
        return $this->factory->createResponse(
            $successful
                ? Json::encode(['success' => 1])
                : Json::encode(['success' => 0, 'message' => $unsuccessful])
        );
    }
}
