<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\CustomFieldProcessor;
use App\Infrastructure\Persistence\QuoteCustom\QuoteCustom;
use App\Invoice\QuoteCustom\QuoteCustomForm;
use App\Invoice\QuoteCustom\QuoteCustomRepository;
use App\Invoice\QuoteCustom\QuoteCustomService;

/**
 * Custom field processor for Quote entities.
 *
 * Handles quote-specific custom field operations while implementing
 * the common CustomFieldProcessor interface.
 */
final class QuoteCustomFieldProcessor implements CustomFieldProcessor
{
    public function __construct(
        private readonly QuoteCustomRepository $repository,
        private readonly QuoteCustomService $service,
    ) {
    }

    #[\Override]
    public function exists(int $entityId, int $customFieldId): bool
    {
        return $this->repository->repoQuoteCustomCount($entityId, $customFieldId) > 0;
    }

    #[\Override]
    public function findExisting(int $entityId, int $customFieldId): ?\App\Infrastructure\Persistence\QuoteCustom\QuoteCustom
    {
        return $this->repository->repoFormValuequery($entityId, $customFieldId);
    }

    #[\Override]
    public function createEntity(): \App\Infrastructure\Persistence\QuoteCustom\QuoteCustom
    {
        return new QuoteCustom();
    }

    #[\Override]
    public function createForm(object $entity): \Yiisoft\FormModel\FormModelInterface
    {
        if (!$entity instanceof QuoteCustom) {
            throw new \InvalidArgumentException('Entity must be an instance of QuoteCustom');
        }

        return new QuoteCustomForm();
    }

    #[\Override]
    public function prepareInputData(int $entityId, int $customFieldId, mixed $value): array
    {
        return [
            'quote_id' => $entityId,
            'custom_field_id' => $customFieldId,
            'value' => is_array($value) ? serialize($value) : $value,
        ];
    }

    #[\Override]
    public function save(object $entity, array $inputData): void
    {
        if (!$entity instanceof QuoteCustom) {
            throw new \InvalidArgumentException('Entity must be an instance of QuoteCustom');
        }

        $this->service->saveQuoteCustom($entity, $inputData);
    }
}
