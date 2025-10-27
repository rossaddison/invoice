<?php

declare(strict_types=1);

namespace App\Invoice;

use Yiisoft\FormModel\FormModelInterface;

/**
 * Interface for processing custom fields for different entity types.
 *
 * This allows the BaseController to handle custom field validation in a
 * generic way while each entity (Invoice, Quote, Payment, etc.) can
 * provide its specific implementation details.
 */
interface CustomFieldProcessor
{
    /**
     * Check if a custom field record exists for the given entity and field.
     *
     * @param string $entityId The entity ID (invoice_id, quote_id, etc.)
     * @param string $customFieldId The custom field ID
     * @return bool True if the record exists
     */
    public function exists(string $entityId, string $customFieldId): bool;

    /**
     * Find an existing custom field record.
     *
     * @param string $entityId The entity ID
     * @param string $customFieldId The custom field ID
     * @return object|null The existing custom field entity or null
     */
    public function findExisting(string $entityId, string $customFieldId): ?object;

    /**
     * Create a new custom field entity instance.
     *
     * @return object The new custom field entity
     */
    public function createEntity(): object;

    /**
     * Create a form instance for the custom field entity.
     *
     * @param object $entity The custom field entity
     * @return FormModelInterface The form instance
     */
    public function createForm(object $entity): FormModelInterface;

    /**
     * Prepare input data for form validation.
     *
     * @param int $entityId The entity ID
     * @param int $customFieldId The custom field ID
     * @param mixed $value The field value (can be string or array)
     * @return array<string, mixed> The prepared input data
     */
    public function prepareInputData(int $entityId, int $customFieldId, mixed $value): array;

    /**
     * Save the custom field entity.
     *
     * @param object $entity The custom field entity
     * @param array<string, mixed> $inputData The input data
     * @return void
     */
    public function save(object $entity, array $inputData): void;
}
