<?php

declare(strict_types=1);

namespace App\Invoice\Client\Traits;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Client\ClientDwellingPicker;
use App\Infrastructure\Persistence\ClientCustom\ClientCustom;
use App\Invoice\Client\ClientForm;
use App\Invoice\ClientCustom\ClientCustomForm;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\FormModel\FormHydrator;

/**
 * Trait providing the POST handling of the Client add action, split out of
 * ClientController::add() to stay within SonarQube S1142 (≤ 3 returns per
 * method) and S1448 (≤ 20 methods per class).
 *
 * Requires the consuming class to expose:
 * @property \App\Invoice\Client\ClientService $clientService
 * @property \App\Invoice\ClientCustom\ClientCustomService $clientCustomService
 * @property \App\Service\WebControllerService $webService
 * @property \Yiisoft\Translator\TranslatorInterface $translator
 */
trait ClientAddTrait
{
    /**
     * Validation errors for a submitted add form -- the form's own errors, or
     * the claimed-Dwelling error -- or an empty array when it can be saved.
     *
     * @return array<string, list<string>>
     */
    private function addPostErrors(
        ClientForm $form,
        FormHydrator $formHydrator,
        Request $request,
        ClientDwellingPicker $dwellingPicker,
    ): array {
        if (!$formHydrator->populateFromPostAndValidate($form, $request)) {
            return $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->dwellingClaimed($form->dwelling_id, null, $dwellingPicker)
            ? $this->dwellingClaimedErrors()
            : [];
    }

    /**
     * Saves the validated new client and its custom fields. Returns the
     * redirect for a known origin, or null to re-render the form.
     */
    private function saveNewClient(
        ClientForm $form,
        Client $newClient,
        Request $request,
        FormHydrator $formHydrator,
        ?string $origin,
    ): ?Response {
        /** @var array<string, mixed> $body */
        $body = $request->getParsedBody() ?? [];
        $formName = $form->getFormName();
        /** @var array<string, mixed> $saveBody */
        $saveBody = isset($body[$formName]) && is_array($body[$formName])
            ? $body[$formName]
            : $body;
        $cId = $this->clientService->saveClient($newClient, $saveBody);
        if (null === $cId) {
            return null;
        }
        $this->saveNewClientCustomFields($saveBody, $cId, $formHydrator);
        $this->flashMessage(
            'info',
            $this->translator->translate('record.successfully.created')
        );
        return $this->redirectAfterAdd($origin);
    }

    /**
     * Save each custom field submitted with a newly created client.
     *
     * @param array<string, mixed> $body
     * @param int|string $cId
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function saveNewClientCustomFields(
        array $body,
        int|string $cId,
        FormHydrator $formHydrator,
    ): void {
        if (!isset($body['custom'])) {
            return;
        }
        /**
         * @var array<int|string, array<mixed>|string> $custom
         */
        $custom = $body['custom'];
        foreach ($custom as $custom_field_id => $value) {
            $clientCustom     = new ClientCustom();
            $clientCustomForm = new ClientCustomForm();
            $client_custom    = [
                'client_id'       => $cId,
                'custom_field_id' => $custom_field_id,
                'value'           => is_array($value) ? serialize($value) : $value,
            ];
            if ($formHydrator->populateAndValidate($clientCustomForm, $client_custom)) {
                $this->clientCustomService->saveClientCustom($clientCustom, $client_custom);
            }
        }
    }

    /**
     * Return the appropriate redirect after a successful client add, or null
     * if the origin does not map to a known route (falls back to re-rendering).
     *
     * @param string|null $origin
     * @return Response|null
     */
    private function redirectAfterAdd(?string $origin): ?Response
    {
        if ($origin === 'dashboard') {
            return $this->webService->getRedirectResponse('invoice/dashboard');
        }
        if ($origin === 'main' || $origin === 'add') {
            return $this->webService->getRedirectResponse(self::ROUTE_INDEX);
        }
        return null;
    }
}
