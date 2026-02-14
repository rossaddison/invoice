<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Auth\Permissions;
use App\Invoice\Traits\FlashMessage;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Invoice\CustomFieldProcessor;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

abstract class BaseController
{
    use FlashMessage;

    // New property for controller name
    protected string $controllerName = 'base';

    public function __construct(
        protected WebControllerService $webService,
        protected UserService $userService,
        protected TranslatorInterface $translator,
        protected ViewRenderer $viewRenderer,
        protected SessionInterface $session,
        protected SettingRepository $sR,
        protected Flash $flash,
    ) {
        $this->initializeViewRenderer();
    }

    /**
     * Initialize the view renderer based on user permissions.
     */
    protected function initializeViewRenderer(): void
    {
        if (!$this->userService->hasPermission(Permissions::VIEW_INV)
                && !$this->userService->hasPermission(Permissions::EDIT_INV)) {
            $this->viewRenderer =
            $this->viewRenderer->withControllerName($this->controllerName)
                     ->withLayout('@views/invoice/layout/fullpage-loader.php')
                     ->withLayout('@views/layout/templates/soletrader/main.php');
        } elseif ($this->userService->hasPermission(Permissions::VIEW_INV)
            && !$this->userService->hasPermission(Permissions::EDIT_INV)
            && !$this->userService->hasPermission(
                    Permissions::NO_ENTRY_TO_BASE_CONTROLLER)
            && $this->userService->hasPermission(
                    Permissions::ENTRY_TO_BASE_CONTROLLER)) {
            $this->viewRenderer =
            $this->viewRenderer->withControllerName($this->controllerName)
                     ->withLayout('@views/invoice/layout/fullpage-loader.php')
                     ->withLayout('@views/layout/guest.php');
        } elseif ($this->userService->hasPermission(Permissions::EDIT_INV)
            && !$this->userService->hasPermission(
                    Permissions::NO_ENTRY_TO_BASE_CONTROLLER)
            && $this->userService->hasPermission(
                    Permissions::ENTRY_TO_BASE_CONTROLLER)) {
            $this->viewRenderer =
            $this->viewRenderer->withControllerName($this->controllerName)
                     ->withLayout('@views/invoice/layout/fullpage-loader.php')
                     ->withLayout('@views/layout/invoice.php');
        }
    }

    /**
      * Render a view with common parameters.
      *
      * @param string $view
      * @param array<string, mixed> $parameters
      * @return Response
      */
    protected function render(string $view, array $parameters = []): Response
    {
        return $this->viewRenderer->render($view, $parameters);
    }

    /**
     * Create a flash alert partial.
     *
     * @return string
     */
    protected function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
                'flash' => $this->flash,
            ],
        );
    }

    /**
     * Fetch custom fields and attach their hard-coded values.
     *
     * This centralizes the common pattern used across multiple controllers so
     * they can reuse the same array shape without duplicating repository calls.
     *
     * @param CustomFieldRepository $cfR
          Repository for fetching custom fields
     * @param CustomValueRepository $cvR
          Repository for fetching custom values
     * @param string $tableName The custom table name (e.g. 'client_custom')
     * @return array{customFields: EntityReader, customValues: array<array-key, mixed>}
     */
    protected function fetchCustomFieldsAndValues(
        CustomFieldRepository $cfR,
        CustomValueRepository $cvR,
        string $tableName): array
    {
        $customFields = $cfR->repoTablequery($tableName);
        $customValues =
        $cvR->attach_hard_coded_custom_field_values_to_custom_field($customFields);

        return [
            'customFields' => $customFields,
            'customValues' => $customValues,
        ];
    }

