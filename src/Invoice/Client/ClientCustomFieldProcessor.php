<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Invoice\CustomFieldProcessor;
use App\Invoice\ClientCustom\ClientCustomRepository;
use App\Invoice\ClientCustom\ClientCustomService;
use App\Invoice\Entity\ClientCustom;
use App\Invoice\ClientCustom\ClientCustomForm;
use Yiisoft\FormModel\FormHydrator;

final class ClientCustomFieldProcessor implements CustomFieldProcessor
{
    public function __construct(
        private readonly ClientCustomRepository $clientCustomRepository,
        private readonly ClientCustomService $clientCustomService,
    ) {}

    #[\Override]
    public function exists(string $entityId, string $customFieldId): bool
    {
        return $this->clientCustomRepository->repoClientCustomCount($entityId, $customFieldId) > 0;
    }

    #[\Override]
    public function findExisting(string $entityId, string $customFieldId): ?\App\Invoice\Entity\ClientCustom
    {
        return $this->clientCustomRepository->repoFormValuequery($entityId, $customFieldId);
    }

    #[\Override]
    public function createEntity(): \App\Invoice\Entity\ClientCustom
    {
        return new ClientCustom();
    }

    #[\Override]
    public function createForm(object $entity): \Yiisoft\FormModel\FormModelInterface
    {
        if (!$entity instanceof \App\Invoice\Entity\ClientCustom) {
            throw new \InvalidArgumentException('Entity must be an instance of ClientCustom');
        }
        return new ClientCustomForm($entity);
    }

    #[\Override]
    public function prepareInputData(int $entityId, int $customFieldId, mixed $value): array
    {
        return [
            'client_id' => $entityId,
            'custom_field_id' => $customFieldId,
            'value' => is_array($value) ? serialize($value) : $value,
        ];
    }

    #[\Override]
    public function save(object $entity, array $inputData): void
    {
        if (!$entity instanceof \App\Invoice\Entity\ClientCustom) {
            throw new \InvalidArgumentException('Entity must be an instance of ClientCustom');
        }
        $this->clientCustomService->saveClientCustom($entity, $inputData);
    }
}
