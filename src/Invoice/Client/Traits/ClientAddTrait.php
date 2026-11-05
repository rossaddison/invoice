<?php

declare(strict_types=1);

namespace App\Invoice\Client\Traits;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Client\ClientDwellingPicker;
use App\Invoice\Client\ClientForm;
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
}