/**
 * Process and validate custom fields for any entity type.
 *
 * This centralizes the common custom field validation pattern used across
 * all controllers, eliminating code duplication while maintaining
 * entity-specific behavior through callbacks.
 *
 * @param array|object|null $requestData
 * @param \Yiisoft\FormModel\FormHydrator $formHydrator
 * @param CustomFieldProcessor $processor
 * @param string|int $entityId
 * @return void
 */
    protected function processCustomFields(
        array|object|null $requestData,
        \Yiisoft\FormModel\FormHydrator $formHydrator,
        CustomFieldProcessor $processor,
        string|int $entityId,
    ): void {
        if (!is_array($requestData)) {
            return;
        }

        /** @var array $custom */
        $custom = $requestData['custom'] ?? [];

        // Handle both direct array format and AJAX format
        $processedCustom = $this->normalizeCustomFieldData($custom);

/**
 * @var string|int $custom_field_id (PHP may auto-convert numeric strings to int)
 * @var mixed $value
 */
        foreach ($processedCustom as $custom_field_id => $value) {
            // Check if custom field record already exists
            $entityIdStr = (string) $entityId;
            $customFieldIdStr = (string) $custom_field_id;

            if ($processor->exists($entityIdStr, $customFieldIdStr)) {
                // Update existing record
                $existingRecord =
                     $processor->findExisting($entityIdStr, $customFieldIdStr);
                if ($existingRecord) {
                    $form = $processor->createForm($existingRecord);
                    $inputData = $processor->prepareInputData((int) $entityId, (int)                                                                    $customFieldIdStr, $value);

                    if ($formHydrator->populateAndValidate($form, $inputData)) {
                        $processor->save($existingRecord, $inputData);
                    }
                }
            } else {
                // Create new record
                $newRecord = $processor->createEntity();
                $form = $processor->createForm($newRecord);
                $inputData = $processor->prepareInputData((int) $entityId, (int)                                                                        $customFieldIdStr, $value);

                if ($formHydrator->populateAndValidate($form, $inputData)) {
                    $processor->save($newRecord, $inputData);
                }
            }
        }
    }

/**
 * Normalize custom field data to handle both direct array format and AJAX format.
 *
 * @param array<array-key, mixed> $custom Raw custom field data
 * @return array<string, mixed> Normalized custom field data
 */
    private function normalizeCustomFieldData(array $custom): array
    {
// If the custom data is already in the format we expect
//  (direct array with field_id => value)
        if (!isset($custom[0]) || !is_array($custom[0])) {
            /** @var array<string, mixed> */
            return $custom;
        }

        // Handle AJAX format: array of objects with 'name' and 'value' keys
        /** @var array<string, mixed> $values */
        $values = [];

        foreach ($custom as $rawItem) {
            // Type guard: ensure $rawItem is an array with the expected structure
            if (!is_array($rawItem)
                    || !isset($rawItem['name'])
                        || !isset($rawItem['value'])) {
                continue;
            }

            /** @var array{name: mixed, value: mixed, ...} $item */
            $item = $rawItem;

            // Type guard: ensure name is convertible to string
            if (!is_string($item['name']) && !is_numeric($item['name'])) {
                continue;
            }

            $name = (string) $item['name'];

            // Type the value explicitly
            /** @var string|int|float|bool|array<mixed>|null $itemValue */
            $itemValue = $item['value'];

            if (preg_match("/^(.*)\[\]$/i", $name, $matches)) {
                $arrayKey = $matches[1];
                if (!isset($values[$arrayKey])) {
                    $values[$arrayKey] = [];
                }
                if (is_array($values[$arrayKey])) {
                    $values[$arrayKey][] = $itemValue;
                }
            } else {
                $values[$name] = $itemValue;
            }
        }

        /** @var array<string, mixed> $processedCustom */
        $processedCustom = [];

        /**
         * @var string $key
         * @var string|int|float|bool|array<mixed>|null $value
         */
        foreach ($values as $key => $value) {
            if (preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches)) {
                $fieldId = preg_match('/\d+/', $key, $m) ? $m[0] : '';
                if ($fieldId !== '') {
                    $processedCustom[$fieldId] = $value;
                }
            }
        }

        return $processedCustom;
    }
    
    /**
     * @param string $_language
     * @param DeliveryLocationRepository $dlr
     * @param string $delivery_location_id
     * @return string
     */
    protected function view_partial_delivery_location(
            string $_language, 
            DeliveryLocationRepository $dlr, 
            string $delivery_location_id): string
    {
        if (!empty($delivery_location_id)) {
            $del = $dlr->repoDeliveryLocationquery($delivery_location_id);
            if (null !== $del) {
                return $this->viewRenderer->renderPartialAsString(
                    '//invoice/inv/partial_inv_delivery_location', [
                    'actionName' => 'del/view',
                    'actionArguments' => [
                        '_language' => $_language,
                        'id' => $delivery_location_id
                    ],
                    'title' => $this->translator->translate('delivery.location'),
                    'building_number' => $del->getBuildingNumber(),
                    'address_1' => $del->getAddress_1(),
                    'address_2' => $del->getAddress_2(),
                    'city' => $del->getCity(),
                    'state' => $del->getZip(),
                    'country' => $del->getCountry(),
                    'global_location_number' => $del->getGlobal_location_number(),
                ]);
            } //null!==$del
        } else {
            return '';
        }
        return '';
    }    
}
