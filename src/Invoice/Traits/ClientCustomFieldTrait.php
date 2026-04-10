<?php

declare(strict_types=1);

namespace App\Invoice\Traits;

use App\Invoice\ClientCustom\ClientCustomForm;
use App\Invoice\ClientCustom\ClientCustomRepository as ccR;
use App\Invoice\Entity\ClientCustom;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Json\Json;

/**
 * Trait providing custom-field read/write methods for the Client domain.
 *
 * Requires the consuming class to expose:
 * @property \App\Invoice\ClientCustom\ClientCustomService $clientCustomService
 * @property \Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface $factory
 * @property \Yiisoft\Session\SessionInterface $session
 */
trait ClientCustomFieldTrait
{
    /**
     * Collect existing custom-field values stored against a client.
     *
     * @param string $cId
     * @param ccR $ccR
     * @return array<string, mixed>
     */
    public function clientCustomValues(string $cId, ccR $ccR): array
    {
        $custom_field_form_values = [];
        if ($ccR->repoClientCount($cId) > 0) {
            $client_custom_fields = $ccR->repoFields($cId);
            /**
             * @var int $key
             * @var string $val
             */
            foreach ($client_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' . (string) $key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }

    /**
     * AJAX endpoint: persist custom fields submitted as name/value pairs.
     *
     * @param FormHydrator $formHydrator
     * @param array<string, mixed> $body
     * @param string $cId
     * @param ccR $ccR
     * @return Response
     */
    public function customFields(FormHydrator $formHydrator, array $body,
            string $cId, ccR $ccR): Response
    {
        if (empty($body['custom'])) {
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        }
        /** @var array<array-key, array<string, string>> $raw */
        $raw = $body['custom'];
        $this->persistCustomFieldEntries(
            $this->buildCustomFieldDbArray($raw),
            $cId, $ccR, $formHydrator
        );
        return $this->factory->createResponse(Json::encode(['success' => 1]));
    }

    /**
     * AJAX endpoint: persist custom fields from the session client.
     *
     * @param FormHydrator $formHydrator
     * @param Request $request
     * @param ccR $ccR
     * @return Response
     */
    public function saveCustom(FormHydrator $formHydrator,
                                        Request $request, ccR $ccR): Response
    {
        $body = $request->getQueryParams();
        if (empty($body['custom'])) {
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        }
        /** @var array<array-key, array<string, string>> $raw */
        $raw = (array) $body['custom'];
        $cId = (string) $this->session->get('client_id');
        $this->persistCustomFieldEntries(
            $this->buildCustomFieldDbArray($raw),
            $cId, $ccR, $formHydrator
        );
        return $this->factory->createResponse(Json::encode(['success' => 1]));
    }

    /**
     * Parse AJAX name/value pairs into a field-id => value map.
     *
     * @param array<array-key, array<string, string>> $rawCustom
     * @return array<string, string|list<string>>
     */
    private function buildCustomFieldDbArray(array $rawCustom): array
    {
        $values = [];
        foreach ($rawCustom as $custom) {
            $matches = [];
            $name    = $custom['name'];
            $value   = $custom['value'];
            if (preg_match("/^(.*)\[\]$/i", $name, $matches)) {
                $values[$matches[1]][] = $value;
            } else {
                $values[$name] = $value;
            }
        }

        $dbArray = [];
        foreach ($values as $key => $value) {
            $matches = [];
            if (preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches)) {
                $dbArray[$matches[1]] = $value;
            }
        }
        return $dbArray;
    }

    /**
     * Upsert each custom field entry for the given client.
     *
     * @param array<string, string|list<string>> $dbArray  field-id => value map
     * @param string $cId
     * @param ccR $ccR
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function persistCustomFieldEntries(
        array $dbArray, string $cId, ccR $ccR, FormHydrator $formHydrator
    ): void {
        foreach ($dbArray as $key => $value) {
            $clientCustom = [
                'client_id'       => $cId,
                'custom_field_id' => $key,
                'value'           => $value,
            ];
            $model = $ccR->repoClientCustomCount($cId, $key) == 1
                ? $ccR->repoFormValuequery($cId, $key)
                : new ClientCustom();
            if ($model instanceof ClientCustom) {
                $form = new ClientCustomForm($model);
                if ($formHydrator->populateAndValidate($form, $clientCustom)) {
                    $this->clientCustomService->saveClientCustom($model, $clientCustom);
                }
            }
        }
    }
}
