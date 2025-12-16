<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use App\Invoice\CustomFieldProcessor;
use App\Invoice\FamilyCustom\FamilyCustomRepository;
use App\Invoice\FamilyCustom\FamilyCustomService;
use App\Invoice\Entity\FamilyCustom;
use App\Invoice\FamilyCustom\FamilyCustomForm;
use Yiisoft\FormModel\FormHydrator;

final class FamilyCustomFieldProcessor implements CustomFieldProcessor
{
    public function __construct(
        private readonly FamilyCustomRepository $familyCustomRepository,
        private readonly FamilyCustomService $familyCustomService,
    ) {
    }

    #[\Override]
    public function exists(string $entityId, string $customFieldId): bool
    {
        return $this->familyCustomRepository->repoFamilyCustomCount($entityId, $customFieldId) > 0;
    }

    #[\Override]
    public function findExisting(string $entityId, string $customFieldId): ?\App\Invoice\Entity\FamilyCustom
    {
        return $this->familyCustomRepository->repoFormValuequery($entityId, $customFieldId);
    }

    #[\Override]
    public function createEntity(): \App\Invoice\Entity\FamilyCustom
    {
        return new FamilyCustom();
    }

    #[\Override]
    public function createForm(object $entity): \Yiisoft\FormModel\FormModelInterface
    {
        if (!$entity instanceof \App\Invoice\Entity\FamilyCustom) {
            throw new \InvalidArgumentException('Entity must be an instance of FamilyCustom');
        }
        return new FamilyCustomForm($entity);
    }

    #[\Override]
    public function prepareInputData(int $entityId, int $customFieldId, mixed $value): array
    {
        return [
            'family_id' => $entityId,
            'custom_field_id' => $customFieldId,
            'value' => is_array($value) ? serialize($value) : $value,
        ];
    }

    #[\Override]
    public function save(object $entity, array $inputData): void
    {
        if (!$entity instanceof \App\Invoice\Entity\FamilyCustom) {
            throw new \InvalidArgumentException('Entity must be an instance of FamilyCustom');
        }
        $this->familyCustomService->saveFamilyCustom($entity, $inputData);
    }
}
