<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Invoice\Entity\{Quote, QuoteAmount};
use App\Invoice\{
    Client\ClientRepository as CR,
    Group\GroupRepository as GR,
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
        CR $clientRepository,
        GR $gR,
        TRR $trR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
    ): Response {
        $quote = new Quote();
        $errors = [];
        $form = new QuoteForm($quote);
        $bootstrap5ModalQuote = new Bootstrap5ModalQuote(
            $this->translator,
            $this->webViewRenderer,
            $clientRepository,
            $gR,
            $this->sR,
            $ucR,
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
                    /**
                     * @var string $body['client_id']
                     */
                    $client_id = $body['client_id'];
                    $client_fullname = '';
                    $user_client = $ucR->repoUserquery($client_id);
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
                            $clientRepository->repoClientquery((int) $client_id)
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
                    $user = $this->activeUser($client_id, $uR, $ucR, $uiR);
                    if (null !== $user) {
                        $saved_model =
                            $this->quote_service->saveQuote(
                                $user, $quote, $body, $this->sR, $gR);
                        /**
                         * The QuoteAmount entity is created automatically
                         * during the above saveQuote
                         * Related logic: see src\Invoice\Entity\Quote
                         * $this->quoteAmount = new QuoteAmount();
                         */
                        $model_id = $saved_model->getId();
                        if (null !== $model_id) {
                            $this->defaultTaxes($quote, $trR, $formHydrator);
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
                        } //$model_id
                        $this->flashMessage('success',
                            $this->translator->translate(
                                'record.successfully.created')
                                . '➡️ '
                                . $client_fullname);
                        if ($origin == 'main' || $origin == 'quote') {
                            return $this->webService->getRedirectResponse(
                                'quote/view', ['id' => $model_id]);
                        }
                        if ($origin == 'dashboard') {
                            return $this->webService->getRedirectResponse(
                                'quote/view', ['id' => $model_id]);
                        }
                        // otherwise return to new quote view (client origin)
                        return $this->webService->getRedirectResponse(
                            'quote/view', ['id' => $model_id]);
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
        // show the form inside a modal when engaging with a view
        if ($origin == 'quote') {
            return $this->webViewRenderer->render('modal_layout', [
                // use type to id the quote\modal_layout.php eg.
                // ->options(['id' => 'modal-add-'.$type,
                'type' => 'quote',
                'form' =>
                    $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString(
                        $origin, $errors),
                'return_url_action' => 'add',
            ]);
        }
        // Otherwise return to client
        if (($origin != 'main') && ($origin != 'quote')) {
            return $this->webViewRenderer->render('modal_layout', [
                'type' => 'client',
                'form' =>
                    $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString(
                        $origin, $errors),
                'return_url_action' => 'add',
            ]);
        }
        return $this->webService->getNotFoundResponse();
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
        $ajax_content = new QuoteForm($quote);
        if ($formHydrator->populate($ajax_content, $ajax_body)
            && $ajax_content->isValid()) {
            $client_id = $ajax_body['client_id'];
            $user_client = $ucR->repoUserquery((string) $client_id);
            // Ensure that the client has only one (paying) user account
            // otherwise reject this quote
            // Related logic: see UserClientRepository
            // function getNotAssignedToUser which ensures that only
            // clients that have   NOT   been assigned to a user account are
            // presented in the dropdown box for available clients
            // So this line is an extra measure to ensure that the invoice is
            // being made out to the correct payer
            // ie. not more than one user is associated with the client.
            $user_client_count = $ucR->repoUserquerycount((string) $client_id);
            if (null !== $user_client && $user_client_count == 1) {
                // Only one user account per client
                $user_id = $user_client->getUserId();
                $user = $uR->findById($user_id);
                if (null !== $user) {
                    $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                    if (null !== $user_inv && $user_inv->getActive()) {
                        $saved_model = $this->quote_service->saveQuote(
                            $user, $quote, $ajax_body, $this->sR, $gR);
                        $model_id = $saved_model->getId();
                        if (null !== $model_id) {
                            $this->quote_amount_service->initializeQuoteAmount(
                                new QuoteAmount(), (int) $model_id);
                            $this->defaultTaxes($quote, $trR, $formHydrator);
                            $parameters = ['success' => 1];
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
                            //return response to quote.js to reload page at
                            //location
                            return $this->factory->createResponse(
                                Json::encode($parameters));
                        }
                    } // null!==$user_inv && $user_inv->getActive()
                    return $this->factory->createResponse(
                        Json::encode([
                            'success' => 0,
                            'message' => $unsuccessful]));
                } // null!==$user
                return $this->factory->createResponse(
                    Json::encode(['success' => 0, 'message' => $unsuccessful]));
            } // null!== $user_client && $user_client_count==1
            // In the event of the database being manually edited
            // (highly unlikely) present this warning anyway
            if ($user_client_count > 1) {
                $this->flashMessage('warning', $this->translator->translate(
                    'user.inv.more.than.one.assigned'));
            }
            return $this->factory->createResponse(
                Json::encode(['success' => 0, 'message' => $unsuccessful]));
        }
        $parameters = [
            'success' => 0,
            'message' => $unsuccessful,
        ];
        //return response to quote.js to reload page at location
        return $this->factory->createResponse(Json::encode($parameters));
    }
}