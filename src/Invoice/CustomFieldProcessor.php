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
     */
    public function exists(int $entityId, int $customFieldId): bool;

    /**
     * Find an existing custom field record.
     * @return object|null The existing custom field entity or null
     */
    public function findExisting(int $entityId, int $customFieldId): ?object;

    /**
     * Create a new custom field entity instance.
     * @return object The new custom field entity
     */
    public function createEntity(): object;

    /**
     * Create a form instance for the custom field entity.
     * @param object $entity The custom field entity
     * @return FormModelInterface The form instance
     */
    public function createForm(object $entity): FormModelInterface;

    /**
     * Prepare input data for form validation.
     *
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
