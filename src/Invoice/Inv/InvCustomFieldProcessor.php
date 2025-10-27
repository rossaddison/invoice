<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\CustomFieldProcessor;
use App\Invoice\Entity\InvCustom;
use App\Invoice\InvCustom\InvCustomForm;
use App\Invoice\InvCustom\InvCustomRepository;
use App\Invoice\InvCustom\InvCustomService;

/**
 * Custom field processor for Invoice entities.
 *
 * Handles invoice-specific custom field operations while implementing
 * the common CustomFieldProcessor interface.
 */
final class InvCustomFieldProcessor implements CustomFieldProcessor
{
    public function __construct(
        private readonly InvCustomRepository $repository,
        private readonly InvCustomService $service,
    ) {}

    #[\Override]
    public function exists(string $entityId, string $customFieldId): bool
    {
        return $this->repository->repoInvCustomCount($entityId, $customFieldId) > 0;
    }

    #[\Override]
    public function findExisting(string $entityId, string $customFieldId): ?\App\Invoice\Entity\InvCustom
    {
        return $this->repository->repoFormValuequery($entityId, $customFieldId);
    }

    #[\Override]
    public function createEntity(): \App\Invoice\Entity\InvCustom
    {
        return new InvCustom();
    }

    #[\Override]
    public function createForm(object $entity): \Yiisoft\FormModel\FormModelInterface
    {
        if (!$entity instanceof InvCustom) {
            throw new \InvalidArgumentException('Entity must be an instance of InvCustom');
        }

        return new InvCustomForm($entity);
    }

    #[\Override]
    public function prepareInputData(int $entityId, int $customFieldId, mixed $value): array
    {
        return [
            'inv_id' => $entityId,
            'custom_field_id' => $customFieldId,
            'value' => is_array($value) ? serialize($value) : $value,
        ];
    }

    #[\Override]
    public function save(object $entity, array $inputData): void
    {
        if (!$entity instanceof InvCustom) {
            throw new \InvalidArgumentException('Entity must be an instance of InvCustom');
        }

        $this->service->saveInvCustom($entity, $inputData);
    }
}
